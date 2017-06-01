<?php

/**
 * Extension for Content Controller that provide methods such as cart
 * link and category list to templates
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package orders
 */
class OrdersControllerExtension extends Extension
{
    
    /**
     * Get the current shoppingcart
     * 
     * @return ShoppingCart
     */
    public function getShoppingCart()
    {
        return ShoppingCart::get();
    }
    
    /**
     * Get the checkout config
     * 
     * @return ShoppingCart
     */
    public function getCheckout()
    {
        return Checkout::create();
    }
    
    public function onBeforeInit()
    {
        $controller = $this->owner->request->param("Controller");
        $action = $this->owner->request->param("Action");
        
        if ($controller != "DevelopmentAdmin" && $action != "build") {
            $config = SiteConfig::current_site_config();
            
            // Set the default currency symbol for this site
            Currency::config()->currency_symbol = Checkout::config()->currency_symbol;
            
            // Auto inject the order prefix to the orders module if it exists
            if (class_exists("Order") && class_exists("SiteConfig") && $config) {
                Order::config()->order_prefix = $config->PaymentNumberPrefix;
            }
        }
    }
}
