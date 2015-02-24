<div class="units-row users-account line">
    <% include Users_Profile_SideBar %>

    <div class="content-container typography unit-75">
        <% if $Order %><% with $Order %>
            <h1><%t Orders.Order "Order" %>: $OrderNumber</h1>
            <div class="units-row-end">
                <p class="unit-50">
                    <strong><%t Orders.Date "Date" %>:</strong> $Created.Nice <br/>
                    <strong><%t Orders.Status "Status" %>:</strong> $TranslatedStatus<br/>
                    <strong><%t Orders.FirstNames "First Name(s)" %>:</strong> $FirstName <br/>
                    <strong><%t Orders.Surname "Surname" %>:</strong> $Surname <br/>
                    <strong><%t Orders.Email "Email" %>:</strong> $Email <br/>
                    <strong><%t Orders.PhoneNumber "Phone Number" %>:</strong> $PhoneNumber <br/>
                </p>

                <p class="unit-50">
                    <strong><%t Orders.DeliveryDetails "Delivery Details" %></strong><br/>
                    <strong><%t Orders.Address1 "Address Line 1" %>:</strong> $DeliveryAddress1 <br/>
                    <strong><%t Orders.Address2 "Address Line 2" %>:</strong> $DeliveryAddress1 <br/>
                    <strong><%t Orders.City "City" %>:</strong> $DeliveryCity <br/>
                    <strong><%t Orders.PostCode "Post Code" %>:</strong> $DeliveryPostCode <br/>
                    <strong><%t Orders.Country "Country" %>:</strong> $DeliveryCountryFull
                </p>
            </div>

            <hr/>

            <% if $Items.exists %>
                <table class="width-100">
                    <thead>
                        <tr>
                            <th class="width-50"><%t Orders.Item "Item" %></th>
                            <th><%t Orders.Qty "Qty" %></th>
                            <th><%t Orders.Price "Price" %></th>
                            <% if $Top.SiteConfig.TaxRate > 0 %>
                                <th class="tax">
                                    <% if $Top.SiteConfig.TaxName %>{$Top.SiteConfig.TaxName}
                                    <% else %><%t Orders.Tax 'Tax' %><% end_if %>
                                </th>
                            <% end_if %>
                            <th><%t Orders.Reorder "Reorder" %></th>
                        </tr>
                    </thead>
                    <tbody>
                        <% loop $Items %>
                            <tr>
                                <td>$Title</td>
                                <td>$Quantity</td>
                                <td>$Price.Nice</td>
                                <% if $Top.SiteConfig.TaxRate > 0 %>
                                    <td class="total">
                                        {$TaxTotal.Nice}
                                    </td>
                                <% end_if %>
                                <td><% if $MatchProduct %>
                                    <a href="$MatchProduct.Link">
                                        <%t Orders.AddToCart "Add to cart" %>
                                    </a>
                                <% end_if %></td>
                            </tr>
                        <% end_loop %>

                        <tr>
                            <td colspan="<% if $Top.SiteConfig.TaxRate > 0 %>5<% else %>4<% end_if %>">&nbsp;</td>
                        </tr>

                        <% if $Top.SiteConfig.TaxRate > 0 %>
                            <tr>
                                <td colspan="3" class="text-right">
                                    <%t Orders.SubTotal "Sub Total" %>
                                </td>
                                <td class="text-right">$SubTotal.Nice</td>
                                <td></td>
                            </tr>

                            <tr>
                                <td colspan="<% if $Top.SiteConfig.TaxRate > 0 %>3<% else %>2<% end_if %>" class="text-right">
                                    <%t Orders.Postage "Postage" %>
                                </td>
                                <td class="text-right">$PostageCost.Nice</td>
                                <td></td>
                            </tr>

                            <tr>
                                <td colspan="3" class="text-right">
                                    <% if $Top.SiteConfig.TaxName %>
                                        {$Top.SiteConfig.TaxName}
                                    <% else %>
                                        <%t Orders.Tax 'Tax' %>
                                    <% end_if %>
                                </td>
                                <td class="text-right">$TaxTotal.Nice</td>
                                <td></td>
                            </tr>
                        <% else %>
                            <tr>
                                <td colspan="<% if $Top.SiteConfig.TaxRate > 0 %>3<% else %>2<% end_if %>" class="text-right">
                                    <%t Orders.Postage "Postage" %>
                                </td>
                                <td class="text-right">$PostageCost.Nice</td>
                                <td></td>
                            </tr>
                        <% end_if %>

                        <tr>
                            <td colspan="<% if $Top.SiteConfig.TaxRate > 0 %>5<% else %>4<% end_if %>">&nbsp;</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="<% if $Top.SiteConfig.TaxRate > 0 %>3<% else %>2<% end_if %>" class="text-right bold">
                                <%t Orders.Total "Total" %>
                            </td>
                            <td class="text-right">$Total.Nice</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            <% end_if %>

        <% end_with %><% else %>
            <p class="message message-error">
                <%t Orders.NotFound "Order not found" %>
            </p>
        <% end_if %>
    </div>
</div>
