<?php

/**
 * Shipping calculator is a basic helper class that can be used to query
 * the shipping table.
 * 
 * At the moment we only output shipping areas based on weight/cost/
 * items and location. Buit this can now be expanded more easily if
 * needed.
 * 
 * @author ilateral (info@ilateral.co.uk)
 * @package checkout
 */
class ShippingCalculator extends Object
{
    
    /**
     * 2 character country code
     * 
     * @var string
     */
    private $country_code;
    
    public function setCountryCode($value)
    {
        $this->country_code = $value;
        return $this;
    }

    public function getCountryCode()
    {
        return $this->country_code;
    }
    
    /**
     * Zip/postal code for the search
     * 
     * @var string
     */
    private $zipcode;
        
    public function setZipCode($value)
    {
        $this->zipcode = $value;
        return $this;
    }

    public function getZipCode()
    {
        return $this->zipcode;
    }
    
    /**
     * The total cost we will be checking the cart against
     * 
     * @var Float
     */
    private $cost = 0.0;
    
    public function setCost($value)
    {
        $this->cost = $value;
        return $this;
    }

    public function getCost()
    {
        return $this->cost;
    }
    
    /**
     * The total weight to check against
     * 
     * @var Float
     */
    private $weight = 0;
    
    public function setWeight($value)
    {
        $this->weight = $value;
        return $this;
    }

    public function getWeight()
    {
        return $this->weight;
    }
    
    /**
     * The total numbers of items to check against
     * 
     * @var Float
     */
    private $items = 0;
    
    public function setItems($value)
    {
        $this->items = $value;
        return $this;
    }

    public function getItems()
    {
        return $this->items;
    }
    
    /**
     * Should we also check for wildcards when doing location/
     * postcode searches
     * 
     * @var Boolean
     */
    private $include_wildcards = true;
    
    public function setWildcards($value)
    {
        $this->include_wildcards = $value;
        return $this;
    }

    public function getWildcards()
    {
        return $this->include_wildcards;
    }
    
    
    /**
     * Simple constructor that sets the country code and zip. If no
     * country is set, this class attempts to autodetect.
     * 
     * @param country_code 2 character country code
     * @param zipcode string of the zipo/postal code
     */
    public function __construct($zipcode, $country_code = null)
    {
        if ($country_code) {
            $this->country_code = $country_code;
        } else {
            $locale = new Zend_Locale();
            $locale->setLocale($this->locale());
            $this->country_code = $locale->getRegion();
        }
        
        if ($zipcode) {
            $this->zipcode = $zipcode;
        }
    }
    
    /**
     * Get the locale of the Member, or if we're not logged in or don't have a locale, use the default one
     * @return string
     */
    protected function locale()
    {
        if (($member = Member::currentUser()) && $member->Locale) {
            return $member->Locale;
        }
            
        return i18n::get_locale();
    }
    
    
    /**
     * Find relevent postage rates, based on supplied:
     * - Country
     * - Zip/postal code
     * - Weight
     * - Cost
     * - Number of Items
     * 
     * This is returned as an ArrayList that can be looped through.
     *
     * @return ArrayList
     */
    public function getPostageAreas()
    {
        $return = ArrayList::create();
        $config = SiteConfig::current_site_config();
        $cart = ShoppingCart::get();
        $discount = $cart->getDiscount();
        $pc_match = "";
        
        if ($this->include_wildcards) {
            $filter = array(
                "Country:PartialMatch" => array($this->country_code, "*"),
            );
        } else {
            $filter = array(
                "Country:PartialMatch" => $this->country_code
            );
        }
        
        // Find any postage areas that match our filter
        $postage_areas = $config
            ->PostageAreas()
            ->filter($filter);

        // Next perform a check to look for the closest match to the
        // current zip/postal code (so we can correctly filter).
        foreach ($postage_areas as $item) {
            $postal_codes = explode(",", $item->ZipCode);

            foreach ($postal_codes as $code) {
                $code = strtolower($code);
                $new_pc_match = substr(strtolower($this->zipcode), 0, strlen($code));

                if ($code && $code == $new_pc_match && strlen($new_pc_match) > strlen($pc_match)) {
                    $pc_match = $new_pc_match;
                }
            }
        }

        // Now perform a zip/postal code comparision against our list
        // and add some 
        foreach ($postage_areas as $item) {
            $postal_codes = explode(",", $item->ZipCode);

            foreach ($postal_codes as $code) {
                $code = strtolower($code);

                if ($code && $code == substr(strtolower($this->zipcode), 0, strlen($pc_match))) {
                    $return->add($item);
                } elseif (!$pc_match && $item->ZipCode == "*" && $this->include_wildcards) {
                    $return->add($item);
                }
            }
        }
            
        // Check if any discounts are set with free postage
        // This is a little hacky at the moment, need to find a nicer
        // way to add free shipping.
        if ($discount && $discount->Type == "Free Shipping" && ((strpos($discount->Country, $this->country_code) !== false) || $discount->Country == "*")) {
            $postage = Checkout::CreateFreePostageObject();
            $return->add($postage);
        }
        
        // Before doing anything else, remove any wildcards (if needed)
        $exact_country = false;
        
        // Find any countries that are exactly matched 
        foreach ($return as $location) {
            if ($location->Country != "*") {
                $exact_country = true;
            }
        }
        
        // If exactly matched, remove any wildcards
        foreach ($return as $location) {
            if ($exact_country && $location->Country == "*" && $location->ID != -1) {
                $return->remove($location);
            }
        }

        // Now we have a list of locations, start checking for additional
        // rules an remove if not applicable.
        $total_cost = $this->cost;
        $total_weight = $this->weight;
        $total_items = $this->items;

        $max_cost = 0;
        $max_weight = 0;
        $max_items = 0;

        // First loop through and find items that are invalid
        foreach ($return as $location) {
            if ($location->Calculation == "Price" && ($total_cost < $location->Unit)) {
                $return->remove($location);
            }

            if ($location->Calculation == "Weight" && ($total_weight < $location->Unit)) {
                $return->remove($location);
            }

            if ($location->Calculation == "Items" && ($total_items < $location->Unit)) {
                $return->remove($location);
            }
        }

        // Now find max values based on units
        foreach ($return as $location) {
            if ($location->Calculation == "Price" && ($location->Unit > $max_cost)) {
                $max_cost = $location->Unit;
            }

            if ($location->Calculation == "Weight" && ($location->Unit > $max_weight)) {
                $max_weight = $location->Unit;
            }

            if ($location->Calculation == "Items" && ($location->Unit > $max_items)) {
                $max_items = $location->Unit;
            }
        }

        // Now loop through again and calculate which brackets each
        // Location fits in
        foreach ($return as $location) {
            if ($location->Calculation == "Price" && ($location->Unit < $max_cost)) {
                $return->remove($location);
            }

            if ($location->Calculation == "Weight" && ($location->Unit < $max_weight)) {
                $return->remove($location);
            }

            if ($location->Calculation == "Items" && ($location->Unit < $max_items)) {
                $return->remove($location);
            }
        }

        return $return;
    }
}
