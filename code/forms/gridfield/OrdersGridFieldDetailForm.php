<?php
/**
 * Edit form specifically customised for the Orders module. This deals
 * with editing Order and Estimate objects specificaly and isn't really
 * intended to be more flexible in terms of support (though this might
 * be added later).
 *
 * @author ilateral
 */

class OrdersGridFieldDetailForm extends GridFieldDetailForm
{
}

class OrdersGridFieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{

    private static $allowed_actions = array(
        'edit',
        'view',
        'ItemEditForm'
    );
    
    public function edit($request)
    {
        $controller = $this->getToplevelController();
        $form = $this->ItemEditForm($this->gridField, $request);

        $return = $this->customise(array(
            'Backlink' => $controller->hasMethod('Backlink') ? $controller->Backlink() : $controller->Link(),
            'ItemEditForm' => $form,
        ))->renderWith($this->template);
        
        // If this is a new record, we need to save it first
        if ($this->record->ID == 0) {
            $this->record->write();
            
            $controller
                ->getRequest()
                ->addHeader('X-Pjax', 'Content');
            
            return $controller->redirect($this->Link());
        }
        

        if ($request->isAjax()) {
            return $return;
        } else {
            // If not requested by ajax, we need to render it within the controller context+template
            return $controller->customise(array(
                // TODO CMS coupling
                'Content' => $return,
            ));
        }
    }

    public function ItemEditForm()
    {
        Requirements::javascript("orders/javascript/entwine.orders.js");
        
        $form = parent::ItemEditForm();
        $fields = $form->Fields();
        $actions = $form->Actions();
        $record = $this->record;
        $member = Member::currentUser();
        
        $can_view = $this->record->canView();
        $can_edit = $this->record->canEdit();
        $can_change_status = $this->record->canChangeStatus();
        $can_delete = $this->record->canDelete();
        $can_create = $this->record->canCreate();
        
        // First remove the delete button
        $actions->removeByName("action_doDelete");
        
        // Deal with Estimate objects
        if ($record->ClassName == "Estimate") {
            if ($record->ID && $record->AccessKey) {
                $html = '<a href="' . $record->QuoteLink() . '" ';
                $html .= 'target="_blank" ';
                $html .= 'class="action ss-ui-button ui-button ui-corner-all open-external" ';
                $html .= '>' . _t('Orders.ViewQuote', 'View Quote') . '</a>';
                
                $actions->insertAfter(
                    LiteralField::create('openQuote', $html),
                    "action_doSave"
                );
            }
            
            if ($record->ID && $can_edit) {
                $actions->insertAfter(
                    FormAction::create(
                        'doConvert',
                        _t('Orders.ConvertToOrder', 'Convert To Order')
                    )->setUseButtonTag(true),
                    "action_doSave"
                );
            }
        }
        
        // Deal with Order objects
        if ($record->ClassName == "Order") {
            // Set our status field as a dropdown (has to be here to
            // ignore canedit)
            // Allow users to change status (as long as they have permission)
            if ($can_edit || $can_change_status) {
                $status_field = DropdownField::create(
                    'Status',
                    null,
                    $record->config()->statuses
                );
                
                // Set default status if we can
                if (!$record->Status && !$record->config()->default_status) {
                    $status_field
                        ->setValue($record->config()->default_status);
                } else {
                    $status_field
                        ->setValue($record->Status);
                }
                
                $fields->replaceField("Status", $status_field);
            }
            
            // Setup order history
            if (Permission::check(array('COMMERCE_ORDER_HISTORY', 'ADMIN'), 'any', $member)) {
                $versions = $record->AllVersions();
                $first_version = $versions->First();
                $curr_version = ($first_version) ? $versions->First() : null;
                $message = "";

                foreach ($versions as $version) {
                    $i = $version->Version;
                    $name = "History_{$i}";

                    if ($i > 0) {
                        $frm = Versioned::get_version($record->class, $record->ID, $i - 1);
                        $to = Versioned::get_version($record->class, $record->ID, $i);
                        $diff = new DataDifferencer($frm, $to);

                        if ($version->Author()) {
                            $message = "<p>{$version->Author()->FirstName} ({$version->LastEdited})</p>";
                        } else {
                            $message = "<p>Unknown ({$version->LastEdited})</p>";
                        }

                        if ($diff->ChangedFields()->exists()) {
                            $message .= "<ul>";

                            // Now loop through all changed fields and track as message
                            foreach ($diff->ChangedFields() as $change) {
                                if ($change->Name != "LastEdited") {
                                    $message .= "<li>{$change->Title}: {$change->Diff}</li>";
                                }
                            }

                            $message .= "</ul>";
                        }
                        
                        $fields->addFieldToTab("Root.History", LiteralField::create(
                            $name,
                            "<div class=\"field\">{$message}</div>"
                        ));
                    }
                }
            }
            
            // Is user cannot edit, but can change status, add change
            // status button
            if ($record->ID && !$can_edit && $can_change_status) {
                $actions
                    ->push(FormAction::create('doChangeStatus', _t('Orders.Save', 'Save'))
                    ->setUseButtonTag(true)
                    ->addExtraClass('ss-ui-action-constructive')
                    ->setAttribute('data-icon', 'accept'));
            }
            
            if ($record->ID && $record->AccessKey) {
                $html = '<a href="' . $record->InvoiceLink() . '" ';
                $html .= 'target="_blank" ';
                $html .= 'class="action ss-ui-button ui-button ui-corner-all open-external" ';
                $html .= '>' . _t('Orders.ViewInvoice', 'View Invoice') . '</a>';
                
                $link_field = LiteralField::create('openQuote', $html);
                
                if ($actions->find("Name", "action_doSave")) {
                    $actions->insertAfter($link_field, "action_doSave");
                }
                
                if ($actions->find("Name", "action_doChangeStatus")) {
                    $actions->insertAfter($link_field, "action_doChangeStatus");
                }
            }
        }
        
        // Add a duplicate button, either after the save button or
        // the change status "save" button.
        if ($record->ID) {
            $duplicate_button = FormAction::create(
                'doDuplicate',
                _t('Orders.Duplicate', 'Duplicate')
            )->setUseButtonTag(true);
            
            if ($actions->find("Name", "action_doSave")) {
                $actions->insertAfter($duplicate_button, "action_doSave");
            }
            
            if ($actions->find("Name", "action_doChangeStatus")) {
                $actions->insertAfter($duplicate_button, "action_doChangeStatus");
            }
        }
        
        // Finally, if allowed, re-add the delete button (so it is last)
        if ($record->ID && $can_delete) {
            $actions->push(FormAction::create('doDelete', _t('GridFieldDetailForm.Delete', 'Delete'))
                ->setUseButtonTag(true)
                ->addExtraClass('ss-ui-action-destructive action-delete'));
        }
        
        // Set our custom template
        $form->setTemplate("OrdersItemEditForm");
        
        $this->extend("updateItemEditForm", $form);
        
        return $form;
    }
    
