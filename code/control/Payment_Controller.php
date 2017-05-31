<?php

use SilverStripe\Omnipay\GatewayInfo;
use SilverStripe\Omnipay\GatewayFieldsFactory;
use SilverStripe\Omnipay\Service\ServiceFactory;

/**
 * Summary Controller is responsible for displaying all order data before posting
 * to the final payment gateway.
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class Payment_Controller extends Controller
{

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
        "complete",
        "Form",
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
     * ID of the payment method we are using
     * 
     * @var string
     */
    protected $payment_method;

    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    public function setPaymentMethod($method)
    {
        $this->payment_method = $method;
        return $this;
    }
    
    public function getClassName()
    {
        return self::config()->class_name;
    }
    
    /**
     * Shortcut to checkout config, to allow us to access it via
     * templates
     * 
     * @return boolean
     */
    public function ShowTax()
    {
        return Checkout::config()->show_tax;
    }
        
    /**
     * Get the link to this controller
     * 
     * @return string
     */
    public function Link($action = null)
    {
        return Controller::join_links(
            Director::BaseURL(),
            $this->config()->url_segment,
            $action
        );
    }

    public function init()
    {
        parent::init();

        // Check if payment ID set and corresponds
        try {
            $cart = ShoppingCart::get();
            $payment_methods = GatewayInfo::getSupportedGateways();
            $payment_session = Session::get('Checkout.PaymentMethodID');

            if (array_key_exists($payment_session, $payment_methods)) {
                $this->payment_method = $payment_session;
            }

        } catch (Exception $e) {
            return $this->httpError(
                400,
                $e->getMessage()
            );
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
    public function index($request)
    {
        $cart = ShoppingCart::get();
        $data = array();
        $payment_data = array();

        // If shopping cart doesn't exist, redirect to base
        if (!$cart->getItems()->exists()) {
            return $this->redirect($cart->Link());
        }

        // Get billing and delivery details and merge into an array
        $billing_data = Session::get("Checkout.BillingDetailsForm.data");
        $delivery_data = Session::get("Checkout.DeliveryDetailsForm.data");
        
        // If we have applied free shipping, set that up, else get 
        if (Session::get('Checkout.PostageID') == -1 || !$cart->isDeliverable()) {
            $postage = Checkout::CreateFreePostageObject();
        } else {
            $postage = PostageArea::get()->byID(Session::get('Checkout.PostageID'));
        }
        
        // If we are using a complex checkout and do not have correct details redirect 
        if (!Checkout::config()->simple_checkout && !$cart->isCollection() && $cart->isDeliverable() && (!$postage || !$billing_data || !$delivery_data)) {
            return $this->redirect(Checkout_Controller::create()->Link());
        }
            
        if ($cart->isCollection() && (!$billing_data)) {
            return $this->redirect(Checkout_Controller::create()->Link());
        }

        // Create an order number
        $data["OrderNumber"] = substr(chunk_split(Checkout::getRandomNumber(), 4, '-'), 0, -1);
        
        // Setup holder for Payment ID
        $data["PaymentID"] = 0;

        // Set status
        $data['Status'] = 'incomplete';

        // Assign billing, delivery and postage data
        if (!Checkout::config()->simple_checkout) {
            $data = array_merge($data, $billing_data);
            $data = (is_array($delivery_data)) ? array_merge($data, $delivery_data) : $data;
            $checkout_data = Checkout::config()->checkout_data;
            
            if (!$cart->isCollection()) {
                $data['PostageType'] = $postage->Title;
                $data['PostageCost'] = $postage->Cost;
                $data['PostageTax'] = ($postage->Tax) ? ($postage->Cost / 100) * $postage->Tax : 0;
            }
            
            if ($cart->getDiscount()) {
                $data['Discount'] = $cart->getDiscount()->Title;
                $data['DiscountAmount'] = $cart->DiscountAmount;
            }
            
            // Add full country names if needed
            if (in_array("CountryFull", $checkout_data)) {
                $data['CountryFull'] = Checkout::country_name_from_code($data["Country"]);
            }
            
            if (in_array("DeliveryCountryFull", $checkout_data) && array_key_exists("DeliveryCountry", $data)) {
                $data['DeliveryCountryFull'] = Checkout::country_name_from_code($data["DeliveryCountry"]);
            }
            
            foreach ($checkout_data as $key) {
                if (array_key_exists($key, $data)) {
                    $payment_data[$key] = $data[$key];
                }
            }
        }

        $order_data = ArrayData::array_to_object($payment_data);

        $this->extend("onBeforeIndex", $order_data);

        Session::set("Checkout.OrderData", serialize($order_data));

        $this->customise(array(
            "Order" => $order_data
        ));

        return $this->renderWith(array(
            "Payment",
            "Page"
        ));
    }

    /*
     * Deal with rendering a completion message to the end user
     *
     * @return String
     */
    public function complete()
    {
        $site = SiteConfig::current_site_config();

        $id = $this->request->param('ID');

        if ($id == "error") {
            $return = $this->error_data();
        } else {
            $return = $this->success_data();
        }
            
        $this->customise($return);
        
        // Extend our completion process, to allow for custom completion
        // templates
        $this->extend("onBeforeComplete");

        // Clear our session data
        if (isset($_SESSION)) {
            ShoppingCart::get()->clear();
            unset($_SESSION['Checkout.PostageID']);
            unset($_SESSION['Checkout.PaymentMethod']);
        }

        return $this->renderWith(array(
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
    public function success_data()
    {
        $site = SiteConfig::current_site_config();

        return array(
            'Title' => _t('Checkout.ThankYouForOrder', 'Thank you for your order'),
            'Content' => ($site->SuccessCopy) ? nl2br(Convert::raw2xml($site->SuccessCopy), true) : false
        );
    }

    /*
     * Pull together data to be used in success templates
     *
     * @return array
     */
    public function error_data()
    {
        $site = SiteConfig::current_site_config();

        return array(
            'Title'     => _t('Checkout.OrderProblem', 'There was a problem with your order'),
            'Content'   => ($site->FailerCopy) ? nl2br(Convert::raw2xml($site->FailerCopy), true) : false
        );
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function Form()
    {
        $factory = new GatewayFieldsFactory($this->getPaymentMethod());

        $submit_btn = FormAction::create(
            "doSubmit",
            _t("Checkout.PayNow", "Pay Now")
        )->addExtraClass("btn btn-success btn-block");

        return Form::create(
            $this,
            "Form",
            $factory->getFields(),
            FieldList::create($submit_btn)
        );
    }

    public function doSubmit($data, $form)
    {
        $order_data = unserialize(Session::get("Checkout.OrderData"));
        $cart = ShoppingCart::get();

        // Map our order data to an array to omnipay
        $omnipay_data = array();
        $omnipay_map = Checkout::config()->omnipay_map;

        foreach ($order_data as $key => $value) {
            if (array_key_exists($key, $omnipay_map)) {
                $omnipay_data[$omnipay_map[$key]] = $value;
            }
        }

        $omnipay_data = array_merge($omnipay_data, $data);

        // Create the payment object. We pass the desired success and failure URLs as parameter to the payment
        $payment = Payment::create()
            ->init(
                $this->getPaymentMethod(),
                Checkout::round_up($cart->TotalCost, 2),
                Checkout::config()->currency_code
            )->setSuccessUrl($this->Link('complete'))
            ->setFailureUrl(Controller::join_links(
                $this->Link('complete'),
                "error"
            ));

        // Save it to the database to generate an ID
        $payment->write();

        // Add an extension before we finalise the payment
        // so we can overwrite our data
        $this->extend("onBeforeSubmit", $payment, $order_data, $data);

        $response = ServiceFactory::create()
            ->getService($payment, ServiceFactory::INTENT_PAYMENT)
            ->initiate($omnipay_data);

        return $response->redirectOrRespond();
    }
}
