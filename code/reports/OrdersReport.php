<?php

// Only load this if reports are active
if (class_exists("SS_Report")) {
    class OrdersReport extends SS_Report
    {
        const DEFAULT_PAST = '-7 days';

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
                'Created' => 'Created',
                'LastEdited' => 'Last Edited',
                'PaymentGateway' => 'Gateway',
                'PaymentDate' => 'Payment Date',
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
            $filter = array('ClassName' => 'Order');
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

            if (!empty($params['Filter_Discount'])) {
                $discount = Discount::get()->byID($params['Filter_Discount']);
                $filter["Discount"] = $discount->Title;
            }

            if (empty($params['ResultsLimit'])) {
                $limit = '';
            } else {
                $limit = $params['ResultsLimit'];
            }

            $this->extend('updateSourceRecords', $params, $sort, $limit, $where_filter);

            $orders = Order::get()
                ->filter($filter)
                ->where($date_filter)
                ->limit($limit)
                ->sort($sort);

            return $orders;
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

            $fields->push(
                DateField::create(
                    'Filter_StartDate',
                    'Filter: StartDate'
                )->setConfig('showcalendar', true)
            );
            
            $fields->push(
                DateField::create(
                    'Filter_EndDate',
                    'Filter: EndDate'
                )->setConfig('showcalendar', true)
            );
            
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

            Config::inst()->update(
                DateField::class,
                'default_config',
                $default_config
            );

            return $fields;
        }
    }
}
