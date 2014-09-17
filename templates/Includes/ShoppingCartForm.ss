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
                        <%t Checkout.ItemCost "Item Cost" %>
                    </th>
                    <th class="actions"></th>
                </tr>
            </thead>

            <tbody>
                <% loop $Controller.Items %>
                    <tr><% with $Object %>
                        <td>
                            <% if $Image %>$Image.CroppedImage(75,75)
                            <% else_if $Images && $Images.exists %>$Images.first.CroppedImage(75,75)<% end_if %>
                        </td>
                        <td>
                            <strong>$Title</strong><br/>
                            <% if $Content %>$Content.Summary(10)<br/><% end_if %>
                            <% if $Up.Customised.exists %><div class="small">
                                <% loop $Up.Customised %><div class="{$ClassName}">
                                    <strong>{$Title}:</strong> {$Value}
                                    <% if not $Last %></br><% end_if %>
                                </div><% end_loop %>
                            </div><% end_if %>
                        </td>
                        <td class="quantity">
                            <input type="text" name="Quantity_{$Up.Key}" value="{$Up.Quantity}" />
                        </td>
                        <td class="total">
                            {$Up.Price.nice}
                        </td>
                        <td class="remove">
                            <a href="{$Top.Controller.Link('remove')}/{$Up.Key}" class="btn btn-red">
                                x
                            </a>
                        </td>
                    <% end_with %></tr>
                <% end_loop %>
            </tbody>

            <tfoot>
                <tr class="subtotal">
                    <td class="text-right" colspan="3">
                        <strong>
                            <%t Checkout.SubTotal "Sub Total" %>
                        </strong>
                    </td>
                    <td colspan="2">
                        {$Controller.SubTotalCost.Nice}
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
