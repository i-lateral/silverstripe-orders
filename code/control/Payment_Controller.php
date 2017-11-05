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
        "PaymentForm",
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
        $member = Member::currentUser();
        $order = null;

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
                $data['PostageTax'] = ($postage->Tax) ? Checkout::round_up(($postage->Cost / 100) * $postage->Tax, 2) : 0;
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

        // If user logged in then get their estimate,
        // otherwise duplicate the cart
        if ($member) {
            $order = $member->getCart();
        }
        
        if ($order) {
            $order->update($payment_data);
            $order->write();
        } else {
            $order = new Estimate();
            $order->update($payment_data);

            // Use this to generate a new order number
            $order->OrderNumber = "";
            
            // If we are using collection, track it here
            if ($cart->isCollection()) {
                $order->Action = "collect";
            }

            // If user logged in, track it against an order
            if ($member) {
                $order->CustomerID = $member->ID;
            }

            // Write so we can setup our foreign keys
            $order->write();

            // Loop through each session cart item and add that item to the order
            foreach ($cart->getItems() as $order_item) {
                $new_item = $order_item->duplicate();
                $new_item->write();

                $cart
                    ->getItems()
                    ->remove($new_item);
                $order
                    ->Items()
                    ->add($new_item);
            }
        }

        $this->extend("onBeforeIndex", $order);

        Session::set("Checkout.OrderID", $order->ID);

        $form = $this->PaymentForm();

        // Generate a map of payment data and load into form.
        // This way we can add users to a form
        $omnipay_data = [];
        $omnipay_map = Checkout::config()->omnipay_map;

        foreach ($payment_data as $key => $value) {
            if (array_key_exists($key, $omnipay_map)) {
                $omnipay_data[$omnipay_map[$key]] = $value;
            }
        }

        $form->loadDataFrom($omnipay_data);

        $this->customise(array(
            "Form" => $form,
            "Order" => $order
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
        $cart = ShoppingCart::get();

        $id = $this->request->param('ID');
        $error = ($id == "error") ? true : false;

        if ($error) {
            $return = $this->error_data();
        } else {
            $return = $this->success_data();
        }

        // Add the paid order data to our completed page
        $order = Order::get()
            ->byID(Session::get("Checkout.OrderID"));

        $return["Order"] = $order;
            
        $this->customise($return);
        
        // Extend our completion process, to allow for custom completion
        // templates
        $this->extend("onBeforeComplete");

        // Clear our session data
        if (!$error && isset($_SESSION)) {
            $cart->clear();
            unset($_SESSION['Checkout.PaymentMethod']);
            unset($_SESSION['Checkout.OrderID']);
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
            'Content' => ($site->PaymentSuccessContent) ? nl2br(Convert::raw2xml($site->PaymentSuccessContent), true) : ""
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
            'Content'   => ($site->PaymentFailerContent) ? nl2br(Convert::raw2xml($site->PaymentFailerContent), true) : ""
        );
    }

    /**
     * Generate a payment form using omnipay scafold
     *
     * @return Form
     */
    public function PaymentForm()
    {
        $factory = new GatewayFieldsFactory($this->getPaymentMethod());

        $form = Form::create(
            $this,
            "PaymentForm",
            $factory->getFields(),
            FieldList::create(
                FormAction::create(
                    "doSubmit",
                    _t("Checkout.PayNow", "Pay Now")
                )->addExtraClass("btn btn-success btn-block")
            )
        );

        $this->extend("updatePaymentForm", $form);

        return $form;
    }

    public function doSubmit($data, $form)
    {
        $order = Order::get()
            ->byID(Session::get("Checkout.OrderID"));

        // If an error with order, redirect back
        if (!$order) {
            $form->sessionMessage(
                "Orders.ErrorCreatingOrder",
                "Error creating order, please try again"
            );
            
            return $this->redirectBack();
        }

        // Map our order data to an array to omnipay
        $omnipay_data = [];
        $omnipay_map = Checkout::config()->omnipay_map;

        foreach ($order->toMap() as $key => $value) {
            if (array_key_exists($key, $omnipay_map)) {
                $omnipay_data[$omnipay_map[$key]] = $value;
            }
        }

        $omnipay_data = array_merge($omnipay_data, $data);

        // Create the payment object. We pass the desired success and failure URLs as parameter to the payment
        $payment = Payment::create()
            ->init(
                $this->getPaymentMethod(),
                Checkout::round_up($order->Total, 2),
                Checkout::config()->currency_code
            )->setSuccessUrl($this->Link('complete'))
            ->setFailureUrl(Controller::join_links(
                $this->Link('complete'),
                "error"
            ));
        
        // Map order ID & save to generate an ID
        $payment->OrderID = $order->ID;
        $payment->write();

        // Add an extension before we finalise the payment
        // so we can overwrite our data
        $this->extend("onBeforeSubmit", $payment, $order, $data);

        $response = ServiceFactory::create()
            ->getService($payment, ServiceFactory::INTENT_PAYMENT)
            ->initiate($omnipay_data);

        return $response->redirectOrRespond();
    }
}
