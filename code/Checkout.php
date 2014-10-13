<?php

/**
 * Helper class for the checkout, contains tools used by all
 * subcomponents of the checkout module.
 * 
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class Checkout extends ViewableData {
    
    /**
     * Seperate tax out in totals on the cart and summary.
     * 
     * NOTE: This assumes that you will pass an object with a "Tax"
     * param added to it, otherwise this will not be set correctly.
     * 
     * @var boolean
     * @config
     */
    private static $show_tax = true;
    
    /**
     * Show login form in checkout process (useful if you have a user
     * account module installed).
     * 
     * @var boolean
     * @config
     */
    private static $login_form = false;
    
    /**
     * Set the checkout into "simple" mode, meaning that billing/
     * delivery forms and postage forms are disabled (EG users are sent
     * direct to payment pages).
     * 
     * @var Boolean
     * @config
     */
    private static $simple_checkout = true;
    
    /**
     * Allow users to checkout as a "guest" meaning they do not have to
     * register/login
     * 
     * @var Boolean
     * @config
     */
    private static $guest_checkout = true;
    
    /**
     * Currency symbol used by default
     * 
     * @var string
     * @config
     */
    private static $currency_symbol = "£";
    
    /**
     * International 3 character currency code to use
     * 
     * @var string
     * @config
     */
    private static $currency_code = "GBP";
    
    /**
     * An array of data that can be populated and sent to payment
     * providers.
     * 
     * NOTE: Be careful changing this as most of these keys are required
     * 
     * @var array
     * @config
     */
    private static $checkout_data = array(
        "OrderNumber",
        "Status",
        "FirstName",
        "Surname",
        "Address1",
        "Address2",
        "City",
        "PostCode",
        "Country",
        "Email",
        "DeliveryFirstnames",
        "DeliverySurname",
        "DeliveryAddress1",
        "DeliveryAddress2",
        "DeliveryCity",
        "DeliveryPostCode",
        "DeliveryCountry",
        "DiscountAmount",
        "TaxRate",
        "PostageType",
        "PostageCost"
    );
    
    /**
     * Return guest checkout status in a way that can be seen by
     * templates
     * 
     * @return Boolean
     */
    public function GuestCheckout() {
        return $this->config()->guest_checkout;
    }
    
    /**
     * Generate a random number based on the current time, a random int
     * and a third int that can be passed as a param.
     * 
     * @param $int integer that can make the number "more random"
     * @param $length Length of the string
     * @return Int
     */
    public static function getRandomNumber($int = 1, $length = 16) {
        return substr(md5(time() * rand() * $int), 0, $length);
    }
    
    /**
     * Function to find relevent postage rates, based on supplied country and
     * zip/postal code data.
     *
     * @param $country String listing the country to search, this has to be an ISO 3166 code
     * @param $zipcode String listing the zip/postage code to filter by
     */
    public static function getPostageAreas($country, $zipcode) {
        $return = new ArrayList();
        $countries = new ArrayList();
        $cart = ShoppingCart::create();
        $config = SiteConfig::current_site_config();
        $all_rates = $config->PostageAreas();

        // First find all area's for this country directly (no wildcards)
        foreach($all_rates as $rate) {
            if(!(strpos(strtolower($rate->Country), strtolower($country)) === false))
                $countries->add($rate);
        }

        // If we have no countries in the list specificly, then check for wildcards
        if(!$countries->exists()) {
            foreach($all_rates as $rate) {
                if($rate->Country == "*") $countries->add($rate);
            }
        }

        // If we have a list of countries check them for post codes
        foreach($countries as $rate) {
            $rate_codes = explode(",",$rate->ZipCode);

            foreach($rate_codes as $rate_to_check) {
                $curr_length = strlen($rate_to_check);
                if(strtolower(substr($zipcode, 0, $curr_length)) == strtolower($rate_to_check)) {
                    $return->add($rate);
                }
            }
        }

        // If we still don't have anything to return, check or list of countries
        // for a wildcard
        if(!$return->exists()) {
            foreach($countries as $rate) {
                if($rate->ZipCode == "*") $return->add($rate);
            }
        }

        // Now we have a list of locations, start checking for additional
        // rules an remove if not applicable.
        $total_cost = str_replace(",","",$cart->SubTotalCost());
        $total_weight = str_replace(",","",$cart->TotalWeight());
        $total_items = str_replace(",","",$cart->TotalItems());

        $max_cost = 0;
        $max_weight = 0;
        $max_items = 0;

        // First loop through and find items that are invalid
        foreach($return as $location) {
            if($location->Calculation == "Price" && ((float)$total_cost < $location->Unit))
                $return->remove($location);

            if($location->Calculation == "Weight" && ((float)$total_weight < $location->Unit))
                $return->remove($location);

            if($location->Calculation == "Items" && ((float)$total_items < $location->Unit))
                $return->remove($location);
        }

        // Now find max values based on units
        foreach($return as $location) {
            if($location->Calculation == "Price" && ($location->Unit > $max_cost))
                $max_cost = $location->Unit;

            if($location->Calculation == "Weight" && ($location->Unit > $max_weight))
                $max_weight = $location->Unit;

            if($location->Calculation == "Items" && ($location->Unit > $max_items))
                $max_items = $location->Unit;
        }

        // Now loop through again and calculate which brackets each
        // Location fits in
        foreach($return as $location) {
            if($location->Calculation == "Price" && ($location->Unit < $max_cost))
                $return->remove($location);

            if($location->Calculation == "Weight" && ($location->Unit < $max_weight))
                $return->remove($location);

            if($location->Calculation == "Items" && ($location->Unit < $max_items))
                $return->remove($location);
        }

        return $return;
    }
    
}
