<?php

/**
 * Controller used to render the checkout process
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class Checkout_Controller extends Controller
{

    /**
     * URL Used to generate links to this controller.
     * 
     * NOTE If you alter routes.yml, you MUST alter this. 
     * 
     * @var string
     * @config
     */
    private static $url_segment = "checkout/checkout";

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
        "finish",
        "LoginForm",
        'CustomerForm',
        "PostagePaymentForm"
    );

    public function getClassName()
    {
        return self::config()->class_name;
    }
    
        
    /**
     * Get the link to this controller
     * 
     * @return string
     */
    public function Link($action = null)
    {
        return Controller::join_links(
            $this->config()->url_segment,
            $action
        );
    }

    /**
     * Get an absolute link to this controller
     *
     * @param string $action The action you want to add to the link
     * @return string
     */
    public function AbsoluteLink($action = null)
    {
        return Director::absoluteURL($this->Link($action));
    }

    /**
     * Get a relative (to the root url of the site) link to this
     * controller
     *
     * @param string $action The action you want to add to the link
     * @return string
     */
    public function RelativeLink($action = null)
    {
        return Controller::join_links(
            Director::baseURL(),
            $this->Link($action)
        );
    }

    public function init()
    {
        parent::init();

        $cart = ShoppingCart::get();

        // If no shopping cart doesn't exist, redirect to base
        if (!$cart->getItems()->exists()) {
            return $this->redirect($cart->Link());
        }
    }

    /**
     *
     */
    public function index()
    {
        // If we are using simple checkout, skip
        if (Checkout::config()->simple_checkout) {
            return $this->redirect($this->Link('finish'));
        }
        
        // If we have turned off login, or member logged in
        $login = false;
        if (Checkout::config()->login_form && !Member::currentUserID()) {
            $login = true;
        }
        
        $this->customise(array(
            'Title'     => _t('Checkout.Checkout', "Checkout"),
            "ShowLoginForm"     => $login,
            "Form"      => $this->CustomerForm()
        ));

        $this->extend("onBeforeIndex");

        return $this->renderWith(array(
            'Checkout',
            'Page'
        ));
    }


    /**
     * Final step, allowing user to select postage and payment method
     *
     * @return array
     */
    public function finish()
    {
        // Check the users details are set, if not, send them to the cart
        $data = Session::get("Checkout.CustomerDetails.data");
        $cart = ShoppingCart::get();
        
        if (!Checkout::config()->simple_checkout && !is_array($data)) {
            return $this->redirect($this->Link('index'));
        }
        
        // Check permissions for guest checkout
        if (!Member::currentUserID() && !Checkout::config()->guest_checkout) {
            return $this->redirect($this->Link('index'));
        }

        if ($cart->isCollection() || !$cart->isDeliverable()) {
            return $this->redirect(Payment_Controller::create()->Link());
        }
            
        if (Checkout::config()->simple_checkout) {
            $title = _t('Checkout.SelectPaymentMethod', "Select Payment Method");
        } else {
            $title = _t('Checkout.SeelctPostageMethod', "Select Postage Method");
        }

        $customer = ArrayData::create($data);

        $this->customise(array(
            'Title'     => $title,
            'Form'      => $this->PostagePaymentForm(),
            'Customer' => $customer
        ));

        $this->extend("onBeforeFinish");

        return $this->renderWith(array(
            'Checkout_finish',
            'Checkout',
            'Page'
        ));
    }

    /**
     * Generate a login form
     *
     * @return MemberLoginForm
     */
    public function LoginForm()
    {
        $form = CheckoutLoginForm::create($this, 'LoginForm');
        $form->setAttribute("action", $this->Link("LoginForm"));
        $form->setTemplate("CheckoutLoginForm");

        $form
            ->Fields()
            ->add(HiddenField::create("BackURL")->setValue($this->Link()));
        
        $login_action = $form
            ->Actions()
            ->dataFieldByName('action_dologin');
        
            if ($login_action) {
            $login_action->addExtraClass("btn btn-primary");
        }

        $this->extend("updateLoginForm", $form);

        return $form;
    }

    /**
     * Form to capture the customers details
     *
     * @return CustomerDetailsForm
     */
    public function CustomerForm()
    {
        $form = CustomerDetailsForm::create($this, 'CustomerForm');

        $data = Session::get("Checkout.CustomerDetailsForm.data");
        if (is_array($data)) {
            $form->loadDataFrom($data);
        } elseif($member = Member::currentUser()) {
            // Fill email, phone, etc
            $form->loadDataFrom($member);
            
            // Then fill with Address info
            if($member->DefaultAddress()) {
                $form->loadDataFrom($member->DefaultAddress());
            }
        }

        $this->extend("updateCustomerForm", $form);

        return $form;
    }

    /**
     * Form to find postage options and allow user to select payment
     *
     * @return PostagePaymentForm
     */
    public function PostagePaymentForm()
    {
        $form = PostagePaymentForm::create($this, "PostagePaymentForm");

        $this->extend("updatePostagePaymentForm", $form);

        return $form;
    }
}
