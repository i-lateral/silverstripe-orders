<?php

class OrderNotification extends DataObject {
    
    /**
     * @config
     */
    private static $db = array(
        "Status" => "Varchar",
        "SendNotificationTo" => "Enum('Customer,Vendor,Both','Customer')",
        "FromEmail" => "Varchar",
        "VendorEmail" => "Varchar"
    );
    
    /**
     * @config
     */
    private static $has_one = array(
        "Parent" => "SiteConfig"
    );
    
    /**
     * @config
     */
    private static $summary_fields = array(
        "Status",
        "SendNotificationTo",
        "FromEmail",
        "VendorEmail"
    );
    
    /**
     * Deal with sending a notification. This is assumed to be an email
     * by default, but can be extended through "augmentSend" to allow
     * adding of additional notification types (such as SMS, XML, etc)
     * 
     */
    public function sendNotification($order) {
        // Deal with customer email
        if($order->Email && ($this->SendNotificationTo == 'Customer' || $this->SendNotificationTo == "Both")) {
            $subject = _t('Orders.Order', 'Order') . " {$order->OrderNumber} {$order->Status}";

            $email = new Email();
            $email->setSubject($subject);
            $email->setTo($order->Email);

            if($this->FromEmail) $email->setFrom($this->FromEmail);

            $email->setTemplate("OrderNotificationEmail_Customer");
            
            $email->populateTemplate(array(
                "Order" => $order,
                "SiteConfig" => $this->Parent()
            ));
            
            $email->send();
        }

        // Deal with vendor email
        if($this->VendorEmail && ($this->SendNotificationTo == 'Vendor' || $this->SendNotificationTo == "Both")) {
            $subject = _t('Orders.Order', 'Order') . " {$order->OrderNumber} {$order->Status}";
            
            $email = new Email();
            $email->setSubject($subject);
            $email->setTo($this->VendorEmail);

            if($this->FromEmail) $email->setFrom($this->FromEmail);

            $email->setTemplate("OrderNotificationEmail_Vendor");
            
            $email->populateTemplate(array(
                "Order" => $order
            ));
            
            $email->send();
        }
        
        $this->extend("augmentSend", $order);
    }
}
