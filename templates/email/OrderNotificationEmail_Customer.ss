<% with $Order %>
    <% if $Status == 'failed' %>
        <p><%t Orders.FailedNotice 'Unfortunately we could not process your order. Please contact us to complete your order.' %></p>
    <% else %>
        <h1><%t Orders.ThankYou 'Thank you for ordering from {title}' title=$Up.SiteConfig.Title %></h1>

        <p><%t Orders.OrderMarkedAs 'Order {ordernumber} has been marked as {status}' ordernumber=$OrderNumber status=$Status %></p>

        <h2><%t Orders.ItemsOrdered 'Items Ordered' %></h2>
        
        <table style="width: 100%;">
            <thead>
                <tr>
                    <td><%t Orders.Details "Details" %></td>
                    <td><%t Orders.Quantity "Quantity" %></td>
                </tr>
            </thead>

            <tbody><% loop $Items() %>
                <tr>
                    <td>
                        <strong>{$Title}</strong>
                        $CustomisationHTML
                    </td>
                    <td>{$Quantity}</td>
                </tr>
            <% end_loop %></tbody>
        </table>

        <h2><%t Orders.DeliveryDetails 'Delivery Details' %></h2>
        
        <p>
            <%t Orders.OrderDispatchedTo "The order is to be dispatched to" %><br/>
            {$BillingFirstnames} {$BillingSurname}<br/>
            {$DeliveryAddress1},<br/>
            <% if $DeliveryAddress1 %>{$DeliveryAddress2},<br/><% end_if %>
            {$DeliveryCity},<br/>
            {$DeliveryPostCode},<br/>
            {$DeliveryCountry}
        </p>

        <p>
            <%t Orders.CustomerEmailFooter 'Many thanks' %>,<br/><br/>
            {$Up.SiteConfig.Title}
        </p>
    <% end_if %>
<% end_with %>
