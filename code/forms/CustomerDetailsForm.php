<?php
/**
 * Description of CheckoutForm
 *
 * @author morven
 */
class CustomerDetailsForm extends Form
{
    public function __construct($controller, $name = "CustomerDetailsForm")
    {
        $member = Member::currentUser();
        $cart = $this->getShoppingCart();     
        
        parent::__construct(
            $controller, 
            $name, 
            $fields = FieldList::create(),
            $actions = FieldList::create()
        );
        
        $data = Session::get("FormInfo.{$this->FormName()}.settings"); 

        // Set default form parameters
        $new_billing = isset($data['NewBilling']) ? $data['NewBilling'] : false;
        $same_shipping = isset($data['DuplicateDelivery']) ? $data['DuplicateDelivery'] : 1;
        $new_shipping = isset($data['NewShipping']) ? $data['NewShipping'] : false;

        $personal_fields = CompositeField::create(
            TextField::create('FirstName', _t('Checkout.FirstName', 'First Name(s)')),
            TextField::create('Surname', _t('Checkout.Surname', 'Surname')),
            TextField::create("Company", _t('Checkout.Company', "Company"))
                ->setRightTitle(_t("Checkout.Optional", "Optional")),
            EmailField::create('Email', _t('Checkout.Email', 'Email')),
            TextField::create('PhoneNumber', _t('Checkout.Phone', 'Phone Number'))
        )->setName("PersonalFields");

        $address_fields = CompositeField::create(
            TextField::create('Address1', _t('Checkout.Address1', 'Address Line 1')),
            TextField::create('Address2', _t('Checkout.Address2', 'Address Line 2'))
                ->setRightTitle(_t("Checkout.Optional", "Optional")),
            TextField::create('City', _t('Checkout.City', 'City')),
            TextField::create('State', _t('Checkout.StateCounty', 'State/County')),
            TextField::create('PostCode', _t('Checkout.PostCode', 'Post Code')),
            CountryDropdownField::create(
                'Country',
                _t('Checkout.Country', 'Country'),
                null,
                'GB'
            )
        )->setName("AddressFields");

        // Is user logged in and has saved addresses
        if ($member && $member->Addresses()->exists()) {
            // Generate saved address dropdown
            $saved_billing = CompositeField::create(
                DropdownField::create(
                    'BillingAddress',
                    _t('Checkout.BillingAddress','Billing Address'),
                    $member->Addresses()->map()
                ),
                FormAction::create(
                    'doAddNewBilling',
                    _t('Checkout.NewAddress', 'Use different address')
                )->addextraClass('btn btn-primary')
                ->setAttribute('formnovalidate',true)                
            )->setName('SavedBilling');
            
            // Add a "use saved address" button
            $address_fields->push(
                FormAction::create(
                    'doUseSavedBilling',
                    _t('Checkout.SavedAddress', 'Use saved address')
                )->addextraClass('btn btn-primary')
                ->setAttribute('formnovalidate',true)
            );
            
            // Generate a dropdown and "use different address" button
            // for shipping address
            $saved_shipping = CompositeField::create(
                DropdownField::create(
                    'ShippingAddress',
                    _t('Checkout.ShippingAddress','Shipping Address'),
                    $member->Addresses()->map()
                ),
                FormAction::create(
                    'doAddNewShipping',
                    _t('Checkout.NewAddress', 'Use different address')
                )->addextraClass('btn btn-primary')
                ->setAttribute('formnovalidate',true)
            )->setName('SavedShipping');
        } else {
            $saved_shipping = null;
            $saved_billing = null;
        }

        if (!$new_billing && $member && $member->Addresses()->exists()) {
            $fields->add($saved_billing);
        } else {
            $fields->add(
                $billing_fields = CompositeField::create(
                    $personal_fields,
                    $address_fields
                )->setName("BillingFields")
                ->setColumnCount(2)
            );

            // Add a save address for later checkbox if a user is logged in
            if ($member) {
                $billing_fields->push(
                    CompositeField::create(
                        CheckboxField::create(
                            "SaveBillingAddress",
                            _t('Checkout.SaveBillingAddress', 'Save this address for later')
                        )
                    )->setName("SaveBillingAddressHolder")
                );
            }
        }

        // If cart is deliverable, add shipping detail fields
        if (!$cart->isCollection() && $cart->isDeliverable()) {
            $fields->add(
                CheckboxField::create(
                    'DuplicateDelivery',
                    _t('Checkout.DeliverHere', 'Deliver to this address?')
                )->setValue($same_shipping)
            );

            $dpersonal_fields = CompositeField::create(
                TextField::create('DeliveryCompany', _t('Checkout.Company', 'Company'))
                    ->setRightTitle(_t("Checkout.Optional", "Optional")),
                TextField::create('DeliveryFirstName', _t('Checkout.FirstName', 'First Name(s)')),
                TextField::create('DeliverySurname', _t('Checkout.Surname', 'Surname'))
            )->setName("PersonalFields");

            $daddress_fields = CompositeField::create(
                TextField::create('DeliveryAddress1', _t('Checkout.Address1', 'Address Line 1')),
                TextField::create('DeliveryAddress2', _t('Checkout.Address2', 'Address Line 2'))
                    ->setRightTitle(_t("Checkout.Optional", "Optional")),
                TextField::create('DeliveryCity', _t('Checkout.City', 'City')),
                TextField::create('DeliveryState', _t('Checkout.StateCounty', 'State/County')),
                TextField::create('DeliveryPostCode', _t('Checkout.PostCode', 'Post Code')),
                CountryDropdownField::create(
                    'DeliveryCountry',
                    _t('Checkout.Country', 'Country')
                )
            )->setName("AddressFields");

            if ($member && $member->Addresses()->exists()) {
                $daddress_fields->push(
                    FormAction::create(
                        'doUseSavedShipping',
                        _t('Checkout.SavedAddress', 'Use saved address')
                    )->addextraClass('btn btn-primary')
                    ->setAttribute('formnovalidate',true)
                );
            }
            
            if (!$new_shipping && $member && $member->Addresses()->count() > 1) {
                $fields->add(
                    $saved_shipping            
                );
            } else {
                $fields->add(
                    CompositeField::create(
                        $dpersonal_fields,
                        $daddress_fields
                    )->setName("DeliveryFields")
                    ->setColumnCount(2)
                );
            }
            
            // Add a save address for later checkbox if a user is logged in
            if ($member) {
                $daddress_fields->push(
                    CheckboxField::create(
                        "SaveShippingAddress",
                        _t('Checkout.SaveShippingAddress', 'Save this address for later')
                    )
                );
            }
        }

        // If we have turned off login, or member logged in
        if ((Checkout::config()->login_form) && !$member) {
            if (Config::inst()->get('Checkout', 'guest_checkout') == true) {
                $register_title = _t('Checkout.CreateAccountOptional', 'Create Account (Optional)');
            } else {
                $register_title = _t('Checkout.CreateAccount', 'Create Account');                
            }
            $fields->add(
                CompositeField::create(
                    HeaderField::create(
                        'CreateAccount',
                        $register_title,
                        3
                    ),
                    $pw_field = ConfirmedPasswordField::create("Password")->setAttribute('formnovalidate',true)
                )->setName("PasswordFields")
            );            
        }

        if (is_array($data)) {
            $this->loadDataFrom($data);
        }

        if ($member && $member->Addresses()->exists()) {
            $this->loadDataFrom($member->Addresses()->First());
        }
        
        $actions->push(
            FormAction::create('doContinue', _t('Checkout.Continue', 'Continue'))
                ->addExtraClass('checkout-action-next')
        );

        $validator = new CheckoutValidator();

        if (Config::inst()->get('Checkout', 'guest_checkout') == false) {
            $validator->addRequiredField('Password');
        } else if ((Checkout::config()->login_form) && !$member) {
            $pw_field->setCanBeEmpty(true);
        }

        if (!$new_billing && $member && $member->Addresses()->exists()) {
            $validator->addRequiredField('BillingAddress');
        } else {
            $validator->appendRequiredFields(new RequiredFields(
                'FirstName',
                'Surname',
                'Address1',
                'City',
                'PostCode',
                'Country',
                'Email',
                'PhoneNumber'
            ));
        }
        if (!$cart->isCollection() && $cart->isDeliverable()) {
            if (!$new_shipping && $member && $member->Addresses()->exists()) {
                $validator->addRequiredField('ShippingAddress');
            } else {
                $validator->appendRequiredFields(new RequiredFields(
                    'DeliveryFirstName',
                    'DeliverySurname',
                    'DeliveryAddress1',
                    'DeliveryCity',
                    'DeliveryPostCode',
                    'DeliveryCountry'
                ));
            }
        }
        
        $this->setValidator($validator);
        
        $this->setTemplate($this->ClassName);
    }
    
