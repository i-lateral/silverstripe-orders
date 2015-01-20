<?php

/**
 * Provides view and edit forms for Order objects vai gridfield
 * 
 * @author ilateral (http://www.ilateral.co.uk)
 * @package orders
 */
class OrderGridFieldDetailForm extends GridFieldDetailForm { }

class OrderGridFieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest {
	private static $allowed_actions = array(
		'ItemEditForm'
	);

	public function updateCMSActions($actions) {
        
		return $actions;
	}

	public function ItemEditForm(){
		$form = parent::ItemEditForm();
        $fields = $form->Fields();
        $record = $this->record;
        
        // Change default status field
        
        if($record->canEdit()) {
            $status_field = DropdownField::create(
                'Status',
                null,
                $record->config()->statuses
            );
            
            $fields->replaceField("Status", $status_field);
            
            $item_field = $fields->dataFieldByName("Items");
            $item_config = $item_field->getConfig();
            
            $item_config
                ->removeComponentsByType("GridFieldAddExistingAutocompleter")
                ->removeComponentsByType("GridFieldDeleteAction")
                ->addComponent(new GridFieldDeleteAction());
        }
        
        
        /*
        // Remove default item admin
        $fields->removeByName('Items');
        $fields->removeByName('Status');
        $fields->removeByName('EmailDispatchSent');
        $fields->removeByName('PostageID');
        $fields->removeByName('PaymentID');
        $fields->removeByName('GatewayData');

        // Remove Billing Details
        $fields->removeByName('Address1');
        $fields->removeByName('Address2');
        $fields->removeByName('City');
        $fields->removeByName('PostCode');
        $fields->removeByName('Country');

        // Remove Delivery Details
        $fields->removeByName('DeliveryFirstnames');
        $fields->removeByName('DeliverySurname');
        $fields->removeByName('DeliveryAddress1');
        $fields->removeByName('DeliveryAddress2');
        $fields->removeByName('DeliveryCity');
        $fields->removeByName('DeliveryPostCode');
        $fields->removeByName('DeliveryCountry');

        // Remove default postage fields
        $fields->removeByName('PostageType');
        $fields->removeByName('PostageCost');
        $fields->removeByName('PostageTax');

        $fields->addFieldToTab(
            'Root.Main',
            ReadonlyField::create('OrderNumber', "#"),
            'Company'
        );
        
        // Set default status if we can
        if($record->config()->default_status && !$record->Status)
            $statusfield->setValue($record->config()->default_status);

        $fields->addFieldToTab(
            'Root.Main',
            ReadonlyField::create('Created')
        );

        $fields->addFieldToTab(
            'Root.Main',
            ReadonlyField::create('LastEdited', 'Last time order was saved')
        );

        // Structure billing details
        $billing_fields = ToggleCompositeField::create('BillingDetails', 'Billing Details',
            array(
                TextField::create('Address1', 'Address 1'),
                TextField::create('Address2', 'Address 2'),
                TextField::create('City', 'City'),
                TextField::create('PostCode', 'Post Code'),
                TextField::create('Country', 'Country')
            )
        )->setHeadingLevel(4);


        // Structure delivery details
        $delivery_fields = ToggleCompositeField::create('DeliveryDetails', 'Delivery Details',
            array(
                TextField::create('DeliveryFirstnames', 'First Name(s)'),
                TextField::create('DeliverySurname', 'Surname'),
                TextField::create('DeliveryAddress1', 'Address 1'),
                TextField::create('DeliveryAddress2', 'Address 2'),
                TextField::create('DeliveryCity', 'City'),
                TextField::create('DeliveryPostCode', 'Post Code'),
                TextField::create('DeliveryCountry', 'Country'),
            )
        )->setHeadingLevel(4);

        // Postage details
        // Structure billing details
        $postage_fields = ToggleCompositeField::create('Postage', 'Postage Details',
            array(
                ReadonlyField::create('PostageType'),
                ReadonlyField::create('PostageCost'),
                ReadonlyField::create('PostageTax')
            )
        )->setHeadingLevel(4);

        $fields->addFieldToTab('Root.Main', $billing_fields);
        $fields->addFieldToTab('Root.Main', $delivery_fields);
        $fields->addFieldToTab('Root.Main', $postage_fields);


        // Add order items and totals
        $fields->addFieldToTab(
            'Root.Info',
            GridField::create(
                'Items',
                "Order Items",
                $record->Items(),
                GridFieldConfig::create()->addComponents(
                    new GridFieldSortableHeader(),
                    new GridFieldDataColumns()
                )
            )
        );

        $fields->addFieldToTab(
            "Root.Info",
            ReadonlyField::create("SubTotal")
                ->setValue($record->getSubTotal()->Nice())
        );

        $fields->addFieldToTab(
            "Root.Info",
            ReadonlyField::create("DiscountAmount")
                ->setValue($record->DiscountAmount)
        );

        $fields->addFieldToTab(
            "Root.Info",
            ReadonlyField::create("Postage")
                ->setValue($record->getPostage()->Nice())
        );
        
        $fields->addFieldToTab(
            "Root.Info",
            ReadonlyField::create("Tax")
                ->setValue($record->getTaxTotal()->Nice())
        );

        $fields->addFieldToTab(
            "Root.Info",
            ReadonlyField::create("Total")
                ->setValue($record->getTotal()->Nice())
        );

        $member = Member::currentUser();

        if(Permission::check('ADMIN', 'any', $member)) {
            // Add non-editable payment ID
            $paymentid_field = TextField::create('PaymentID', "Payment gateway ID number")
                ->setReadonly(true)
                ->performReadonlyTransformation();


            $gateway_data = LiteralField::create(
                "FormattedGatewayData",
                "<strong>Data returned from the payment gateway:</strong><br/><br/>" .
                str_replace(",",",<br/>",$record->GatewayData)
            );


            $fields->addFieldToTab('Root.Gateway', $paymentid_field);
            $fields->addFieldToTab("Root.Gateway", $gateway_data);
        }

        if(Permission::check(array('COMMERCE_ORDER_HISTORY','ADMIN'), 'any', $member)) {
            // Setup basic history of this order
            $versions = $record->AllVersions();
            $first_version = $versions->First();
            $curr_version = ($first_version) ? $versions->First() : null;
            $message = "";

            foreach($versions as $version) {
                $i = $version->Version;
                $name = "History_{$i}";

                if($i > 1) {
                    $frm = Versioned::get_version($record->class, $record->ID, $i - 1);
                    $to = Versioned::get_version($record->class, $record->ID, $i);
                    $diff = new DataDifferencer($frm, $to);

                    if($version->Author())
                        $message = "<p>{$version->Author()->FirstName} ({$version->LastEdited})</p>";
                    else
                        $message = "<p>Unknown ({$version->LastEdited})</p>";

                    if($diff->ChangedFields()->exists()) {
                        $message .= "<ul>";

                        // Now loop through all changed fields and track as message
                        foreach($diff->ChangedFields() as $change) {
                            if($change->Name != "LastEdited")
                                $message .= "<li>{$change->Title}: {$change->Diff}</li>";
                        }

                        $message .= "</ul>";
                    }
                }

                $fields->addFieldToTab("Root.History", LiteralField::create(
                    $name,
                    "<div class=\"field\">{$message}</div>"
                ));
            }
        }
        */
		return $form;
	}
}
