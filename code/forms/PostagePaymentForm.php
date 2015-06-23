<?php
/**
 * Description of CheckoutForm
 *
 * @author morven
 */
class PostagePaymentForm extends Form {
    
    public function __construct($controller, $name = "PostagePaymentForm") {
        
        if(!Checkout::config()->simple_checkout && !ShoppingCart::get()->isCollection()) {
            // Get delivery data and postage areas from session
            $delivery_data = Session::get("Checkout.DeliveryDetailsForm.data");
            $country = $delivery_data['DeliveryCountry'];
            $postcode = $delivery_data['DeliveryPostCode'];
            $postage_areas = Checkout::getPostageAreas($country, $postcode);

            // Loop through all postage areas and generate a new list
            $postage_array = array();
            foreach($postage_areas as $area) {
                $area_currency = new Currency("Cost");
                $area_currency->setValue($area->Cost);
                $postage_array[$area->ID] = $area->Title . " (" . $area_currency->Nice() . ")";
            }

            if(Session::get('Checkout.PostageID'))
                $postage_id = Session::get('Checkout.PostageID');
            elseif($postage_areas->exists())
                $postage_id = $postage_areas->first()->ID;
            else
                $postage_id = 0;

            if(count($postage_array)) {
                $select_postage_field = OptionsetField::create(
                    "PostageID",
                    _t('Checkout.PostageSelection', 'Please select your preferred postage'),
                    $postage_array
                )->setValue($postage_id);
            } else {
                $select_postage_field = ReadonlyField::create(
                    "NoPostage",
                    "",
                    _t('Checkout.NoPostageSelection', 'Unfortunatly we cannot deliever to your address')
                )->addExtraClass("label")
                ->addExtraClass("label-red");
            }

            // Setup postage fields
            $postage_field = CompositeField::create(
                HeaderField::create("PostageHeader", _t('Checkout.Postage',"Postage")),
                $select_postage_field
            )->setName("PostageFields")
            ->addExtraClass("unit")
            ->addExtraClass("size1of2")
            ->addExtraClass("unit-50");
        } elseif(ShoppingCart::get()->isCollection()) {
            $postage_field = CompositeField::create(
                HeaderField::create("PostageHeader", _t('Checkout.CollectionOnly',"Collection Only")),
                ReadonlyField::create(
                    "CollectionText",
                    "",
                    _t("Checkout.ItemsReservedInstore", "Your items will be held instore until you collect them")
                )
            )->setName("CollectionFields")
            ->addExtraClass("unit")
            ->addExtraClass("size1of2")
            ->addExtraClass("unit-50");
        } else {
            $postage_field = null;
        }

        // Get available payment methods and setup payment
        $payment_methods = ArrayList::create();
        
        foreach(SiteConfig::current_site_config()->PaymentMethods() as $payment_method) {
            if($payment_method->canView())
                $payment_methods->add($payment_method);
        }

        // Deal with payment methods
        if($payment_methods->exists()) {
            $payment_field = OptionsetField::create(
                'PaymentMethodID',
                _t('Checkout.PaymentSelection', 'Please choose how you would like to pay'),
                $payment_methods->map('ID','Label'),
                $payment_methods->filter('Default',1)->first()->ID
            );
        } else {
            $payment_field = ReadonlyField::create(
                "PaymentMethodID",
                _t('Checkout.PaymentSelection', 'Please choose how you would like to pay'),
                _t('Checkout.NoPaymentMethods', 'You cannot pay at this time, if you feel there has been an error please contact us.')
            );
        }

        $payment_field = CompositeField::create(
            HeaderField::create('PaymentHeading', _t('Checkout.Payment', 'Payment'), 2),
            $payment_field
        )->setName("PaymentFields")
        ->addExtraClass("unit")
        ->addExtraClass("size1of2")
        ->addExtraClass("unit-50");

        $fields = FieldList::create(
            CompositeField::create(
                $postage_field,
                $payment_field
            )->setName("PostagePaymentFields")
            ->addExtraClass("units-row")
            ->addExtraClass("line")
        );

        $back_url = $controller->Link("billing");
        
        if($payment_methods->exists()) {
            $actions = FieldList::create(
                LiteralField::create(
                    'BackButton',
                    '<a href="' . $back_url . '" class="btn btn-red checkout-action-back">' . _t('Checkout.Back','Back') . '</a>'
                ),

                FormAction::create('doContinue', _t('Checkout.PaymentDetails','Enter Payment Details'))
                    ->addExtraClass('btn')
                    ->addExtraClass('checkout-action-next')
                    ->addExtraClass('btn-green')
            );
        } else
            $actions = FieldList::create();

        $validator = RequiredFields::create(array(
            "PostageID",
            "PaymentMethod"
        ));

        parent::__construct($controller, $name, $fields, $actions, $validator);
    }

    public function doContinue($data) {
        Session::set('Checkout.PaymentMethodID', $data['PaymentMethodID']);
        Session::set("Checkout.PostageID", $data["PostageID"]);

        $url = Controller::join_links(
            Director::absoluteBaseUrl(),
            Payment_Controller::config()->url_segment
        );

        return $this
            ->controller
            ->redirect($url);
    }
}
