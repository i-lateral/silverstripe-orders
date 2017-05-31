<?php

/**
 * Helper class for the checkout, contains tools used by all
 * subcomponents of the checkout module.
 * 
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class Checkout extends ViewableData
{
    
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
     * Allow users skip the 
     * 
     * @var Boolean
     * @config
     */
    private static $click_and_collect = false;
    
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
        "PaymentNo",
        "PaymentID",
        "Status",
        "Company",
        "FirstName",
        "Surname",
        "Address1",
        "Address2",
        "City",
        "State",
        "PostCode",
        "Country",
        "CountryFull",
        "PhoneNumber",
        "Email",
        "DeliveryCompany",
        "DeliveryFirstnames",
        "DeliverySurname",
        "DeliveryAddress1",
        "DeliveryAddress2",
        "DeliveryCity",
        "DeliveryState",
        "DeliveryPostCode",
        "DeliveryCountry",
        "DeliveryCountryFull",
        "Discount",
        "DiscountAmount",
        "TaxRate",
        "PostageType",
        "PostageCost",
        "PostageTax"
    );

    /**
     * A list of fields in the checkout module that are mapped to
     * omnipay allowed fields.
     * 
     * This map is used to send the submitted checkout data
     * to omnipay services for payment.
     * 
     * NOTE: Be careful changing this as most of these keys are required
     * 
     * @var array
     * @config
     */
    private static $omnipay_map = array(
        "OrderNumber" => "transactionId",
        "FirstName" => "firstName",
        "Surname" => "lastName",
        "Email" => "email",
        "Company" => "company",
        "Address1" => "billingAddress1",
        "Address2" => "billingAddress2",
        "City" => "billingCity",
        "State" => "billingState",
        "PostCode" => "billingPostcode",
        "Country" => "billingCountry",
        "PhoneNumber" => "billingPhone",
        "DeliveryAddress1" => "shippingAddress1",
        "DeliveryAddress2" => "shippingAddress2",
        "DeliveryCity" => "shippingCity",
        "DeliveryState" => "shippingState",
        "DeliveryPostCode" => "shippingPostcode",
        "DeliveryContry" => "shippingCountry",
        "PhoneNumber" => "shippingPhone"
    );
    
    /**
     * Generate a free postage object we can use in our code.
     * 
     * @todo This is a little hacky, ideally we need to find a cleaner
     * way of dealing with postage options that doesn't involve unsaved
     * database objects.
     * 
     * @return PostageArea
     */
    public static function CreateFreePostageObject()
    {
        $postage = new PostageArea();
        $postage->ID = -1;
        $postage->Title = _t("Checkout.FreeShipping", "Free Shipping");
        $postage->Country = "*";
        $postage->ZipCode = "*";
        
        return $postage;
    }

    /**
     * Get the full translated country name from a 2 digit country code
     * EG: GB
     * 
     * @param $country_code 2 character code
     * @return string
     */
    public static function country_name_from_code($country_code)
    {
        try {
            $source = Zend_Locale::getTranslationList(
                'territory',
                $country_code,
                2
            );

            return (array_key_exists($country_code, $source)) ? $source[$country_code] : $country_code;
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Return guest checkout status in a way that can be seen by
     * templates
     * 
     * @return Boolean
     */
    public function GuestCheckout()
    {
        return $this->config()->guest_checkout;
    }
    
    /**
     * Return guest click and collect status in a way that can be seen
     * by templates
     * 
     * @return Boolean
     */
    public function ClickAndCollect()
    {
        return $this->config()->click_and_collect;
    }
    
    /**
     * Generate a random number based on the current time, a random int
     * and a third int that can be passed as a param.
     * 
     * @param $int integer that can make the number "more random"
     * @param $length Length of the string
     * @return Int
     */
    public static function getRandomNumber($int = 1, $length = 16)
    {
        return substr(md5(time() * rand() * $int), 0, $length);
    }

    /**
     * Rounds up a float to a specified number of decimal places
     * (basically acts like ceil() but allows for decimal places)
     *
     * @param float $value Float to round up
     * @param int $places the number of decimal places to round to
     * @return void
     */
    function round_up ($value, $places = 0)
    {
        if ($places < 0) {
            $places = 0;
        }
    
        $mult = pow(10, $places);
    
        return ceil($value * $mult) / $mult;
    }
}
