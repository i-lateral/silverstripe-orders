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
        "PostageForm"
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

        $customer = ArrayData::create($data);

        $this->customise(array(
            'Form'      => $this->PostageForm(),
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

        $data = Session::get("Checkout.CustomerDetails.data");
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
    public function PostageForm()
    {
        $cart = ShoppingCart::get();
        $validator = RequiredFields::create();
        
        if (!Checkout::config()->simple_checkout && !$cart->isCollection() && $cart->isDeliverable()) {
            // Get delivery data and postage areas from session
            $delivery_data = Session::get("Checkout.CustomerDetails.data");
            $country = $delivery_data['DeliveryCountry'];
            $postcode = $delivery_data['DeliveryPostCode'];
            
            $postage_areas = new ShippingCalculator($postcode, $country);
            $postage_areas
                ->setCost($cart->SubTotalCost)
                ->setWeight($cart->TotalWeight)
                ->setItems($cart->TotalItems);
                
            $postage_areas = $postage_areas->getPostageAreas();

            // Loop through all postage areas and generate a new list
            $postage_array = array();
            foreach ($postage_areas as $area) {
                $area_currency = new Currency("Cost");
                $area_currency->setValue($area->Cost);
                $postage_array[$area->ID] = $area->Title . " (" . $area_currency->Nice() . ")";
            }

            if (Session::get('Checkout.PostageID')) {
                $postage_id = Session::get('Checkout.PostageID');
            } elseif ($postage_areas->exists()) {
                $postage_id = $postage_areas->first()->ID;
            } else {
                $postage_id = 0;
            }

            if (count($postage_array)) {
                $select_postage_field = OptionsetField::create(
                    "PostageID",
                    _t('Checkout.PostageSelection', 'Please select your preferred postage'),
                    $postage_array
                )->setValue($postage_id);
            } else {
                $select_postage_field = ReadonlyField::create(
                    "NoPostage",
                    "",
                    _t('Checkout.NoPostageSelection', 'Unfortunately we cannot deliver to your address')
                )->addExtraClass("label")
                ->addExtraClass("label-red");
            }

            // Setup postage fields
            $postage_field = CompositeField::create(
                HeaderField::create("PostageHeader", _t('Checkout.Postage', "Postage")),
                $select_postage_field
            )->setName("PostageFields");

            $validator->addRequiredField("PostageID");
        } elseif ($cart->isCollection()) {
            $postage_field = CompositeField::create(
                HeaderField::create("PostageHeader", _t('Checkout.CollectionOnly', "Collection Only")),
                ReadonlyField::create(
                    "CollectionText",
                    "",
                    _t("Checkout.ItemsReservedInstore", "Your items will be held instore until you collect them")
                )
            )->setName("CollectionFields");
        } elseif (!$cart->isDeliverable()) {
            $postage_field = CompositeField::create(
                HeaderField::create(
                    "PostageHeader",
                    _t('Checkout.Postage', "Postage")
                ),
                ReadonlyField::create(
                    "CollectionText",
                    "",
                    _t("Checkout.NoDeliveryForOrder", "Your order does not contain items that can be posted")
                )
            )->setName("CollectionFields");
        } else {
            $postage_field = null;
        }

        $form = Form::create(
            $this,
            "PostageForm",
            FieldList::create(
                $postage_field
            ),
            FieldList::create(
                FormAction::create(
                    'doSetPostage',
                    _t('Checkout.PaymentDetails', 'Enter Payment Details')
                )->addExtraClass('checkout-action-next btn btn-success')
            ),
            $validator
        );

        $this->extend("updatePostageForm", $form);

        return $form;
    }

    public function doSetPostage($data)
    {
        Session::set("Checkout.PostageID", $data["PostageID"]);

        $controller = Injector::inst()->get("Payment_Controller");

        return $this->redirect($controller->Link());
    }
}
