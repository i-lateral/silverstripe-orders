<?php

class Estimate extends Order
{

    /**
     * Standard DB columns
     *
     * @var array
     * @config
     */
    private static $db = array(
        "Cart"      => "Boolean"
    );

    /**
     * Fields to show in summary views
     *
     * @var array
     * @config
     */
    private static $summary_fields = array(
        "ID"        => "#",
        'Company'   => 'Company',
        'FirstName' => 'First Name',
        'Surname'   => 'Surname',
        "Total"     => "Total",
        "Created"   => "Created",
        "LastEdited"=> "Last Edited"
    );

    /**
     * Factory method to convert this estimate to an
     * order.
     *
     * At the moment this only changes the classname, but
     * using a factory allows us to add more complex
     * functionality in the future.
     *
     */
    public function convertToOrder()
    {
        $this->ClassName = "Order";
    }
    
    public function getCMSFields()
    {
        $existing_customer = $this->config()->existing_customer_class;
        
        $fields = new FieldList(
            $tab_root = new TabSet(
                "Root",
                
                // Main Tab Fields
                $tab_main = new Tab(
                    'Main',
                    
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
                                new GridFieldEditButton(),
                                new GridFieldDetailForm(),
                                new GridFieldDeleteAction(),
                                new GridFieldAddOrderItem()
                            )
                    ),
                    
                    // Postage
                    new HeaderField(
                        "PostageDetailsHeader",
                        _t("Orders.PostageDetails", "Postage Details")
                    ),
                    TextField::create("PostageType"),
                    TextField::create("PostageCost"),
                    TextField::create("PostageTax"),
                    
                    // Discount
                    new HeaderField(
                        "DiscountDetailsHeader",
                        _t("Orders.DiscountDetails", "Discount")
                    ),
                    TextField::create("Discount"),
                    TextField::create("DiscountAmount"),
                    
                    // Sidebar
                    OrderSidebar::create(
                        ReadonlyField::create("QuoteNumber", "#")
                            ->setValue($this->ID),
                        ReadonlyField::create("SubTotalValue",_t("Orders.SubTotal", "Sub Total"))
                            ->setValue($this->obj("SubTotal")->Nice()),
                        ReadonlyField::create("DiscountValue",_t("Orders.Discount", "Discount"))
                            ->setValue($this->dbObject("DiscountAmount")->Nice()),
                        ReadonlyField::create("PostageValue",_t("Orders.Postage", "Postage"))
                            ->setValue($this->obj("Postage")->Nice()),
                        ReadonlyField::create("TaxValue",_t("Orders.Tax", "Tax"))
                            ->setValue($this->obj("TaxTotal")->Nice()),
                        ReadonlyField::create("TotalValue",_t("Orders.Total", "Total"))
                            ->setValue($this->obj("Total")->Nice())
                    )->setTitle("Details")
                ),
                
                // Main Tab Fields
                $tab_customer = new Tab(
                    'Customer',
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
        
        if ($this->canEdit()) {
            // Sidebar
            $tab_customer->insertBefore(
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
                "Company"
            );
            
            if (is_array($this->config()->existing_customer_fields)) {
                $columns = $config->getComponentByType("GridFieldDataColumns");
                
                if ($columns) {
                    $columns
                        ->setDisplayFields($this
                            ->config()
                            ->existing_customer_fields
                        );
                }
            }
        
            // Set the record ID
            $map_extension->setMapFields($this->config()->existing_customer_map);
        }
        
		$tab_root->addextraClass('orders-root');
        $tab_main->addExtraClass("order-admin-items");
        $tab_customer->addExtraClass("order-admin-customer");

        $this->extend("updateCMSFields", $fields);
        
        return $fields;
    }
    
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        
        $this->Status = $this->config()->default_status;
    }
}
