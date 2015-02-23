<% include OrderEmailHead %>

<% with $Order %>
    <% if $Status == 'failed' %>
        <p><%t Orders.FailedNotice 'Unfortunately we could not process your order. Please contact us to complete your order.' %></p>
    <% else %>
        <h1><%t Orders.ThankYou 'Thank you for ordering from {title}' title=$Up.SiteConfig.Title %></h1>

        <p><%t Orders.OrderMarkedAs 'Order {ordernumber} has been marked as {status}' ordernumber=$OrderNumber status=$Status %></p>
        
        <hr/>

        <h2><%t Orders.ItemsOrdered 'Items Ordered' %></h2>
        
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th style="text-align: left"><%t Orders.Details "Details" %></th>
                    <th style="text-align: center"><%t Orders.QTY "Qty" %></th>
                </tr>
            </thead>

            <tbody><% loop $Items %>
                <tr>
                    <td>
                        {$Title} <% if $StockID %>($StockID)<% end_if %><br/>
                        <em>$CustomisationHTML</em>
                    </td>
                    <td style="text-align: center">{$Quantity}</td>
                </tr>
            <% end_loop %></tbody>
        </table>
        
        <hr/>

        <h2><%t Orders.DeliveryDetails 'Delivery Details' %></h2>
        
        <p>
            {$BillingFirstnames} {$BillingSurname}<br/>
            {$DeliveryAddress1},<br/>
            <% if $DeliveryAddress2 %>{$DeliveryAddress2},<br/><% end_if %>
            {$DeliveryCity},<br/>
            {$DeliveryPostCode},<br/>
            {$DeliveryCountry}
        </p>
        
        <hr/>

        <p>
            <%t Orders.CustomerEmailFooter 'Many thanks' %>,<br/><br/>
            {$Up.SiteConfig.Title}
        </p>
    <% end_if %>
<% end_with %>

<% include OrderEmailFooter %>
