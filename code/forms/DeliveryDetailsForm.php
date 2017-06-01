<?php
/**
 * Description of CheckoutForm
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class DeliveryDetailsForm extends Form
{
    public function __construct($controller, $name = "DeliveryDetailsForm")
    {
        $personal_fields = CompositeField::create(
            HeaderField::create(
                'PersonalHeader',
                _t('Checkout.PersonalDetails', 'Personal Details'),
                3
            ),
            TextField::create('DeliveryCompany', _t('Checkout.Company', 'Company'))
                ->setRightTitle(_t("Checkout.Optional", "Optional")),
            TextField::create('DeliveryFirstnames', _t('Checkout.FirstName', 'First Name(s)')),
            TextField::create('DeliverySurname', _t('Checkout.Surname', 'Surname'))
        )->setName("PersonalFields");

        $address_fields = CompositeField::create(
            HeaderField::create(
                'AddressHeader',
                _t('Checkout.Address', 'Address'),
                3
            ),
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

        $fields= FieldList::create(
            CompositeField::create(
                $personal_fields,
                $address_fields
            )->setName("DeliveryFields")
            ->setColumnCount(2)
        );

        // Add a save address for later checkbox if a user is logged in
        if (Member::currentUserID()) {
            $member = Member::currentUser();

            $fields->add(
                CompositeField::create(
                    CheckboxField::create(
                        "SaveAddress",
                        _t('Checkout.SaveAddress', 'Save this address for later')
                    )
                )->setName("SaveAddressHolder")
            );
        }

        $actions = FieldList::create(
            FormAction::create('doContinue', _t('Checkout.PostageDetails', 'Select Postage'))
                ->addExtraClass('checkout-action-next')
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
        
        $this->setTemplate($this->ClassName);
    }
    
    public function getBackURL()
    {
        return $this->controller->Link();
    }

    public function doContinue($data)
    {
        Session::set("Checkout.DeliveryDetailsForm.data", $data);

        // If the user ticked "save address" then add to their account
        if (array_key_exists('SaveAddress', $data) && $data['SaveAddress']) {
            $address = MemberAddress::create();
            $address->Company = $data['DeliveryCompany'];
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
