<?php

/**
 * Add association to an order to payments and add
 * setting order status on capture.
 * 
 * @author Mo <morven@ilateral.co.uk>
 * @package checkout
 * @subpackage extensions
 */
class OrdersPaymentExtension extends DataExtension
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
        $order = $this->owner->Order();

        if ($order->exists()) {    
            $payment_amount = Checkout::round_up($this->owner->getAmount(), 2);
            $order_amount = Checkout::round_up($order->Total, 2);

            // First ensure we have an order (not an estimate)
            if ($order instanceof Estimate) {            
                $order->convertToOrder();
                $order->write();
                $order = Order::get()->byID($order->ID);
            }

            // If our payment is the value of the order, mark paid
            // else mark part paid
            if (abs(($payment_amount - $order_amount) / $order_amount) < 0.00001) {
                $order->markPaid();
            } else {
                $order->markPartPaid();
            }

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
        $order = $this->owner->Order();

        if ($order->exists()) {
            $order->markRefunded();
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
        $order = $this->owner->Order();

        if ($order->exists()) {
            $order->markRefunded();
            $order->write();
        }
    }
}