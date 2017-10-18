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

        // set default form parameters
        $new_billing = true;
        $diff_shipping = false;
        $new_shipping = false;
        
        $fields = FieldList::create();
        if ($member && $member->Addresses()->count() > 1) {
            $saved_billing = CompositeField::create(
                DropdownField::create(
                    'BillingAddress',
                    _t('Checkout.BillingAddress','Billing Address'),
                    $member->Addresses()->map()
                ),
                FormAction::create(
                    'doAddNewBilling',
                    _t('Checkout.NewAddress', 'Use different address')
                )->addextraClass('btn btn-default')
            )->setName('SavedBilling');
        } else {
            $saved_billing = null;
        }

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
            ),
            CheckboxField::create(
                'DuplicateDelivery',
                _t('Checkout.DeliverHere', 'Deliver to this address?')
            )
        )->setName("AddressFields");

        if ($member && $member->Addresses()->count() > 1) {
            $saved_shipping = CompositeField::create(
                DropdownField::create(
                    'ShippingAddress',
                    _t('Checkout.ShippingAddress','Shipping Address'),
                    $member->Addresses()->map()
                ),
                FormAction::create(
                    'doAddNewShipping',
                    _t('Checkout.NewAddress', 'Use different address')
                )->addextraClass('btn btn-default')
            );
        } else {
            $saved_shipping = null;
        }

        $fields->add(
            // Add default fields
            $saved_billing,            
            $billing_fields = CompositeField::create(
                $personal_fields,
                $address_fields
            )->setName("BillingFields")
            ->setColumnCount(2),
            $saved_shipping            
        );

        // Add a save address for later checkbox if a user is logged in
        if (Member::currentUserID()) {
            $fields->add(
                CompositeField::create(
                    CheckboxField::create(
                        "SaveBillingAddress",
                        _t('Checkout.SaveBillingAddress', 'Save this address for later')
                    )
                )->setName("SaveBillingAddressHolder")
            );
        }

        $dpersonal_fields = CompositeField::create(
            TextField::create('DeliveryCompany', _t('Checkout.Company', 'Company'))
                ->setRightTitle(_t("Checkout.Optional", "Optional")),
            TextField::create('DeliveryFirstnames', _t('Checkout.FirstName', 'First Name(s)')),
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

        $fields->add(
            CompositeField::create(
                $dpersonal_fields,
                $daddress_fields
            )->setName("DeliveryFields")
            ->setColumnCount(2)
        );

        // Add a save address for later checkbox if a user is logged in
        if (Member::currentUserID()) {
            $member = Member::currentUser();

            $fields->add(
                CompositeField::create(
                    CheckboxField::create(
                        "SaveShippingAddress",
                        _t('Checkout.SaveShippingAddress', 'Save this address for later')
                    )
                )->setName("SaveShippingAddressHolder")
            );
        }

        // If we have turned off login, or member logged in
        if ((Checkout::config()->login_form) && !Member::currentUserID()) {
            $fields->add(
                CompositeField::create(
                    HeaderField::create(
                        'CreateAccount',
                        _t('Checkout.CreateAccount', 'Create Account (Optional)'),
                        3
                    ),
                    ConfirmedPasswordField::create("Password")
                )->setName("PasswordFields")
            );            
        }

        $actions = FieldList::create();

        if(!$cart->isDeliverable() || $cart->isCollection()) {

        } else {

        }

        $actions = FieldList::create(
            FormAction::create('doContinue', _t('Checkout.Continue', 'Continue'))
                ->addExtraClass('checkout-action-next')
        );

        $validator = new RequiredFields(
            'FirstName',
            'Surname',
            'Address1',
            'City',
            'State',
            'PostCode',
            'Country',
            'Email',
            'PhoneNumber',
            'DeliveryFirstnames',
            'DeliverySurname',
            'DeliveryAddress1',
            'DeliveryCity',
            'DeliveryPostCode',
            'DeliveryCountry'
        );

        parent::__construct($controller, $name, $fields, $actions, $validator);
        
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
        // Set delivery details based billing details
        $delivery_data = array();
        $delivery_data['DeliveryCompany']     = $data['Company'];
        $delivery_data['DeliveryFirstnames'] = $data['FirstName'];
        $delivery_data['DeliverySurname']    = $data['Surname'];
        $delivery_data['DeliveryAddress1']   = $data['Address1'];
        $delivery_data['DeliveryAddress2']   = $data['Address2'];
        $delivery_data['DeliveryCity']       = $data['City'];
        $delivery_data['DeliveryPostCode']   = $data['PostCode'];
        $delivery_data['DeliveryCountry']    = $data['Country'];

        // Save both sets of data to sessions
        Session::set("Checkout.BillingDetailsForm.data", $data);
        Session::set("Checkout.DeliveryDetailsForm.data", $delivery_data);

        $this->save_address($data);

        $url = $this
            ->controller
            ->Link("finish");

        return $this
            ->controller
            ->redirect($url);
    }

    /**
     * Method used to save data (without delivery info) to an order and redirect
     * to the delivery details page
     *
     * @param $data Form data
     *
     * @return Redirect
     */
    public function doSetDelivery($data)
    {
        // Save billing data to sessions
        Session::set("Checkout.BillingDetailsForm.data", $data);

        $this->save_address($data);

        $url = $this
            ->controller
            ->Link("delivery");

        return $this
            ->controller
            ->redirect($url);
    }

    /**
     * If the flag has been set from the provided array, create a new
     * address and assign to the current user.
     *
     * @param $data Form data submitted
     */
    private function save_address($data)
    {
        $member = Member::currentUser();
        
        // If the user ticked "save address" then add to their account
        if ($member && array_key_exists('SaveAddress', $data) && $data['SaveAddress']) {
            // First save the details to the users account if they aren't set
            // We don't save email, as this is used for login
            $member->FirstName = ($member->FirstName) ? $member->FirstName : $data['FirstName'];
            $member->Surname = ($member->Surname) ? $member->Surname : $data['Surname'];
            $member->Company = ($member->Company) ? $member->Company : $data['Company'];
            $member->PhoneNumber = ($member->PhoneNumber) ? $member->PhoneNumber : $data['PhoneNumber'];
            $member->write();
            
            $address = MemberAddress::create();
            $address->Company = $data['Company'];
            $address->FirstName = $data['FirstName'];
            $address->Surname = $data['Surname'];
            $address->Address1 = $data['Address1'];
            $address->Address2 = $data['Address2'];
            $address->City = $data['City'];
            $address->PostCode = $data['PostCode'];
            $address->Country = $data['Country'];
            $address->OwnerID = $member->ID;
            $address->write();
        }
    }
}
