<?php

/**
 * Overwrite group object
 * 
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class CheckoutGroupExtension extends DataExtension
{

    private static $belongs_many_many = array(
        "Discounts" => "Discount"
    );
}
