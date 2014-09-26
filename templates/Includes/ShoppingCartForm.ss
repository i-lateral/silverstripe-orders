<form $FormAttributes>
    <fieldset class="checkout-cart-items">
        $Fields.dataFieldByName(SecurityID)

        <table>
            <thead>
                <tr>
                    <th class="image"></th>
                    <th class="description">
                        <%t Checkout.Description "Description" %>
                    </th>
                    <th class="quantity">
                        <%t Checkout.Qty "Qty" %>
                    </th>
                    <th class="price">
                        <%t Checkout.Price "Price" %>
                    </th>
                    <% if $Controller.ShowTax %>
                        <th class="tax">
                            <%t Checkout.Tax "Tax" %>
                        </th>
                    <% end_if %>
                    <th class="actions"></th>
                </tr>
            </thead>

            <tbody>
                <% loop $Controller.Items %>
                    <tr>
                        <td>
                            $Image.CroppedImage(75,75)
                        </td>
                        <td>
                            <strong>$Title</strong><br/>
                            <% if $Content %>$Content.Summary(10)<br/><% end_if %>
                            <% if $Customisations && $Customisations.exists %><div class="small">
                                <% loop $Customisations %><div class="{$ClassName}">
                                    <strong>{$Title}:</strong> {$Value}
                                    <% if not $Last %></br><% end_if %>
                                </div><% end_loop %>
                            </div><% end_if %>
                        </td>
                        <td class="quantity">
                            <input type="text" name="Quantity_{$Key}" value="{$Quantity}" />
                        </td>
                        <td class="price">
                            {$Price.nice}
                        </td>
                        <% if $Top.Controller.ShowTax %>
                            <td class="tax">
                                {$Tax.nice}
                            </td>
                        <% end_if %>
                        <td class="remove">
                            <a href="{$Top.Controller.Link('remove')}/{$Key}" class="btn btn-red">
                                x
                            </a>
                        </td>
                    </tr>
                <% end_loop %>
            </tbody>

            <tfoot>
                <tr class="subtotal">
                    <td class="text-right" colspan="<% if $Top.Controller.ShowTax %>4<% else %>3<% end_if %>">
                        <strong>
                            <%t Checkout.SubTotal "Sub Total" %>
                        </strong>
                    </td>
                    <td colspan="2">
                        {$Controller.SubTotalCost.nice}
                    </td>
                </tr>
            </tfoot>
        </table>
    </fieldset>

    <fieldset class="checkout-cart-actions Actions">
        <a href="$Controller.Link('emptycart')" class="btn btn-red">
            <%t Checkout.CartEmpty "Empty Cart" %>
        </a>
        
        $Actions.dataFieldByName(action_doUpdate)
    </fieldset>
</form>
