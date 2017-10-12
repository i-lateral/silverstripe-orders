<?php
/**
 * Find all OrderItems with customisations and convert them to using
 * OrderItemCustomisation associations instead
 *
 * @package orders
 * @subpackage tasks
 */
class OrderItemCustomisationMigrationTask extends MigrationTask {

    /**
     * Run this task during dev/build
     *
     * @var boolean
     * @config
     */
    private static $run_during_dev_build = true;
    
    protected $title = 'Convert OrderItems to use new customisations';

    protected $description = 'Find all OrderItems with customisations and convert them to using the OrderItemCustomisation association';

    public function up() {
        $this->log("Finding OrderItems to update...");

        $items = OrderItem::get();
        $converted = 0;
        $cleaned = 0;

        foreach ($items as $item) {
            if ($item->Customisation) {
                $curr_items = unserialize($item->Customisation);
                $clean = false;

                if ($curr_items instanceof ArrayList) {
                    if ($curr_items->count() == 0) {
                        $clean = true;
                    }

                    foreach($curr_items as $curr_item) {
                        $new_item = OrderItemCustomisation::create(array(
                            "Title" => $curr_item->Title,
                            "Value" => $curr_item->Value,
                            "Price" => $curr_item->Price
                        ));
                        $new_item->OrderItemID = $item->ID;
                        $new_item->write();
                        $clean = true;
                    }
                    $converted++;
                } elseif (is_array($curr_items)) {
                    if (count($curr_items) == 0) {
                        $clean = true;
                    }

                    foreach($curr_items as $curr_item) {
                        $new_item = OrderItemCustomisation::create(array(
                            "Title" => $curr_item["Title"],
                            "Value" => $curr_item["Value"],
                            "Price" => $curr_item["Price"]
                        ));
                        $new_item->OrderItemID = $item->ID;
                        $new_item->write();
                        $clean = true;
                    }
                    $converted++;
                }
                
                if ($clean) {
                    $item->Customisation = null;
                    $item->write();
                    $cleaned++;
                }
            }
        }

        $this->log("Converted {$converted} items");
        $this->log("Expunged {$cleaned} items");
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
