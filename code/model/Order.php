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
        "",
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
    
    
    /**
     * This is the class that can be auto mapped to an order/estimate.
     * This is used to generate the gridfield under the customer details
     * tab.
     * 
     */
    private static $existing_customer_class = "Member";

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
        "PaymentNo"         => "Varchar(255)",
        "GatewayData"       => "Text",
        
        // Misc Data
        "AccessKey"         => "Varchar(20)",
    );

    private static $has_one = array(
        "Customer"          => "Member"
    );

    private static $has_many = array(
        'Items'             => 'OrderItem'
    );

    // Cast method calls nicely
    private static $casting = array(
        'CountryFull'       => 'Varchar',
        'BillingAddress'    => 'Text',
        'DeliveryCountryFull'=> 'Varchar',
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
        $member = Member::currentUser();
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
                    
                    // Sidebar
                    OrderSidebar::create(
                        TextField::create('Status'),
                        ReadonlyField::create("QuoteNumber", "#")
                            ->setValue($this->ID),
                        ReadonlyField::create("Created"),
                        LiteralField::create("SubTotal", $subtotal_html),
                        LiteralField::create("Postage", $postage_html),
                        LiteralField::create("TaxTotal", $tax_html),
                        LiteralField::create("Total", $total_html)
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
                    ),
                    
                    // Postage
                    new HeaderField(
                        "PostageDetailsHeader",
                        _t("Orders.PostageDetails", "Postage Details")
                    ),
                    TextField::create("PostageType"),
                    TextField::create("PostageCost"),
                    TextField::create("PostageTax")
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
                    
                    HeaderField::create(
                        "BillingDetailsHeader",
                        _t("Orders.BillingDetails", "Customer Details")
                    ),
                    TextField::create("Company"),
                    TextField::create("FirstName"),
                    TextField::create("Surname"),
                    TextField::create("Address1"),
                    TextField::create("Address2"),
                    TextField::create("City"),
                    TextField::create("PostCode"),
                    CountryDropdownField::create("Country"),
                    TextField::create("Email"),
                    TextField::create("PhoneNumber"),
                    
                    HeaderField::create(
                        "DeliveryDetailsHeader",
                        _t("Orders.DeliveryDetails", "Delivery Details")
                    ),
                    TextField::create("DeliveryFirstnames"),
                    TextField::create("DeliverySurname"),
                    TextField::create("DeliveryAddress1"),
                    TextField::create("DeliveryAddress2"),
                    TextField::create("DeliveryCity"),
                    TextField::create("DeliveryPostCode"),
                    CountryDropdownField::create("DeliveryCountry")
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
    
    /**
     * Get the rendered name of the billing country, based on the local
     * 
     * @return String 
     */
    public function getCountryFull() {
        try {
            $source = Zend_Locale::getTranslationList(
                'territory',
                $this->Country,
                2
            );

            return (array_key_exists($this->Country, $source)) ? $source[$this->Country] : $this->Country;
        } catch(Exception $e) {
            return "";
        }
    }

    public function getDeliveryAddress() {
        $address = ($this->DeliveryAddress1) ? $this->DeliveryAddress1 . ",\n" : '';
        $address .= ($this->DeliveryAddress2) ? $this->DeliveryAddress2 . ",\n" : '';
        $address .= ($this->DeliveryCity) ? $this->DeliveryCity . ",\n" : '';
        $address .= ($this->DeliveryPostCode) ? $this->DeliveryPostCode . ",\n" : '';
        $address .= ($this->DeliveryCountry) ? $this->DeliveryCountry : '';

        return $address;
    }
    
    /**
     * Get the rendered name of the delivery country, based on the local
     * 
     * @return String 
     */
    public function getDeliveryCountryFull() {
        try {
            $source = Zend_Locale::getTranslationList(
                'territory',
                $this->DeliveryCountry,
                2
            );

            return (array_key_exists($this->DeliveryCountry, $source)) ? $source[$this->DeliveryCountry] : $this->DeliveryCountry;
        } catch(Exception $e) {
            return "";
        }
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
        $return->setName("SubTotal");
        $total = 0;

        // Calculate total from items in the list
        foreach($this->Items() as $item) {
            $total += ($item->Price) ? $item->Price * $item->Quantity : 0;
        }
        
        $return->setValue($total);
        
        $this->extend("updateSubTotal", $return);

        return $return;
    }

    /**
     * Get the postage cost for this order
     *
     * @return Decimal
     */
    public function getPostage() {
        $return = new Currency();
        $return->setName("Postage");
        $total = $this->PostageCost;
        
        $return->setValue($total);
        
        $this->extend("updatePostage", $return);
        
        return $return;
    }
    
    /**
     * Total values of items in this order
     *
     * @return Decimal
     */
    public function getTaxTotal() {
        $return = new Currency();
        $return->setName("TaxTotal");
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
        $return->setName("Total");
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
    
    protected function generate_random_string($length = 20) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
    
    protected function validAccessKey() {
        $existing = Order::get()
            ->filter("AccessKey", $this->AccessKey)
            ->first();
		
		return !($existing);
    }
    
    protected function validOrderNumber() {
        $existing = Order::get()
            ->filterAny("OrderNumber", $this->OrderNumber)
            ->first();
		
		return !($existing);
    }
    
    /**
	 * Create a duplicate of this order/estimate as well as duplicating
     * associated items
	 *
	 * @param $doWrite Perform a write() operation before returning the object.  If this is true, it will create the
	 *                 duplicate in the database.
	 * @return DataObject A duplicate of this node. The exact type will be the type of this node.
	 */
	public function duplicate($doWrite = true) {
        $clone = parent::duplicate($doWrite);
        
        // Set up items
        if($doWrite) {
            foreach($this->Items() as $item) {
                $item_class = $item->class;
                $clone_item = new $item_class($item->toMap(), false, $this->model);
                $clone_item->ID = 0;
                $clone_item->ParentID = $clone->ID;
                $clone_item->write();
            }
		}
        
		$clone->invokeWithExtensions('onAfterDuplicate', $this, $doWrite);
		
		return $clone;
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
     * API Callback after this object is written to the DB
     *
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        
        // Ensure that this object has a non-conflicting Access Key
        if(!$this->AccessKey) {
            $this->AccessKey = $this->generate_random_string();
            
            while(!$this->validAccessKey()) {
                $this->AccessKey = $this->generate_random_string();
            }
        }
    }
    
    /**
     * API Callback after this object is written to the DB
     *
     */
    public function onAfterWrite() {
        parent::onAfterWrite();
		
        
        // Check if an order number has been generated, if not, add it and save again
        if(!$this->OrderNumber) {
            $this->OrderNumber = $this->generate_order_number();
            
            while(!$this->validOrderNumber()) {
                $this->OrderNumber = $this->generate_order_number();
            }
            $this->write();
        }

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
        $extended = $this->extend('canEdit', $member);
        if($extended && $extended !== null) return $extended;

        if($member instanceof Member)
            $memberID = $member->ID;
        else if(is_numeric($member))
            $memberID = $member;
        else
            $memberID = Member::currentUserID();

        if($memberID && Permission::checkMember($memberID, array("ADMIN", "COMMERCE_DELETE_ORDERS")))
            return true;

        return false;
    }
}
