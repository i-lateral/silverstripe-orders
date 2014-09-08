<?php

class CheckoutMember extends DataExtension {
    private static $db = array(
        "PhoneNumber"   => "Varchar",
        "Company"       => "Varchar(99)"
    );

    private static $has_many = array(
        "Addresses"     => "MemberAddress"
    );

    public function updateCMSFields(FieldList $fields) {
        $fields->remove("PhoneNumber");

        $fields->addFieldToTab(
            "Root.Main",
            TextField::create("PhoneNumber"),
            "Password"
        );

        $fields->addFieldToTab(
            "Root.Main",
            TextField::create("Company"),
            "FirstName"
        );

        return $fields;
    }

    /**
     * Get a discount from the groups this member is in
     *
     * @return Discount
     */
    public function getDiscount() {
        $discounts = ArrayList::create();

        foreach($this->owner->Groups() as $group) {
            foreach($group->Discounts() as $discount) {
                $discounts->add($discount);
            }
        }

        $discounts->sort("Amount", "DESC");

        return $discounts->first();
    }
}
