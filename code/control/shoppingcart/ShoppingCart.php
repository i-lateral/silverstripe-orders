<?php

/**
 * Holder for items in the shopping cart and interacting with them, as
 * well as rendering these items into an interface that allows editing
 * of items,
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package checkout
 */
class ShoppingCart extends Controller {

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
     * Name of the current controller. Mostly used in templates.
     *
     * @var string
     * @config
     */
    private static $item_class = "ShoppingCartItem";

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
     * Overwrite the default title for this controller which is taken
     * from the translation files. This is used for Title and MetaTitle
     * variables in templates.
     *
     * @var string
     * @config
     */
    private static $title;

    /**
     * Track all items stored in the current shopping cart
     *
     * @var ArrayList
     */
    protected $items;

    /**
     * Track a discount object placed against this cart
     *
     * @var Discount
     */
    protected $discount;
    
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
    public function getClassName() {
        return self::config()->class_name;
    }

    public function getTitle() {
        return ($this->config()->title) ? $this->config()->title : _t("Checkout.CartName", "Shopping Cart");
    }

    public function getMetaTitle() {
        return $this->getTitle();
    }

    public function getShowDiscountForm() {
        return $this->config()->show_discount_form;
    }

    public function getItems() {
        return $this->items;
    }

    public function getDiscount() {
        return $this->discount;
    }

    public function setDiscount(Discount $discount) {
        $this->discount = $discount;
    }
        
