<?php

// Only load this if reports are active
if (class_exists("SS_Report")) {
    class OrderItemReport extends SS_Report
    {
        const DEFAULT_PAST = '-7 days';
        
        public function title()
        {
            return _t("Orders.OrderItems", "Items ordered");
        }

        public function description()
        {
            return _t("Orders.OrderItemReportDescription", "View all individual products ordered through this site");
        }

        public function columns()
        {
            return array(
                "StockID" => "StockID",
                "Details" => "Details",
                "Price" => "Price",
                "Quantity" => "Quantity"
            );
        }

        public function exportColumns()
        {
            // Loop through all colls and replace BR's with spaces
            $cols = array();

            foreach ($this->columns() as $key => $value) {
                $cols[$key] = $value;
            }

            return $cols;
        }

        public function sortColumns()
        {
            return array();
        }

        public function getReportField()
        {
            $gridField = parent::getReportField();

            // Edit CSV export button
            $export_button = $gridField->getConfig()->getComponentByType('GridFieldExportButton');
            $export_button->setExportColumns($this->exportColumns());

            return $gridField;
        }

        public function sourceRecords($params, $sort, $limit)
        {
            $return = ArrayList::create();
            $filter = array();
            $db = DB::get_conn();
            $format = "%Y-%m-%d";
            $created = $db->formattedDatetimeClause(
                "Created",
                $format
            );

            if (empty($params['Filter_StartDate'])) {
                $past = new DateTime(self::DEFAULT_PAST);
            } else {
                $past = new DateTime($params['Filter_StartDate']);
            }

            if (empty($params['Filter_EndDate'])) {
                $now = new DateTime();
            } else {
                $now = new DateTime($params['Filter_EndDate']);
            }

            $date_filter = [
                $created . ' <= ?' =>  $now->format("Y-m-d"),
                $created . ' >= ?' =>  $past->format("Y-m-d")
            ];

            if (!empty($params['Filter_Status'])) {
                $filter["Status"] = $params['Filter_Status'];
            }
    
            if (!empty($params['Filter_FirstName'])) {
                $filter['FirstName'] = $params['Filter_FirstName'];
            }
            if (!empty($params['Filter_Surname'])) {
                $filter['Surname'] = $params['Filter_Surname'];
            }

            $orders = Order::get()
                ->filter($filter)
                ->where($date_filter);

            foreach ($orders as $order) {
                // Setup a filter for our order items
                $filter = array();

                if (!empty($params['Filter_ProductName'])) {
                    $filter["Title:PartialMatch"] = $params['Filter_ProductName'];
                }

                if (!empty($params['Filter_StockID'])) {
                    $filter["StockID"] = $params['Filter_StockID'];
                }

                $list = (count($filter)) ? $order->Items()->filter($filter) : $order->Items();

                foreach ($list as $order_item) {
                    if ($order_item->StockID) {
                        if ($list_item = $return->find("StockID", $order_item->StockID)) {
                            $list_item->Quantity = $list_item->Quantity + $order_item->Quantity;
                        } else {
                            $report_item = OrderItemReportItem::create();
                            $report_item->ID = $order_item->StockID;
                            $report_item->StockID = $order_item->StockID;
                            $report_item->Details = $order_item->Title;
                            $report_item->Price = $order_item->Price;
                            $report_item->Quantity = $order_item->Quantity;

                            $return->add($report_item);
                        }
                    }
                }
            }

            return $return;
        }

        public function parameterFields()
        {
            // Ensure date fields are set to the correct format
            $default_config = Config::inst()->get(
                DateField::class,
                'default_config'
            );
            $update_config = $default_config;
            $update_config['dateformat'] = 'yyyy-MM-dd';
            $update_config['datavalueformat'] = 'yyyy-MM-dd';

            Config::inst()->update(
                DateField::class,
                'default_config',
                $update_config
            );

            $fields = new FieldList();

            // Order Status
            $statuses = Order::config()->statuses;
            array_unshift($statuses, 'All');

            //Result Limit
            $result_limit_options = array(
                0 => 'All',
                50 => 50,
                100 => 100,
                200 => 200,
                500 => 500,
            );

            $fields->push(DateField::create(
                'Filter_StartDate',
                'Filter: StartDate'
            )->setConfig('showcalendar', true));
            
            $fields->push(DateField::create(
                'Filter_EndDate',
                'Filter: EndDate'
            )->setConfig('showcalendar', true));

            $fields->push(TextField::create(
                'Filter_FirstName',
                'Customer First Name'
            ));
            
            $fields->push(TextField::create(
                'Filter_Surname',
                'Customer Surname'
            ));
            
            $fields->push(TextField::create(
                'Filter_StockID',
                'Stock ID'
            ));
            
            $fields->push(TextField::create(
                'Filter_ProductName',
                'Product Name'
            ));
            
            $fields->push(DropdownField::create(
                'Filter_Status',
                'Filter By Status',
                $statuses
            ));
            
            $fields->push(DropdownField::create(
                "ResultsLimit",
                "Limit results to",
                $result_limit_options
            ));

            Config::inst()->update(
                DateField::class,
                'default_config',
                $default_config
            );

            return $fields;
        }
    }
}

/**
 * Item that can be loaded into an OrderItem report
 *
 */
class OrderItemReportItem extends SS_Object
{

    public $ClassName = "OrderItemReportItem";

    public $StockID;
    public $Details;
    public $Price;
    public $Quantity;

    public function canView($member = null)
    {
        return true;
    }
}
