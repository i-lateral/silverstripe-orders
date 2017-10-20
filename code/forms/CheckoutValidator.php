<?php

class CheckoutValidator extends RequiredFields
{
    public function php($data) {    
        if($this->form->buttonClicked()->actionName() != 'doContinue') {
            $this->removeValidation();
        }
        if (isset($data['DuplicateDelivery']) && $data['DuplicateDelivery'] == 1) {
            $this->removeRequiredField('ShippingAddress');
            $this->removeRequiredField('DeliveryFirstnames');
            $this->removeRequiredField('DeliverySurname');
            $this->removeRequiredField('DeliveryAddress1');
            $this->removeRequiredField('DeliveryCity');
            $this->removeRequiredField('DeliveryPostCode');
            $this->removeRequiredField('DeliveryCountry');
        }
        return parent::php($data);        
    }    
}