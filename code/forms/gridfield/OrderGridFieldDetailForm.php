<?php

/**
 * Provides view and edit forms for Order objects vai gridfield
 * 
 * @author ilateral (http://www.ilateral.co.uk)
 * @package orders
 */
class OrderGridFieldDetailForm extends GridFieldDetailForm { }

class OrderGridFieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest {
	private static $allowed_actions = array(
		'ItemEditForm'
	);

	public function updateCMSActions($actions) {
        
		return $actions;
	}

	public function ItemEditForm(){
		$form = parent::ItemEditForm();
        $fields = $form->Fields();
        $actions = $form->Actions();
        $record = $this->record;
        $member = Member::currentUser();
        
        // Setup order history
        if(Permission::check(array('COMMERCE_ORDER_HISTORY','ADMIN'), 'any', $member)) {
            $versions = $record->AllVersions();
            $first_version = $versions->First();
            $curr_version = ($first_version) ? $versions->First() : null;
            $message = "";

            foreach($versions as $version) {
                $i = $version->Version;
                $name = "History_{$i}";

                if($i > 1) {
                    $frm = Versioned::get_version($record->class, $record->ID, $i - 1);
                    $to = Versioned::get_version($record->class, $record->ID, $i);
                    $diff = new DataDifferencer($frm, $to);

                    if($version->Author())
                        $message = "<p>{$version->Author()->FirstName} ({$version->LastEdited})</p>";
                    else
                        $message = "<p>Unknown ({$version->LastEdited})</p>";

                    if($diff->ChangedFields()->exists()) {
                        $message .= "<ul>";

                        // Now loop through all changed fields and track as message
                        foreach($diff->ChangedFields() as $change) {
                            if($change->Name != "LastEdited")
                                $message .= "<li>{$change->Title}: {$change->Diff}</li>";
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
        if($record->ID && !$record->canEdit() && $record->canChangeStatus()) {
            $actions
                ->push(FormAction::create('doChangeStatus', _t('Orders.ChangeStatus', 'Change Status'))
                ->setUseButtonTag(true)
                ->addExtraClass('ss-ui-action-constructive')
                ->setAttribute('data-icon', 'accept'));
        }
        
		return $form;
	}
    
    public function doChangeStatus($data, $form) {
		$new_record = $this->record->ID == 0;
		$controller = $this->getToplevelController();
		$list = $this->gridField->getList();

		try {
			$this->record->Status = $data["Status"];
			$this->record->write();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad', false);
            
			$responseNegotiator = new PjaxResponseNegotiator(array(
				'CurrentForm' => function() use(&$form) {
					return $form->forTemplate();
				},
				'default' => function() use(&$controller) {
					return $controller->redirectBack();
				}
			));
            
			if($controller->getRequest()->isAjax()){
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

		if($this->gridField->getList()->byId($this->record->ID)) {
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
