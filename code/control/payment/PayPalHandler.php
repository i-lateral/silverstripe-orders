<?php

class PayPalHandler extends PaymentHandler {

    public function index($request) {
        
        $this->extend('onBeforeIndex');
        
        $site = SiteConfig::current_site_config();
        $order = $this->getOrderData();
        $cart = ShoppingCart::get();

        // Setup the paypal gateway URL
        if(Director::isDev())
            $gateway_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        else
            $gateway_url = "https://www.paypal.com/cgi-bin/webscr";

        $callback_url = Controller::join_links(
            Director::absoluteBaseURL(),
            Payment_Controller::config()->url_segment,
            "callback",
            $this->payment_gateway->ID
        );

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

        $back_url = Controller::join_links(
            Director::absoluteBaseURL(),
            Checkout_Controller::config()->url_segment,
            "finish"
        );

        $fields = new FieldList(
            // Account details
            HiddenField::create('business', null, $this->payment_gateway->BusinessID),
            HiddenField::create('item_name', null, $site->Title),
            HiddenField::create('cmd', null, "_cart"),
            HiddenField::create('paymentaction', null, "sale"),
            HiddenField::create('invoice', null, $order->OrderNumber),
            HiddenField::create('custom', null, $order->OrderNumber), //Track the order number in the paypal custom field
            HiddenField::create('upload', null, 1),
            HiddenField::create('discount_amount_cart', null, number_format($cart->DiscountAmount, 2)),

            // Currency details
            HiddenField::create('currency_code', null, Checkout::config()->currency_code),

            // Payee details
            HiddenField::create('first_name', null, $order->FirstName),
            HiddenField::create('last_name', null, $order->Surname),
            HiddenField::create('address1', null, $order->Address1),
            HiddenField::create('address2', null, $order->Address2),
            HiddenField::create('city', null, $order->City),
            HiddenField::create('zip', null, $order->PostCode),
            HiddenField::create('country', null, $order->Country),
            HiddenField::create('email', null, $order->Email),
            
            // Shipping Details
            HiddenField::create('shipping_addressee_name', null, $order->DeliveryFirstnames . " " . $order->DeliverySurname),
            HiddenField::create('shipping_address1', null, $order->DeliveryAddress1),
            HiddenField::create('shipping_address2', null, $order->DeliveryAddress2),
            HiddenField::create('shipping_city', null, $order->DeliveryCity),
            HiddenField::create('shipping_zip', null, $order->DeliveryPostCode),
            HiddenField::create('shipping_country', null, $order->DeliveryCountry),

            // Notification details
            HiddenField::create('return', null, $success_url),
            HiddenField::create('notify_url', null, $callback_url),
            HiddenField::create('cancel_return', null, $error_url)
        );

        $i = 1;

        foreach($cart->getItems() as $item) {
            $fields->add(HiddenField::create('item_name_' . $i, null, $item->Title));
            $fields->add(HiddenField::create('amount_' . $i, null, number_format($item->Price,2)));
            $fields->add(HiddenField::create('quantity_' . $i, null, $item->Quantity));

            $i++;
        }
        
        if(!Checkout::config()->simple_checkout) {
            // Add shipping as an extra product
            $fields->add(HiddenField::create('item_name_' . $i, null, $order->PostageType));
            $fields->add(HiddenField::create('amount_' . $i, null, number_format($cart->PostageCost, 2)));
            $fields->add(HiddenField::create('quantity_' . $i, null, "1"));
        }
        
        // Add tax (if needed) else just total
        if($cart->TaxCost) {
            $fields->add(HiddenField::create(
                'tax_cart',
                null,
                number_format($cart->TaxCost, 2)
            ));
        }

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
        
        $this->extend('onAfterIndex');
        
        return $this->renderWith(array(
            "Payment_PayPal",
            "Payment",
            "Checkout",
            "Page"
        ));
    }

    /**
     * Process the callback data from the payment provider
     */
    public function callback($request) {
        
        $this->extend('onBeforeCallback');
        
        $data = $this->request->postVars();
        $status = "error";
        $order_id = 0;
        $payment_id = 0;
        
        $this->extend('onBeforeCallback');

        $error_url = Controller::join_links(
            Director::absoluteBaseURL(),
            Payment_Controller::config()->url_segment,
            'complete',
            'error'
        );

        // Check if CallBack data exists and install id matches the saved ID
        if(isset($data) && isset($data['custom']) && isset($data['payment_status'])) {
            $order_id = $data['custom'];
            $paypal_request = 'cmd=_notify-validate';
            $final_response = "";
            
            // If the transaction ID is set, keep it
            if(array_key_exists("txn_id", $data)) $payment_id = $data["txn_id"];
            
            $listener = new IpnListener();
            
            if(Director::isDev()) $listener->use_sandbox = true;

            try {
                $verified = $listener->processIpn($data);
            } catch (Exception $e) {
                error_log("Exception caught: " . $e->getMessage());
                return $this->httpError(500);
            }

            if ($verified) {
                // IPN response was "VERIFIED"
                switch($data['payment_status']) {
                    case 'Canceled_Reversal':
                        $status = "canceled";
                        break;
                    case 'Completed':
                        $status = "paid";
                        break;
                    case 'Denied':
                        $status = "failed";
                        break;
                    case 'Expired':
                        $status = "failed";
                        break;
                    case 'Failed':
                        $status = "failed";
                        break;
                    case 'Pending':
                        $status = "pending";
                        break;
                    case 'Processed':
                        $status = "pending";
                        break;
                    case 'Refunded':
                        $status = "refunded";
                        break;
                    case 'Reversed':
                        $status = "canceled";
                        break;
                    case 'Voided':
                        $status = "canceled";
                        break;
                }
            } else {
                return $this->httpError(500);
            }
            
        } else {
            return $this->httpError(500);
        }
        
        $payment_data = ArrayData::array_to_object(array(
            "OrderID" => $order_id,
            "PaymentID" => $payment_id,
            "Status" => $status,
            "GatewayData" => $data
        ));
        
        $this->setPaymentData($payment_data);
        
        $this->extend('onAfterCallback');
        
        return $this->httpError(200);
    }
}
