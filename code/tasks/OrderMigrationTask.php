<?php
/**
 * Find all OrderItems with customisations and convert them to using
 * OrderItemCustomisation associations instead
 *
 * @package orders
 * @subpackage tasks
 */
class OrderMigrationTask extends MigrationTask {

    /**
     * Run this task during dev/build
     *
     * @var boolean
     * @config
     */
    private static $run_during_dev_build = true;
    
    protected $title = 'Update old orders to match new system';

    protected $description = 'Find all orders using "DeliveryFirstnames" and update them to use "DeliveryFirstName"';

    public function up() {
        $this->log("Finding Orders to update...");

        $orders = Order::get();
        $updated = 0;

        foreach ($orders as $order) {
            if ($order->DeliveryFirstnames && !$order->DeliveryFirstName) {
                $order->DeliveryFirstName = $order->DeliveryFirstnames;
                $order->DeliveryFirstnames = null;
                $order->write();
                $updated++;
            }
        }

        $this->log("Updated {$updated} items");
    }

    public function log($message)
    {
        if (Director::is_cli()) {
            echo "{$message}\n";
        } else {
            echo "{$message}<br />";
        }
    }

}
