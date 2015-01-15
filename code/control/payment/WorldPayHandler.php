<?php

class WorldPayHandler extends PaymentHandler {

    public function index() {
        // Setup payment gateway form
        $data = $this->order_data;
        $order = ArrayData::create($data);
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
            HiddenField::create('region', null, $order->State),
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
        
        if(!Checkout::config()->simple_checkout) {
            // Add postage type to description
            $desc_string .= _t("Checkout.Postage", "Postage") . ': ' . $order->PostageType . '; ';
            
            // Add postage address to description
            $desc_string .= _t("Checkout.PostTo", "Post to") . ': ';
            $desc_string .= $order->DeliveryFirstnames . " " . $order->DeliverySurname . ', ';
            $desc_string .= $order->DeliveryAddress1 . ', ';
            $desc_string .= ($order->DeliveryAddress2) ? $order->DeliveryAddress2 . ', ' : '';
            $desc_string .= $order->DeliveryCity . ', ';
            $desc_string .= ($order->DeliveryState) ? $order->DeliveryState . ', ' : '';
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

        $this->extend('updateForm',$form);
        
        $this->parent_controller->setOrder($order);
        $this->parent_controller->setPaymentForm($form);

        return array(
            "Title"     => _t('Checkout.Summary',"Summary"),
            "MetaTitle" => _t('Checkout.Summary',"Summary")
        );
    }

    /**
     * Retrieve and process order data from the request
     */
    public function callback() {
        $data = $this->request->postVars();
        $order_id = null;
        $status = 'error';

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
            $status = $data['transStatus'];

            if($data['transStatus'] == 'Y') {
                $status = 'paid';
                $vars["RedirectURL"] = $success_url;
            } else {
                $status = 'failed';
            }
        }

        return array(
            "OrderID" => $order_id,
            "Status" => $status,
            "GatewayData" => $data,
            "Template" => $this->renderWith(array("PaymentWorldPay"), $vars),
        );
    }

}
