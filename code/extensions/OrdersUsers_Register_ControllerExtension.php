<?php

class OrdersUsers_Register_ControllerExtension extends Extension
{
    public function updateNewMember($member, $data) 
    {
        if (isset($data['Address1'])) {
            $address = new MemberAddress($data);
            $address->MemberID = $member->ID;
            $address->write();

            $member->Addresses()->add($address);

            if (!isset($data['DuplicateAddress']) || $data['DuplicateAddress'] != 1) {
                $new_data = [];

                $new_data['Company'] = $data['DeliveryCompany'];
                $new_data['FirstName'] = $data['DeliveryFirstnames'];
                $new_data['Surname'] = $data['DeliverySurname'];
                $new_data['Address1'] = $data['DeliveryAddress1'];
                $new_data['Address2'] = $data['DeliveryAddress2'];
                $new_data['City'] = $data['DeliveryCity'];
                $new_data['State'] = $data['DeliveryState'];
                $new_data['PostCode'] = $data['DeliveryPostCode'];
                $new_data['Country'] = $data['DeliveryCountry'];

                $address = new MemberAddress($new_data);
                $address->MemberID = $member->ID;
                $address->write();
            
                $member->Addresses()->add($address);
            }
        }
        $member->write();     
    }
}