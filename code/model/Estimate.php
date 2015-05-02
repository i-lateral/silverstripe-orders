<?php

class Estimate extends Order {
    
    private static $summary_fields = array(
        "ID"        => "#",
        "Status"    => "Status",
        "Total"     => "Total",
        "Created"   => "Created"
    );
    
    public function getCMSFields() {
        
        $existing_customer = $this->config()->existing_customer_class;
        
        $fields = new FieldList(
            $tab_root = new TabSet(
                "Root",
                
                // Main Tab Fields
                $tab_main = new Tab(
                    'Main',
                    
                    // Sidebar
                    OrderSidebar::create(
                        ReadonlyField::create("QuoteNumber", "#")
                            ->setValue($this->ID),
                        ReadonlyField::create("SubTotal")
                            ->setValue($this->SubTotal),
                        ReadonlyField::create("Postage")
                            ->setValue($this->Postage),
                        ReadonlyField::create("Tax")
                            ->setValue($this->TaxTotal),
                        ReadonlyField::create("Total")
                            ->setValue($this->Total)
                    )->setTitle("Details"),
                    
                    // Items field
                    new OrderItemGridField(
                        "Items",
                        "",
                        $this->Items(),
                        $config = GridFieldConfig::create()
                            ->addComponents(
                                new GridFieldButtonRow('before'),
                                new GridFieldTitleHeader(),
                                new GridFieldEditableColumns(),
                                new GridFieldDeleteAction(),
                                new GridFieldAddOrderItem()
                            )
                    )
                ),
                
                // Main Tab Fields
                $tab_customer = new Tab(
                    'Customer',
                    
                    // Sidebar
                    CustomerSidebar::create(
                        // Items field
                        new GridField(
                            "ExistingCustomers",
                            "",
                            $existing_customer::get(),
                            $config = GridFieldConfig_Base::create()
                                ->addComponents(
                                    $map_extension = new GridFieldMapExistingAction()
                                )
                        )
                    )->setTitle("Use Existing Customer"),
                    
                    TextField::create("Company"),
                    TextField::create("FirstName"),
                    TextField::create("Surname"),
                    TextField::create("Address1"),
                    TextField::create("Address2"),
                    TextField::create("City"),
                    TextField::create("PostCode"),
                    TextField::create("Country"),
                    TextField::create("Email"),
                    TextField::create("PhoneNumber")
                )
            )
        );
        
        // Set the record ID
        $map_extension->setMapFields(array(
            "FirstName",
            "Surname",
            "Email"
        ));
        
        $tab_main->addExtraClass("order-admin-items");
        $tab_customer->addExtraClass("order-admin-customer");
        
        return $fields;
    }
    
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        
        $this->Status = $this->config()->default_status;
    } 
    
}
