<?php

class Estimate extends Order {
    
    private static $summary_fields = array(
        "OrderNumber"   => "#",
        "Status"        => "Status",
        "Total"         => "Total",
        "Created"       => "Created"
    );
    
    public function getCMSFields() {
        
        $fields = new FieldList(
            $tab_root = new TabSet(
                "Root",
                
                // Main Tab Fields
                $tab_main = new Tab(
                    'Main',
                    
                    // Sidebar
                    OrderSidebar::create(
                        ReadonlyField::create("OrderNumber", "#"),
                        
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
                )
            )
        );
        
        $tab_main->addExtraClass("order-admin-items");
        
        return $fields;
    }
    
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        
        $this->Status = $this->config()->default_status;
    } 
    
}
