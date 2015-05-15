<?php

/**
 * Controller responsible for displaying either an rendered order or a
 * rendered quote that can be emailed or printed.
 * 
 * @package Orders
 */
class OrdersFront extends Controller {
    
    private static $allowed_actions = array(
        "invoice",
        "quote"
    );

    /**
     * ClassName of Order object 
     *
     * @var string
     * @config
     */
    private static $order_class = "Order";
    
    /**
     * ClassName of Order object 
     *
     * @var string
     * @config
     */
    private static $estimate_class = "Estimate";

    public function invoice() {
        $object = Order::get()
            ->filter(array(
                "ClassName" => $this->config()->order_class,
                "ID" => $this->request->param("ID")
            ))->first();

        if($object && $object->AccessKey && $object->AccessKey == $this->request->param("OtherID")) {
            return $this
                ->customise(array(
                    "SiteConfig" => SiteConfig::current_site_config(),
                    "MetaTitle" => _t("Orders.InvoiceTitle", "Invoice"),
                    "Object" => $object
                ))->renderWith(array(
                    "OrderFront_invoice",
                    "Orders",
                    "Page"
                ));
        } else
            return $this->httpError(404);
    }
    
    public function quote() {
        $object = Order::get()
            ->filter(array(
                "ClassName" => $this->config()->estimate_class,
                "ID" => $this->request->param("ID")
            ))->first();

        if($object && $object->AccessKey && $object->AccessKey == $this->request->param("OtherID")) {            
            return $this
                ->customise(array(
                    "SiteConfig" => SiteConfig::current_site_config(),
                    "MetaTitle" => _t("Orders.QuoteTitle", "Quote"),
                    "Object" => $object
                ))->renderWith(array(
                    "OrderFront_quote",
                    "Orders",
                    "Page"
                ));
        } else
            return $this->httpError(404);
    }
}
