<?php

class OrdersSiteConfigExtension extends DataExtension {
    
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
        
    }
    
}
