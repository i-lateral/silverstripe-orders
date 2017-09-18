<?php

class Discount extends DataObject
{

    private static $db = array(
        "Title"     => "Varchar",
        "Type"      => "Enum('Fixed,Percentage,Free Shipping','Percentage')",
        "Code"      => "Varchar(299)",
        "Amount"    => "Decimal",
        "Country"   => "Varchar(255)",
        "Expires"   => "Date"
    );

    private static $has_one = array(
        "Site"      => "SiteConfig"
    );

    private static $many_many = array(
        "Groups"    => "Group"
    );

    private static $summary_fields = array(
        "Title",
        "Code",
        "Expires"
    );

    /**
     * Generate a random string that we can use for the code by default
     *
     * @return string
     */
    private static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $string;
    }

    /**
     * Set more complex default data
     */
    public function populateDefaults()
    {
        $this->setField('Code', self::generateRandomString());
    }

    /**
     * Return a URL that allows this code to be added to a cart
     * automatically
     *
     * @return String
     */
    public function AddLink()
    {
        $cart = ShoppingCart::get();
        
        return Controller::join_links(
            $cart->AbsoluteLink("usediscount"),
            $this->Code
        );
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        if ($this->Code) {
            $fields->addFieldToTab(
                "Root.Main",
                ReadonlyField::create(
                    "DiscountURL",
                    _t("CheckoutAdmin.AddDiscountURL", "Add discount URL"),
                    $this->AddLink()
                ),
                "Code"
            );
        }

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Ensure that the code is URL safe
        $this->Code = Convert::raw2url($this->Code);
    }

    public function canView($member = null)
    {
        $extended = $this->extendedCan('canView', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }
    
    public function canCreate($member = null)
    {
        $extended = $this->extendedCan('canCreate', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }

    public function canEdit($member = null)
    {
        $extended = $this->extendedCan('canEdit', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }

    public function canDelete($member = null)
    {
        $extended = $this->extendedCan('canDelete', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }
}
