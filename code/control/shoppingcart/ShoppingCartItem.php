<?php

/**
 * Single item class that needs to be added to a shopping cart. This
 * class is used to provide a structure of how shopping cart items can
 * be interacted with.
 * 
 * This is a base class, you can add your own item class and then update
 * ShoppingCart.item_class in your config file.
 * 
 */
class ShoppingCartItem extends ViewableData
{
    /**
     * The param used on a related product to track Stock Levels
     *
     */
    private static $stock_param = "StockLevel";

    /**
     * ID used to detect this item
     * 
     * @var String
     */
    public $Key;
    
    /**
     * Title of the item
     * 
     * @var String
     */
    public $Title;
    
    /**
     * Description of this object
     * 
     * @var String
     */
    public $Content;
    
    /**
     * Image attached to this object
     * 
     * @var Image
     */
    public $Image;
    
    /**
     * Base price of this item (used to calculate actual price)
     * 
     * @var Float
     */
    public $BasePrice = 0;
    
    /**
     * Number or items in the cart
     * 
     * @var Int
     */
    public $Quantity = 1;
    
    /**
     * Weight of this item
     * 
     * @var Float
     */
    public $Weight = 1;
    
    /**
     * Rate of tax for this item (e.g. 20.00 for 20%)
     * 
     * @var Float
     */
    public $TaxRate = 0;
    
    /**
     * Type of stock item that this item is matched against
     * 
     * @var String
     */
    public $ClassName;
    
    /**
     * ID of the object matched
     * 
     * @var Int
     */
    public $ID;
    
    /**
     * Unique identifier of the item
     * 
     * @var String
     */
    public $StockID;

    /**
     * Is this item stocked (and as such reduces in stock when bought)
     * If ShoppingCart.check_stock_levels is set to true, then this is
     * ignored.
     * 
     * @var Boolean
     */
    public $Stocked = false;

    /**
     * Is this a locked item? Locked items cannot be changed in
     * the shopping cart.
     * 
     * @var Boolean
     */
    public $Locked = false;

    /**
     * Is this a product that can be delivered? If the cart contains only
     * non deliverable items, shipping and delivery options wont be
     * factored into the checkout.
     * 
     * @var Boolean
     */
    public $Deliverable = true;
    
    /**
     * A list of customisations that has been made to this item. This
     * will be rendered into the template ansd requires customisations
     * to have the following keys by default:  
     * 
     * - Title the title of this customisation (EG colour).
     * - Value the value of this customisation (eg red). 
     * - Price adjust the price by this much (eg 10.00 or -5.00). 
     * 
     * @var array
     */
    public $CustomisationArray = array();
    
    private static $casting = array(
        "Price" => "Currency",
        "Discount" => "Currency",
        "TotalDiscount" => "Currency",
        "SubTotal" => "Currency",
        "Tax" => "Currency",
        "TotalTax" => "Currency",
        "TotalPrice" => "Currency",
    );
    
    /**
     * Find the cost of all items in this line, without any tax.
     *
     * @return Currency
     */
    public function getPrice()
    {
        $price = $this->BasePrice;
                
        // Check for customisations that modify price
        foreach ($this->getCustomisations() as $item) {
            $price += ($item->Price) ? $item->Price : 0;
        }
        
        return $price;
    }
    
    /**
     * Find the total discount amount for this line item
     * 
     * @return Float
     */
    public function getDiscount()
    {
        $amount = 0;
        $cart = ShoppingCart::get();
        $items = $cart->TotalItems;
        $discount = $cart->getDiscount();
        
        if ($this->BasePrice && $discount && $discount->Amount) {
            if ($discount->Type == "Fixed") {
                $amount = ($discount->Amount / $items) * $this->Quantity;
            } elseif ($discount->Type == "Percentage") {
                $amount = (($this->Price / 100) * $discount->Amount) * $this->Quantity;
            }
        }
        
        return $amount;
    }
    
    /**
     * Find the total discount amount for this line item
     * 
     * @return Float
     */
    public function getTotalDiscount()
    {
        return $this->Discount;
    }
    
    /**
     * Generate the subtotal for this line item (without tax)
     *
     * @return Currency
     */
    public function getSubTotal()
    {
        return $this->Price * $this->Quantity;
    }
    
    
    /**
     * Generate the total price, accounting for price, quantity, discount
     * and tax
     *
     * @return Currency
     */
    public function getTotalPrice()
    {
        return $this->SubTotal + $this->TotalTax - $this->Discount;
    }
    
    
    /**
     * Find the tax cost for one instance of this item.
     *
     * @return Currency
     */
    public function getTax()
    {
        $amount = 0;

        if ($this->Price && $this->TaxRate) {
            $amount = (($this->Price - $this->Discount) / 100) * $this->TaxRate;
        }
        
        return $amount;
    }
    
    /**
     * Find the tax cost for all of the items in this line.
     *
     * @return Currency
     */
    public function getTotalTax()
    {
        $amount = 0;

        if ($this->Price && $this->TaxRate && $this->Quantity) {
            $amount = ((($this->Price * $this->Quantity) - $this->Discount) / 100) * $this->TaxRate;
        }
        
        return $amount;
    }
    
    public function getCustomisations()
    {
        $return = ArrayList::create();
        
        foreach ($this->CustomisationArray as $item) {
            $return->add(ArrayData::create($item));
        }
        
        return $return;
    }
    
    /**
     * Find our original stock item (useful for adding links back to the
     * original product)
     * 
     * @return DataObject
     */
    public function FindStockItem()
    {
        $classname = $this->ClassName;
        $id = $this->ID;
        
        if ($classname && $id) {
            return $classname::get()->byID($id);
        } else {
            return null;
        }
    }

    /**
     * Check stock levels for this item.
     *
     * If stock levels are too low, throws an exception
     * 
     * @param $qty The quantity we want to check against
     * @return null
     */
    public function checkStockLevel($qty)
    {
        $stock_param = $this->config()->stock_param;
        $item = $this->FindStockItem();
        $stock = ($item->$stock_param) ? $item->$stock_param : 0;
        
        // if not enough stock, throw an exception
        if($stock < $qty) {
            throw new ValidationException(_t(
                "Checkout.NotEnoughStock",
                "There are not enough '{title}' in stock",
                "Message to show that an item hasn't got enough stock",
                array('title' => $item->Title)
            ));
        }
    }
}
