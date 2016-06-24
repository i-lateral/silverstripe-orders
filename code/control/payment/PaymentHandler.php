<?php

/**
 * Abstract class that contains methods for processing interactions with a
 * particular payment class
 *
 */
abstract class PaymentHandler extends Controller
{
    
    private static $allowed_actions = array(
        "index",
        "callback"
    );
    
    /**
     * Set up the "restful" URLs
     *
     * @config
     * @var array
     */
    private static $url_handlers = array(
        '$Action/$ID' => 'handleAction',
    );
    
    /**
     * The current payment gateway we are using
     *
     * @var PaymentMethod
     */
    protected $payment_gateway;

    public function getPaymentGateway()
    {
        return $this->payment_gateway;
    }

    public function setPaymentGateway($gateway)
    {
        $this->payment_gateway = $gateway;
        return $this;
    }

    /**
     * An object of the current order data we are dealing with, this can
     * be ArrayData, or a DataObject.
     *
     * @var Object
     */
    protected $order_data;

    public function getOrderData()
    {
        return $this->order_data;
    }

    public function setOrderData($data)
    {
        $this->order_data = $data;
        return $this;
    }
    
    
    /**
     * An object of the current payment data. This can be tapped into
     * via extensions to find out what the gateway returned and then
     * used to update orders.
     * 
     * The standard format is to return an object with the folowing
     * paramaters set:
     * 
     *   -  OrderID (ID of the order just completed)
     *   -  PaymentID (The ID of the payment at the gateway)
     *   -  Status (status of the payment)
     *   -  GatewayData (the raw data from the payment gateway)
     *
     * @var Object
     */
    protected $payment_data;

    public function getPaymentData()
    {
        return $this->payment_data;
    }

    public function setPaymentData($data)
    {
        $this->payment_data = $data;
        return $this;
    }
    
    public function handleRequest(SS_HTTPRequest $request, DataModel $model)
    {
        if (!$request) {
            user_error("Controller::handleRequest() not passed a request!", E_USER_ERROR);
        }
        
        $this->urlParams = $request->allParams();
        $this->request = $request;
        $this->setDataModel($model);
        
        // Find our action or set to index if not found
        $action = $this->request->param("Action");
        if (!$action) {
            $action = "index";
        }

        $result = $this->$action($request);

        // Try to determine what response we are dealing with
        if($result instanceof SS_HTTPResponse) {
            $this->response = $result;
        } else {
            $this->response = new SS_HTTPResponse();
            $this->response->setBody($result);
        }

        // If we had a redirection or something, halt processing.
        if ($this->response->isFinished()) {
            return $this->response;
        }

        ContentNegotiator::process($this->response);
        HTTP::add_cache_headers($this->response);

        return $this->response;
    }

    /**
     * The index action is called by the payment controller before order
     * is processed by relevent payment gateway.
     *
     * This action should return a rendered response that will then be
     * directly reterned by the payment controller.
     * 
     * @param $request Current request object
     */
    abstract public function index($request);


    /**
     * Retrieve and process callback info from the payment gateway.
     *
     * This action is called directly from the payment controller and
     * should return either a rendered template or a response (such as a
     * redirect).
     * 
     * @param $request Current request object
     */
    abstract public function callback($request);
}
