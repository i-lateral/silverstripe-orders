<?php

use SilverStripe\Omnipay\GatewayInfo;

/**
 * Description of CheckoutForm
 *
 * @author morven
 */
class PostagePaymentForm extends Form
{
    
    public function __construct($controller, $name = "PostagePaymentForm")
    {
        $cart = ShoppingCart::get();
        
        if (!Checkout::config()->simple_checkout && !$cart->isCollection() && $cart->isDeliverable()) {
            // Get delivery data and postage areas from session
            $delivery_data = Session::get("Checkout.DeliveryDetailsForm.data");
            $country = $delivery_data['DeliveryCountry'];
            $postcode = $delivery_data['DeliveryPostCode'];
            
            $postage_areas = new ShippingCalculator($postcode, $country);
            $postage_areas
                ->setCost($cart->SubTotalCost)
                ->setWeight($cart->TotalWeight)
                ->setItems($cart->TotalItems);
                
            $postage_areas = $postage_areas->getPostageAreas();

            // Loop through all postage areas and generate a new list
            $postage_array = array();
            foreach ($postage_areas as $area) {
                $area_currency = new Currency("Cost");
                $area_currency->setValue($area->Cost);
                $postage_array[$area->ID] = $area->Title . " (" . $area_currency->Nice() . ")";
            }

            if (Session::get('Checkout.PostageID')) {
                $postage_id = Session::get('Checkout.PostageID');
            } elseif ($postage_areas->exists()) {
                $postage_id = $postage_areas->first()->ID;
            } else {
                $postage_id = 0;
            }

            if (count($postage_array)) {
                $select_postage_field = OptionsetField::create(
                    "PostageID",
                    _t('Checkout.PostageSelection', 'Please select your preferred postage'),
                    $postage_array
                )->setValue($postage_id);
            } else {
                $select_postage_field = ReadonlyField::create(
                    "NoPostage",
                    "",
                    _t('Checkout.NoPostageSelection', 'Unfortunately we cannot deliver to your address')
                )->addExtraClass("label")
                ->addExtraClass("label-red");
            }

            // Setup postage fields
            $postage_field = CompositeField::create(
                HeaderField::create("PostageHeader", _t('Checkout.Postage', "Postage")),
                $select_postage_field
            )->setName("PostageFields");
        } elseif ($cart->isCollection()) {
            $postage_field = CompositeField::create(
                HeaderField::create("PostageHeader", _t('Checkout.CollectionOnly', "Collection Only")),
                ReadonlyField::create(
                    "CollectionText",
                    "",
                    _t("Checkout.ItemsReservedInstore", "Your items will be held instore until you collect them")
                )
            )->setName("CollectionFields");
        } elseif (!$cart->isDeliverable()) {
            $postage_field = CompositeField::create(
                HeaderField::create(
                    "PostageHeader",
                    _t('Checkout.Postage', "Postage")
                ),
                ReadonlyField::create(
                    "CollectionText",
                    "",
                    _t("Checkout.NoDeliveryForOrder", "Your order does not contain items that can be posted")
                )
            )->setName("CollectionFields");
        } else {
            $postage_field = null;
        }

        $fields = FieldList::create();
        $actions = FieldList::create();

        try {
            // Get available payment methods and setup payment
            $payment_methods = GatewayInfo::getSupportedGateways();

            $payment_field = OptionsetField::create(
                'PaymentMethodID',
                _t('Checkout.PaymentSelection', 'Please choose how you would like to pay'),
                $payment_methods
            )->setTemplate("PaymentsOptionsetField");

            $actions
                ->add(FormAction::create(
                    'doContinue',
                    _t('Checkout.PaymentDetails', 'Enter Payment Details')
                )->addExtraClass('checkout-action-next'));
        } catch (Exception $e) {
            $payment_field = ReadonlyField::create(
                "PaymentMethodID",
                _t('Checkout.PaymentSelection', 'Please choose how you would like to pay'),
                $e->getMessage()
            );
        }

        $payment_field = CompositeField::create(
            HeaderField::create('PaymentHeading', _t('Checkout.Payment', 'Payment'), 2),
            $payment_field
        )->setName("PaymentFields");

        $fields->add(
            CompositeField::create(
                $postage_field,
                $payment_field
            )->setName("PostagePaymentFields")
            ->setColumnCount(2)
        );

        $validator = RequiredFields::create(array(
            "PostageID",
            "PaymentMethodID"
        ));

        parent::__construct(
            $controller,
            $name,
            $fields,
            $actions,
            $validator
        );
        
        $this->setTemplate($this->ClassName);
    }
    
    public function getBackURL() {
        return $this->controller->Link("billing");
    } 

    public function doContinue($data)
    {
        $cart = ShoppingCart::get();
        
        Session::set('Checkout.PaymentMethodID', $data['PaymentMethodID']);

        // Only set postage ID if required
        if (!Checkout::config()->simple_checkout && !$cart->isCollection() && $cart->isDeliverable()) {
            Session::set("Checkout.PostageID", $data["PostageID"]);
        }

        $url = Controller::join_links(
            Director::absoluteBaseUrl(),
            Payment_Controller::config()->url_segment
        );

        return $this
            ->controller
            ->redirect($url);
    }
}
