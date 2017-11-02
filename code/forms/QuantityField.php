<?php
/**
 * Text input field with validation for numeric values.
 * 
 * @package forms
 * @subpackage fields-formattedinput
 */
class QuantityField extends NumericField
{

    public function Type()
    {
        return 'quantity numeric text';
    }

    /**
	 * @return array
	 */
	public function getAttributes() {
		$attributes = array(
            "type" => "number",
            "min" => "0"
        );

		return array_merge(
			parent::getAttributes(),
			$attributes
		);
	}

    /** PHP Validation **/
    public function validate($validator)
    {
        // First check if value is numeric
        if ($this->value && $this->isNumeric()) {
            // Convert to a number to check
            $value = $this->value + 0;
            
            if(is_int($value)) {
                return true;
            }
        }
        
        $validator->validationError(
            $this->name,
            _t(
                'Checkout.VALIDATION', '{value} is not a valid number, only whole numbers can be accepted for this field',
                array('value' => $this->value)
            ),
            "validation"
        );
        return false;
    }
    
    public function dataValue()
    {
        return (is_numeric($this->value)) ? $this->value : 0;
    }
}
