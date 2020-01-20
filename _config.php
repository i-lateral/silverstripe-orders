<?php

// Ensure compatibility with PHP 7.2 ("object" is a reserved word),
// with SilverStripe 3.6 (using Object) and SilverStripe 3.7 (using SS_Object)
if (!class_exists('SS_Object')) class_alias('Object', 'SS_Object');

// If subsites is installed
if(class_exists('Users_Account_Controller')) {
    Users_Account_Controller::add_extension('OrdersUserAccountControllerExtension');
}

// If subsites is installed
if(class_exists('Subsite')) {
    Order::add_extension('SubsitesOrdersExtension');
    OrderAdmin::add_extension('SubsiteMenuExtension');
}
