<?php
 /**
  * Add interface to manage orders through the CMS
  *
  * @package Commerce
  */
class OrderAdmin extends ModelAdmin
{

    private static $url_segment = 'orders';

    private static $menu_title = 'Orders';

    private static $menu_priority = 4;

    private static $managed_models = array(
        'Order' => array("title" => "Orders"),
        'Estimate' => array("title" => "Estimates"),
        'Payment' => array("title" => "Payments")
    );

    private static $model_importers = array();
    
    /**
     * For an order, export all fields by default
     * 
     */
    public function getExportFields()
    {
        if ($this->modelClass == 'Order') {
            $return = array(
                "OrderNumber"       => "#",
                "Status"            => "Status",
                "Created"           => "Created",
                "Company"           => "Company Name",
                "FirstName"         => "First Name(s)",
                "Surname"           => "Surname",
                "Email"             => "Email",
                "PhoneNumber"       => "Phone Number",
                "ItemSummary"       => "Items Ordered",
                "SubTotal"          => "SubTotal",
                "Postage"           => "Postage",
                "TaxTotal"          => "TaxTotal",
                "Total"             => "Total",
                "Address1"          => "Billing Address 1",
                "Address2"          => "Billing Address 2",
                "City"              => "Billing City",
                "PostCode"          => "Billing Post Code",
                "CountryFull"       => "Billing Country",
                "DeliveryFirstnames"=> "Delivery First Name(s)",
                "DeliverySurname"   => "Delivery Surname",
                "DeliveryAddress1"  => "Delivery Address 1",
                "DeliveryAddress2"  => "Delivery Address 2",
                "DeliveryCity"      => "Delivery City",
                "DeliveryPostCode"  => "Delivery Post Code",
                "DeliveryCountryFull"=> "Delivery Country",
                "DiscountAmount"    => "Discount Amount",
                "PostageType"       => "Postage Type",
                "PostageCost"       => "Postage Cost",
                "PostageTax"        => "Postage Tax",
            );
        } else {
            $return = singleton($this->modelClass)->summaryFields();
        }

        $extend = $this->extend("updateExportFields", $return);

        if ($extend && is_array($extend)) {
            $return = $extend;
        }

        return $return;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $fields = $form->Fields();
        $config = null;
        
        // Bulk manager
        $manager = new GridFieldBulkManager();
        $manager->removeBulkAction("bulkEdit");
        $manager->removeBulkAction("unLink");


        // Manage orders
        if ($this->modelClass == 'Order') {
            $gridField = $fields->fieldByName('Order');
            $config = $gridField->getConfig();

            $manager->addBulkAction(
                'cancelled',
                'Mark Cancelled',
                'OrdersFieldBulkActions'
            );
            
            $manager->addBulkAction(
                'paid',
                'Mark Paid',
                'OrdersFieldBulkActions'
            );

            $manager->addBulkAction(
                'processing',
                'Mark Processing',
                'OrdersFieldBulkActions'
            );

            $manager->addBulkAction(
                'dispatched',
                'Mark Dispatched',
                'OrdersFieldBulkActions'
            );

            // Update list of items for subsite (if used)
            if (class_exists('Subsite')) {
                $list = $gridField
                    ->getList()
                    ->filter(array(
                        'SubsiteID' => Subsite::currentSubsiteID()
                    ));

                $gridField->setList($list);
            }
        }
        
        
        // Manage Estimates
        if ($this->modelClass == 'Estimate') {
            $gridField = $fields->fieldByName('Estimate');
            $config = $gridField->getConfig();

            // Update list of items for subsite (if used)
            if (class_exists('Subsite')) {
                $list = $gridField
                    ->getList()
                    ->filter(array(
                        'SubsiteID' => Subsite::currentSubsiteID()
                    ));

                $gridField->setList($list);
            }
        }
        
        // Set our default detailform and bulk manager
        if ($config) {
            $config
                ->removeComponentsByType('GridFieldDetailForm')
                ->addComponent($manager)
                ->addComponent(new OrdersGridFieldDetailForm());
        }

        $this->extend("updateEditForm", $form);

        return $form;
    }
    
    public function getList()
    {
        $list = parent::getList();
        
        // Ensure that we only show Order objects in the order tab
        if ($this->modelClass == "Order") {
            $list = $list
                ->addFilter(array("ClassName" => "Order"));
        }
                
        $this->extend("updateList", $list);

        return $list;
    }
}
