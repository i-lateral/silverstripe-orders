<?php
/**
 * OrderItem is a single line item on an order, extimate or even in
 * the shopping cart.
 * 
 * An item has a number of fields that describes a product:
 * 
 * - Key: ID used to detect this item
 * - Title: Title of the item
 * - Content: Description of this object
 * - Quantity: Number or items in this order
 * - Weight: Weight of this item (unit of measurment is defined globally)
 * - TaxRate: Rate of tax for this item (e.g. 20.00 for 20%)
 * - ProductClass: ClassName of product that this item is matched against
 * - StockID: Unique identifier of this item (used with ProductClass
 *            match to a product)
 * - Locked: Is this a locked item? Locked items cannot be changed in the
 *           shopping cart
 * - Deliverable: Is this a product that can be delivered? This can effect
 *                delivery options in the checkout
 *
 * @author Mo <morven@ilateral.co.uk>
 */
class OrderItem extends DataObject
{
    /**
     * The name of the param used on a related product to
     * track Stock Levels.
     * 
     * Defaults to StockLevel
     *
     * @var string
     * @config
     */
    private static $stock_param = "StockLevel";

    /**
     * Standard database columns
     * 
     * @var array
     * @config
     */
    private static $db = array(
        "Key"           => "Varchar(255)",
        "Title"         => "Varchar",
        "Content"       => "HTMLText",
        "Quantity"      => "Int",
        "Weight"        => "Decimal",
        "StockID"       => "Varchar(100)",
        "ProductClass"  => "Varchar",
        "Customisation" => "Text",
        "Price"         => "Currency",
        "TaxRate"       => "Decimal",
        "Locked"        => "Boolean",
        "Stocked"       => "Boolean",
        "Deliverable"   => "Boolean"
    );

    /**
     * Foreign key associations in DB
     * 
     * @var array
     * @config
     */
    private static $has_one = array(
        "Parent"        => "Order"
    );

    /**
     * Specify default values of a field
     *
     * @var array
     * @config
     */
    private static $defaults = array(
        "Quantity"      => 1,
        "ProductClass"  => "Product",
        "Locked"        => false,
        "Stocked"       => false,
        "Deliverable"   => true
    );

    /**
     * Fields to display in list tables
     * 
     * @var array
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
     * Function to DB Object conversions
     * 
     * @var array
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
    public function Tax()
    {
        return ($this->Price / 100) * $this->TaxRate;
    }

    /**
     * Get an image object associated with this line item.
     * By default this is retrieved from the base product.
     * 
     * @return Image | null
     */
    public function Image()
    {
        $product = $this->FindStockItem();

        if ($product && method_exists($product, "SortedImages")) {
            return  $product->SortedImages()->first();
        } elseif ($product && method_exists($product, "Images")) {
            return $product->Images()->first();
        } elseif ($product && method_exists($product, "Image") && $product->Image()->exists()) {
            return $product->Image();
        }
    }

    /**
     * Unserialise the list of customisations
     *
     * @return ArrayList
     */
    public function Customisations()
    {
        $customisations = unserialize($this->Customisation);
        return ($customisations) ? $customisations : ArrayList::create();
    }
    
    /**
     * Provide a string of customisations seperated by a comma
     *
     * @return String
     */
    public function CustomisationList()
    {
        $return = "";
        $items = $this->Customisations();
        
        if ($items && $items->exists()) {
            $i = 1;

            foreach ($items as $item) {
                $return .= $item->Title . ': ' . $item->Value;

                if ($i < $items->count()) {
                    $return .= ", ";
                }
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
    public function CustomisationHTML()
    {
        $return = HTMLText::create();
        $items = $this->Customisations();
        $html = "";
        
        if ($items && $items->exists()) {
            foreach ($items as $item) {
                $html .= $item->Title . ': ' . $item->Value . ";<br/>";
            }
        }

        $return->setValue($html);
        return $return;
    }
        
    /**
     * Match this item to another object in the Database, by the
     * provided details.
     * 
     * @param $relation_name = The class name of the related dataobject
     * @param $relation_col = The column name of the related object
     * @param $match_col = The column we use to match the two objects
     * @return DataObject
     */
    public function Match($relation_name = null, $relation_col = "StockID", $match_col = "StockID")
    {
        // Try to determine relation name
        if (!$relation_name && !$this->ProductClass) {
            $relation_name = "Product";
        } elseif(!$relation_name && $this->ProductClass) {
            $relation_name = $this->ProductClass;
        }
        
        return $relation_name::get()
            ->filter($relation_col, $this->$match_col)
            ->first();
    }

    /**
     * Find our original stock item (useful for adding links back to the
     * original product).
     * 
     * This function is a synonym for @Link Match (as a result of) merging
     * OrderItem and ShoppingCartItem
     * 
     * @return DataObject
     */
    public function FindStockItem()
    {
        return $this->Match();
    }
    
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        
        $fields->removeByName("Customisation");
        
        return $fields;
    }

    /**
     * Check stock levels for this item, will return the actual number
     * of remaining stock after removing the current quantity
     * 
     * @param $qty The quantity we want to check against
     * @return Int
     */
    public function checkStockLevel($qty)
    {
        $stock_param = $this->config()->stock_param;
        $item = $this->Match();
        $stock = ($item->$stock_param) ? $item->$stock_param : 0;
        
        // Return remaining stock
        return $stock - $qty;
    }

    /**
     * Only order creators or users with VIEW admin rights can view
     *
     * @return Boolean
     */
    public function canView($member = null)
    {
        $extended = $this->extend('canView', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        return $this->Parent()->canView($member);
    }

    /**
     * Anyone can create an order item
     *
     * @return Boolean
     */
    public function canCreate($member = null)
    {
        $extended = $this->extend('canCreate', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        return true;
    }

    /**
     * No one can edit items once they are created
     *
     * @return Boolean
     */
    public function canEdit($member = null)
    {
        $extended = $this->extend('canEdit', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        return $this->Parent()->canEdit($member);
    }

    /**
     * No one can delete items once they are created
     *
     * @return Boolean
     */
    public function canDelete($member = null)
    {
        $extended = $this->extend('canDelete', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        return $this->Parent()->canEdit($member);
    }
}
