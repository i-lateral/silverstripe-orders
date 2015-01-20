<?php
/**
 * Order objects track all the details of an order and if they were completed or
 * not.
 *
 * Makes use of permissions provider to lock out users who have not got the
 * relevent COMMERCE permissions for:
 *   VIEW
 *   EDIT
 *   DELETE
 * 
 * You can define an order prefix by using the "order_prefix" config
 * variable
 *
 * Any user can create an order (this allows us to support "guest" users).
 *
 * @author ilateral (http://www.ilateral.co.uk)
 */
class Order extends DataObject implements PermissionProvider {
    
    /**
     * Add a string to the start of an order number (can be useful for
     * exporting orders).
     * 
     * @var string
     * @config
     */
    private static $order_prefix = "";
    
    /**
     * List of possible statuses this order can have. Rather than using
     * an enum, we load this as a config variable that can be changed
     * more freely.
     * 
     * @var array
     * @config
     */
    private static $statuses = array(
        "incomplete" => "Incomplete",
        "failed" => "Failed",
        "cancelled" => "Cancelled",
        "pending" => "Pending",
        "paid" => "Paid",
        "processing" => "Processing",
        "dispatched" => "Dispatched",
        "refunded" => "Refunded"
    );
    
    /**
     * List of statuses that allow editing of an order. We can use this
     * to fix certain orders in the CMS 
     * 
     * @var array
     * @config
     */
    private static $editable_statuses = array(
        "incomplete",
        "pending",
        "paid",
        "failed",
        "cancelled"
    );
    
    /**
     * Set the default status for a new order, if this is set to null or
     * blank, it will not be used.
     * 
     * @var string
     * @config
     */
    private static $default_status = "incomplete";

    private static $db = array(
        'Status'            => "Varchar",
        'OrderNumber'       => 'Varchar',
        
        // Billing Details
        'Company'           => 'Varchar',
        'FirstName'         => 'Varchar',
        'Surname'           => 'Varchar',
        'Address1'          => 'Varchar',
        'Address2'          => 'Varchar',
        'City'              => 'Varchar',
        'PostCode'          => 'Varchar',
        'Country'           => 'Varchar',
        'Email'             => 'Varchar',
        'PhoneNumber'       => 'Varchar',
        
        // Delivery Details
        'DeliveryFirstnames'=> 'Varchar',
        'DeliverySurname'   => 'Varchar',
        'DeliveryAddress1'  => 'Varchar',
        'DeliveryAddress2'  => 'Varchar',
        'DeliveryCity'      => 'Varchar',
        'DeliveryPostCode'  => 'Varchar',
        'DeliveryCountry'   => 'Varchar',
        
        // Discount provided
        "DiscountAmount"    => "Currency",
        
        // Postage
        'PostageType'       => 'Varchar',
        'PostageCost'       => 'Currency',
        'PostageTax'        => 'Currency',
        
        // Payment Gateway Info
        'GatewayData'       => 'Text'
    );

    private static $has_one = array(
        "Customer"          => "Member"
    );

    private static $has_many = array(
        'Items'             => 'OrderItem'
    );

    // Cast method calls nicely
    private static $casting = array(
        'BillingAddress'    => 'Text',
        'DeliveryAddress'   => 'Text',
        'SubTotal'          => 'Currency',
        'Postage'           => 'Currency',
        'TaxTotal'          => 'Currency',
        'Total'             => 'Currency',
        'ItemSummary'       => 'Text',
        'ItemSummaryHTML'   => 'HTMLText',
        'TranslatedStatus'  => 'Varchar'
    );

    private static $defaults = array(
        'EmailDispatchSent' => 0,
        'DiscountAmount'    => 0
    );

    private static $summary_fields = array(
        "OrderNumber"   => "#",
        "FirstName"     => "First Name(s)",
        "Surname"       => "Surname",
        "Email"         => "Email",
        "Status"        => "Status",
        "Total"         => "Total",
        "Created"       => "Created"
    );

    private static $extensions = array(
        "Versioned('History')"
    );

    private static $default_sort = "Created DESC";

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $this->extend("updateCMSFields", $fields);

