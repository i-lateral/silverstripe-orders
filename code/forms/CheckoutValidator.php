<?php

class CheckoutValidator extends RequiredFields
{
    public function php($data) {    
        if($this->form->buttonClicked()->actionName() != 'doContinue') {
            $this->removeValidation();
        }
        return parent::php($data);        
    }    
}