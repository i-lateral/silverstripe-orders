<?php
/**
 * OrderItem is a physical component of an order, that describes a product
 *
 * @author morven
 */
class OrderItem extends DataObject {
    
    /**
     * @config
     */
    private static $db = array(
        "Title"         => "Varchar",
        "StockID"       => "Varchar(100)",
        "Type"          => "Varchar",
        "Customisation" => "Text",
        "Quantity"      => "Int",
        "Price"         => "Currency",
        "TaxRate"       => "Decimal"
    );

    /**
     * @config
     */
    private static $has_one = array(
        "Parent"        => "Order"
    );

    /**
     * @config
     */
    private static $summary_fields = array(
        "Quantity",
        "Title",
        "StockID",
        "CustomisationList",
        "Price",
        "TaxRate"
    );
    
    /**
     * @config
     */
    private static $casting = array(
        "Tax" => "Currency"
    );
    
    /**
     * Get the amount of tax for a single unit of this item
     * 
     * @return Float
     */
    public function Tax() {
        return ($this->Price / 100) * $this->TaxRate;
    }

    /**
     * Unserialise the list of customisations
     *
     * @return ArrayList
     */
    public function Customisations() {
        $customisations = unserialize($this->Customisation);
        return ($customisations) ? $customisations : ArrayList::create();
    }
    
    /**
     * Provide a string of customisations seperated by a comma
     *
     * @return String
     */
    public function CustomisationList() {
        $return = "";
        $items = $this->Customisations();
        
        if($items && $items->exists()) {
            $i = 1;

            foreach($items as $item) {
                $return .= $item->Title . ': ' . $item->Value;

                if($i < $items->count()) $return .= ", ";
                $i++;
            }
        }

        
        return $return;
    }
    
    /**
     * Unserialise the list of customisations and rendering into a basic
     * HTML string
     *
     */
    public function CustomisationHTML() {
        $return = HTMLText::create();
        $items = $this->Customisations();
        $html = "";
        
        if($items && $items->exists()) {
            foreach($items as $item) {
                $html .= $item->Title . ': ' . $item->Value . ";<br/>";
            }
        }

        $return->setValue($html);
        return $return;
    }
    
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        
        $fields->removeByName("Customisation");
        
        return $fields;
    }

    /**
     * Only order creators or users with VIEW admin rights can view
     *
     * @return Boolean
     */
    public function canView($member = null) {
        $extended = $this->extend('canView', $member);
        if($extended && $extended !== null) return $extended;

        return $this->Parent()->canView($member);
    }

    /**
     * Anyone can create an order item
     *
     * @return Boolean
     */
    public function canCreate($member = null) {
        $extended = $this->extend('canCreate', $member);
        if($extended && $extended !== null) return $extended;

        return true;
    }

    /**
     * No one can edit items once they are created
     *
     * @return Boolean
     */
    public function canEdit($member = null) {
        $extended = $this->extend('canEdit', $member);
        if($extended && $extended !== null) return $extended;

        return $this->Parent()->canEdit($member);
    }

    /**
     * No one can delete items once they are created
     *
     * @return Boolean
     */
    public function canDelete($member = null) {
        $extended = $this->extend('canDelete', $member);
        if($extended && $extended !== null) return $extended;

        return $this->Parent()->canEdit($member);
    }
}
