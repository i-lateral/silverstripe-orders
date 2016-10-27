<?php

class Estimate extends Order
{
    
    private static $summary_fields = array(
        "ID"        => "#",
        'Company'   => 'Company',
        'FirstName' => 'First Name',
        'Surname'   => 'Surname',
        "Total"     => "Total",
        "Created"   => "Created"
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
        
        // Manually inject HTML for totals as Silverstripe refuses to
        // render Currency.Nice any other way.
        $subtotal_html = '<div id="SubTotal" class="field readonly">';
        $subtotal_html .= '<label class="left" for="Form_ItemEditForm_SubTotal">';
        $subtotal_html .= _t("Orders.SubTotal", "Sub Total");
        $subtotal_html .= '</label>';
        $subtotal_html .= '<div class="middleColumn"><span id="Form_ItemEditForm_SubTotal" class="readonly">';
        $subtotal_html .= $this->SubTotal->Nice();
        $subtotal_html .= '</span></div></div>';
        
        $discount_html = '<div id="Discount" class="field readonly">';
        $discount_html .= '<label class="left" for="Form_ItemEditForm_Discount">';
        $discount_html .= _t("Orders.Discount", "Discount");
        $discount_html .= '</label>';
        $discount_html .= '<div class="middleColumn"><span id="Form_ItemEditForm_Discount" class="readonly">';
        $discount_html .= $this->dbObject("DiscountAmount")->Nice();
        $discount_html .= '</span></div></div>';

        $postage_html = '<div id="Postage" class="field readonly">';
        $postage_html .= '<label class="left" for="Form_ItemEditForm_Postage">';
        $postage_html .= _t("Orders.Postage", "Postage");
        $postage_html .= '</label>';
        $postage_html .= '<div class="middleColumn"><span id="Form_ItemEditForm_Postage" class="readonly">';
        $postage_html .= $this->Postage->Nice();
        $postage_html .= '</span></div></div>';
        
        $tax_html = '<div id="TaxTotal" class="field readonly">';
        $tax_html .= '<label class="left" for="Form_ItemEditForm_TaxTotal">';
        $tax_html .= _t("Orders.Tax", "Tax");
        $tax_html .= '</label>';
        $tax_html .= '<div class="middleColumn"><span id="Form_ItemEditForm_TaxTotal" class="readonly">';
        $tax_html .= $this->TaxTotal->Nice();
        $tax_html .= '</span></div></div>';
        
        $total_html = '<div id="Total" class="field readonly">';
        $total_html .= '<label class="left" for="Form_ItemEditForm_Total">';
        $total_html .= _t("Orders.Total", "Total");
        $total_html .= '</label>';
        $total_html .= '<div class="middleColumn"><span id="Form_ItemEditForm_Total" class="readonly">';
        $total_html .= $this->Total->Nice();
        $total_html .= '</span></div></div>';
        
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
                        LiteralField::create("SubTotal", $subtotal_html),
                        LiteralField::create("Discount", $discount_html),
                        LiteralField::create("Postage", $postage_html),
                        LiteralField::create("TaxTotal", $tax_html),
                        LiteralField::create("Total", $total_html)
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
        
        $tab_main->addExtraClass("order-admin-items");
        $tab_customer->addExtraClass("order-admin-customer");

        $this->extend("updateCMSFields", $fields);
        
        return $fields;
    }
    
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        
        $this->Status = $this->config()->default_status;
        
        $this->extend("onBeforeWrite");
    }
}
