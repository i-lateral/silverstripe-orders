<?php
/**
 * Description of CheckoutForm
 *
 * @author morven
 */
class BillingDetailsForm extends Form
{
    public function __construct($controller, $name = "BillingDetailsForm")
    {
        $cart = $this->getShoppingCart();
        
        $personal_fields = CompositeField::create(
            HeaderField::create(
                'PersonalHeader',
                _t('Checkout.PersonalDetails', 'Personal Details'),
                3
            ),
            TextField::create('FirstName', _t('Checkout.FirstName', 'First Name(s)')),
            TextField::create('Surname', _t('Checkout.Surname', 'Surname')),
            TextField::create("Company", _t('Checkout.Company', "Company"))
                ->setRightTitle(_t("Checkout.Optional", "Optional")),
            EmailField::create('Email', _t('Checkout.Email', 'Email')),
            TextField::create('PhoneNumber', _t('Checkout.Phone', 'Phone Number'))
        )->setName("PersonalFields");

        $address_fields = CompositeField::create(
            HeaderField::create(
                'AddressHeader',
                _t('Checkout.Address', 'Address'),
                3
            ),
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

        $fields= FieldList::create(
            // Add default fields
            $billing_fields = CompositeField::create(
                $personal_fields,
                $address_fields
            )->setName("BillingFields")
            ->setColumnCount(2)
        );

        // Add a save address for later checkbox if a user is logged in
        if (Member::currentUserID()) {
            $fields->add(
                CompositeField::create(
                    CheckboxField::create(
                        "SaveAddress",
                        _t('Checkout.SaveAddress', 'Save this address for later')
                    )
                )->setName("SaveAddressHolder")
            );
        }

        $actions = FieldList::create();

        if(!$cart->isDeliverable() || $cart->isCollection()) {
            $actions->add(
                FormAction::create('doSetDelivery', _t('Checkout.UseTheseDetails', 'Use these details'))
                    ->addExtraClass('checkout-action-next')
            );
        } else {
            $actions->add(
                FormAction::create('doSetDelivery', _t('Checkout.SetDeliveryAddress', 'Deliver to another address'))
                    ->addExtraClass('checkout-action-next')
            );
            
            $actions->add(
                FormAction::create('doContinue', _t('Checkout.DeliverThisAddress', 'Deliver to this address'))
                    ->addExtraClass('checkout-action-next')
            );
        }

        $validator = new RequiredFields(
            'FirstName',
            'Surname',
            'Address1',
            'City',
            'State',
            'PostCode',
            'Country',
            'Email',
            'PhoneNumber'
        );

        parent::__construct($controller, $name, $fields, $actions, $validator);
        
        $this->setTemplate($this->ClassName);
    }
    
    public function getShoppingCart() {
        return ShoppingCart::get();
    }
    
    public function getBackURL()
    {
        return Controller::join_links(
            BASE_URL,
            ShoppingCart::config()->url_segment
        );
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
