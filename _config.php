<?php

if(class_exists("Users_Account_Controller"))
    Users_Account_Controller::add_extension("CheckoutUserAccountControllerExtension");

if(class_exists("CatalogueProductController"))
    CatalogueProductController::add_extension("CheckoutCatalogueProductControllerExtension");
