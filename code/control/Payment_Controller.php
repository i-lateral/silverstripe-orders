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
        "index",
        "callback",
        "complete"
    );

	/**
	 * Set up the "restful" URLs
	 *
	 * @config
	 * @var array
	 */
	private static $url_handlers = array(
		'$Action/$ID' => 'handleAction',
	);
    
    /**
     * Name of the payment handler we are using
     * 
     * @var string
     */
    protected $payment_handler;

    public function getPaymentHandler() {
        return $this->payment_handler;
    }

    public function setPaymentHandler($handler) {
        $this->payment_handler = $handler;
        return $this;
    }

    /**
     * Name of the payment method we are using
     * 
     * @var string
     */
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
     * Shortcut to checkout config, to allow us to access it via
     * templates
     * 
     * @return boolean
     */
    public function ShowTax() {
        return Checkout::config()->show_tax;
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

        // Check if payment ID set and corresponds 
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
            $this->payment_handler->setPaymentGateway($this->getPaymentMethod());
        }
    }
    

    /**
     * Action that gets called before we interface with our payment
     * method.
     *
     * This action is responsible for setting up an order and
     * saving it into the database (as well as a session) and then hands
     * the current request over to the relevent payment handler
     * for final processing.
     *
     * @param $request Current request object
     */
    public function index($request) {
        $cart = ShoppingCart::get();
        $data = array();
        $payment_data = array();
        $handler = $this->payment_handler;

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

        // Create an order number
        $data["OrderNumber"] = substr(chunk_split(Checkout::getRandomNumber(), 4, '-'), 0, -1);

        // Set status
        $data['Status'] = 'incomplete';

        // Assign billing, delivery and postage data
        if(!Checkout::config()->simple_checkout) {
            $data = array_merge($data, $billing_data, $delivery_data);
            
            $data['PostageType'] = $postage->Title;
            $data['PostageCost'] = $postage->Cost;
            $data['PostageTax'] = ($postage->Tax) ? ($postage->Cost / 100) * $postage->Tax : 0;
            $data['DiscountAmount'] = $cart->DiscountAmount;
            
            foreach(Checkout::config()->checkout_data as $key) {
                if(array_key_exists($key,$data))
                    $payment_data[$key] = $data[$key];
            }
        }
        
        // Set our order data as a generic object
        $handler->setOrderData(ArrayData::array_to_object($payment_data));
        
        return $handler->handleRequest($request, $this->model);
    }


    /**
     * This method can be called by a payment gateway to provide
     * automated integration.
     * 
     * This action performs some basic setup then hands control directly
     * to the payment handler's "callback" action.
     * 
     * @param $request Current Request Object
     */
    public function callback($request) {
        // If post data exists, process. Otherwise provide error
        if($this->payment_handler === null) {
            // Redirect to error page
            return $this->redirect(Controller::join_links(
                Director::BaseURL(),
                $this->config()->url_segment,
                'complete',
                'error'
            ));
        }
        
        // Hand the request over to the payment handler
        return $this
            ->payment_handler
            ->handleRequest($request, $this->model);
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
                "Payment_complete_" . $this->payment_handler,
                "Payment_complete",
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
