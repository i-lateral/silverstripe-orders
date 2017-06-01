<?php
/**
 * Extension for Users Account Controller that adds address management
 * interfaces
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class CheckoutUserAccountControllerExtension extends Extension
{

    private static $allowed_actions = array(
        "addresses",
        "addaddress",
        "editaddress",
        "removeaddress",
        "AddressForm"
    );

    /**
     * Display all addresses associated with the current user
     */
    public function addresses()
    {
        return $this
            ->owner
            ->customise(array(
                "ClassName" => "AccountPage",
                "Title"     => _t("Checkout.YourAddresses", "Your Addresses")
            ))->renderWith(array(
                "UserAccount_addresses",
                "Users",
                "Page"
            ));
    }

    /**
     * Display all addresses associated with the current user
     */
    public function addaddress()
    {
        $form = $this->AddressForm();
        $form
            ->Fields()
            ->dataFieldByName("OwnerID")
            ->setValue(Member::currentuserID());

        return $this
            ->owner
            ->customise(array(
                "ClassName" => "AccountPage",
                "Title"     => _t("Checkout.AddAddress", "Add Address"),
                "Form"  => $form
            ))->renderWith(array(
                "UserAccount_addaddress",
                "Users",
                "Page"
            ));
    }

    /**
     * Display all addresses associated with the current user
     */
    public function editaddress()
    {
        $member = Member::currentUser();
        $id = $this->owner->request->param("ID");
        $address = MemberAddress::get()->byID($id);

        if ($address && $address->canEdit($member)) {
            $form = $this->AddressForm();
            $form->loadDataFrom($address);
            $form
                ->Actions()
                ->dataFieldByName("action_doSaveAddress")
                ->setTitle(_t("Checkout.Save", "Save"));

            return $this
                ->owner
                ->customise(array(
                    "ClassName" => "AccountPage",
                    "Title"     => _t("Checkout.EditAddress", "Edit Address"),
                    "Form" => $form
                ))->renderWith(array(
                    "UserAccount_editaddress",
                    "Users",
                    "Page"
                ));
        } else {
            return $this->owner->httpError(404);
        }
    }

    /**
     * Remove an addresses by the given ID (if allowed)
     */
    public function removeaddress()
    {
        $member = Member::currentUser();
        $id = $this->owner->request->param("ID");
        $address = MemberAddress::get()->byID($id);

        if ($address && $address->canDelete($member)) {
            $address->delete();
            $this->owner->setSessionMessage(
                "success",
                _t("Checkout.AddressRemoved", "Address Removed")
            );

            return $this->owner->redirectback();
        } else {
            return $this->owner->httpError(404);
        }
    }

    /**
     * Form used for adding or editing addresses
     */
    public function AddressForm()
    {
        $personal_fields = CompositeField::create(
            HeaderField::create('PersonalHeader', _t('Checkout.PersonalDetails', 'Personal Details'), 2),
            TextField::create('FirstName', _t('Checkout.FirstName', 'First Name(s)')),
            TextField::create('Surname', _t('Checkout.Surname', 'Surname')),
            CheckboxField::create('Default', _t('Checkout.DefaultAddress', 'Default Address?'))
                ->setRightTitle(_t('Checkout.Optional', 'Optional'))
        )->setName("PersonalFields")
        ->addExtraClass('unit')
        ->addExtraClass('size1of2')
        ->addExtraClass('unit-50');

        $address_fields = CompositeField::create(
            HeaderField::create('AddressHeader', _t('Checkout.Address', 'Address'), 2),
            TextField::create('Address1', _t('Checkout.Address1', 'Address Line 1')),
            TextField::create('Address2', _t('Checkout.Address2', 'Address Line 2'))
                ->setRightTitle(_t('Checkout.Optional', 'Optional')),
            TextField::create('City', _t('Checkout.City', 'City')),
            TextField::create('State', _t('Checkout.StateCounty', 'State/County')),
            TextField::create('PostCode', _t('Checkout.PostCode', 'Post Code')),
            CountryDropdownField::create(
                'Country',
                _t('Checkout.Country','Country')
            )->setAttribute("class",'countrydropdown dropdown')
        )->setName("AddressFields")
        ->addExtraClass('unit')
        ->addExtraClass('size1of2')
        ->addExtraClass('unit-50');

        $fields= FieldList::create(
            HiddenField::create("ID"),
            HiddenField::create("OwnerID"),
            CompositeField::create(
                $personal_fields,
                $address_fields
            )->setName("DeliveryFields")
            ->addExtraClass('line')
            ->addExtraClass('units-row')
        );

        $actions = FieldList::create(
            LiteralField::create(
                'BackButton',
                '<a href="' . $this->owner->Link('addresses') . '" class="btn btn-red">' . _t('Checkout.Cancel', 'Cancel') . '</a>'
            ),

            FormAction::create('doSaveAddress', _t('Checkout.Add', 'Add'))
                ->addExtraClass('btn')
                ->addExtraClass('btn-green')
        );

        $validator = RequiredFields::create(array(
            'FirstName',
            'Surname',
            'Address1',
            'City',
            'State',
            'PostCode',
            'Country',
        ));

        $form = Form::create($this->owner, "AddressForm", $fields, $actions, $validator);

        return $form;
    }


    /**
     * Method responsible for saving (or adding) a member's address.
     * If the ID field is set, the method assums we are saving
     * an address.
     *
     * If the ID field is not set, we assume a new address is being
     * created.
     *
     */
    public function doSaveAddress($data, $form)
    {
        if (!$data["ID"]) {
            $address = MemberAddress::create();
        } else {
            $address = MemberAddress::get()->byID($data["ID"]);
        }

        if ($address) {
            $form->saveInto($address);
            $address->write();

            $this->owner->setSessionMessage(
                "success",
                _t("Checkout.AddressSaved", "Address Saved")
            );
        } else {
            $this->owner->setSessionMessage(
                "error",
                _t("Checkout.Error", "There was an error")
            );
        }

        return $this->owner->redirect($this->owner->Link("addresses"));
    }

    /**
     * Add links to account menu
     *
     */
    public function updateAccountMenu($menu)
    {
        $curr_action = $this->owner->request->param("Action");
        
        $menu->add(new ArrayData(array(
            "ID"    => 11,
            "Title" => _t('Checkout.Addresses', 'Addresses'),
            "Link"  => $this->owner->Link("addresses"),
            "LinkingMode" => ($curr_action == "addresses") ? "current" : "link"
        )));
    }
    
    /**
     * Add fields used by this module to the profile editing form
     *
     */
    public function updateEditAccountForm($form)
    {
        // Add company name field
        $company_field = TextField::create(
            "Company",
            _t('CheckoutUsers.Company', "Company")
        );
        $company_field->setRightTitle(_t("Checkout.Optional", "Optional"));
        $form->Fields()->insertBefore($company_field, "FirstName");

        // Add contact phone number field
        $phone_field = TextField::create(
            "PhoneNumber",
            _t("CheckoutUsers.PhoneNumber", "Phone Number")
        );
        $phone_field->setRightTitle(_t("Checkout.Optional", "Optional"));
        $form->Fields()->add($phone_field);
    }
}
