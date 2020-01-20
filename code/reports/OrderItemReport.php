<?php

// Only load this if reports are active
if (class_exists("SS_Report")) {
    class OrderItemReport extends SS_Report
    {
        
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

            if (!isset($params['Filter_Start'])) {
                $start = new DateTime();
                $start->modify("-1 year");
            } else {
                $start = new DateTime($params['Filter_Start']);
            };

            if (!isset($params['Filter_End'])) {
                $end = new DateTime();
            } else {
                $end = new DateTime($params['Filter_End']);
            };

            // Modify start/end to include ALL of today
            $start->modify("today");
            $end->modify("tomorrow");
            $end->modify("-1 second");

            // Only show events assigned to you
            $filter = [
                'ClassName' => 'Order',
                "Created:GreaterThanOrEqual" => $start->format("Y-m-d H:i:s"),
                "Created:LessThanOrEqual" => $end->format("Y-m-d H:i:s")
            ];

            if (!empty($params['Filter_Status'])) {
                $filter['Status'] = $params['Filter_Status'];
            }
            if (!empty($params['Filter_FirstName'])) {
                $filter['FirstName'] = $params['Filter_FirstName'];
            }
            if (!empty($params['Filter_Surname'])) {
                $filter['Surname'] = $params['Filter_Surname'];
            }

            $orders = Order::get()
                ->filter($filter);

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
            $fields = new FieldList();

            if (class_exists("Subsite")) {
                $first_order = Subsite::get_from_all_subsites("Order")
                    ->sort('Created', 'ASC')
                    ->first();
            } else {
                $first_order = Order::get()
                    ->sort('Created', 'ASC')
                    ->first();
            }

            // Check if any order exist
            if ($first_order) {
                // Order Status
                $statuses = Order::config()->statuses;
                array_unshift($statuses, 'All');

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
                
                $fields->push(DateField::create(
                    'Filter_Start',
                    'start Date'
                )->setConfig('showcalendar', true));
                
                $fields->push(DateField::create(
                    'Filter_End',
                    'End Date'
                )->setConfig('showcalendar', true));
                
                $fields->push(DropdownField::create(
                    'Filter_Status',
                    'Order Status',
                    $statuses
                ));
            }

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
