<?php

class OrdersUserAccountControllerExtension extends Extension {
    private static $allowed_actions = array(
        "history",
        "outstanding",
        "order"
    );
    
    /**
     * Display all historic orders for the current user
     *
     */
    public function history() {
        $member = Member::currentUser();
        $orders = new PaginatedList(
            $member->getHistoricOrders(),
            $this->owner->request
        );

        $this->owner->customise(array(
            "ClassName" => "AccountPage",
            "Title" => _t('Orders.OrderHistory','Order History'),
            "Orders" => $orders
        ));

        return $this->owner->renderWith(array(
            "UserAccount_history",
            "UserAccount",
            "Page"
        ));
    }

    /**
     * Display all outstanding orders for the current user
     *
     */
    public function outstanding() {
        $member = Member::currentUser();
        $orders = new PaginatedList(
            $member->getOutstandingOrders(),
            $this->owner->request
        );

        $this->owner->customise(array(
            "ClassName" => "AccountPage",
            "Title" => _t('Orders.OutstandingOrders','Outstanding Orders'),
            "Orders" => $orders
        ));

        return $this->owner->renderWith(array(
            "UserAccount_outstanding",
            "UserAccount",
            "Page"
        ));
    }

    /**
     * Display the currently selected order from the URL
     *
     */
    public function order() {
        $orderID = $this->owner->request->param("ID");
        $order = Order::get()->byID($orderID);

        $this->owner->customise(array(
            "ClassName" => "AccountPage",
            "Order" => $order
        ));

        return $this->owner->renderWith(array(
            "UserAccount_order",
            "UserAccount",
            "Page"
        ));
    }

    /**
     * Add commerce specific links to account menu
     *
     */
    public function updateAccountMenu($menu) {
        $menu->add(new ArrayData(array(
            "ID"    => 1,
            "Title" => _t('Orders.OutstandingOrders','Outstanding Orders'),
            "Link"  => $this->owner->Link("outstanding")
        )));

        $menu->add(new ArrayData(array(
            "ID"    => 2,
            "Title" => _t('Orders.OrderHistory',"Order history"),
            "Link"  => $this->owner->Link("history")
        )));
    }
}
