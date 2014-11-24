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

    private static $allowed_actions = array(
        "remove",
        "emptycart",
        "clear",
        "update",
        "usediscount",
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
     * @var ArrayList
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
        // Set postage data and save into a session
        $postage_areas = Checkout::getPostageAreas($country, $code);
        Session::set("Checkout.AvailablePostage", $postage_areas);

        return $this;
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
        
        $this->items = ArrayList::create();
        
        // If items are stored in a session, get them now
        if(Session::get('Checkout.ShoppingCart.Items'))
            $items = unserialize(Session::get('Checkout.ShoppingCart.Items'));
        else
            $items = ArrayList::create();
        
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
            
        // Add our unserialised item
        foreach($items as $item) {
            // Setup a price as currency (if it is set)
            if($item->Price) {
                $price = $item->Price;
                $item->Price = new Currency("Price");
                $item->Price->setValue($price);
            }
            
            // Calculate the discount
            if($this->discount) {
                if($item->Price->RAW() && $this->discount->Type == "Fixed" && $this->discount->Amount) {
                    $item->Discount = new Currency("Discount");
                    $item->Discount->setValue($this->discount->Amount / $items->count());
                } elseif($item->Price && $this->discount->Type == "Percentage" && $this->discount->Amount) {
                    $item->Discount = new Currency("Discount");
                    $item->Discount->setValue(($item->Price->RAW() / 100) * $this->discount->Amount);
                }
            }
            
            // If tax rate set work out tax
            if($item->TaxRate) {
                $item->Tax = new Currency("Tax");
                $item->Tax->setValue((($item->Price->RAW() - $item->Discount->RAW()) / 100) * $item->TaxRate);
            }
            
            $this->items->add($item);
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
        return $this
            ->owner
            ->renderWith('ViewCartButton');
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

        if(!empty($key)) {
            foreach($this->items as $item) {
                if($item->Key == $key)
                    $this->items->remove($item);
            }

            $this->save();
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
    public function add($object, $quantity = 1) {
        $added = false;

        // Make a string to match id's against ones already in the cart
        $key = ($object->Customisations) ? (int)$object->ID . ':' . base64_encode(serialize($object->Customisations)) : (int)$object->ID;

        // Check if object already in the cart and update quantity
        foreach($this->items as $item) {
            if($item->Key == $key) {
                $this->update($item->Key, ($item->Quantity + $quantity));
                $added = true;
            }
        }

        // If no update was sucessfull then add to cart items
        if(!$added) {
            $object->Key = $key;
            $object->Quantity = $quantity;
            
            $this->extend("onBeforeAdd", $object);

            $this->items->add($object);
            $this->save();

            $this->extend("onAfterAdd", $object);
        }
    }

    /**
     * Find an existing item and update its quantity
     *
     * @param Item
     * @param Quantity
     */
    public function update($item_key, $quantity) {
        foreach($this->items as $item) {
            if ($item->Key === $item_key) {
                $this->extend("onBeforeUpdate", $item);

                $item->Quantity = $quantity;
                $this->save();

                $this->extend("onAfterUpdate", $item);
                return true;
            }
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
        
        // Setup our items so they are suitable for storage
        $items = ArrayList::create();
        
        foreach($this->Items as $item) {
            if($item->Price && $item->Price instanceOf Currency)
                $item->Price = $item->Price->RAW();
            
            $items->add($item);
        }
        
        // Extend our save operation
        $this->extend("onBeforeSave", $items);

        // Save cart items
        Session::set(
            "Checkout.ShoppingCart.Items",
            serialize($items)
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
    public function TotalWeight() {
        $total = 0;
        $return = new Decimal();

        foreach($this->items as $item) {
            if($item->Weight && $item->Quantity)
                $total = $total + ($item->Weight * $item->Quantity);
        }
        
        $this->extend("updateTotalWeight", $total);
        
        $return->setValue($total);
        return $return;
    }

    /**
     * Find the total quantity of items in the shopping cart
     *
     * @return Int
     */
    public function TotalItems() {
        $total = 0;
        $return = new Int();

        foreach($this->items as $item) {
            $total = ($item->Quantity) ? $total + $item->Quantity : 0;
        }
        
        $this->extend("updateTotalItems", $total);

        $return->setValue($total);
        return $return;
    }

    /**
     * Find the cost of all items in the cart, without any tax.
     *
     * @return Currency
     */
    public function SubTotalCost() {
        $total = 0;
        $return = new Currency();

        foreach($this->items as $item) {
            if($item->Price && $item->Quantity)
                $total = $total + ($item->Quantity * $item->Price->RAW());
        }
        
        $this->extend("updateSubTotalCost", $total);

        $return->setValue($total);
        return $return;
    }

    /**
     * Get the cost of postage
     *
     * @return Currency
     */
    public function PostageCost() {
        $total = 0;
        $return = new Currency();
        
        if($this->postage) $total = $this->postage->Cost;
            
        $this->extend("updatePostageCost", $total);

        $return->setValue($total);
        return $return;
    }

    /**
     * Find the total discount based on discount items added.
     *
     * @return Currency
     */
    public function DiscountAmount() {
        $total = 0;
        $return = new Currency();
        
        foreach($this->items as $item) {
            if($item->Discount) $total += $item->Discount->RAW();
        }
        
        $return->setValue($total);
        
        $this->extend("updateDiscountAmount", $return);

        return $return;
    }

    /**
     * Find the total cost of tax for the items in the cart, as well as shipping
     * (if set)
     *
     * @return Currency
     */
    public function TaxCost() {
        $total = 0;
        $return = new Currency();

        foreach($this->items as $item) {
            if($item->Tax && $item->Quantity)
                $total += ($item->Quantity * $item->Tax->RAW());
            
        }

        if($this->postage && $this->postage->Cost && $this->postage->Tax)
            $total += ($this->postage->Cost / 100) * $this->postage->Tax;
        
        
        $return->setValue($total);

        $this->extend("updateTaxCost", $return);
        
        return $return;
    }

    /**
     * Find the total cost of for all items in the cart, including tax and
     * shipping (if applicable)
     *
     * @return Currency
     */
    public function TotalCost() {
        $return = new Currency();
        
        $subtotal = $this->SubTotalCost()->RAW();
        $discount = $this->DiscountAmount()->RAW();
        $tax = 0;
        
        foreach($this->items as $item) {
            if($item->Tax && $item->Quantity)
                $tax += ($item->Quantity * $item->Tax->RAW());
        }
        
        $postage = $this->PostageCost()->RAW();
        
        if($this->postage && $this->postage->Cost && $this->postage->Tax)
            $postage_tax = ($this->postage->Cost / 100) * $this->postage->Tax;
        else
            $postage_tax = 0;

        $total = (($subtotal - $discount) + $tax) + $postage + $postage_tax;

        $this->extend("updateTotalCost", $total);

        $return->setValue($total);
        return $return;
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

            // Setup default postage fields
            $country_select = CompositeField::create(
                CountryDropdownField::create('Country',_t('Checkout.Country','Country')),
                TextField::create("ZipCode",_t('Checkout.ZipCode',"Zip/Postal Code"))
            )->addExtraClass("size1of2")
            ->addExtraClass("unit")
            ->addExtraClass("unit-50");

            // If we have stipulated a search, then see if we have any results
            // otherwise load empty fieldsets
            if($available_postage) {
                $search_text = _t('Checkout.Update',"Update");

                // Loop through all postage areas and generate a new list
                $postage_array = array();
                foreach($available_postage as $area) {
                    $area_currency = new Currency("Cost");
                    $area_currency->setValue($area->Cost);
                    $postage_array[$area->ID] = $area->Title . " (" . $area_currency->Nice() . ")";
                }

                $postage_select = CompositeField::create(
                    OptionsetField::create(
                        "PostageID",
                        _t('Checkout.SelectPostage',"Select Postage"),
                        $postage_array
                    )
                )->addExtraClass("size1of2")
                ->addExtraClass("unit")
                ->addExtraClass("unit-50");

                $confirm_action = CompositeField::create(
                    FormAction::create("doSavePostage", _t('Checkout.Confirm',"Confirm"))
                        ->addExtraClass('btn')
                        ->addExtraClass('btn-green')
                )->addExtraClass("size1of2")
                ->addExtraClass("unit")
                ->addExtraClass("unit-50");
            } else {
                $search_text = _t('Checkout.Search',"Search");
                $postage_select = CompositeField::create()
                    ->addExtraClass("size1of2")
                    ->addExtraClass("unit")
                    ->addExtraClass("unit-50");
                $confirm_action = CompositeField::create()
                    ->addExtraClass("size1of2")
                    ->addExtraClass("unit")
                    ->addExtraClass("unit-50");
            }

            // Set search field
            $search_action = CompositeField::create(
                FormAction::create("doGetPostage", $search_text)
                    ->addExtraClass('btn')
            )->addExtraClass("size1of2")
            ->addExtraClass("unit")
            ->addExtraClass("unit-50");


            // Setup fields and actions
            $fields = new FieldList(
                CompositeField::create($country_select,$postage_select)
                    ->addExtraClass("line")
                    ->addExtraClass("units-row")
            );

            $actions = new FieldList(
                CompositeField::create($search_action,$confirm_action)
                    ->addExtraClass("line")
                    ->addExtraClass("units-row")
            );

            $required = RequiredFields::create(array(
                "Country",
                "ZipCode"
            ));

            $form = Form::create($this, 'PostageForm', $fields, $actions, $required)
                ->addExtraClass('forms')
                ->addExtraClass('forms-inline');

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
                        if($value > 0) {
                            $this->update($cart_item->Key, $value);
                        } else
                            $this->remove($cart_item->Key);
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
     * Search and find applicable postage rates based on submitted data
     *
     * @param $data
     * @param $form
     */
    public function doGetPostage($data, $form) {
        $country = $data["Country"];
        $code = $data["ZipCode"];

        $this->setAvailablePostage($country, $code);

        // Set the form pre-populate data before redirecting
        Session::set("Form.{$form->FormName()}.data", $data);

        $url = Controller::join_links($this->Link(),"#{$form->FormName()}");

        return $this->redirect($url);
    }

    /**
     * Save applicable postage data to session
     *
     * @param $data
     * @param $form
     */
    public function doSavePostage($data, $form) {
        Session::set("Checkout.PostageID", $data["PostageID"]);

        $url = Controller::join_links($this->Link(),"#{$form->FormName()}");

        return $this->redirect($url);
    }
}


