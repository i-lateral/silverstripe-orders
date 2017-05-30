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
class Order extends DataObject implements PermissionProvider
{
    
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
        "collected" => "Collected",
        "refunded" => "Refunded"
    );
    
    
    /**
     * What statuses does an order need to be marked as "outstanding".
     * At the moment this is only used against an @Member.
     * 
     * @var array
     * @config
     */
    private static $outstanding_statuses = array(
        "paid",
        "processing"
    );
    
    
    /**
     * What statuses does an order need to be marked as "historic".
     * At the moment this is only used against an @Member.
     * 
     * @var array
     * @config
     */
    private static $historic_statuses = array(
        "dispatched",
        "canceled"
    );
    
    /**
     * Actions on an order are to determine what will happen on
     * completion (the defaults are post or collect).
     * 
     * @var array
     * @config
     */
    private static $actions = array(
        "post" => "Post",
        "collect" => "Collect"
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
     * The status which an order is considered "complete" (meaning
     * ready for processing, dispatch, etc).
     * 
     * @var string
     * @config
     */
    private static $completion_status = "paid";

    /**
     * The status which an order has not been completed (meaning
     * it is not ready for processing, dispatch, etc).
     * 
     * @var string
     * @config
     */
    private static $incomplete_status = "incomplete";
    
    /**
     * Set the default action on our order. If we were using this module
     * for a more POS type solution, we would probably change this to
     * collect.
     * 
     * @var string
     * @config
     */
    private static $default_action = "post";
    
    /**
     * This is the class that can be auto mapped to an order/estimate.
     * This is used to generate the gridfield under the customer details
     * tab.
     * 
     * @config
     */
    private static $existing_customer_class = "Member";
    
    /**
     * The list of fields that will show in the existing customer
     * gridfield.
     * 
     * If not set, will default to summary_fields
     * 
     * @config
     */
    private static $existing_customer_fields;
    
    /**
     * Select the fields that will be copied from the source object to
     * our order. We add these here so they can be easily altered 
     * through config.
     * 
     * @var array
     * @config
     */
    private static $existing_customer_map = array(
        "FirstName" => "FirstName",
        "Surname" => "Surname",
        "Email" => "Email",
        "PhoneNumber" => "PhoneNumber",
        "Address1" => "Address1",
        "Address2" => "Address2",
        "City" => "City",
        "PostCode" => "PostCode"
    );

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
        'DeliveryCompany'    => 'Varchar',
        'DeliveryFirstnames'=> 'Varchar',
        'DeliverySurname'   => 'Varchar',
        'DeliveryAddress1'  => 'Varchar',
        'DeliveryAddress2'  => 'Varchar',
        'DeliveryCity'      => 'Varchar',
        'DeliveryPostCode'  => 'Varchar',
        'DeliveryCountry'   => 'Varchar',
        
        // Discount Provided
        "Discount"          => "Varchar",
        "DiscountAmount"    => "Currency",
        
        // Completion Action
        "Action"            => "Varchar",
        
        // Postage
        "PostageType"       => "Varchar",
        "PostageCost"       => "Currency",
        "PostageTax"        => "Currency",
        
        // Payment Gateway Info
        "PaymentProvider"   => "Varchar",
        "PaymentNo"         => "Varchar(255)",
        
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
        'TranslatedStatus'  => 'Varchar',
        "QuoteLink"         => 'Varchar',
        "InvoiceLink"       => 'Varchar'
    );

    private static $defaults = array(
        'EmailDispatchSent' => 0,
        'DiscountAmount'    => 0
    );

    private static $summary_fields = array(
        "OrderNumber"   => "#",
        "Status"        => "Status",
        "Action"        => "Action",
        "FirstName"     => "First Name(s)",
        "Surname"       => "Surname",
        "Email"         => "Email",
        "Total"         => "Total",
        "Created"       => "Created"
    );

    private static $extensions = array(
        "Versioned('History')"
    );

    private static $default_sort = "Created DESC";
    
    public function QuoteLink() {
        return Controller::join_links(
            OrdersFront_Controller::create()->AbsoluteLink("quote"),
            $this->ID,
            $this->AccessKey
        );
    }
    
    public function InvoiceLink() {
        return Controller::join_links(
            OrdersFront_Controller::create()->AbsoluteLink(),
            $this->ID,
            $this->AccessKey
        );
    }
    
    public function populateDefaults()
    {
        parent::populateDefaults();
        $this->Status = $this->config()->default_status;
        $this->Action = $this->config()->default_action;
    }

    public function getCMSFields()
    {
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
                        TextField::create('Status'),
                        DropdownField::create(
                            'Action',
                            null,
                            $this->config()->actions
                        ),
                        ReadonlyField::create("QuoteNumber", "#")
                            ->setValue($this->ID),
                        ReadonlyField::create("Created"),
                        LiteralField::create("SubTotal", $subtotal_html),
                        LiteralField::create("Discount", $discount_html),
                        LiteralField::create("Postage", $postage_html),
                        LiteralField::create("TaxTotal", $tax_html),
                        LiteralField::create("Total", $total_html),
                        TextField::create('PaymentProvider'),
                        TextField::create('PaymentNo')
                    )->setTitle("Details")
                ),
                
                // Main Tab Fields
                $tab_customer = new Tab(
                    'Customer',
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
                    TextField::create("PhoneNumber")
                ),
                
                // Delivery Tab
                $tab_delivery = new Tab(
                    'Delivery',
                    HeaderField::create(
                        "DeliveryDetailsHeader",
                        _t("Orders.DeliveryDetails", "Delivery Details")
                    ),
                    TextField::create("DeliveryCompany"),
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
        
        
        // Add Sidebar is editable
        if ($this->canEdit()) {
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
                "BillingDetailsHeader"
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
            $map_extension
                ->setMapFields($this->config()->existing_customer_map);
        }
        
        $tab_main->addExtraClass("order-admin-items");
        $tab_customer->addExtraClass("order-admin-customer");

        $this->extend("updateCMSFields", $fields);

		$fields->fieldByName('Root')->addextraClass('orders-root');

        return $fields;
    }

    public function getBillingAddress()
    {
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
    public function getCountryFull()
    {
        try {
            $source = Zend_Locale::getTranslationList(
                'territory',
                $this->Country,
                2
            );

            return (array_key_exists($this->Country, $source)) ? $source[$this->Country] : $this->Country;
        } catch (Exception $e) {
            return "";
        }
    }

    public function getDeliveryAddress()
    {
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
    public function getDeliveryCountryFull()
    {
        try {
            $source = Zend_Locale::getTranslationList(
                'territory',
                $this->DeliveryCountry,
                2
            );

            return (array_key_exists($this->DeliveryCountry, $source)) ? $source[$this->DeliveryCountry] : $this->DeliveryCountry;
        } catch (Exception $e) {
            return "";
        }
    }


    /**
     * Mark this order as "complete" which generally is intended
     * to mean "paid for, ready for processing".
     *
     * @param string $reference the unique reference from the gateway
     * @return Order
     */
    public function markComplete($reference = null)
    {
        $this->Status = $this->config()->completion_status;
        
        if ($reference) {
            $this->PaymentNo = $reference;
        }

        return $this;
    }


    public function hasDiscount()
    {
        return (ceil($this->DiscountAmount)) ? true : false;
    }

    /**
     * Total values of items in this order (without any tax)
     *
     * @return Decimal
     */
    public function getSubTotal()
    {
        $return = new Currency();
        $return->setName("SubTotal");
        $total = 0;

        // Calculate total from items in the list
        foreach ($this->Items() as $item) {
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
    public function getPostage()
    {
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
    public function getTaxTotal()
    {
        $return = new Currency();
        $return->setName("TaxTotal");
        $total = 0;
        $items = $this->Items();
        
        // Calculate total from items in the list
        foreach ($items as $item) {
            $tax = (($item->Price - ($this->DiscountAmount / $items->count())) / 100) * $item->TaxRate;
            
            $total += $tax * $item->Quantity;
        }
        
        if ($this->PostageTax) {
            $total += $this->PostageTax;
        }

        $return->setValue($total);
        
        $this->extend("updateTaxTotal", $return);

        return $return;
    }

    /**
     * Total of order including postage
     *
     * @return Decimal
     */
    public function getTotal()
    {
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
    public function getItemSummary()
    {
        $return = '';

        foreach ($this->Items() as $item) {
            $return .= "{$item->Quantity} x {$item->Title};\n";
        }

        return $return;
    }
    
    /**
     * Return a list string summarising each item in this order
     *
     * @return string
     */
    public function getItemSummaryHTML()
    {
        $html = new HTMLText("ItemSummary");
        
        $html->setValue(nl2br($this->ItemSummary));
        
        $this->extend("updateItemSummary", $html);

        return $html;
    }

    protected function generate_order_number()
    {
        $id = str_pad($this->ID, 8,  "0");

        $guidText =
            substr($id, 0, 4) . '-' .
            substr($id, 4, 4) . '-' .
            rand(1000, 9999);

        // Work out if an order prefix string has been set
        $prefix = $this->config()->order_prefix;
        $guidText = ($prefix) ? $prefix . '-' . $guidText : $guidText;

        return $guidText;
    }
    
    protected function generate_random_string($length = 20)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
    
    protected function validAccessKey()
    {
        $existing = Order::get()
            ->filter("AccessKey", $this->AccessKey)
            ->first();
        
        return !($existing);
    }
    
    protected function validOrderNumber()
    {
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
    public function duplicate($doWrite = true)
    {
        $clone = parent::duplicate($doWrite);
        
        // Set up items
        if ($doWrite) {
            foreach ($this->Items() as $item) {
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
    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        
        // Delete all items attached to this order
        foreach ($this->Items() as $item) {
            $item->delete();
        }
        
        $this->extend("onBeforeDelete");
    }
    
    /**
     * API Callback after this object is written to the DB
     *
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        
        // Ensure that this object has a non-conflicting Access Key
        if (!$this->AccessKey) {
            $this->AccessKey = $this->generate_random_string();
            
            while (!$this->validAccessKey()) {
                $this->AccessKey = $this->generate_random_string();
            }
        }
        
        // Is delivery address set, if not, set it here
        if (!$this->DeliveryAddress1 && !$this->DeliveryPostCode) {
            $this->DeliveryCompany = $this->Company;
            $this->DeliveryFirstnames = $this->FirstName;
            $this->DeliverySurname = $this->Surname;
            $this->DeliveryAddress1 = $this->Address1;
            $this->DeliveryAddress2 = $this->Address2;
            $this->DeliveryCity = $this->City;
            $this->DeliveryPostCode = $this->PostCode;
            $this->DeliveryCountry = $this->Country;
        }
        
        
        $this->Status = (!$this->Status) ? $this->config()->default_status : $this->Status;
        $this->Action = (!$this->Action) ? $this->config()->default_action :  $this->Action;
        
        $this->extend("onBeforeWrite");
    }
    
    /**
     * API Callback after this object is written to the DB
     *
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        
        
        // Check if an order number has been generated, if not, add it and save again
        if (!$this->OrderNumber) {
            $this->OrderNumber = $this->generate_order_number();
            
            while (!$this->validOrderNumber()) {
                $this->OrderNumber = $this->generate_order_number();
            }
            $this->write();
        }

        // Deal with sending the status emails
        if ($this->isChanged('Status')) {
            $notifications = OrderNotification::get()
                ->filter("Status", $this->Status);
                
            // Loop through available notifications and send
            foreach ($notifications as $notification) {
                $notification->sendNotification($this);
            }
        }
        
        $this->extend("onAfterWrite");
    }

    public function providePermissions()
    {
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
    public function canView($member = null)
    {
        $extended = $this->extend('canView', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "COMMERCE_VIEW_ORDERS"))) {
            return true;
        } elseif ($memberID && $memberID == $this->CustomerID) {
            return true;
        }

        return false;
    }

    /**
     * Anyone can create orders, even guest users
     *
     * @return Boolean
     */
    public function canCreate($member = null)
    {
        $extended = $this->extend('canCreate', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        return true;
    }

    /**
     * Only users with EDIT admin rights can view an order
     *
     * @return Boolean
     */
    public function canEdit($member = null)
    {
        $extended = $this->extend('canEdit', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if (
            $memberID &&
            Permission::checkMember($memberID, array("ADMIN", "COMMERCE_EDIT_ORDERS")) &&
            in_array($this->Status, $this->config()->editable_statuses)
        ) {
            return true;
        }

        return false;
    }
    
    /**
     * Only users with EDIT admin rights can view an order
     *
     * @return Boolean
     */
    public function canChangeStatus($member = null)
    {
        $extended = $this->extend('canEdit', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "COMMERCE_STATUS_ORDERS"))) {
            return true;
        }

        return false;
    }

    /**
     * No one should be able to delete an order once it has been created
     *
     * @return Boolean
     */
    public function canDelete($member = null)
    {
        $extended = $this->extend('canEdit', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "COMMERCE_DELETE_ORDERS"))) {
            return true;
        }

        return false;
    }
}
