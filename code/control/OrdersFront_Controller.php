<?php

/**
 * Controller responsible for displaying either an rendered order or a
 * rendered quote that can be emailed or printed.
 * 
 * @package Orders
 */
class OrdersFront_Controller extends Controller
{
    /**
     * ClassName of Order object 
     *
     * @var string
     * @config
     */
    private static $url_segment = "orders/front";
    
    
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
    
    /**
     * Get a relative link to anorder or invoice
     * 
     * NOTE: this controller will always require an ID of an order and
     * access key to be passed (as well as an action). 
     * 
     * @param $action Action we would like to view.
     * @param $id ID or the order we want to view.
     * @param $key Access key of the order (for security).
     * @return string
     */
    public function Link($action = "invoice") {
        return Controller::join_links(
            $this->config()->url_segment,
            $action
        );
    }
    
    /**
     * Get an absolute link to an order or invoice
     * 
     * NOTE: this controller will always require an ID of an order and
     * access key to be passed (as well as an action). 
     * 
     * @param $action Action we would like to view.
     * @param $id ID or the order we want to view.
     * @param $key Access key of the order (for security).
     * @return string
     */
    public function AbsoluteLink($action = "invoice") {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            $this->Link($action)
        );
    }

    public function invoice()
    {
        $object = Order::get()
            ->filter(array(
                "ClassName" => $this->config()->order_class,
                "ID" => $this->request->param("ID")
            ))->first();

        if ($object && $object->AccessKey && $object->AccessKey == $this->request->param("OtherID")) {
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
        } else {
            return $this->httpError(404);
        }
    }
    
    public function quote()
    {
        $object = Order::get()
            ->filter(array(
                "ClassName" => $this->config()->estimate_class,
                "ID" => $this->request->param("ID")
            ))->first();

        if ($object && $object->AccessKey && $object->AccessKey == $this->request->param("OtherID")) {
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
        } else {
            return $this->httpError(404);
        }
    }
}