    public function getShoppingCart()
    {
        return ShoppingCart::get();
    }
    
    public function getBackURL()
    {
        return ShoppingCart::get()->Link();
    }

    /** ## Form Processing ## **/
    public function doAddNewBilling($data) 
    {
        $data['NewBilling'] = true;
        return $this->doUpdateForm($data);        
    }

    public function doAddNewShipping($data) 
    {
        $data['NewShipping'] = true;
        return $this->doUpdateForm($data);        
    }

    public function doUseSavedBilling($data) 
    {
        $data['NewBilling'] = false;
        return $this->doUpdateForm($data);        
    }

    public function doUseSavedShipping($data) 
    {
        $data['NewShipping'] = false;
        return $this->doUpdateForm($data);
    }

    public function doUpdateForm($data) 
    {
        if (!isset($data['DuplicateDelivery'])) {
            $data['DuplicateDelivery'] = 0;
        }
        Session::set("FormInfo.{$this->FormName()}.settings", $data);
        return $this->controller->redirectBack();        
    }

    /**
     * Method used to save all data to an order and redirect to the order
     * summary page
     *
     * @param $data Form data
     *
     * @return Redirect
     */
    public function doContinue($data)
    {        
        $member = Member::currentUser();
        $cart = Injector::inst()->create('ShoppingCart');
        
        if (!isset($data['Address1']) && isset($data['BillingAddress'])) {
            $billing_address = MemberAddress::get()->byID($data['BillingAddress']);
            foreach ($billing_address->toMap() as $key => $value) {
                $data[$key] = $value;
            }
        }

        if (isset($data['DuplicateDelivery']) && $data['DuplicateDelivery'] == 1) {
            $data['DeliveryCompany'] = isset($data['Company']) ? $data['Company'] : '';
            $data['DeliveryFirstName'] = isset($data['FirstName']) ? $data['FirstName'] : '';
            $data['DeliverySurname'] = isset($data['Surname']) ? $data['Surname'] : '';
            $data['DeliveryAddress1'] = isset($data['Address1']) ? $data['Address1'] : '';
            $data['DeliveryAddress2'] = isset($data['Address2']) ? $data['Address2'] : '';
            $data['DeliveryCity'] = isset($data['City']) ? $data['City'] : '';
            $data['DeliveryState'] = isset($data['State']) ? $data['State'] : '';
            $data['DeliveryPostCode'] = isset($data['PostCode']) ? $data['PostCode'] : '';
            $data['DeliveryCountry'] = isset($data['Country']) ? $data['Country'] : '';
        } elseif (!isset($data['DeliveryAddress1']) && isset($data['ShippingAddress'])) {
            $shipping_address = MemberAddress::get()->ByID($data['ShippingAddress']);
            foreach ($shipping_address->toMap() as $key => $value) {
                $data['Delivery'.$key] = $value;
            }
        }

        Session::set("FormInfo.{$this->FormName()}.settings",$data);       
        
        if (!$member && (!Checkout::config()->guest_checkout || isset($data['Password']))) {
            $this->registerUser($data);
        }

        if ($member) {
            $estimate = $cart->getEstimate();
            $this->saveInto($estimate);

            foreach ($data as $key => $value) {
                $estimate->{$key} = $value;
            }
            
            if (isset($data['SaveBillingAddress']) && $data['SaveBillingAddress'] == 1) {
                $this->save_billing_address($data);
            }
            if (isset($data['SaveShippingAddress']) && $data['SaveShippingAddress'] == 1) {
                $this->save_shipping_address($data);
            }
        }
            
        Session::set('Checkout.CustomerDetails.data',$data);

        $url = $this
            ->controller
            ->Link("finish");

        return $this
            ->controller
            ->redirect($url);
    }

