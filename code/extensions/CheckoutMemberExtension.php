<?php

/**
 * Overwrite default member object
 * 
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class CheckoutMemberExtension extends DataExtension
{
    
    /**
     * Cache an address object for if we need to us it again.
     * 
     * @var MemberAddress
     */
    private $cached_address;
    
    private static $db = array(
        "PhoneNumber"   => "Varchar",
        "Company"       => "Varchar(99)"
    );

    private static $has_many = array(
        "Addresses"     => "MemberAddress"
    );
    
    private static $casting = array(
        'Address1'          => 'Varchar',
        'Address2'          => 'Varchar',
        'City'              => 'Varchar',
        'PostCode'          => 'Varchar',
        'Country'           => 'Varchar'
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->remove("PhoneNumber");

        $fields->addFieldToTab(
            "Root.Main",
            TextField::create("PhoneNumber"),
            "Password"
        );

        $fields->addFieldToTab(
            "Root.Main",
            TextField::create("Company"),
            "FirstName"
        );

        return $fields;
    }
    
    
    /**
     * Get the default address from our list of addreses. If no default
     * is set, we should return the first in the list.
     * 
     * @return MemberAddress
     */
    public function getDefaultAddress()
    {
        if ($this->cached_address) {
            return $this->cached_address;
        } else {
            $address = $this
                ->owner
                ->Addresses()
                ->sort("Default", "DESC")
                ->first();
                
            $this->cached_address = $address;
            
            return $address;
        }
    }
    
    /**
     * Get address line one from our default address
     * 
     * @return String
     */
    public function getAddress1()
    {
        if ($address = $this->owner->getDefaultAddress()) {
            return $address->Address1;
        }
    }
    
    /**
     * Get address line two from our default address
     * 
     * @return String
     */
    public function getAddress2()
    {
        if ($address = $this->owner->getDefaultAddress()) {
            return $address->Address2;
        }
    }
    
    /**
     * Get city from our default address
     * 
     * @return String
     */
    public function getCity()
    {
        if ($address = $this->owner->getDefaultAddress()) {
            return $address->City;
        }
    }
    
    public function getPostCode()
    {
        if ($address = $this->owner->getDefaultAddress()) {
            return $address->PostCode;
        }
    }
    
    /**
     * Get country from our default address
     * 
     * @return String
     */
    public function getCountry()
    {
        if ($address = $this->owner->getDefaultAddress()) {
            return $address->Country;
        }
    }

    /**
     * Get a discount from the groups this member is in
     *
     * @return Discount
     */
    public function getDiscount()
    {
        $discounts = ArrayList::create();

        foreach ($this->owner->Groups() as $group) {
            foreach ($group->Discounts() as $discount) {
                $discounts->add($discount);
            }
        }

        $discounts->sort("Amount", "DESC");

        return $discounts->first();
    }
}