    /**
     * Get the link to this controller
     * 
     * @return string
     */
    public function Link($action = null) {
        return Controller::join_links(
            Director::BaseURL(),
            $this->config()->url_segment,
            $action
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
    public function setAvailablePostage($country, $code) {
        
        $postage_areas = new ShippingCalculator($code, $country);
        
        $postage_areas
            ->setCost($this->SubTotalCost)
            ->setWeight($this->TotalWeight)
            ->setItems($this->TotalItems);
            
        $postage_areas = $postage_areas->getPostageAreas();
        
        Session::set("Checkout.AvailablePostage", $postage_areas);

        return $this;
    }
    
    /**
     * Are we collecting the current cart? If click and collect is
     * disabled then this returns false, otherwise checks if the user
     * has set this via a session.
     * 
     * @return Boolean
     */
    public function isCollection() {
        if(Checkout::config()->click_and_collect) {
            $type = Session::get("Checkout.Delivery");
            
            return ($type == "collect") ? true : false;
        } else
            return false;
    }

    /**
     * Shortcut for ShoppingCart::create, exists because create()
     * doesn't seem quite right.
     *
     * @return ShoppingCart
     */
    public static function get() {
        return ShoppingCart::create();
    }


    public function __construct() {
        parent::__construct();
        
        // If items are stored in a session, get them now
        if(Session::get('Checkout.ShoppingCart.Items'))
            $this->items = unserialize(Session::get('Checkout.ShoppingCart.Items'));
        else
            $this->items = ArrayList::create();
        
        // If discounts stored in a session, get them, else create new list
        if(Session::get('Checkout.ShoppingCart.Discount'))
            $this->discount = unserialize(Session::get('Checkout.ShoppingCart.Discount'));

        // If we don't have any discounts, a user is logged in and he has
        // access to discounts through a group, add the discount here
        if(!$this->discount && Member::currentUserID()) {
            $member = Member::currentUser();
            $this->discount = $member->getDiscount();
            Session::set('Checkout.ShoppingCart.Discount', serialize($this->discount));
        }
        
        // Setup postage
        if($postage = PostageArea::get()->byID(Session::get("Checkout.PostageID")))
            $this->postage = $postage;
        
        // Allow extension of the shopping cart after initial setup
        $this->extend("augmentSetup");
    }
    
    /**
     * Return a rendered button for the shopping cart
     *
     * @return string
     */
    public function getViewCartButton(){
        return $this->renderWith('ViewCartButton');
    }

    /**
     * Actions for this controller
     */

    /**
     * Default acton for the shopping cart
     */
    public function index() {
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
    public function remove() {
        $key = $this->request->param('ID');
        $title = "";

        if(!empty($key)) {
            foreach($this->items as $item) {
                if($item->Key == $key) {
                    $title = $item->Title;
                    $this->items->remove($item);
                }
            }

            $this->save();
            
            if($title) {
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
    public function emptycart() {
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
    public function usediscount() {
        $this->extend("onBeforeUseDiscount");

        $code_to_search = $this->request->param("ID");
        $code = false;

        if(!$code_to_search)
            return $this->httpError(404, "Page not found");

        // First check if the discount is already added (so we don't
        // query the DB if we don't have to).
        if(!$this->discount || ($this->discount && $this->discount->Code != $code_to_search)) {
            $codes = Discount::get()
                ->filter("Code", $code_to_search)
                ->exclude("Expires:LessThan", date("Y-m-d"));

            if($codes->exists()) {
                $code = $codes->first();
                $this->discount = $code;
                $this->save();
            }
        } elseif($this->discount && $this->discount->Code == $code_to_search)
            $code = $this->discount;

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
    public function setdeliverytype() {
        $this->extend("onBeforeSetDeliveryType");

        $type = $this->request->param("ID");
        
        if($type && in_array($type, array("deliver", "collect"))) {
            Session::set("Checkout.Delivery", $type);
            Session::clear("Checkout.PostageID");
        }
        
        $this->extend("onAfterSetDeliveryType");
        
        $this->redirectBack();
    }

    /**
     * Add an item to the shopping cart. To make this process as generic
     * as possible, we require that an object is submitted. This object
     * can have any params, but by default we usually use:
     * 
     * "Title": The Title to appear in the cart
     * "Content": A description of the item
     * "Price": Our item's base price
     * "Image": Image to display in cart
     * "Customisations": array of customisations
     * "ID": Unique identifier for this object
     * 
     * @param $object Object that we will add to the shopping cart
     * @param $quantity Number of these objects to add
     */
    public function add($data, $quantity = 1) {
        if(array_key_exists("Key", $data)) {
            $added = false;
            $item_key = $data['Key'];

            // Check if object already in the cart and update quantity
            foreach($this->items as $item) {
                if($item->Key == $item_key) {
                    $this->update($item->Key, ($item->Quantity + $quantity));
                    $added = true;
                }
            }

            // If no update was sucessfull then add to cart items
            if(!$added) {
                $cart_item = self::config()->item_class;
                $cart_item = $cart_item::create();
                
                foreach($data as $key => $value) {                
                    $cart_item->$key = $value;
                }
                
                $cart_item->Key = $item_key;
                $cart_item->Quantity = $quantity;
                
                $this->extend("onBeforeAdd", $cart_item);

                $this->items->add($cart_item);
                $this->save();
            }
        }
    }

    /**
     * Find an existing item and update its quantity
     *
     * @param Item
     * @param Quantity
     */
    public function update($item_key, $quantity) {
        $item = $this
            ->items
            ->find("Key", $item_key);
        
        if($item) {
            $item->Quantity = $quantity;
            
            $this->extend("onBeforeUpdate", $item);
            
            $this->save();
        }

        return false;
     }

    /**
     * Empty the shopping cart object of all items.
     *
     */
    public function removeAll() {
        foreach($this->items as $item) {
            $this->items->remove($item);
        }
    }

    /**
     * Save the current products list and postage to a session.
     *
     */
    public function save() {
        Session::clear("Checkout.PostageID");
        
        // Extend our save operation
        $this->extend("onBeforeSave");

        // Save cart items
        Session::set(
            "Checkout.ShoppingCart.Items",
            serialize($this->items)
        );

        // Save cart discounts
        Session::set(
            "Checkout.ShoppingCart.Discount",
            serialize($this->discount)
        );

        // Update available postage
        if($data = Session::get("Form.Form_PostageForm.data")) {
            $country = $data["Country"];
            $code = $data["ZipCode"];
            $this->setAvailablePostage($country, $code);
        }
    }

    /**
     * Clear the shopping cart object and destroy the session. Different to
     * empty, as that retains the session.
     *
     */
    public function clear() {
        Session::clear('Checkout.ShoppingCart.Items');
        Session::clear('Checkout.ShoppingCart.Discount');
        Session::clear("Checkout.PostageID");
    }
    
    /**
     * Shortcut to checkout config, to allow us to access it via
     * templates
     * 
     * @return boolean
     */
    public function ShowTax() {
        return Checkout::config()->show_tax;
    }

    /**
     * Find the total weight of all items in the shopping cart
     *
     * @return Decimal
     */
    public function getTotalWeight() {
        $total = 0;

        foreach($this->items as $item) {
            if($item->Weight && $item->Quantity)
                $total = $total + ($item->Weight * $item->Quantity);
        }
        
        return $total;
    }

    /**
     * Find the total quantity of items in the shopping cart
     *
     * @return Int
     */
    public function getTotalItems() {        
        $total = 0;
        
        foreach($this->items as $item) {
            $total += ($item->Quantity) ? $item->Quantity : 1;
        }

        return $total;
    }

    /**
     * Find the cost of all items in the cart, without any tax.
     *
     * @return Currency
     */
    public function getSubTotalCost() {
        $total = 0;

        foreach($this->items as $item) {
            if($item->SubTotal) $total += $item->SubTotal;
        }
        
        return $total;
    }

    /**
     * Get the cost of postage
     *
     * @return Currency
     */
    public function getPostageCost() {
        $total = 0;
        
        if($this->postage) $total = $this->postage->Cost;
        
        return $total;
    }

    /**
     * Find the total discount based on discount items added.
     *
     * @return Currency
     */
    public function getDiscountAmount() {
        $total = 0;
        $discount = 0;
        
        foreach($this->items as $item) {
            if($item->Price)
                $total += ($item->Price * $item->Quantity);
            
            if($item->Discount)
                $discount += ($item->TotalDiscount);
        }
        
        if($discount > $total) $discount = $total;

        return $discount;
    }

    /**
     * Find the total cost of tax for the items in the cart, as well as shipping
     * (if set)
     *
     * @return Currency
     */
    public function getTaxCost() {
        $total = 0;

        foreach($this->items as $item) {            
            if($item->TotalTax) $total += $item->TotalTax;
        }

        if($this->postage && $this->postage->Cost && $this->postage->Tax)
            $total += ($this->postage->Cost / 100) * $this->postage->Tax;
        
        return $total;
    }

    /**
     * Find the total cost of for all items in the cart, including tax and
     * shipping (if applicable)
     *
     * @return Currency
     */
    public function getTotalCost() {
        $subtotal = $this->SubTotalCost;
        $discount = $this->DiscountAmount;
        $postage = $this->PostageCost;
        $tax = $this->TaxCost;

        return ($subtotal - $discount) + $postage + $tax;
    }


    /**
     * Form responsible for listing items in the shopping cart and
     * allowing management (such as addition, removal, etc)
     *
     * @return Form
     */
    public function CartForm() {
        $fields = new FieldList();

        $actions = new FieldList(
            FormAction::create('doUpdate', _t('Checkout.UpdateCart','Update Cart'))
                ->addExtraClass('btn')
                ->addExtraClass('btn-blue')
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
    public function DiscountForm() {
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
            FormAction::create('doAddDiscount', _t('Checkout.Add','Add'))
                ->addExtraClass('btn')
                ->addExtraClass('btn-blue')
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
    public function PostageForm() {
        if(!Checkout::config()->simple_checkout) {
            $available_postage = Session::get("Checkout.AvailablePostage");
            
            // Setup form
            $form = Form::create(
                $this,
                'PostageForm',
                $fields = new FieldList(
                    CountryDropdownField::create(
                        'Country',
                        _t('Checkout.Country','Country')
                    ),
                    TextField::create(
                        "ZipCode",
                        _t('Checkout.ZipCode',"Zip/Postal Code")
                    )
                ),
                $actions = new FieldList(
                    FormAction::create(
                        "doSetPostage",
                        _t('Checkout.Search',"Search")
                    )->addExtraClass('btn')
                    ->addExtraClass('btn btn-green')
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
            if($available_postage && $available_postage->exists()) {
                // Loop through all postage areas and generate a new list
                $postage_array = array();
                
                foreach($available_postage as $area) {
                    $area_currency = new Currency("Cost");
                    $area_currency->setValue($area->Cost);
                    $postage_array[$area->ID] = $area->Title . " (" . $area_currency->Nice() . ")";
                }

                $fields->add(OptionsetField::create(
                    "PostageID",
                    _t('Checkout.SelectPostage',"Select Postage"),
                    $postage_array
                ));

                $actions
                    ->dataFieldByName("action_doSetPostage")
                    ->setTitle(_t('Checkout.Update',"Update"));
            }

            // Check if the form has been re-posted and load data
            $data = Session::get("Form.{$form->FormName()}.data");
            if(is_array($data)) $form->loadDataFrom($data);

            // Check if the postage area has been set, if so, Set Postage ID
            $data = array();
            $data["PostageID"] = Session::get("Checkout.PostageID");
            if(is_array($data)) $form->loadDataFrom($data);

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
    public function doUpdate($data, $form) {
        foreach($this->items as $cart_item) {
            foreach($data as $key => $value) {
                $sliced_key = explode("_", $key);
                if($sliced_key[0] == "Quantity") {
                    if(isset($cart_item) && ($cart_item->Key == $sliced_key[1])) {
                        try {
                            if($value > 0) {
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
                        } catch(ValidationException $e) {
                            $this->setSessionMessage(
                                "bad",
                                $e->getMessage()
                            );
                        } catch(Exception $e) {
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
    public function doAddDiscount($data, $form) {
        $code_to_search = $data['DiscountCode'];

        // First check if the discount is already added (so we don't
        // query the DB if we don't have to).
        if(!$this->discount || ($this->discount && $this->discount->Code != $code_to_search)) {
            $code = Discount::get()
                ->filter("Code", $code_to_search)
                ->exclude("Expires:LessThan", date("Y-m-d"))
                ->first();

            if($code) $this->discount = $code;
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
    public function doSetPostage($data, $form) {
        $country = $data["Country"];
        $code = $data["ZipCode"];

        $this->setAvailablePostage($country, $code);
        
        $postage = Session::get("Checkout.AvailablePostage");

        // Check that postage is set, if not, see if we can set a default
        if(array_key_exists("PostageID", $data) && $data["PostageID"]) {
            
            // First is the current postage ID in the list of postage
            // areas
            if($postage && $postage->exists() && $postage->find("ID", $data["PostageID"]))
                $id = $data["PostageID"];
            else
                $id = $postage->first()->ID;
                
            $data["PostageID"] = $id;
            Session::set("Checkout.PostageID", $id);
        } else {
            // Finally set the default postage
            if($postage && $postage->exists()) {
                $data["PostageID"] = $postage->first()->ID;
                Session::set("Checkout.PostageID", $postage->first()->ID);
            }
        }

        // Set the form pre-populate data before redirecting
        Session::set("Form.{$form->FormName()}.data", $data);
        
        $url = Controller::join_links($this->Link(),"#{$form->FormName()}");

        return $this->redirect($url);
    }
}


