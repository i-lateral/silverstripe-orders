<?php

class OrdersSiteConfigExtension extends DataExtension {
    
    private static $db = array(
        "OrdersHeader" => "HTMLText",
        "QuoteFooter" => "HTMLText",
        "InvoiceFooter" => "HTMLText",
    );
    
    private static $has_many = array(
        "OrderNotifications" => "OrderNotification"
    );
    
    public function updateCMSFields(FieldList $fields) {
        
        $fields->addFieldToTab(
            "Root.Orders",
            GridField::create(
                "OrderNotifications",
                "Order status notifications",
                $this->owner->OrderNotifications(),
                GridFieldConfig_RecordEditor::create()
            )
        );
        
        $fields->addFieldToTab(
            "Root.Orders",
            HTMLEditorField::create("OrdersHeader", _t("Orders.QuoteInvoiceHeader", "Quote and Invoice Header"))
        );
        
        $fields->addFieldToTab(
            "Root.Orders",
            HTMLEditorField::create("QuoteFooter")
        );
        
        $fields->addFieldToTab(
            "Root.Orders",
            HTMLEditorField::create("InvoiceFooter")
        );
    }
    
}
