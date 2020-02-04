<?php

// Only load this if reports are active
if (class_exists("SS_Report")) {
    class OrdersReport extends SS_Report
    {

        public function title()
        {
            return _t("Orders.OrdersMade", "Orders made");
        }

        public function description()
        {
            return _t("Orders.OrdersReportDescription", "View reports on all orders made through this site");
        }

        public function columns()
        {
            return array(
                'OrderNumber' => '#',
                'Created' => 'Date',
                'SubTotal' => 'Sub Total',
                'PostageCost' => 'Postage',
                'TaxTotal' => 'Tax',
                'Total' => 'Total',
                'FirstName' => 'First Name(s)',
                'Surname' => 'Surname',
                'Email' => 'Email Address',
                'DeliveryAddress1' => 'Delivery:<br/>Address 1',
                'DeliveryAddress2' => 'Delivery:<br/>Address 2',
                'DeliveryCity' => 'Delivery:<br/>City',
                'DeliveryPostCode' => 'Delivery:<br/>Post Code',
                'DeliveryCountry' => 'Delivery:<br/>Country'
            );
        }

        public function exportColumns()
        {
            // Loop through all colls and replace BR's with spaces
            $cols = array();

            foreach ($this->columns() as $key => $value) {
                $cols[$key] = str_replace('<br/>', ' ', $value);
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
            // Check filters
            $where_filter = array();
            $db = DB::get_conn();
            $format = "%Y-%m-%d";
            $created = $db->formattedDatetimeClause(
                "LastEdited",
                $format
            );

            if (!empty($params['Filter_StartDate'])) {
                $start = new DateTime($params['Filter_StartDate']);
                $where_filter[] = $created . " >= '{$params['Filter_StartDate']}'";
            }
            if (!empty($params['Filter_EndDate'])) {
                $where_filter[] = $created . " <= '{$params['Filter_EndDate']}'";
            }
            if (!empty($params['Filter_Status'])) {
                $where_filter[] = "Status = '{$params['Filter_Status']}'";
            }
            if (!empty($params['Filter_Discount'])) {
                $discount = Discount::get()->byID($params['Filter_Discount']);
                $where_filter[] = "Discount = '{$discount->Title}'";
            }

            $limit = (isset($params['ResultsLimit']) && $params['ResultsLimit'] != 0) ? $params['ResultsLimit'] : '';

            $this->extend('updateSourceRecords', $params, $sort, $limit, $where_filter);

            $orders = Order::get()
                ->filter('ClassName', 'Order')
                ->where(implode(' AND ', $where_filter))
                ->limit($limit)
                ->sort($sort);

            return $orders;
        }

        public function parameterFields()
        {
            $fields = new FieldList();

            // Check if any order exist
            if (Order::get()->exists()) {
                $first_order = Order::get()
                    ->sort('Created ASC')
                    ->first();
                    
                $months = array('All');
                
                $statuses = Order::config()->statuses;
                array_unshift($statuses, 'All');
                
                for ($i = 1; $i <= 12; $i++) {
                    $months[] = date("F", mktime(0, 0, 0, $i + 1, 0, 0));
                }

                // Get the first order, then count down from current year to that
                $firstyear = new SS_Datetime('FirstDate');
                $firstyear->setValue($first_order->Created);
                
                $years = array();
                for ($i = date('Y'); $i >= $firstyear->Year(); $i--) {
                    $years[$i] = $i;
                }

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
                )->setConfig('showcalendar', true)
                ->setConfig('dateformat', 'y-mm-dd'));
                
                $fields->push(DateField::create(
                    'Filter_EndDate',
                    'Filter: EndDate'
                )->setConfig('showcalendar', true)
                ->setConfig('dateformat', 'y-mm-dd'));
                
                $fields->push(DropdownField::create(
                    'Filter_Status',
                    'Filter By Status',
                    $statuses
                ));

                $fields->push(DropdownField::create(
                    'Filter_Discount',
                    'Filter By Discount',
                    Discount::get()->map('ID', 'Title')
                )->setEmptyString('All'));
                
                $fields->push(DropdownField::create(
                    "ResultsLimit",
                    "Limit results to",
                    $result_limit_options
                ));
            }
            
            $this->extend('updateParameterFields', $fields);

            return $fields;
        }
    }
}
