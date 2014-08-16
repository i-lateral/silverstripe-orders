<?php

class OrdersLeftAndMainExtension extends LeftAndMainExtension {
    public function init() {
        parent::init();

        Requirements::css('orders/css/admin.css');
    }
}
