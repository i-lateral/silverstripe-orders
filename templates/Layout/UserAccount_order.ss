<div class="units-row row users-account line container">
    <% include Users_Profile_SideBar %>

    <div class="content-container typography unit-75 col-xs-12 col-sm-9">
        <% if $Order %><% with $Order %>
            <h1><%t Orders.Order "Order" %>: $OrderNumber</h1>
            <div class="units-row-end row">
                <p class="unit-50 col-xs-12 col-sm-6">
                    <strong><%t Orders.Date "Date" %>:</strong> $Created.Nice <br/>
                    <strong><%t Orders.Status "Status" %>:</strong> $TranslatedStatus<br/>
                    <strong><%t Orders.FirstNames "First Name(s)" %>:</strong> $FirstName <br/>
                    <strong><%t Orders.Surname "Surname" %>:</strong> $Surname <br/>
                    <strong><%t Orders.Email "Email" %>:</strong> $Email <br/>
                    <strong><%t Orders.PhoneNumber "Phone Number" %>:</strong> $PhoneNumber <br/>
                </p>

                <p class="unit-50 col-xs-12 col-sm-6">
                    <strong><%t Orders.DeliveryDetails "Delivery Details" %></strong><br/>
                    <% if $DeliveryCompany %>
						<strong><%t Orders.Company "Company" %>:</strong> $DeliveryCompany <br/>
					<% end_if %>
                    <strong><%t Orders.FirstName "First Name" %>:</strong> $DeliveryFirstName <br/>
                    <strong><%t Orders.Surname "Surname" %>:</strong> $DeliverySurname <br/>
                    <strong><%t Orders.Address1 "Address Line 1" %>:</strong> $DeliveryAddress1 <br/>
                    <strong><%t Orders.Address2 "Address Line 2" %>:</strong> $DeliveryAddress1 <br/>
                    <strong><%t Orders.City "City" %>:</strong> $DeliveryCity <br/>
                    <strong><%t Orders.PostCode "Post Code" %>:</strong> $DeliveryPostCode <br/>
                    <strong><%t Orders.Country "Country" %>:</strong> $DeliveryCountryFull
                </p>
            </div>

            <hr/>

            <% if $Items.exists %>
                    <div class="table-responsive">
                        <table class="table width-100 table-hovered">
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
                            </tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-md-4 col-md-offset-8">
                            <table class="table">
                                <tbody>
                                    <% if $Top.SiteConfig.TaxRate > 0 %>
                                        <tr>
                                            <th class="text-right">
                                                <%t Orders.SubTotal "Sub Total" %>
                                            </th>
                                            <td class="text-right">
                                                $SubTotal.Nice
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="text-right">
                                                <%t Orders.Postage "Postage" %>
                                            </th>
                                            <td class="text-right">
                                                $PostageCost.Nice
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="text-right">
                                                <% if $Top.SiteConfig.TaxName %>
                                                    {$Top.SiteConfig.TaxName}
                                                <% else %>
                                                    <%t Orders.Tax 'Tax' %>
                                                <% end_if %>
                                            </th>
                                            <td class="text-right">
                                                $TaxTotal.Nice
                                            </td>
                                        </tr>
                                    <% else %>
                                        <tr>
                                            <th class="text-right">
                                                <%t Orders.Postage "Postage" %>
                                            </th>
                                            <td class="text-right">
                                                $PostageCost.Nice
                                            </td>
                                        </tr>
                                    <% end_if %>

                                    <tr>
                                        <td colspan="3">&nbsp;</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-right bold">
                                            <%t Orders.Total "Total" %>
                                        </th>
                                        <td class="text-right">
                                            $Total.Nice
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                <% end_if %>

        <% end_with %><% else %>
            <p class="message message-error">
                <%t Orders.NotFound "Order not found" %>
            </p>
        <% end_if %>
    </div>
</div>
