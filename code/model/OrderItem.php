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
        "CustomisationList" => "ArrayList",
        "CustomisationHTML" => "HTMLText"
    );

    private static $summary_fields = array(
        "StockID" => "SKU",
        "Title" => "Title",
        "CustomisationHTML" => "Customisations",
        "Quantity" => "QTY",
        "Price" => "Price"
    );
    
    /**
     * Unserialise the list of customisations
     *
     * @return ArrayList
     */
    public function getCustomisationList() {
        return $this->Customisation ? unserialize($this->Customisation) : ArrayList::create();
    }
    
    /**
     * Unserialise the list of customisations and rendering into a basic
     * HTML string
     *
     */
    public function getCustomisationHTML() {
        $htmltext = HTMLText::create();
        $items = $this->getCustomisationList();
        $return = "";
        
        if($items->exists()) {
            foreach($items as $item) {
                $return .= $item->Title . ': ' . $item->Value . ";<br/>";
            }
        }

        return $htmltext->setValue($return);
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