    public function registerUser($data) 
    {
        $url = $this
            ->controller
            ->Link("finish");

        Session::set('BackURL',$url);

        $reg_con = Injector::inst()->create('Users_Register_Controller');
        $reg_con->doRegister($data,$this);
    }

    /**
     * If the flag has been set from the provided array, create a new
     * address and assign to the current user.
     *
     * @param $data Form data submitted
     */
    private function save_billing_address($data)
    {
        $member = Member::currentUser();
        
        // If the user ticked "save address" then add to their account
        if ($member && array_key_exists('SaveBillingAddress', $data) && $data['SaveBillingAddress'] == 1) {
            // First save the details to the users account if they aren't set
            // We don't save email, as this is used for login
            $member->FirstName = ($member->FirstName) ? $member->FirstName : $data['FirstName'];
            $member->Surname = ($member->Surname) ? $member->Surname : $data['Surname'];
            $member->Company = ($member->Company) ? $member->Company : $data['Company'];
            $member->PhoneNumber = ($member->PhoneNumber) ? $member->PhoneNumber : $data['PhoneNumber'];
            
            $address = MemberAddress::create();
            $address->Company = $data['Company'];
            $address->FirstName = $data['FirstName'];
            $address->Surname = $data['Surname'];
            $address->Address1 = $data['Address1'];
            $address->Address2 = $data['Address2'];
            $address->City = $data['City'];
            $address->State = $data['Stste'];
            $address->PostCode = $data['PostCode'];
            $address->Country = $data['Country'];
            $address->OwnerID = $member->ID;
            $address->write();

            $member->Addresses()->add($address);
            $member->write();       
        }
    }

