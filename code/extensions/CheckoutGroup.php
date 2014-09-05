<?php

/**
 * Overwrite group object so we can setup some more default groups
 */
class CheckoutGroup extends DataExtension {

    private static $belongs_many_many = array(
        "Discounts" => "Discount"
    );
}

