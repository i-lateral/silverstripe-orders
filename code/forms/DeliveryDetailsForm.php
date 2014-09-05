<?php
/**
 * Description of CheckoutForm
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class DeliveryDetailsForm extends Form {
    public function __construct($controller, $name = "DeliveryDetailsForm") {

        $personal_fields = CompositeField::create(
            HeaderField::create(
                'PersonalHeader',
                _t('Checkout.PersonalDetails','Personal Details'),
                3
            ),
            TextField::create('DeliveryFirstnames',_t('Checkout.FirstName','First Name(s)') . '*'),
            TextField::create('DeliverySurname',_t('Checkout.Surname','Surname') . '*')
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
            TextField::create('DeliveryAddress1',_t('Checkout.Address1','Address Line 1') . '*'),
            TextField::create('DeliveryAddress2',_t('Checkout.Address2','Address Line 2')),
            TextField::create('DeliveryCity',_t('Checkout.City','City') . '*'),
            TextField::create('DeliveryPostCode',_t('Checkout.PostCode','Post Code') . '*'),
            CountryDropdownField::create(
                    'DeliveryCountry',
                    _t('Checkout.Country','Country')
                )->setAttribute("class",'countrydropdown dropdown btn')
        )->setName("AddressFields")
        ->addExtraClass('unit')
        ->addExtraClass('size1of2')
        ->addExtraClass('unit-50');

        $fields= FieldList::create(
            CompositeField::create(
                $personal_fields,
                $address_fields
            )->setName("DeliveryFields")
            ->addExtraClass('line')
            ->addExtraClass('units-row')
        );

        // Add a save address for later checkbox if a user is logged in
        if(Member::currentUserID()) {
            $member = Member::currentUser();

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

        $back_url = $controller->Link();

        $actions = FieldList::create(
            LiteralField::create(
                'BackButton',
                '<a href="' . $back_url . '" class="btn btn-red checkout-action-back">' . _t('Checkout.Back','Back') . '</a>'
            ),

            FormAction::create('doContinue', _t('Checkout.PostageDetails','Select Postage'))
                ->addExtraClass('btn')
                ->addExtraClass('checkout-action-next')
                ->addExtraClass('btn-green')
        );

        $validator = new RequiredFields(
            'DeliveryFirstnames',
            'DeliverySurname',
            'DeliveryAddress1',
            'DeliveryCity',
            'DeliveryPostCode',
            'DeliveryCountry'
        );

        parent::__construct($controller, $name, $fields, $actions, $validator);
    }

    public function doContinue($data) {
        Session::set("Checkout.DeliveryDetailsForm.data",$data);

        // If the user ticked "save address" then add to their account
        if(array_key_exists('SaveAddress',$data) && $data['SaveAddress']) {
            $address = MemberAddress::create();
            $address->FirstName = $data['DeliveryFirstnames'];
            $address->Surname = $data['DeliverySurname'];
            $address->Address1 = $data['DeliveryAddress1'];
            $address->Address2 = $data['DeliveryAddress2'];
            $address->City = $data['DeliveryCity'];
            $address->PostCode = $data['DeliveryPostCode'];
            $address->Country = $data['DeliveryCountry'];
            $address->OwnerID = Member::currentUserID();
            $address->write();
        }

        $url = $this
            ->controller
            ->Link("finish");

        return $this
            ->controller
            ->redirect($url);
    }
}
