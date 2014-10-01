<?php

/**
 * Abstract class that contains methods for processing interactions with a
 * particular payment class
 *
 */
abstract class PaymentHandler extends Controller {

    /**
     * The owner of this controller
     *
     * @var Controller
     */
    protected $parent_controller;

    public function getParentController() {
        return $this->parent_controller;
    }

    public function setParentController($parent) {
        $this->parent_controller = $parent;
        return $this;
    }
    
    /**
     * The current payment gateway we are using
     *
     * @var PaymentMethod
     */
    protected $payment_gateway;

    public function getPaymentGateway() {
        return $this->payment_gateway;
    }

    public function setPaymentGateway($gateway) {
        $this->payment_gateway = $gateway;
        return $this;
    }

    /**
     * The current order data we are dealing with
     *
     * @var Order
     */
    protected $order_data;

    public function getOrderData() {
        return $this->order_date;
    }

    public function setOrderData($data) {
        $this->order_data = $data;
        return $this;
    }

    public function getPaymentInfo() {
        return $this->payment_gateway->PaymentInfo;
    }

    /**
     * The index action is called by the payment controller before order
     * is processed by relevent payment gateway.
     *
     * This action should return a rendered response that will then be
     * directly reterned by the payment controller.
     */
    abstract public function index();


    /**
     * Retrieve and process callback info from the payment gateway.
     *
     * This action is called directly from the payment controller and
     * should return either a rendered template or a response (such as a
     * redirect).
     */
    abstract public function callback();
}
