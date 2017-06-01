<?php

if (class_exists("Order")) {
    /**
     * Add association to an order to payments and add
     * setting order status on capture.
     * 
     * @author Mo <morven@ilateral.co.uk>
     * @package checkout
     * @subpackage extensions
     */
    class CheckoutPaymentExtension extends DataExtension
    {
        private static $has_one = array(
            'Order' => 'Order'
        );

        /**
         * Process attached order when payment is taken
         *
         * @param ServiceResponse $response
         * @return void
         */
        public function onCaptured($response)
        {
            if ($this->owner->OrderID) {
                $order = $this->owner->Order();

                $order->convertToOrder();
                $order->write();

                $order = Order::get()->byID($order->ID);
                $order->PaymentProvider = $this->owner->Gateway;
                $order->markComplete($this->owner->TransactionReference);
                $order->write();
            }
        }

        /**
         * Process attached order when payment is refunded
         *
         * @param ServiceResponse $response
         * @return void
         */
        public function onRefunded($response)
        {
            if ($this->owner->OrderID) {
                $order = $this->owner->Order();
                $order->Status = "refunded";
                $order->write();
            }
        }

        /**
         * Process attached order when payment is voided/cancelled
         *
         * @param ServiceResponse $response
         * @return void
         */
        public function onVoid($response)
        {
            if ($this->owner->OrderID) {
                $order = $this->owner->Order();
                $order->Status = "cancelled";
                $order->write();
            }
        }
    }
}