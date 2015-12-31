<?php

class OrdersMemberExtension extends DataExtension
{
    
    private static $has_many = array(
        "Orders"        => "Order"
    );

    /**
     * Get all orders that have been generated and are marked as paid or
     * processing
     *
     * @return DataList
     */
    public function getOutstandingOrders()
    {
        return $this
            ->owner
            ->Orders()
            ->filter(array(
                "Status" => array("paid", "processing")
            ));
    }

    /**
     * Get all orders that have been generated and are marked as dispatched or
     * canceled
     *
     * @return DataList
     */
    public function getHistoricOrders()
    {
        return $this
            ->owner
            ->Orders()
            ->filter(array(
                "Status" => array("dispatched", "canceled")
            ));
    }
}
