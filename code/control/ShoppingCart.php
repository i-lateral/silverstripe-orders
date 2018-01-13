<?php

/**
 * Holder for items in the shopping cart and interacting with them, as
 * well as rendering these items into an interface that allows editing
 * of items,
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class ShoppingCart extends Controller
{
    
    /**
     * URL Used to access this controller
     *
     * @var string
     * @config
     */
    private static $url_segment = 'checkout/cart';
    
    /**
     * Name of the current controller. Mostly used in templates.
     *
     * @var string
     * @config
     */
    private static $class_name = "ShoppingCart";
    
    /**
     * Overwrite the default title for this controller which is taken
     * from the translation files. This is used for Title and MetaTitle
     * variables in templates.
     *
     * @var string
     * @config
     */
    private static $title;
    
    /**
     * Class Name of object we use as an assotiated estimate.
     * This defaults to Estimate
     *
     * @var string
     * @config
     */
    private static $estimate_class = "Estimate";
    
    /**
     * Class Name of item we add to the shopping cart/an estimate.
     * This defaults to OrderItem
     *
     * @var string
     * @config
     */
    private static $item_class = "OrderItem";
    
    /**
     * Should the cart globally check for stock levels on items added?
     * Using this setting will ignore individual "Stocked" settings
     * on Shopping Cart Items.
     *
     * @var string
     * @config
     */
    private static $check_stock_levels = false;
    
    /**
     * These methods are mapped to sub URLs of this
     * controller.
     *
     * @var array
     */
    private static $allowed_actions = array(
        "remove",
        "emptycart",
        "clear",
        "update",
        "usediscount",
        "setdeliverytype",
        "CartForm",
        "PostageForm",
        "DiscountForm"
    );
    
    /**
     * Track all items stored in the current shopping cart
     *
     * @var ArrayList
     */
    protected $items;
    
    /**
     * An estimate object that this shopping cart is associated
     * with.
     *
     * This estimate is used to calculate things such as Total,
     * Tax, etc.
     *
     * @var Estimate
     */
    protected $estimate;
    
    /**
     * Track a discount object placed against this cart
     *
     * @var Int
     */
    protected $discount_id;
    
    /**
     * Track the currently selected postage (if available)
     *
     * @var Postage
     */
    protected $postage;
    
    /**
     * Show the discount form on the shopping cart
     *
     * @var boolean
     * @config
     */
    private static $show_discount_form = false;
    
    private static $casting = array(
        "TotalWeight" => "Decimal",
        "TotalItems" => "Int",
        "SubTotalCost" => "Currency",
        "DiscountAmount" => "Currency",
        "TaxCost" => "Currency",
        "PostageCost" => "Currency",
        "TotalCost" => "Currency"
    );
    
    /**
     * Getters and setters
     *
     */
    public function getClassName()
    {
        return self::config()->class_name;
    }
    
    public function getTitle()
    {
        return ($this->config()->title) ? $this->config()->title : _t("Checkout.CartName", "Shopping Cart");
    }
    
    public function getMetaTitle()
    {
        return $this->getTitle();
    }
    
    public function getShowDiscountForm()
    {
        return $this->config()->show_discount_form;
    }
    
    public function getEstimate()
    {
        return $this->estimate;
    }
    
    public function setEstimate($estimate)
    {
        $this->estimate = $estimate;
    }
    
    public function getItems()
    {
        return $this->estimate->Items();
    }
    
    public function getDiscountID()
    {
        return $this->discount_id;
    }
    
    public function setDiscountID(Int $discount_id)
    {
        $this->discount_id = $discount_id;
    }

    public function getDiscount()
    {
        return Discount::get()->ByID($this->discount_id);
    }
    
    public function setDiscount(Discount $discount)
    {
        $this->discount_id = $discount->ID;
    }
    
    /**
     * Get the link to this controller
     *
     * @param string $action The action you want to add to the link
     * @return string
     */
    public function Link($action = null)
    {
        return Controller::join_links(
            $this->config()->url_segment,
            $action
        );
    }

    /**
     * Get an absolute link to this controller
     *
     * @param string $action The action you want to add to the link
     * @return string
     */
    public function AbsoluteLink($action = null)
    {
        return Director::absoluteURL($this->Link($action));
    }

    /**
     * Get a relative (to the root url of the site) link to this
     * controller
     *
     * @param string $action The action you want to add to the link
     * @return string
     */
    public function RelativeLink($action = null)
    {
        return Controller::join_links(
            Director::baseURL(),
            $this->Link($action)
        );
    }
    
    /**
     * Set postage that is available to the shopping cart based on the
     * country and zip code submitted
     *
     * @param $country 2 character country code
     * @param $code Zip or Postal code
     * @return ShoppingCart
     */
    public function setAvailablePostage($country, $code)
    {
        $postage_areas = new ShippingCalculator($code, $country);
        
        $postage_areas
            ->setCost($this->SubTotalCost)
            ->setWeight($this->TotalWeight)
            ->setItems($this->TotalItems);
        
        $postage_areas = $postage_areas->getPostageAreas();
        
        $this->extend('updateAvailablePostage',$postage_areas);

        Session::set("Checkout.AvailablePostage", $postage_areas);

        
        // If current postage is not available, clear it.
        $postage_id = Session::get("Checkout.PostageID");

        if (!$postage_areas->find("ID", $postage_id)) {
            if ($postage_areas->exists()) {
                Session::set("Checkout.PostageID", $postage_areas->first()->ID);
            } else {
                Session::clear("Checkout.PostageID");
            }
        }
        
        return $this;
    }
    
    /**
     * Are we collecting the current cart? If click and collect is
     * disabled then this returns false, otherwise checks if the user
     * has set this via a session.
     *
     * @return Boolean
     */
    public function isCollection()
    {
        if (Checkout::config()->click_and_collect) {
            $type = Session::get("Checkout.Delivery");
            
            return ($type == "collect") ? true : false;
        } else {
            return false;
        }
    }
    
    /**
     * Determine if the current cart contains delivereable items.
     * This is used to determine setting and usage of delivery and
     * postage options in the checkout.
     *
     * @return Boolean
     */
    public function isDeliverable()
    {
        $deliverable = false;
        
        foreach ($this->getItems() as $item) {
            if ($item->Deliverable) {
                $deliverable = true;
            }
        }
        
        return $deliverable;
    }
    
    /**
     * Determine if the current cart contains only locked items.
     *
     * @return Boolean
     */
    public function isLocked()
    {
        foreach ($this->getItems() as $item) {
            if (!$item->Locked) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Shortcut for ShoppingCart::create, exists because create()
     * doesn't seem quite right.
     *
     * @return ShoppingCart
     */
    public static function get()
    {
        return ShoppingCart::create();
    }
    
    /**
     * Build the shopping cart, either from session based items and a temporary
     * estimate or from a saved estimate against a user.
     *
     * If a user has logged in and also has items in a session, then
     * push these items into a saved estimate.
     *
     */
    public function __construct()
    {
        parent::__construct();

        $member = Member::currentUser();
        $estimate_class = self::config()->estimate_class;
        $estimate_id = Cookie::get('ShoppingCart.EstimateID');
        $estimate = null;
        $write = false;
        // If the current member doesn't have a cart, set one
        // up, else get their estimate or create a blank one
        // (if no member).
        if ($member && !$member->getCart() && !$estimate_id) {
            $estimate = $estimate_class::create();
            $estimate->Cart = true;
            $write = true;
        } elseif ($member && $member->getCart()) {
            $estimate = $member->getCart();
        } elseif ($estimate_id) {
            $estimate = $estimate_class::get()->byID($estimate_id);
        }

        if (!$estimate) {
            $estimate = $estimate_class::create();
            $estimate->Cart = true;
            $write = true;
        }

        if ($member && $estimate->CustomerID != $member->ID) {
            $estimate->CustomerID = $member->ID;
            $write = true;
        }

        if ($write) {
            $estimate->write();
        }


        // Get any saved items from a session
        if ($estimate_id && $estimate_id != $estimate->ID) {
            $old_est = $estimate_class::get()->byID($estimate_id);
            if ($old_est) {
                $items = $old_est->Items();

                // If the current member has an estimate, but also session items
                // add to the order
                foreach ($items as $item) {
                    $existing = $estimate
                        ->Items()
                        ->find("Key", $item->Key);
                    
                    if (!$existing) {
                        if ($member) {
                            $item->write();
                        }
                        $estimate
                            ->Items()
                            ->add($item);
                    }

                    if ($item->Customisation) {
                        $data = unserialize($item->Customisation);
                        if ($data instanceof ArrayList) {
                            foreach ($data as $data_item) {
                                $item
                                    ->Customisations()
                                    ->push($data_item);
                            }
                        }
                    }
                }

                $old_est->delete();
                Cookie::force_expiry('ShoppingCart.EstimateID');
            }

        }

        // Set our estimate to this cart
        if (!$member) {
            Cookie::set('ShoppingCart.EstimateID',$estimate->ID);
        }

        $this->setEstimate($estimate);

        // If discount stored in a session, get it
        if (Session::get('ShoppingCart.DiscountID')) {
            $this->discount_id = Session::get('ShoppingCart.DiscountID');
            $this
                ->getEstimate()
                ->setDiscount(
                    $this->getDiscount()->Title,
                    $this->getDiscountAmount()
                );
        }
        
        // If we don't have any discounts, a user is logged in and he has
        // access to discounts through a group, add the discount here
        if (!$this->discount_id && $member && $member->getDiscount()) {
            $this->discount_id = $member->getDiscount()->ID;
            Session::set('ShoppingCart.DiscountID', $this->discount_id);
            $this
                ->getEstimate()
                ->setDiscount(
                    $this->getDiscount()->Title,
                    $this->getDiscountAmount()
                );
        }
        
        // Setup postage
        $postage_id = Session::get("Checkout.PostageID");
        
        if ($postage_id && $postage = PostageArea::get()->byID($postage_id)) {
            $this->postage = $postage;
            $this
                ->getEstimate()
                ->setPostage(
                    $postage->Title,
                    $postage->Cost,
                    $postage->TaxAmount
                );
        }
        
        // Allow extension of the shopping cart after initial setup
        $this->extend("augmentSetup");
    }
    
    /**
     * Return a rendered button for the shopping cart
     *
     * @return string
     */
    public function getViewCartButton()
    {
        return $this->renderWith('ViewCartButton');
    }

    public function init() {
        parent::init();

        if (!Config::inst()->get('Checkout', 'cron_cleaner')) {
            $siteconfig = SiteConfig::current_site_config();
            $date = $siteconfig->dbobject("LastEstimateClean");
            if (!$date || ($date && !$date->IsToday())) {
                $task = Injector::inst()->create('CleanExpiredEstimatesTask');
                $task->setSilent(true);
                $task->run($this->getRequest());
                $siteconfig->LastEstimateClean = SS_Datetime::now()->Value;
                $siteconfig->write();
            }
        }

    }

    
    /**
     * Default acton for the shopping cart
     */
    public function index()
    {
        $this->extend("onBeforeIndex");
        
        return $this->renderWith(array(
            'ShoppingCart',
            'Checkout',
            'Page'
        ));
    }
    
    /**
     * Remove a product from ShoppingCart Via its ID. This action
     * expects an ID to be sent through the URL that matches a specific
     * key added to an item in the cart
     *
     * @return Redirect
     */
    public function remove()
    {
        $key = $this->request->param('ID');
        $title = "";
        
        if (!empty($key)) {
            foreach ($this->getItems() as $item) {
                if ($item->Key == $key) {
                    $title = $item->Title;
                    $this->getItems()->remove($item);
                }
            }
            
            $this->save();
            
            if ($title) {
                $this->setSessionMessage(
                    "bad",
                    _t(
                        "Checkout.RemovedItem",
                        "Removed '{title}' from your cart",
                        "Message to tell user they removed an item",
                        array("title" => $title)
                    )
                );
            }
        }
        
        return $this->redirectBack();
    }
    
    /**
     * Action that will clear shopping cart and associated sessions
     *
     */
    public function emptycart()
    {
        $this->extend("onBeforeEmpty");
        $this->removeAll();
        $this->save();
        
        $this->setSessionMessage(
            "bad",
            _t("Checkout.EmptiedCart", "Shopping cart emptied")
        );
        
        return $this->redirectBack();
    }
    
    
    /**
     * Action used to add a discount to the users session via a URL.
     * This is preferable to using the dicount form as disount code
     * forms seem to provide a less than perfect user experience
     *
     */
    public function usediscount()
    {
        $this->extend("onBeforeUseDiscount");
        
        $code_to_search = $this->request->param("ID");
        $code = false;
        
        if (!$code_to_search) {
            return $this->httpError(404, "Page not found");
        }
        
        // First check if the discount is already added (so we don't
        // query the DB if we don't have to).
        if (!$this->discount_id || ($this->discount_id && $this->getDiscount()->Code != $code_to_search)) {
            $codes = Discount::get()
                ->filter("Code", $code_to_search)
                ->exclude("Expires:LessThan", date("Y-m-d"));
            
            if ($codes->exists()) {
                $code = $codes->first();
                $this->discount_id = $code->ID;
                $this->save();
            }
        } elseif ($this->discount_id && $this->getDiscount()->Code == $code_to_search) {
            $code = $this->getDiscount();
        }
        
        return $this
            ->customise(array(
                "Discount" => $code
            ))->renderWith(array(
                'ShoppingCart_discount',
                'Checkout',
                'Page'
            ));
    }
    
    
    /**
     * Set the current session to click and collect (meaning no shipping)
     *
     * @return Redirect
     */
    public function setdeliverytype()
    {
        $this->extend("onBeforeSetDeliveryType");
        
        $type = $this->request->param("ID");
        
        if ($type && in_array($type, array("deliver", "collect"))) {
            Session::set("Checkout.Delivery", $type);
            Session::clear("Checkout.PostageID");
        }
        
        $this->extend("onAfterSetDeliveryType");
        
        $this->redirectBack();
    }
    
    /**
     * Add an item to the shopping cart. To make this process as generic
     * as possible, we require that an array of data is submitted.
     *
     * This data is then converted to an @link OrderItem, or used to update
     * an existing order item.
     *
     * @param array $data An array of data that we will be added to the cart
     * @param int $quantity Number of these items to add
     * @throws ValidationException
     * @return ShoppingCart
     */
    public function add($data, $quantity = 1)
    {
        $estimate = $this->getEstimate();
        if (!array_key_exists("Key", $data)) {
            throw new ValidationException(_t(
                "Checkout.NoKeyOnItem",
                "No valid Key set on item"
            ));
        } else {
            $added = false;
            $item_key = $data['Key'];
            $custom_list = ArrayList::create();
            
            // If using the old ClassName variable, update to the
            // new ProductClass variable and wipe
            if (array_key_exists("ClassName", $data)) {
                $data["ProductClass"] = $data["ClassName"];
                unset($data["ClassName"]);
            }
            
            // If using the old BasePrice variable, update to the
            // new Price variable and wipe
            if (array_key_exists("BasePrice", $data)) {
                $data["Price"] = $data["BasePrice"];
                unset($data["BasePrice"]);
            }

            // Legacy support for old customisation calls
            if (array_key_exists("CustomisationArray", $data)) {
                $data["Customisation"] = $data["CustomisationArray"];
            }

            // Convert customisation into  
            if (array_key_exists("Customisation", $data) && is_array($data["Customisation"])) {
                foreach ($data["Customisation"] as $custom_item) {
                    if (!array_key_exists("Title", $custom_item) || !array_key_exists("Value", $custom_item)) {
                        throw new ValidationException(_t(
                            "Checkout.NoValidCustomisation",
                            "Customisation title or value incorrect"
                        ));
                        return;
                    }

                    $custom_list->push(OrderItemCustomisation::create(array(
                        "Title" => $custom_item["Title"],
                        "Value" => $custom_item["Value"],
                        "Price" => (array_key_exists("Price", $custom_item)) ? $custom_item["Price"] : 0
                    )));
                }
                
                $data["Customisation"] = serialize($custom_list);
            }
            
            // Ensure we don't alllow any object ID's to be set
            if (array_key_exists("ID", $data)) {
                unset($data["ID"]);
            }
            
            // Check if object already in the cart and update quantity
            foreach ($this->getItems() as $item) {
                if ($item->Key == $item_key) {
                    $this->update($item->Key, ($item->Quantity + $quantity));
                    $added = true;
                }
            }
            
            // If no update was sucessfull then add to cart items
            if (!$added) {
                $cart_item = self::config()->item_class;
                $cart_item = $cart_item::create();
                
                foreach ($data as $key => $value) {
                    $cart_item->$key = $value;
                }
                
                // If we need to track stock, do it now
                if ($cart_item->Stocked || $this->config()->check_stock_levels) {
                    if ($cart_item->checkStockLevel($quantity) < 0) {
                        throw new ValidationException(_t(
                            "Checkout.NotEnoughStock",
                            "There are not enough '{title}' in stock",
                            "Message to show that an item hasn't got enough stock",
                            array('title' => $cart_item->Title)
                        ));
                    }
                }
                
                $cart_item->Key = $item_key;
                $cart_item->Quantity = floor($quantity);
                
                $this->extend("onBeforeAdd", $cart_item);
                
                $estimate
                    ->Items()
                    ->add($cart_item);
                
                $this->save();
            }

            $estimate->write();
        }

        return $this;
    }
    
    /**
     * Find an existing item and update its quantity
     *
     * @param Item
     * @param Quantity
     * @throws ValidationException
     * @return ShoppingCart
     */
    public function update($item_key, $quantity)
    {
        $cart_item = $this
            ->getItems()
            ->find("Key", $item_key);
        
        if ($cart_item && !$cart_item->Locked) {
            // If we need to track stock, do it now
            if ($cart_item->Stocked || $this->config()->check_stock_levels) {
                if ($cart_item->checkStockLevel($quantity) <= 0) {
                    throw new ValidationException(_t(
                        "Checkout.NotEnoughStock",
                        "There are not enough '{title}' in stock",
                        "Message to show that an item hasn't got enough stock",
                        array('title' => $cart_item->Title)
                    ));
                }
            }
            
            $cart_item->Quantity = floor($quantity);
            
            $this->extend("onBeforeUpdate", $cart_item);

            // If the current item is in the DB, update
            if ($cart_item->ID) {
                $cart_item->write();
            }
            
            $this->save();
        } else {
            throw new ValidationException(_t("Checkout.UnableToEditItem", "Unable to change item's quantity"));
        }
        
        return $this;
    }
    
    /**
     * Empty the shopping cart object of all items.
     *
     */
    public function removeAll()
    {
        foreach ($this->getItems() as $item) {
            // If we are dealing with a session object,
            // unset, otherwise delete
            if ($item->ID) {
                $item->delete();
            } else {
                unset($item);
            }
        }
    }
    
    /**
     * Save the current products list and postage to a session.
     *
     */
    public function save()
    {
        // Extend our save operation
        $this->extend("onBeforeSave");
        
        $member = Member::currentUser();
        
        // Clear any currently set postage
        Session::clear("Checkout.PostageID");
        
        // Save cart items
        $estimate = $this->getEstimate();
        
        // Save cart discounts
        if ($this->discount_id) {
            $estimate->setDiscount(
                $this->getDiscount()->Title,
                $this->getDiscountAmount()
            );
        }
        
        // Update available postage (or clear any set if not deliverable)
        $data = Session::get("Form.Form_PostageForm.data");
        if ($data && is_array($data) && $this->isDeliverable()) {
            $country = $data["Country"];
            $code = $data["ZipCode"];
            $this->setAvailablePostage($country, $code);
        } else {
            Session::clear("Checkout.PostageID");
            $estimate->setPostage("",0,0);
        }

        $estimate->write();

        // Extend our save operation
        $this->extend("onAfterSave");
    }
    
    /**
     * Clear the shopping cart object and destroy the session. Different to
     * empty, as that retains the session.
     *
     */
    public function clear()
    {
        // First tear down any objects in our estimate
        $estimate = $this->getEstimate();

        // Now remove any sessions
        Cookie::force_expiry('ShoppingCart.EstimateID');
        Session::clear('ShoppingCart.DiscountID');
        Session::clear("Checkout.PostageID");

        // If member logged in, clear postage and
        // discount on the tracked estimate
        if ($estimate) {
            $estimate->setPostage("", 0, 0);
            $estimate->setDiscount("", 0);
            $estimate->write();
        }
        
    }
    
    /**
     * Shortcut to checkout config, to allow us to access it via
     * templates
     *
     * @return boolean
     */
    public function ShowTax()
    {
        return Checkout::config()->show_tax;
    }
    
    /**
     * Find the total weight of all items in the shopping cart
     *
     * @return Decimal
     */
    public function getTotalWeight()
    {
        return $this
            ->getEstimate()
            ->getTotalWeight();
    }
    
    /**
     * Find the total quantity of items in the shopping cart
     *
     * @return Int
     */
    public function getTotalItems()
    {
        return $this
            ->getEstimate()
            ->getTotalItems();
    }
    
    /**
     * Find the cost of all items in the cart, without any tax.
     *
     * @return Currency
     */
    public function getSubTotalCost()
    {
        return $this
            ->getEstimate()
            ->getSubTotal();
    }
    
    /**
     * Get the cost of postage
     *
     * @return Currency
     */
    public function getPostageCost()
    {
        $total = 0;
        $estimate = $this->getEstimate();
        
        if ($estimate) {
            $total = $estimate->PostageCost;
        }
        
        return $total;
    }
    
    /**
     * Find the total discount based on discount items added.
     *
     * @return Float
     */
    public function getDiscountAmount()
    {
        $discount = $this->getDiscount();
        $total = 0;
        $discount_amount = 0;
        $items = $this->TotalItems;
        
        foreach ($this->getItems() as $item) {
            if ($item->Price) {
                $total += ($item->Price * $item->Quantity);
            }
            
            if ($item->Price && $discount && $discount->Amount) {
                if ($discount->Type == "Fixed") {
                    $discount_amount = $discount_amount + ($discount->Amount / $items) * $item->Quantity;
                } elseif ($discount->Type == "Percentage") {
                    $discount_amount = $discount_amount + (($item->Price / 100) * $discount->Amount) * $item->Quantity;
                }
            }
        }

        if ($discount_amount > $total) {
            $discount_amount = $total;
        }

        $this->extend("updateDiscountAmount", $discount_amount);
        
        return $discount_amount;
    }
    
    /**
     * Find the total cost of tax for the items in the cart, as well as shipping
     * (if set)
     *
     * @return Currency
     */
    public function getTaxCost()
    {
        return $this
            ->getEstimate()
            ->getTaxTotal();
    }
    
    /**
     * Find the total cost of for all items in the cart, including tax and
     * shipping (if applicable)
     *
     * @return Currency
     */
    public function getTotalCost()
    {
        return $this
            ->getEstimate()
            ->getTotal();
    }

    /**
     * Form responsible for listing items in the shopping cart and
     * allowing management (such as addition, removal, etc)
     *
     * @return Form
     */
    public function CartForm()
    {
        $fields = new FieldList();
        
        $actions = new FieldList(
            FormAction::create('doUpdate', _t('Checkout.UpdateCart', 'Update Cart'))
                ->addExtraClass('btn')
                ->addExtraClass('btn-blue btn-info')
        );
        
        $form = Form::create($this, "CartForm", $fields, $actions)
            ->addExtraClass("forms")
            ->setTemplate("ShoppingCartForm");
        
        $this->extend("updateCartForm", $form);
        
        return $form;
    }
    
    /**
     * Form that allows you to add a discount code which then gets added
     * to the cart's list of discounts.
     *
     * @return Form
     */
    public function DiscountForm()
    {
        $fields = new FieldList(
            TextField::create(
                "DiscountCode",
                _t("Checkout.DiscountCode", "Discount Code")
            )->setAttribute(
                "placeholder",
                _t("Checkout.EnterDiscountCode", "Enter a discount code")
            )
        );
        
        $actions = new FieldList(
            FormAction::create('doAddDiscount', _t('Checkout.Add', 'Add'))
                ->addExtraClass('btn')
                ->addExtraClass('btn-blue btn-info')
        );
        
        $form = Form::create($this, "DiscountForm", $fields, $actions)
            ->addExtraClass("forms");
        
        $this->extend("updateDiscountForm", $form);
        
        return $form;
    }
    
    /**
     * Form responsible for estimating shipping based on location and
     * postal code
     *
     * @return Form
     */
    public function PostageForm()
    {
        if (!Checkout::config()->simple_checkout && $this->isDeliverable()) {
            $available_postage = Session::get("Checkout.AvailablePostage");
            
            // Setup form
            $form = Form::create(
                $this,
                'PostageForm',
                $fields = new FieldList(
                    CountryDropdownField::create(
                        'Country',
                        _t('Checkout.Country', 'Country')
                    ),
                    TextField::create(
                        "ZipCode",
                        _t('Checkout.ZipCode', "Zip/Postal Code")
                    )
                ),
                $actions = new FieldList(
                    FormAction::create(
                        "doSetPostage",
                        _t('Checkout.Search', "Search")
                    )->addExtraClass('btn')
                    ->addExtraClass('btn btn-green btn-success')
                ),
                $required = RequiredFields::create(array(
                    "Country",
                    "ZipCode"
                ))
            )->addExtraClass('forms')
            ->addExtraClass('forms-inline')
            ->setLegend(_t("Checkout.EstimateShipping", "Estimate Shipping"));

            // If we have stipulated a search, then see if we have any results
            // otherwise load empty fieldsets
            if ($available_postage && $available_postage->exists()) {
                // Loop through all postage areas and generate a new list
                $postage_array = array();
                
                foreach ($available_postage as $area) {
                    $area_currency = new Currency("Cost");
                    $area_currency->setValue($area->Cost);
                    $postage_array[$area->ID] = $area->Title . " (" . $area_currency->Nice() . ")";
                }
                
                $fields->add(OptionsetField::create(
                    "PostageID",
                    _t('Checkout.SelectPostage', "Select Postage"),
                    $postage_array
                ));
                
                $actions
                    ->dataFieldByName("action_doSetPostage")
                    ->setTitle(_t('Checkout.Update', "Update"));
            }
            
            // Check if the form has been re-posted and load data
            $data = Session::get("Form.{$form->FormName()}.data");
            if (is_array($data)) {
                $form->loadDataFrom($data);
            }
            
            // Check if the postage area has been set, if so, Set Postage ID
            $data = array();
            $data["PostageID"] = Session::get("Checkout.PostageID");
            if (is_array($data)) {
                $form->loadDataFrom($data);
            }
            
            // Extension call
            $this->extend("updatePostageForm", $form);
            
            return $form;
        }
    }

    /**
     * Action that will update cart
     *
     * @param type $data
     * @param type $form
     */
    public function doUpdate($data, $form)
    {
        foreach ($this->getItems() as $cart_item) {
            foreach ($data as $key => $value) {
                $sliced_key = explode("_", $key);
                if ($sliced_key[0] == "Quantity") {
                    if (isset($cart_item) && ($cart_item->Key == $sliced_key[1])) {
                        try {
                            if ($value > 0) {
                                $this->update($cart_item->Key, $value);
                                
                                $this->setSessionMessage(
                                    "success",
                                    _t("Checkout.UpdatedShoppingCart", "Shopping cart updated")
                                );
                            } else {
                                $this->remove($cart_item->Key);
                                
                                $this->setSessionMessage(
                                    "success",
                                    _t("Checkout.EmptiedShoppingCart", "Shopping cart emptied")
                                );
                            }
                        } catch (ValidationException $e) {
                            $this->setSessionMessage(
                                "bad",
                                $e->getMessage()
                            );
                        } catch (Exception $e) {
                            $this->setSessionMessage(
                                "bad",
                                $e->getMessage()
                            );
                        }
                    }
                }
            }
        }
        
        $this->save();
        
        return $this->redirectBack();
    }
    
    /**
     * Action that will find a discount based on the code
     *
     * @param type $data
     * @param type $form
     */
    public function doAddDiscount($data, $form)
    {
        $code_to_search = $data['DiscountCode'];
        
        // First check if the discount is already added (so we don't
        // query the DB if we don't have to).
        if (!$this->discount_id || ($this->discount_id && $this->getDiscount()->Code != $code_to_search)) {
            $code = Discount::get()
                ->filter("Code", $code_to_search)
                ->exclude("Expires:LessThan", date("Y-m-d"))
                ->first();
            
            if ($code) {
                $this->discount_id = $code->ID;
            }
        }
        
        $this->save();
        
        return $this->redirectBack();
    }
    
    /**
     * Method that deals with get postage details and setting the
     * postage
     *
     * @param $data
     * @param $form
     */
    public function doSetPostage($data, $form)
    {
        $country = $data["Country"];
        $code = $data["ZipCode"];
        
        $this->setAvailablePostage($country, $code);
        
        $postage = Session::get("Checkout.AvailablePostage");
        
        // Check that postage is set, if not, see if we can set a default
        if (array_key_exists("PostageID", $data) && $data["PostageID"]) {
            // First is the current postage ID in the list of postage
            // areas
            if ($postage && $postage->exists() && $postage->find("ID", $data["PostageID"])) {
                $id = $data["PostageID"];
            } else {
                $id = $postage->first()->ID;
            }
            
            $data["PostageID"] = $id;
            Session::set("Checkout.PostageID", $id);
        } else {
            // Finally set the default postage
            if ($postage && $postage->exists()) {
                $data["PostageID"] = $postage->first()->ID;
                Session::set("Checkout.PostageID", $postage->first()->ID);
            }
        }
        
        // Set the form pre-populate data before redirecting
        Session::set("Form.{$form->FormName()}.data", $data);
        
        $url = Controller::join_links($this->Link(), "#{$form->FormName()}");
        
        return $this->redirect($url);
    }
}
