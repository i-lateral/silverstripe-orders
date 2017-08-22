<?php

class OrderNotification extends DataObject
{
    
    /**
     * @config
     */
    private static $db = array(
        "Status" => "Varchar",
        "SendNotificationTo" => "Enum('Customer,Vendor,Both','Customer')",
        "CustomSubject" => "Varchar(255)",
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
        "VendorEmail",
        "CustomSubject"
    );
    
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        
        $status_field = DropdownField::create(
            "Status",
            $this->fieldLabel("Status"),
            Order::config()->statuses
        );
        
        $fields->replaceField("Status", $status_field);
        
        $vendor = $fields->dataFieldByName("VendorEmail");
        
        if ($vendor) {
            $vendor->setDescription(_t(
                "Orders.VendorEmailDescription",
                "Only needed when notification sent to vendor (or both)"
            ));
        }
        
        $subject = $fields->dataFieldByName("CustomSubject");
        
        if ($subject) {
            $subject->setDescription(_t(
                "Orders.CustomSubjectDescription",
                "Overwrite the default subject created in the notification email"
            ));
        }
        
        return $fields;
    }
    
    /**
     * Deal with sending a notification. This is assumed to be an email
     * by default, but can be extended through "augmentSend" to allow
     * adding of additional notification types (such as SMS, XML, etc)
     * 
     */
    public function sendNotification($order)
    {
        // Deal with customer email
        if ($order->Email && ($this->SendNotificationTo == 'Customer' || $this->SendNotificationTo == "Both")) {
            if ($this->CustomSubject) {
                $subject = $this->CustomSubject;
            } else {
                $subject = _t('Orders.Order', 'Order') . " {$order->OrderNumber} {$order->Status}";
            }

            $email = new Email();
            $email->setSubject($subject);
            $email->setTo($order->Email);

            if ($this->FromEmail) {
                $email->setFrom($this->FromEmail);
            }

            $email->setTemplate("OrderNotificationEmail_Customer");
            
            $email->populateTemplate(array(
                "Order" => $order,
                "SiteConfig" => $this->Parent(),
                "Notification" => $this
            ));
            
            $this->extend("augmentEmailCustomer", $email, $order);
            
            $email->send();
        }

        // Deal with vendor email
        if ($this->VendorEmail && ($this->SendNotificationTo == 'Vendor' || $this->SendNotificationTo == "Both")) {
            if ($this->CustomSubject) {
                $subject = $this->CustomSubject;
            } else {
                $subject = _t('Orders.Order', 'Order') . " {$order->OrderNumber} {$order->Status}";
            }
            
            $email = new Email();
            $email->setSubject($subject);
            $email->setTo($this->VendorEmail);

            if ($this->FromEmail) {
                $email->setFrom($this->FromEmail);
            }

            $email->setTemplate("OrderNotificationEmail_Vendor");
            
            $email->populateTemplate(array(
                "Order" => $order,
                "Notification" => $this
            ));
            
            $this->extend("augmentEmailVendor", $email, $order);
            
            $email->send();
        }
        
        $this->extend("augmentSend", $order);
    }
}
