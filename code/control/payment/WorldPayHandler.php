<?php

class WorldPayHandler extends PaymentHandler {

    public function index($request) {
        
        $this->extend('onBeforeIndex');
        
        // Setup payment gateway form
        $order = $this->getOrderData();
        $cart = ShoppingCart::get();
        
        // Setup the gateway URL
        if(Director::isDev())
            $gateway_url = "https://secure-test.worldpay.com/wcc/purchase";
        else
            $gateway_url = "https://secure.worldpay.com/wcc/purchase ";

        $callback_url = Controller::join_links(
            Director::absoluteBaseURL(),
            Payment_Controller::config()->url_segment,
            "callback",
            $this->payment_gateway->ID
        );

        $back_url = Controller::join_links(
            Director::absoluteBaseURL(),
            Checkout_Controller::config()->url_segment,
            "finish"
        );

        $fields = FieldList::create(
            // Account details
            HiddenField::create('instId', null, $this->payment_gateway->InstallID),
            HiddenField::create('cartId', null, $order->OrderNumber),
            HiddenField::create('MC_callback', null, $callback_url),

            // Amount and Currency details
            HiddenField::create('amount', null, number_format($cart->TotalCost,2)),
            HiddenField::create('currency', null, Checkout::config()->currency_code),

            // Payee details
            HiddenField::create('name', null, $order->FirstName . " " . $order->Surname),
            HiddenField::create('address1', null, $order->Address1),
            HiddenField::create('address2', null, $order->Address2),
            HiddenField::create('town', null, $order->City),
            HiddenField::create('region', null, $order->Country),
            HiddenField::create('postcode', null, $order->PostCode),
            HiddenField::create('country', null, $order->Country),
            HiddenField::create('email', null, $order->Email),
            HiddenField::create('tel', null, $order->PhoneNumber)
        );

        // Create a string of items ordered (to manage the order via WorldPay)
        $desc_string = "";
        
        foreach($cart->getItems() as $item) {
            $desc_string .= $item->Title . ' x ' . $item->Quantity . ', ';
        }
        
        if(!Checkout::config()->simple_checkout && !$cart->isCollection()) {
            // Add postage type to description
            $desc_string .= _t("Checkout.Postage", "Postage") . ': ' . $order->PostageType . '; ';
            
            // Add postage address to description
            $desc_string .= _t("Checkout.PostTo", "Post to") . ': ';
            $desc_string .= $order->DeliveryFirstnames . " " . $order->DeliverySurname . ', ';
            $desc_string .= $order->DeliveryAddress1 . ', ';
            $desc_string .= ($order->DeliveryAddress2) ? $order->DeliveryAddress2 . ', ' : '';
            $desc_string .= $order->DeliveryCity . ', ';
            $desc_string .= ($order->DeliveryCountry) ? $order->DeliveryCountry . ', ' : '';
            $desc_string .= $order->DeliveryPostCode . ', ';
            $desc_string .= $order->DeliveryCountry;
        }

        $fields->add(HiddenField::create('desc', null, $desc_string));

        if(Director::isDev())
            $fields->add(HiddenField::create('testMode', null, '100'));

        $actions = FieldList::create(
            LiteralField::create('BackButton','<a href="' . $back_url . '" class="btn btn-red checkout-action-back">' . _t('Checkout.Back','Back') . '</a>'),
            FormAction::create('Submit', _t('Checkout.ConfirmPay','Confirm and Pay'))
                ->addExtraClass('btn')
                ->addExtraClass('btn-green')
        );

        $form = Form::create($this,'Form',$fields,$actions)
            ->addExtraClass('forms')
            ->setFormMethod('POST')
            ->setFormAction($gateway_url);
        
        $this->customise(array(
            "Title"     => _t('Checkout.Summary',"Summary"),
            "MetaTitle" => _t('Checkout.Summary',"Summary"),
            "Form"      => $form,
            "Order"     => $order
        ));
        
        $this->extend("onAfterIndex");
        
        return $this->renderWith(array(
            "Worldpay",
            "Payment",
            "Checkout",
            "Page"
        ));
    }

    /**
     * Retrieve and process order data from the request
     */
    public function callback($request) {
        
        $this->extend('onBeforeCallback');
        
        $data = $this->request->postVars();
        $status = "error";
        $order_id = 0;
        $payment_id = 0;

        $success_url = Controller::join_links(
            Director::absoluteBaseURL(),
            Payment_Controller::config()->url_segment,
            'complete'
        );

        $error_url = Controller::join_links(
            Director::absoluteBaseURL(),
            Payment_Controller::config()->url_segment,
            'complete',
            'error'
        );

        $vars = array(
            "SiteConfig" => SiteConfig::current_site_config(),
            "RedirectURL" => $error_url
        );

        // Check if CallBack data exists and install id matches the saved ID
        if(
            isset($data) && // Data and order are set
            (isset($data['instId']) && isset($data['cartId']) && isset($data['transStatus']) && isset($data["callbackPW"])) && // check required
            $this->payment_gateway->InstallID == $data['instId'] && // The current install ID matches the postback ID
            $this->payment_gateway->ResponsePassword == $data["callbackPW"]
        ) {
            $order_id = $data['cartId'];
            $payment_id = $data['transId'];
            $status = $data['transStatus'];

            if($data['transStatus'] == 'Y') {
                $status = 'paid';
                $vars["RedirectURL"] = $success_url;
            } else {
                $status = 'failed';
            }
        } else
            return $this->httpError(500);
        
        $payment_data = ArrayData::array_to_object(array(
            "OrderID" => $order_id,
            "PaymentProvider" => "WorldPay",
            "PaymentID" => $payment_id,
            "Status" => $status,
            "GatewayData" => $data
        ));
        
        $this
            ->setPaymentData($payment_data)
            ->customise($vars);
        
        $this->extend('onAfterCallback');
        
        return $this->renderWith(array("Worldpay_callback"));
    }

}
