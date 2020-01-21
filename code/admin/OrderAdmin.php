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
     * @return array
     */
    public function getExportFields()
    {
        $obj = singleton($this->modelClass);
        
        if (isset($obj->config()->export_fields)) {
            $return = $obj->config()->export_fields;
        } else {
            $return = $obj->summaryFields();
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
        $query = $this->getSearchContext();
        $params = $this->getRequest()->requestVar('q');
        $db = DB::get_conn();
        if ($params) {
            if (array_key_exists("Start", $params)) {
                $start = $params["Start"];
            }
            if (array_key_exists("End", $params)) {
                $end = $params["End"];
            }
        

            $format = "%Y-%m-%d";
            $start_field = $db->formattedDatetimeClause(
                '"Order"."Created"',
                $format
            );
            if (isset($start) && isset($end)) {
                $list = $list->where(
                    [
                        $start_field . ' >= ?' => $start,
                        $start_field . ' <= ?' => $end
                    ]
                );
            } else if (isset($start)) {
                $list = $list->where(
                    [
                        $start_field . ' >= ?' => $start
                    ]
                );
            } else if (isset($end)) {
                $list = $list->where(
                    [
                        $start_field . ' <= ?' => $end
                    ]
                );
            }
        }
        
        // Ensure that we only show Order objects in the order tab
        if ($this->modelClass == "Order") {
            $list = $list
                ->addFilter(array("ClassName" => "Order"));
        }
                
        $this->extend("updateList", $list);

        return $list;
    }

    public function SearchForm()
    {
        $form = parent::SearchForm();
        $fields = $form->Fields();
        $query = $this->getSearchContext();
        $params = $this->getRequest()->requestVar('q');

        $fields->add(
            $start_field = DateField::create("q[Start]", 'Created After')
                ->setConfig('showcalendar', true)
        );

        if ($params && array_key_exists("Start", $params)) {
            $start_field->setValue($params["Start"]);
        }
        
        $fields->add(
            $start_field = DateField::create("q[End]", "Created Before")
                ->setConfig('showcalendar', true)
        );

        if ($params && array_key_exists("End", $params)) {
            $start_field->setValue($params["End"]);
        }

        $form->loadDataFrom($query);

        return $form;
    }
}
