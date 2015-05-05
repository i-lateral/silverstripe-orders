<?php
/**
 * Base class for editing a catlogue object.
 * 
 * Currently allows enabling or disabling of an object via additional buttons
 * added to the gridfield.
 * 
 * NOTE: The object being edited must implement a "Disabled" parameter
 * on it's DB fields.
 *
 * @author ilateral
 */

class EstimateGridFieldDetailForm extends GridFieldDetailForm { }

class EstimateGridFieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest {

	private static $allowed_actions = array(
		'edit',
		'view',
		'ItemEditForm'
	);

	public function ItemEditForm() {
		$form = parent::ItemEditForm();
        $fields = $form->Fields();
        $actions = $form->Actions();
        
        if($this->record->ID && $this->record->canEdit()) {
            $actions->insertAfter(
                FormAction::create(
                    'doConvert',
                    _t('Orders.ConvertToOrder', 'Convert To Order')
                )->setUseButtonTag(true),
                "action_doSave"
            );
		}
        
		return $form;
	}


	public function doConvert($data, $form)	{
		$record = $this->record;

		if($record && !$record->canEdit())
			return Security::permissionFailure($this);

		$form->saveInto($record);
        
        $record->ClassName = "Order";
        
		$record->write();
		$this->gridField->getList()->add($record);

		$message = sprintf(
			_t('Orders.ConvertedToOrder', 'Converted %s %s'),
			$this->record->singular_name(),
			'"'.Convert::raw2xml($this->record->Title).'"'
		);
        
        $toplevelController = $this->getToplevelController();
		if($toplevelController && $toplevelController instanceof LeftAndMain) {
			$backForm = $toplevelController->getEditForm();
			$backForm->sessionMessage($message, 'good', false);
		} else {
			$form->sessionMessage($message, 'good', false);
		}
        
        $toplevelController = $this->getToplevelController();
		$toplevelController->getRequest()->addHeader('X-Pjax', 'Content');

		return $toplevelController->redirect($this->getBacklink(), 302);
	}
}