    private function save_shipping_address($data)
    {
        $member = Member::currentUser();
        
        // If the user ticked "save address" then add to their account
        if ($member && array_key_exists('SaveShippingAddress', $data) && $data['SaveShippingAddress'] == 1) {
            // First save the details to the users account if they aren't set
            // We don't save email, as this is used for login
            $member->FirstName = ($member->FirstName) ? $member->FirstName : $data['FirstName'];
            $member->Surname = ($member->Surname) ? $member->Surname : $data['Surname'];
            $member->Company = ($member->Company) ? $member->Company : $data['Company'];
            $member->PhoneNumber = ($member->PhoneNumber) ? $member->PhoneNumber : $data['PhoneNumber'];
            
            $address = MemberAddress::create();
            $address->Company = $data['DeliveryCompany'];
            $address->FirstName = $data['DeliveryFirstName'];
            $address->Surname = $data['DeliverySurname'];
            $address->Address1 = $data['DeliveryAddress1'];
            $address->Address2 = $data['DeliveryAddress2'];
            $address->City = $data['DeliveryCity'];
            $address->PostCode = $data['DeliveryPostCode'];
            $address->State = $data['DeliveryState'];
            $address->Country = $data['DeliveryCountry'];
            $address->OwnerID = $member->ID;
            $address->write();

            $member->Addresses()->add($address);
            $member->write();            
        }
    }
}
