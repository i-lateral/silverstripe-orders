<div class="content-container typography">
    <div class="units-row">
        <div class="unit-66">
            &nbsp;
        </div>
        <div class="unit-33 unit-push-right">
            $SiteConfig.OrdersHeader
        </div>
    </div>
    
    <h1><%t Orders.QuoteTitle "Quote" %></h1>

    <hr/>

    <% with $Object %>
        <div class="units-row end">
            <div class="unit-66">
                <p>
                    $FirstName $Surname<br/>
                    <% if $Company %>$Company<br/><% end_if %>
                    $Address1<br/>
                    <% if $Address2 %>$Address2<br/><% end_if %>
                    $City<br/>
                    $PostCode<br/>
                    $Country
                </p>
            </div>

            <div class="unit-33">
                <table class="width-100">
                    <tbody>
                        <tr>
                            <td class="bold"><%t Orders.RefNo "Ref No." %></td>
                            <td>$ID</td>
                        </tr>
                        <tr>
                            <td class="bold"><%t Commerce.Date "Date" %></td>
                            <td>$Created.Format('dd/MM/yyyy')</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <hr/>

        <table class="width-100">
            <thead>
                <tr>
                    <th class="sku"><%t Orders.SKU "SKU" %></th>
                    <th class="width-66"><%t Orders.Item "Item" %></th>
                    <th class="text-centered"><%t Orders.Qty "Qty" %></th>
                    <th class="text-right"><%t Orders.UnitPrice "Unit Price" %></th>
                    <th class="text-right"><%t Orders.Tax "Tax" %></th>
                </tr>
            </thead>

            <tbody>
                <% loop $Items %>
                    <tr>
                        <td>{$StockID}</td>
                        <td>{$Title}</td>
                        <td class="text-centered">{$Quantity}</td>
                        <td class="text-right">{$Price.Nice}</td>
                        <td class="text-right">{$Tax.Nice}</td>
                    </tr>
                <% end_loop %>
            </tbody>
        </table>

        <hr/>

        <div class="units-row">
            <div class="unit-33">
				$Up.SiteConfig.QuoteFooter				
			</div>
            <div class="unit-33 unit-push-right">
                <table class="width-100">
                    <tbody>
                        <tr>
                            <td class="text-right bold">
                                <%t Orders.SubTotal "SubTotal" %>
                            </td>
                            <td class="text-right">$SubTotal.Nice</td>
                        </tr>

                        <tr>
                            <td class="text-right bold">
                                <%t Orders.Postage "Postage" %>
                            </td>
                            <td class="text-right">
                                $PostageCost.Nice
                            </td>
                        </tr>

                        <tr>
                            <td class="text-right bold">
                                <%t Orders.TotalTax "Total Tax" %>
                            </td>
                            <td class="text-right">
                                $TaxTotal.Nice
                            </td>
                        </tr>

                        <tr>
                            <td class="text-right bold">
                                <%t Orders.GrandTotal "Grand Total" %>
                            </td>
                            <td class="text-right">$Total.Nice</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    <% end_with %>
</div>
