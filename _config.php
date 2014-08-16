<?php

// If subsites is installed
if(class_exists('Subsite')) {
    Order::add_extension('SubsitesOrdersExtension');
    OrderAdmin::add_extension('SubsiteMenuExtension');
}
