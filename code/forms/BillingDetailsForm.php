<?php
/**
 * Description of CheckoutForm
 *
 * @author morven
 */
class BillingDetailsForm extends Form {
    public function __construct($controller, $name = "BillingDetailsForm") {

        $personal_fields = CompositeField::create(
            HeaderField::create(
                'PersonalHeader',
                _t('Checkout.PersonalDetails','Personal Details'),
                3
            ),
            TextField::create('FirstName',_t('Checkout.FirstName','First Name(s)') . '*'),
            TextField::create('Surname',_t('Checkout.Surname','Surname') . '*'),
            TextField::create("Company",_t('Checkout.Company',"Company")),
            EmailField::create('Email',_t('Checkout.Email','Email') . '*'),
            TextField::create('PhoneNumber',_t('Checkout.Phone','Phone Number') . "*")
        )->setName("PersonalFields")
        ->addExtraClass('unit')
        ->addExtraClass('size1of2')
        ->addExtraClass('unit-50');

        $address_fields = CompositeField::create(
            HeaderField::create(
                'AddressHeader',
                _t('Checkout.Address','Address'),
                3
            ),
            TextField::create('Address1',_t('Checkout.Address1','Address Line 1') . '*'),
            TextField::create('Address2',_t('Checkout.Address2','Address Line 2')),
            TextField::create('City',_t('Checkout.City','City') . '*'),
            TextField::create('PostCode',_t('Checkout.PostCode','Post Code') . '*'),
            CountryDropdownField::create(
                'Country',
                _t('Checkout.Country','Country') . '*',
                null,
                'GB'
            )
        )->setName("AddressFields")
        ->addExtraClass('unit')
        ->addExtraClass('size1of2')
        ->addExtraClass('unit-50');

        $fields= FieldList::create(
            // Add default fields
            CompositeField::create(
                $personal_fields,
                $address_fields
            )->setName("BillingFields")
            ->addExtraClass('line')
            ->addExtraClass('units-row')
        );

        // Add a save address for later checkbox if a user is logged in
        if(Member::currentUserID()) {
            $fields->add(
                CompositeField::create(
                    CheckboxField::create(
                        "SaveAddress",
                        _t('Checkout.SaveAddress','Save this address for later')
                    )
                )->setName("SaveAddressHolder")
                ->addExtraClass('line')
                ->addExtraClass('units-row')
            );
        }

        $back_url = Controller::join_links(
            BASE_URL,
            ShoppingCart::config()->url_segment
        );

        $actions = FieldList::create(
            LiteralField::create(
                'BackButton',
                '<a href="' . $back_url . '" class="btn btn-red checkout-action-back">' . _t('Checkout.Back','Back') . '</a>'
            ),

            FormAction::create('doSetDelivery', _t('Checkout.SetDeliveryAddress','Deliver to another address'))
                ->addExtraClass('btn')
                ->addExtraClass('checkout-action-next'),

            FormAction::create('doContinue', _t('Checkout.DeliverThisAddress','Deliver to this address'))
                ->addExtraClass('btn')
                ->addExtraClass('checkout-action-next')
                ->addExtraClass('btn-green')
        );

        $validator = new RequiredFields(
            'FirstName',
            'Surname',
            'Address1',
            'City',
            'PostCode',
            'Country',
            'Email',
            'PhoneNumber'
        );

        parent::__construct($controller, $name, $fields, $actions, $validator);
    }

    /**
     * Method used to save all data to an order and redirect to the order
     * summary page
     *
     * @param $data Form data
     *
     * @return Redirect
     */
    public function doContinue($data) {
        // Set delivery details based billing details
        $delivery_data = array();
        $delivery_data['DeliveryFirstnames'] = $data['FirstName'];
        $delivery_data['DeliverySurname']    = $data['Surname'];
        $delivery_data['DeliveryAddress1']   = $data['Address1'];
        $delivery_data['DeliveryAddress2']   = $data['Address2'];
        $delivery_data['DeliveryCity']       = $data['City'];
        $delivery_data['DeliveryPostCode']   = $data['PostCode'];
        $delivery_data['DeliveryCountry']    = $data['Country'];

        // Save both sets of data to sessions
        Session::set("Checkout.BillingDetailsForm.data",$data);
        Session::set("Checkout.DeliveryDetailsForm.data",$delivery_data);

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
    public function doSetDelivery($data) {
        // Save billing data to sessions
        Session::set("Checkout.BillingDetailsForm.data",$data);

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
    private function save_address($data) {
        // If the user ticked "save address" then add to their account
        if(array_key_exists('SaveAddress',$data) && $data['SaveAddress']) {
            $address = MemberAddress::create();
            $address->FirstName = $data['FirstName'];
            $address->Surname = $data['Surname'];
            $address->Address1 = $data['Address1'];
            $address->Address2 = $data['Address2'];
            $address->City = $data['City'];
            $address->PostCode = $data['PostCode'];
            $address->Country = $data['Country'];
            $address->OwnerID = Member::currentUserID();
            $address->write();
        }
    }
}
