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
    /**
     * Standard database columns
     *
     * @var array
     * @config
     */
    private static $db = array(
        "Title" => "Varchar",
        "Value" => "Text",
        "Price" => "Currency"
    );

    /**
     * DB foreign key associations
     *
     * @var array
     * @config
     */
    private static $has_one = array(
        "OrderItem" => "OrderItem"
    );

    /**
     * Fields to display in gridfields
     *
     * @var array
     * @config
     */
    private static $summary_fields = array(
        "Title",
        "Value",
        "Price"
    );
}