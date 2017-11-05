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
        "part-paid" => "Part Paid",
        "paid" => "Paid",
        "processing" => "Processing",
        "ready" => "Ready",
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
        "part-paid",
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
        "collected",
        "canceled"
    );

    /**
     * What statuses are considered "paid for". Meaning
     * they are complete, ready for processing, etc.
     * 
     * @var array
     * @config
     */
    private static $paid_statuses = array(
        "paid",
        "processing",
        "ready",
        "dispatched",
        "collected"
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
        "part-paid",
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
     * The status which an order is considered "complete".
     * 
     * @var string
     * @config
     */
    private static $completion_status = "paid";

    /**
     * The status which an order has been marked pending
     * (meaning we are awaiting payment).
     * 
     * @var string
     * @config
     */
    private static $pending_status = "pending";

    /**
     * The status which an order is considered "paid" (meaning
     * ready for processing, dispatch, etc).
     * 
     * @var string
     * @config
     */
    private static $paid_status = "paid";

    /**
     * The status which an order is considered "part paid" (meaning
     * partially paid, possibly deposit paid).
     * 
     * @var string
     * @config
     */
    private static $part_paid_status = "part-paid";

    /**
     * The status which an order has not been completed (meaning
     * it is not ready for processing, dispatch, etc).
     * 
     * @var string
     * @config
     */
    private static $incomplete_status = "incomplete";

    /**
     * The status which an order has been canceled.
     * 
     * @var string
     * @config
     */
    private static $canceled_status = "canceled";

    /**
     * The status which an order has been refunded.
     * 
     * @var string
     * @config
     */
    private static $refunded_status = "refunded";

    /**
     * The status which an order has been dispatched
     * (sent to customer).
     * 
     * @var string
     * @config
     */
    private static $dispatched_status = "dispatched";
    
    /**
     * The status which an order has been marked collected
     * (meaning goods collected from store).
     * 
     * @var string
     * @config
     */
    private static $collected_status = "collected";

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
     * @var string
     * @config
     */
    private static $existing_customer_class = "Member";

    /**
     * The list of fields that will show in the existing customer
     * gridfield.
     * 
     * If not set, will default to summary_fields
     * 
     * @var array
     * @config
     */
    private static $existing_customer_fields = array();

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
        "AmountPaid"        => 'Currency',
        'TotalItems'        => 'Int',
        'TotalWeight'       => 'Decimal',
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
        "Created"       => "Created",
        "LastEdited"    => "Last Edited"
    );

    private static $extensions = array(
        "Versioned('History')"
    );

    private static $default_sort = "Created DESC";

    /**
     * Generate a link to view the associated front end quote
     * for this order
     *
     * @return string
     */
    public function QuoteLink()
    {
        return Controller::join_links(
            OrdersFront_Controller::create()->AbsoluteLink("quote"),
            $this->ID,
            $this->AccessKey
        );
    }

    /**
     * Generate a link to view an assocaited front end invoice for
     * this order
     *
     * @return string
     */
    public function InvoiceLink()
    {
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
        $payment = $this->getPayment();

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
                    $order_sidebar = OrderSidebar::create(
                        TextField::create('Status'),
                        DropdownField::create(
                            'Action',
                            null,
                            $this->config()->actions
                        ),
                        ReadonlyField::create("QuoteNumber", "#")
                            ->setValue($this->ID),
                        ReadonlyField::create("Created"),
                        ReadonlyField::create("SubTotalValue",_t("Orders.SubTotal", "Sub Total"))
                            ->setValue($this->obj("SubTotal")->Nice()),
                        ReadonlyField::create("DiscountValue",_t("Orders.Discount", "Discount"))
                            ->setValue($this->dbObject("DiscountAmount")->Nice()),
                        ReadonlyField::create("PostageValue",_t("Orders.Postage", "Postage"))
                            ->setValue($this->obj("Postage")->Nice()),
                        ReadonlyField::create("TaxValue",_t("Orders.Tax", "Tax"))
                            ->setValue($this->obj("TaxTotal")->Nice()),
                        ReadonlyField::create("TotalValue",_t("Orders.Total", "Total"))
                            ->setValue($this->obj("Total")->Nice()),
                        ReadonlyField::create("AmountPaidValue",_t("Orders.AmountPaid", "Amount Paid"))
                            ->setValue($this->obj("AmountPaid")->Nice())
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
                ),

                // List payments
                $tab_payments = new Tab(
                    "Payments",
                    GridField::create(
                        "Payments",
                        "",
                        $this->Payments(),
                        $config = GridFieldConfig::create()
                            ->addComponents(
                                new GridFieldButtonRow('before'),
                                new GridFieldTitleHeader(),
                                new GridFieldDataColumns(),
                                new GridFieldEditButton(),
                                new GridFieldDetailForm()
                            )
                    )
                )
            )
        );

        if ($payment) {
            $payment_ref = $payment->TransactionReference;
        } elseif ($this->PaymentNo) {
            $payment_ref = $this->PaymentNo;
        } else {
            $payment_ref = null;
        }

        if ($payment_ref) {
            $order_sidebar->push(
                ReadonlyField::create("PaymentNoValue",_t("Orders.PaymentNo", "Payment No"))
                    ->setValue($payment_ref)
            );
        }
        
        
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
            
            if (is_array($this->config()->existing_customer_fields) && count($this->config()->existing_customer_fields)) {
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

		$tab_root->addextraClass('orders-root');
        $tab_main->addExtraClass("order-admin-items");
        $tab_customer->addExtraClass("order-admin-customer");

        $this->extend("updateCMSFields", $fields);

        return $fields;
    }

    /**
     * Get the complete billing address for this order
     *
     * @return string
     */
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
     * @return string
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

    /**
     * Get the complete delivery address for this order
     *
     * @return string
     */
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
     * @return string 
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
     * Has this order been paid for? We determine this
     * by checking one of the pre-defined "paid_statuses"
     * in the config variable:
     * 
     *   # Order.paid_statuses
     *
     * @return boolean
     */
    public function isPaid()
    {
        $statuses = $this->Config()->paid_statuses;

        if (!is_array($statuses)) {
            return $this->Status == $statuses;
        } else {
            return in_array($this->Status, $statuses);
        }
    }

    /**
     * Get the captured payment object associated with this order
     *
     * @return Payment || null
     */
    public function getPayment()
    {
        foreach ($this->Payments() as $payment) {
            $a = Checkout::round_up($payment->getAmount(), 2);
            $b = Checkout::round_up($this->Total, 2);

            if ($payment->isCaptured() && (abs(($a-$b)/$b) < 0.00001)) {
                return $payment;
            }
        }

        return null;
    }

    /**
     * Mark this order as "complete" which generally is intended
     * to mean "paid for, ready for processing".
     *
     * @return Order
     */
    public function markComplete()
    {
        $this->Status = $this->config()->completion_status;
        return $this;
    }

    /**
     * Mark this order as "paid"
     *
     * @param string $reference the unique reference from the gateway
     * @return Order
     */
    public function markPaid()
    {
        $this->Status = $this->config()->paid_status;
        return $this;
    }

    /**
     * Mark this order as "part paid".
     *
     * @return Order
     */
    public function markPartPaid()
    {
        $this->Status = $this->config()->part_paid_status;
        return $this;
    }

    /**
     * Mark this order as "pending" (awaiting payment to clear/reconcile).
     *
     * @return Order
     */
    public function markPending()
    {
        $this->Status = $this->config()->pending_status;
        return $this;
    }

    /**
     * Mark this order as "canceled".
     *
     * @return Order
     */
    public function markCanceled()
    {
        $this->Status = $this->config()->canceled_status;
        return $this;
    }

    /**
     * Mark this order as "refunded".
     *
     * @return Order
     */
    public function markRefunded()
    {
        $this->Status = $this->config()->refunded_status;
        return $this;
    }

    /**
     * Mark this order as "dispatched".
     *
     * @return Order
     */
    public function markDispatched()
    {
        $this->Status = $this->config()->dispatched_status;
        return $this;
    }

    /**
     * Mark this order as "collected".
     *
     * @return Order
     */
    public function markCollected()
    {
        $this->Status = $this->config()->collected_status;
        return $this;
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

        $this->extend("updateItemSummary", $return);

        return $return;
    }

    /**
     * Return a list string summarising each item in this order
     *
     * @return HTMLText
     */
    public function getItemSummaryHTML()
    {
        $html = new HTMLText("ItemSummary");
        
        $html->setValue(nl2br($this->ItemSummary));
        
        $this->extend("updateItemSummaryHTML", $html);

        return $html;
    }

    /**
     * Has this order got a discount applied?
     *
     * @return boolean
     */
    public function hasDiscount()
    {
        return (ceil($this->DiscountAmount)) ? true : false;
    }

    /**
     * Setup the discount available on this order, you need to
     * pass a Title (that will be rendered on the order) and an
     * amount (that will be removed from the order).
     *
     * @param $title Title of the discount
     * @param $amount The value of the discount
     * @return void
     */
    public function setDiscount($title, $amount = 0)
    {
        $this->Discount = $title;
        $this->DiscountAmount = $amount;
    }

    /**
     * Get the postage cost for this order
     *
     * @return float
     */
    public function getPostage()
    {
        $total = $this->PostageCost;
        
        $this->extend("updatePostage", $total);
        
        return $total;
    }

    /**
     * Setup the postage selected on this order, you need to
     * pass a Title (that will be rendered on the order) an
     * amount (that will be removed from the order) and any
     * tax that applies
     *
     * @param $title Title of the postage
     * @param $amount The value of the postage
     * @param $tav The value of the tax for this postage
     * @return void
     */
    public function setPostage($title, $amount = 0, $tax = 0)
    {
        $this->PostageType = $title;
        $this->PostageCost = $amount;
        $this->PostageTax = $tax;
    }

    /**
     * Find the total quantity of items in the shopping cart
     *
     * @return Int
     */
    public function getTotalItems()
    {
        $total = 0;

        foreach ($this->Items() as $item) {
            $total += ($item->Quantity) ? $item->Quantity : 1;
        }

        $this->extend("updateTotalItems", $total);

        return $total;
    }

    /**
    * Find the total weight of all items in the shopping cart
    *
    * @return float
    */
    public function getTotalWeight()
    {
        $total = 0;
        
        foreach ($this->Items() as $item) {
            if ($item->Weight && $item->Quantity) {
                $total = $total + ($item->Weight * $item->Quantity);
            }
        }

        $this->extend("updateTotalWeight", $total);
        
        return $total;
    }

    /**
     * Total values of items in this order (without any tax)
     *
     * @return float
     */
    public function getSubTotal()
    {
        $total = 0;

        // Calculate total from items in the list
        foreach ($this->Items() as $item) {
            $total += $item->SubTotal;
        }
        
        $this->extend("updateSubTotal", $total);

        return $total;
    }

    /**
     * Total values of items in this order
     *
     * @return float
     */
    public function getTaxTotal()
    {
        $total = 0;
        $items = $this->Items();
        
        // Calculate total from items in the list
        foreach ($items as $item) {
            // If a discount applied, get the tax based on the
            // discounted amount
            if ($this->DiscountAmount > 0) {
                $discount = $this->DiscountAmount / $this->TotalItems;
                $price = $item->UnitPrice - $discount;
                $tax = ($price / 100) * $item->TaxRate;
            } else {
                $tax = $item->UnitTax;
            }

            $total += $tax * $item->Quantity;
        }
        
        if ($this->PostageTax) {
            $total += $this->PostageTax;
        }
        
        $this->extend("updateTaxTotal", $total);

        $total = Checkout::round_up($total, 2);

        return $total;
    }

    /**
     * Total of order including postage
     *
     * @return float
     */
    public function getTotal()
    {   
        $total = (($this->SubTotal + $this->Postage) - $this->DiscountAmount) + $this->TaxTotal;
        
        $this->extend("updateTotal", $total);
        
        return $total;
    }

    /**
     * Find the total amount paid for this order
     * (based on the sum of its payments)
     * 
     * @return float
     */
    public function getAmountPaid()
    {
        $total = 0;

        foreach ($this->Payments() as $payment) {
            if ($payment->isCaptured()) {
                $total += $payment->getAmount();
            }
        }

        $this->extend("updateAmountPaid", $total);

        return $total;
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
