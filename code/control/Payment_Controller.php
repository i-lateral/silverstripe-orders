<?php

/**
 * Summary Controller is responsible for displaying all order data before posting
 * to the final payment gateway.
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class Payment_Controller extends Controller {

    /**
     * URL Used to generate links to this controller.
     * 
     * NOTE If you alter routes.yml, you MUST alter this. 
     * 
     * @var string
     * @config
     */
    private static $url_segment = "checkout/payment";
    
    /**
     * Name of the current controller. Mostly used in templates for
     * targeted styling.
     *
     * @var string
     * @config
     */
    private static $class_name = "Checkout";
    

    private static $allowed_actions = array(
        'index',
        'callback',
        'complete'
    );

    protected $payment_handler;

    public function getPaymentHandler() {
        return $this->payment_handler;
    }

    public function setPaymentHandler($handler) {
        $this->payment_handler = $handler;
        return $this;
    }


    protected $payment_method;

    public function getPaymentMethod() {
        return $this->payment_method;
    }

    public function setPaymentMethod($method) {
        $this->payment_method = $method;
        return $this;
    }
    
    public function getClassName() {
        return self::config()->class_name;
    }
        
    /**
     * Get the link to this controller
     * 
     * @return string
     */
    public function Link($action = null) {
        return Controller::join_links(
            Director::BaseURL(),
            $this->config()->url_segment,
            $action
        );
    }

    public function init() {
        parent::init();

        // Check if payment slug is set and that corresponds to a payment
        if($this->request->param('ID') && $method = PaymentMethod::get()->byID($this->request->param('ID')))
            $this->payment_method = $method;
        // Then check session
        elseif($method = PaymentMethod::get()->byID(Session::get('Checkout.PaymentMethodID')))
            $this->payment_method = $method;

        // Setup payment handler
        if($this->payment_method && $this->payment_method !== null) {
            $handler = $this->payment_method->ClassName;
            $handler = $handler::$handler;

            $this->payment_handler = $handler::create();
            $this->payment_handler->setRequest($this->request);
            $this->payment_handler->setURLParams = $this->request->allParams();
            $this->payment_handler->setPaymentGateway($this->getPaymentMethod());
        }
    }

    /**
     * Action that gets called before we interface with our payment
     * method.
     *
     * This action is responsible for setting up an order and
     * saving it into the database (as well as a session) and also then
     * generating an order summary before the user performs any final
     * actions needed.
     *
     * This action is then mapped directly to the index action of the
     * Handler for the payment method that was selected by the user
     * in the "Postage and Payment" form.
     *
     */
    public function index() {
        $cart = ShoppingCart::get();
        $data = array();

        // If shopping cart doesn't exist, redirect to base
        if(!$cart->getItems()->exists() || $this->getPaymentHandler() === null)
            return $this->redirect($cart->Link());

        // Get billing and delivery details and merge into an array
        $billing_data = Session::get("Checkout.BillingDetailsForm.data");
        $delivery_data = Session::get("Checkout.DeliveryDetailsForm.data");
        $postage = PostageArea::get()->byID(Session::get('Checkout.PostageID'));

        // If we are using a complex checkout and do not have correct
        // details redirect 
        if(!Checkout::config()->simple_checkout && (!$postage || !$billing_data || !$delivery_data))
            return $this->redirect(Checkout_Controller::create()->Link());

        $config = SiteConfig::current_site_config();
        
        $data['OrderNumber'] = ($config->OrderPrefix) ? $config->OrderPrefix . '-' : '';
        $data["OrderNumber"] .= substr(chunk_split(Checkout::getRandomNumber(), 4, '-'), 0, -1);

        // Set status
        $data['Status'] = 'incomplete';

        // Assign billing, delivery and postage data 
        if(!Checkout::config()->simple_checkout) {
            foreach(Checkout::config()->checkout_data as $key=>$value) {
                if(array_key_exists($key,$billing_data))
                    $data[$key] = $billing_data[$key];
                elseif(array_key_exists($key,$delivery_data))
                    $data[$key] = $delivery_data[$key];
            }
            
            $data['PostageType'] = $postage->Title;
            $data['PostageCost'] = $postage->Cost;
            $data['PostageTax'] = ($config->TaxRate > 0 && $postage->Cost > 0) ? ((float)$postage->Cost / 100) * $config->TaxRate : 0;
        }

        // Set discount info
        $data['DiscountAmount'] = $cart->DiscountAmount();
        
        // Get gateway data
        $return = $this
            ->payment_handler
            ->setOrderData($data)
            ->index();
        
        // Extend this method
        $this->extend("onBeforeIndex", $data, $return);

        return $this
            ->customise($return)
            ->renderWith(array(
                "Payment",
                "Checkout",
                "Page"
            ));
    }


    /**
     * This method is what is called at the end of the transaction. It
     * takes either post data or get data and then sends it to the
     * relevent payment method for processing.
     * 
     * If you add a new payment method and handler, you will be expected
     * to return an array, containging the following possible options:
     * 
     * - OrderNumber (ID of an order that the callback related to)
     * - Status (status that the order returned)
     * - Template (a rendered template to display)
     * - Redirect (a URL to redirect to)
     * 
     * 
     */
    public function callback() {
        // If post data exists, process. Otherwise provide error
        if($this->payment_handler !== null) {
            $callback = $this->payment_handler->callback();
        } else {
            // Redirect to error page
            return $this->redirect(Controller::join_links(
                Director::BaseURL(),
                $this->config()->url_segment,
                'complete',
                'error'
            ));
        }
        
        $this->extend("onBeforeCallback", $callback);
        
        if(array_key_exists("Template",$callback))
            return $callback["Template"];
        
        if(array_key_exists("Redirect",$callback))
            return $callback["Redirect"];

        // Otherwise just return the $callback
        return $callback;
    }

    /*
     * Deal with rendering a completion message to the end user
     *
     * @return String
     */
    public function complete() {
        $site = SiteConfig::current_site_config();

        $id = $this->request->param('ID');

        if($id == "error")
            $return = $this->error_data();
        else
            $return = $this->success_data();

        if($order) {
            $return['CheckoutPaymentSuccess'] = true;
            $return['Order'] = $order;
        } else {
            $return['CheckoutPaymentSuccess'] = false;
            $return['Order'] = false;
        }

        // Clear our session data
        if(isset($_SESSION)) {
            ShoppingCart::get()->clear();
            unset($_SESSION['Checkout.Order']);
            unset($_SESSION['Checkout.PostageID']);
            unset($_SESSION['Checkout.PaymentMethod']);
        }

        return $this
            ->customise($return)
            ->renderWith(array(
                "Payment_Response",
                "Checkout",
                "Page"
            ));
    }

    /*
     * Pull together data to be used in success templates
     *
     * @return array
     */
    public function success_data() {
        $site = SiteConfig::current_site_config();

        return array(
            'Title' => _t('Checkout.ThankYouForOrder','Thank you for your order'),
            'Content' => ($site->SuccessCopy) ? nl2br(Convert::raw2xml($site->SuccessCopy), true) : false
        );
    }

    /*
     * Pull together data to be used in success templates
     *
     * @return array
     */
    public function error_data() {
        $site = SiteConfig::current_site_config();

        return array(
            'Title'     => _t('Checkout.OrderProblem','There was a problem with your order'),
            'Content'   => ($site->FailerCopy) ? nl2br(Convert::raw2xml($site->FailerCopy), true) : false
        );
    }
}
