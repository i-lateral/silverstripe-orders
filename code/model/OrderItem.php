<?php
/**
 * OrderItem is a physical component of an order, that describes a product
 *
 * @author morven
 */
class OrderItem extends DataObject {
    private static $db = array(
        "Title"         => "Varchar",
        "StockID"       => "Varchar(100)",
        "Type"          => "Varchar",
        "Customisation" => "Text",
        "Quantity"      => "Int",
        "Price"         => "Currency",
        "Tax"           => "Currency"
    );

    private static $has_one = array(
        "Parent"        => "Order"
    );
    
    private static $defaults = array(
        "Tax"           => 0
    );

    private static $casting = array(
        "CustomisationHTML" => "HTMLText",
        "SubTotal"      => "Currency",
        "TaxTotal"      => "Currency",
        "Total"         => "Currency"
    );

    private static $summary_fields = array(
        "Title",
        "SKU",
        "CustomisationHTML",
        "Quantity",
        "Price",
        "Tax",
        "Total"
    );
    
    /**
     * Unserialise the list of customisations
     *
     * @return ArrayList
     */
    public function getCustomisation() {
        return unserialize($this->Customisation);
    }
    
    /**
     * Unserialise the list of customisations and rendering into a basic
     * HTML string
     *
     */
    public function getCustomisationHTML() {
        $htmltext = HTMLText::create();
        $return = "";

        if($items = $this->getCustomisation()) {
            foreach($items as $item) {
                $return .= $item->Title . ': ' . $item->Value . ";<br/>";
            }
        }

        return $htmltext->setValue($return);
    }

    /**
     * Get the total cost of this item based on the quantity, not including tax
     *
     * @return Decimal
     */
    public function getSubTotal() {
        return $this->Quantity * $this->Price;
    }

    /**
     * Get the total cost of tax for this item based on the quantity
     *
     * @return Decimal
     */
    public function getTaxTotal() {
        return $this->Quantity * $this->Tax;
    }

    /**
     * Get the total cost of this item based on the quantity
     *
     * @return Currency
     */
    public function getTotal() {
        return $this->SubTotal + $this->TaxTotal;
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

        return $this->Parent()->canDelete($member);
    }
}