    public function doDuplicate($data, $form)
    {
        $record = $this->record;

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }

        $form->saveInto($record);
        
        $record->write();
        
        $new_record = $record->duplicate();
        
        $this->gridField->getList()->add($new_record);

        $message = sprintf(
            _t('Orders.Duplicated', 'Duplicated %s %s'),
            $this->record->singular_name(),
            '"'.Convert::raw2xml($this->record->Title).'"'
        );
        
        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            $backForm = $toplevelController->getEditForm();
            $backForm->sessionMessage($message, 'good', false);
        } else {
            $form->sessionMessage($message, 'good', false);
        }
        
        $toplevelController = $this->getToplevelController();
        $toplevelController->getRequest()->addHeader('X-Pjax', 'Content');

        return $toplevelController->redirect($this->getBacklink(), 302);
    }
    
    public function doConvert($data, $form)
    {
        $record = $this->record;

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }

        $form->saveInto($record);
        
        $record->convertToOrder();
        $record->write();
        
        $this->gridField->getList()->add($record);

        $message = sprintf(
            _t('Orders.ConvertedToOrder', 'Converted %s %s'),
            $this->record->singular_name(),
            '"'.Convert::raw2xml($this->record->Title).'"'
        );
        
        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            $backForm = $toplevelController->getEditForm();
            $backForm->sessionMessage($message, 'good', false);
        } else {
            $form->sessionMessage($message, 'good', false);
        }
        
        $toplevelController = $this->getToplevelController();
        $toplevelController->getRequest()->addHeader('X-Pjax', 'Content');

        return $toplevelController->redirect($this->getBacklink(), 302);
    }
    
    public function doChangeStatus($data, $form)
    {
        $new_record = $this->record->ID == 0;
        $controller = $this->getToplevelController();
        $list = $this->gridField->getList();

        try {
            $this->record->Status = $data["Status"];
            $this->record->write();
        } catch (ValidationException $e) {
            $form->sessionMessage($e->getResult()->message(), 'bad', false);
            
            $responseNegotiator = new PjaxResponseNegotiator(array(
                'CurrentForm' => function () use (&$form) {
                    return $form->forTemplate();
                },
                'default' => function () use (&$controller) {
                    return $controller->redirectBack();
                }
            ));
            
            if ($controller->getRequest()->isAjax()) {
                $controller->getRequest()->addHeader('X-Pjax', 'CurrentForm');
            }
            
            return $responseNegotiator->respond($controller->getRequest());
        }

        $link = '<a href="' . $this->Link('edit') . '">"'
            . htmlspecialchars($this->record->Title, ENT_QUOTES)
            . '"</a>';

        $message = _t(
            'Orders.StatusChanged',
            'Status Changed {name} {link}',
            array(
                'name' => $this->record->i18n_singular_name(),
                'link' => $link
            )
        );
        
        $form->sessionMessage($message, 'good', false);

        if ($this->gridField->getList()->byId($this->record->ID)) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->edit($controller->getRequest());
        } else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $noActionURL = $controller->removeAction($data['url']);
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }
}
