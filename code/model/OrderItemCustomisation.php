<?php

/**
 * A single customisation that can be applied to an OrderItem.
 * 
 * A customisation by default allows the following details:
 *  - Title: The name of the customisation (eg. "Colour")
 *  - Value: The data associated with thie customisation (eg. "Red")
 *  - Price: Does this customisation change the OrderItem's price?
 */
class OrderItemCustomisation extends DataObject
{
    private static $db = array(
        "Title" => "Varchar",
        "Value" => "Text",
        "Price" => "Currency"
    );

    private static $has_one = array(
        "OrderItem" => "OrderItem"
    );
}