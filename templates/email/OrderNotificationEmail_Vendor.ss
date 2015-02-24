<% include OrderEmailHead %>

<% with $Order %>
    <h1><%t Orders.OrderStatusUpdate "Order Status Update" %></h1>
    
    <p><%t Orders.OrderMarkedAs 'Order {ordernumber} has been marked as {status}' ordernumber=$OrderNumber status=$Status %></p>
    
    <hr/>

    <h2><%t Orders.Items "Items" %></h2>

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

    <h2><%t Orders.CustomerDetails "Customer Details" %></h2>

    <p>
        <%t Orders.Name "Name" %>: {$FirstName} {$Surname}<br/>
        <% if $Phone %><%t Orders.Phone "Phone" %>: {$Phone}<br/><% end_if %>
        <% if $Email %><%t Orders.Email "Email" %>: <a href="mailto:{$Email}">{$Email}</a><br/><% end_if %>
    </p>
        
    <hr/>

    <h2><%t Orders.DeliveryDetails 'Delivery Details' %></h2>
    
    <p>
        {$FirstName} {$Surname}<br/>
        {$DeliveryAddress1},<br/>
        <% if $DeliveryAddress2 %>{$DeliveryAddress2},<br/><% end_if %>
        {$DeliveryCity},<br/>
        {$DeliveryPostCode},<br/>
        {$DeliveryCountryFull}
    </p>
<% end_with %>

<% include OrderEmailFooter %>