        return $fields;
    }

    public function getBillingAddress() {
        $address = ($this->Address1) ? $this->Address1 . ",\n" : '';
        $address .= ($this->Address2) ? $this->Address2 . ",\n" : '';
        $address .= ($this->City) ? $this->City . ",\n" : '';
        $address .= ($this->PostCode) ? $this->PostCode . ",\n" : '';
        $address .= ($this->Country) ? $this->Country : '';

        return $address;
    }

    public function getDeliveryAddress() {
        $address = ($this->DeliveryAddress1) ? $this->DeliveryAddress1 . ",\n" : '';
        $address .= ($this->DeliveryAddress2) ? $this->DeliveryAddress2 . ",\n" : '';
        $address .= ($this->DeliveryCity) ? $this->DeliveryCity . ",\n" : '';
        $address .= ($this->DeliveryPostCode) ? $this->DeliveryPostCode . ",\n" : '';
        $address .= ($this->DeliveryCountry) ? $this->DeliveryCountry : '';

        return $address;
    }


    public function hasDiscount() {
         return (ceil($this->DiscountAmount)) ? true : false;
    }

    /**
     * Total values of items in this order (without any tax)
     *
     * @return Decimal
     */
    public function getSubTotal() {
        $return = new Currency();
        $total = 0;

        // Calculate total from items in the list
        foreach($this->Items() as $item) {
            $total += ($item->Price) ? $item->Price * $item->Quantity : 0;
        }
        
        $this->extend("updateSubTotal", $total);

        $return->setValue($total);
        return $return;
    }

    /**
     * Get the postage cost for this order
     *
     * @return Decimal
     */
    public function getPostage() {
        $return = new Currency();
        $total = $this->PostageCost;
        
        $return->setValue($total);
        
        $this->extend("updatePostageCost", $return);
        
        return $return;
    }
    
    /**
     * Total values of items in this order
     *
     * @return Decimal
     */
    public function getTaxTotal() {
        $return = new Currency();
        $total = 0;
        $items = $this->Items();
        
        // Calculate total from items in the list
        foreach($items as $item) {
            $tax = (($item->Price - ($this->DiscountAmount / $items->count())) / 100) * $item->TaxRate;
            
            $total += $tax * $item->Quantity;
        }
        
        if($this->PostageTax)
            $total += $this->PostageTax;

        $return->setValue($total);
        
        $this->extend("updateTaxTotal", $return);

        return $return;
    }

    /**
     * Total of order including postage
     *
     * @return Decimal
     */
    public function getTotal() {
        $return = new Currency();
        $total = (($this->getSubTotal()->RAW() + $this->getPostage()->RAW()) - $this->DiscountAmount) + $this->getTaxTotal()->RAW();
        
        $return->setValue($total);
        
        $this->extend("updateTotal", $return);
        
        return $return;
    }

    /**
     * Return a list string summarising each item in this order
     *
     * @return string
     */
    public function getItemSummary() {
        $return = '';

        foreach($this->Items() as $item) {
            $return .= "{$item->Quantity} x {$item->Title};\n";
        }

        return $return;
    }
    
    /**
     * Return a list string summarising each item in this order
     *
     * @return string
     */
    public function getItemSummaryHTML() {
        $html = new HTMLText("ItemSummary");
        
        $html->setValue(nl2br($this->ItemSummary));
        
        $this->extend("updateItemSummary", $html);

        return $html;
    }

    protected function generate_order_number() {
        $id = str_pad($this->ID, 8,  "0");

        $guidText =
            substr($id, 0, 4) . '-' .
            substr($id, 4, 4) . '-' .
            rand(1000,9999);

        // Work out if an order prefix string has been set
        $prefix = $this->config()->order_prefix;
        $guidText = ($prefix) ? $prefix . '-' . $guidText : $guidText;

        return $guidText;
    }

    /**
     * API Callback before this object is removed from to the DB
     *
     */
    public function onBeforeDelete() {
        // Delete all items attached to this order
        foreach($this->Items() as $item) {
            $item->delete();
        }

        parent::onBeforeDelete();
    }


    /**
     * API Callback before this object is written to the DB
     *
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();

        // Check if an order number has been generated, if not, add it and save again
        if(!$this->OrderNumber) {
            $this->OrderNumber = $this->generate_order_number();
        }
    }
    
    
    /**
     * API Callback after this object is written to the DB
     *
     */
    public function onAfterWrite() {
        parent::onAfterWrite();

        // Deal with sending the status emails
        if($this->isChanged('Status')) {
            $notifications = OrderNotification::get()
                ->filter("Status", $this->Status);
                
            // Loop through available notifications and send
            foreach($notifications as $notification) {
                $notification->sendNotification($this);
            }
        }
    }


    /**
     * API Callback after this object is removed from to the DB
     *
     */
    public function onAfterDelete() {
        parent::onAfterDelete();

        foreach ($this->Items() as $item) {
            $item->delete();
        }
    }

    public function providePermissions() {
        return array(
            "COMMERCE_VIEW_ORDERS" => array(
                'name' => 'View any order',
                'help' => 'Allow user to view any commerce order',
                'category' => 'Orders',
                'sort' => 99
            ),
            "COMMERCE_STATUS_ORDERS" => array(
                'name' => 'Change status of any order',
                'help' => 'Allow user to change the status of any order',
                'category' => 'Orders',
                'sort' => 98
            ),
            "COMMERCE_EDIT_ORDERS" => array(
                'name' => 'Edit any order',
                'help' => 'Allow user to edit any order',
                'category' => 'Orders',
                'sort' => 98
            ),
            "COMMERCE_DELETE_ORDERS" => array(
                'name' => 'Delete any order',
                'help' => 'Allow user to delete any order',
                'category' => 'Orders',
                'sort' => 97
            ),
            "COMMERCE_ORDER_HISTORY" => array(
                'name' => 'View order history',
                'help' => 'Allow user to see the history of an order',
                'category' => 'Orders',
                'sort' => 96
            )
        );
    }

    /**
     * Only order creators or users with VIEW admin rights can view
     *
     * @return Boolean
     */
    public function canView($member = null) {
        $extended = $this->extend('canView', $member);
        if($extended && $extended !== null) return $extended;

        if($member instanceof Member)
            $memberID = $member->ID;
        else if(is_numeric($member))
            $memberID = $member;
        else
            $memberID = Member::currentUserID();

        if($memberID && Permission::checkMember($memberID, array("ADMIN", "COMMERCE_VIEW_ORDERS")))
            return true;
        else if($memberID && $memberID == $this->CustomerID)
            return true;

        return false;
    }

    /**
     * Anyone can create orders, even guest users
     *
     * @return Boolean
     */
    public function canCreate($member = null) {
        $extended = $this->extend('canCreate', $member);
        if($extended && $extended !== null) return $extended;

        return true;
    }

    /**
     * Only users with EDIT admin rights can view an order
     *
     * @return Boolean
     */
    public function canEdit($member = null) {
        $extended = $this->extend('canEdit', $member);
        if($extended && $extended !== null) return $extended;

        if($member instanceof Member)
            $memberID = $member->ID;
        else if(is_numeric($member))
            $memberID = $member;
        else
            $memberID = Member::currentUserID();

        if(
            $memberID &&
            Permission::checkMember($memberID, array("ADMIN", "COMMERCE_EDIT_ORDERS")) &&
            in_array($this->Status, $this->config()->editable_statuses)
        )
            return true;

        return false;
    }
    
    /**
     * Only users with EDIT admin rights can view an order
     *
     * @return Boolean
     */
    public function canChangeStatus($member = null) {
        $extended = $this->extend('canEdit', $member);
        if($extended && $extended !== null) return $extended;

        if($member instanceof Member)
            $memberID = $member->ID;
        else if(is_numeric($member))
            $memberID = $member;
        else
            $memberID = Member::currentUserID();

        if($memberID && Permission::checkMember($memberID, array("ADMIN", "COMMERCE_STATUS_ORDERS")))
            return true;

        return false;
    }

    /**
     * No one should be able to delete an order once it has been created
     *
     * @return Boolean
     */
    public function canDelete($member = null) {
        $extended = $this->extend('canDelete', $member);
        if($extended && $extended !== null) return $extended;

        return false;
    }
}
