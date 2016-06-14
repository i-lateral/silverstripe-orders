<% include OrderEmailHead %>

<% with $Order %>
    <h1><%t Orders.OrderStatusUpdate "Order Status Update" %></h1>
    
    <p><%t Orders.OrderMarkedAs 'Order {ordernumber} has been marked as {status}' ordernumber=$OrderNumber status=$Status %></p>
    
    <% if $Items.exists %>
        <hr/>

        <h2><%t Orders.Items "Items" %></h2>

        <table style="width: 100%;">
            <thead>
                <tr>
                    <th style="text-align: left"><%t Orders.Details "Details" %></th>
                    <th style="text-align: right"><%t Orders.QTY "Qty" %></th>
                    <th style="text-align: right"><%t Orders.Price "Price" %></th>
                </tr>
            </thead>

            <tbody><% loop $Items %>
                <tr>
                    <td>
                        {$Title} <% if $StockID %>($StockID)<% end_if %><br/>
                        <em>$CustomisationHTML</em>
                    </td>
                    <td style="text-align: right">{$Quantity}</td>
                    <td style="text-align: right">{$Price.Nice}</td>
                </tr>
            <% end_loop %></tbody>
            
            <tfoot>
                <tr><td colspan="2">&nbsp;</td></tr>
                
                <% if $DiscountAmount.RAW > 0 || $Discount %><tr>
                    <td colspan="2" style="text-align: right;">
                        <strong>
                            <%t Orders.Discount "Discount" %>
                            <% if $Discount %>($Discount)<% end_if %>
                        </strong>
                    </td>
                    <td style="text-align: right;">$DiscountAmount.Nice</td>
                </tr><% end_if %>
                
                <tr>
                    <td colspan="2" style="text-align: right;">
                        <strong><%t Orders.Postage "Postage" %></strong>
                    </td>
                    <td style="text-align: right;">$PostageCost.Nice</td>
                </tr>
                
                <% if $TaxTotal %>
                <tr>
                    <td colspan="2" style="text-align: right;">
                        <strong><%t Orders.SubTotal "Sub Total" %></strong>
                    </td>
                    <td style="text-align: right;">$SubTotal.Nice</td>
                </tr>
                
                <tr>
                    <td colspan="2" style="text-align: right;">
                        <strong><%t Orders.Tax "Tax" %></strong>
                    </td>
                    <td style="text-align: right;">$TaxTotal.Nice</td>
                </tr>
                <% end_if %>
                
                <tr>
                    <td colspan="2" style="text-align: right;">
                        <strong><%t Orders.Total "Total" %></strong>
                    </td>
                    <td style="text-align: right;">$Total.Nice</td>
                </tr>
            </tfoot>
        </table>
    <% end_if %>
    
    <hr/>

    <h2><%t Orders.CustomerDetails "Customer Details" %></h2>

    <p>
        <%t Orders.Name "Name" %>: {$FirstName} {$Surname}<br/>
        <% if $Phone %><%t Orders.Phone "Phone" %>: {$Phone}<br/><% end_if %>
        <% if $Email %><%t Orders.Email "Email" %>: <a href="mailto:{$Email}">{$Email}</a><br/><% end_if %>
    </p>
        
    <hr/>

    <h2><%t Orders.DeliveryDetails 'Delivery Details' %></h2>
    
    <% if $Action == "collect" %>
        <p><%t Orders.ItemsToBeCollected "The items are to be collected" %></p>
    <% else %>
        <p>
            <% if $DeliveryCompany %>$DeliveryCompany<br/><% end_if %>
            {$DeliveryFirstnames} {$DeliverySurnameSurname}<br/>
            {$DeliveryAddress1},<br/>
            <% if $DeliveryAddress2 %>{$DeliveryAddress2},<br/><% end_if %>
            {$DeliveryCity},<br/>
            {$DeliveryPostCode},<br/>
            {$DeliveryCountryFull}
        </p>
    <% end_if %>
<% end_with %>

<% include OrderEmailFooter %>
