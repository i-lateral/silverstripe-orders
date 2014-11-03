<% with $Order %>
    <h1><%t Orders.OrderStatusUpdate "Order Status Update" %></h1>
    
    <p><%t Orders.OrderMarkedAs 'Order {ordernumber} has been marked as {status}' ordernumber=$OrderNumber status=$Status %></p>
    
    <h2><%t Orders.Items "Items" %></h2>

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

    <h2><%t Orders.CustomerDetails "Customer Details" %></h2>

    <p>
        <%t Orders.Name "Name" %>: {$FirstName} {$Surname}<br/>
        <% if $Phone %><%t Orders.Phone "Phone" %>: {$Phone}<br/><% end_if %>
        <% if $Email %><%t Orders.Email "Email" %>: {$Email}<br/><% end_if %>
    </p>

    <h2><%t Orders.DeliveryDetails 'Delivery Details' %></h2>
    
    <p>
        <%t Orders.OrderDispatchedTo "The order is to be dispatched to" %><br/>
        <br/>
        {$FirstName} {$Surname}<br/>
        {$DeliveryAddress1},<br/>
        <% if $DeliveryAddress1 %>{$DeliveryAddress2},<br/><% end_if %>
        {$DeliveryCity},<br/>
        {$DeliveryPostCode},<br/>
        {$DeliveryCountry}
    </p>
<% end_with %>
