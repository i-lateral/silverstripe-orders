<?php

// If subsites is installed
if(class_exists('Users_Account_Controller')) {
    Users_Account_Controller::add_extension('OrdersUserAccountControllerExtension');
}

// If subsites is installed
if(class_exists('Subsite')) {
    Order::add_extension('SubsitesOrdersExtension');
    OrderAdmin::add_extension('SubsiteMenuExtension');
}
