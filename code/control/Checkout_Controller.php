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
        "usememberaddress",
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
            Director::BaseURL(),
            $this->config()->url_segment,
            $action
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
     * Use the address provided via the $ID param in the URL. The
     * $OtherID param is used to determine if the address is billing
     * or delivery.
     *
     * If no $ID or $OtherID is provided, we return an error.
     *
     * @return redirect
     */
    public function usememberaddress()
    {
        $allowed_otherids = array("billing","delivery");
        $id = $this->request->param("ID");
        $otherid = $this->request->param("OtherID");
        $data = array();
        $member = Member::currentUser();
        $address = MemberAddress::get()->byID($id);
        $cart = ShoppingCart::get();
        $action = "billing";

        // If our required details are not set, return a server error
        if (
            !$address ||
            !$member ||
            ($address && !$address->canView($member)) ||
            !in_array($otherid, $allowed_otherids)
        ) {
            return $this
                ->httpError(
                    404,
                    "There was an error selecting your address"
                );
        }

        // Set the session data
        if ($otherid == "billing") {
            $data["FirstName"]  = $address->FirstName;
            $data["Surname"]    = $address->Surname;
            $data["Address1"]   = $address->Address1;
            $data["Address2"]   = $address->Address2;
            $data["City"]       = $address->City;
            $data["State"]      = $address->State;
            $data["PostCode"]   = $address->PostCode;
            $data["Country"]    = $address->Country;
            $data["Email"]      = $member->Email;
            $data["PhoneNumber"]= $member->PhoneNumber;
            $data["Company"]    = $address->Company;

            Session::set("Checkout.BillingDetailsForm.data", $data);
            $action = "delivery";
        }

        if ($otherid == "delivery" || !$cart->isDeliverable()) {
            $data['DeliveryCompany']  = $address->Company;
            $data['DeliveryFirstName']  = $address->FirstName;
            $data['DeliverySurname']    = $address->Surname;
            $data['DeliveryAddress1']   = $address->Address1;
            $data['DeliveryAddress2']   = $address->Address2;
            $data['DeliveryCity']       = $address->City;
            $data['DeliveryState']      = $address->State;
            $data['DeliveryPostCode']   = $address->PostCode;
            $data['DeliveryCountry']    = $address->Country;

            Session::set("Checkout.DeliveryDetailsForm.data", $data);
            $action = "finish";
        }

        $this->extend("onBeforeUseMemberAddress");

        return $this->redirect($this->Link($action));
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

        if (!Checkout::config()->simple_checkout && !is_array($data)) {
            return $this->redirect($this->Link('index'));
        }
        
        // Check permissions for guest checkout
        if (!Member::currentUserID() && !Checkout::config()->guest_checkout) {
            return $this->redirect($this->Link('index'));
        }
            
        if (Checkout::config()->simple_checkout) {
            $title = _t('Checkout.SelectPaymentMethod', "Select Payment Method");
        } else {
            $title = _t('Checkout.SeelctPostagePayment', "Select Postage and Payment Method");
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
