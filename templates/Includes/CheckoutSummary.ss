<% with $ShoppingCart %>
<% if $Items.Exists %>
    <div>
        <% loop $Items %>
            <div class="row">
                <div class="col-xs-3">$Image.CroppedImage(45,45)</div>
                <div class="col-xs-9">
                    <h3 class="h4">$Title</h3>
                    <p>$Quantity x $UnitPrice.Nice</p>
                </div>
            </div>
        <% end_loop %>
        <table class="checkout-total-table width-100">
            <tr class="subtotal">
                <td class="text-right">
                    <strong>
                        <%t Checkout.SubTotal 'Sub Total' %>
                    </strong>
                </td>
                <td class="text-right">
                    {$SubTotalCost.Nice}
                </td>
            </tr>
            
            <% if $Discount %>
                <tr class="discount">
                    <td class="text-right">
                        <strong>
                            <%t Checkout.Discount 'Discount' %>
                        </strong><br/>
                        ($Discount.Title)
                    </td>
                    <td class="text-right">
                        {$DiscountAmount.Nice}
                    </td>
                </tr>
            <% end_if %>

            <% if $PostageForm %>
                <tr class="shipping">
                    <td class="text-right">
                        <strong>
                            <%t Checkout.Shipping 'Shipping' %>
                        </strong>
                    </td>
                    <td class="text-right">
                        {$PostageCost.Nice}
                    </td>
                </tr>
            <% end_if %>
            
            <% if $ShowTax %>
                <tr class="tax">
                    <td class="text-right">
                        <strong>
                            <%t Checkout.Tax 'Tax' %>
                        </strong>
                    </td>
                    <td class="text-right">
                        {$TaxCost.Nice}
                    </td>
                </tr>
            <% end_if %>
            
            <tr class="total">
                <td class="text-right">
                    <strong class="uppercase bold">
                        <%t Checkout.CartTotal 'Total' %>
                    </strong>
                </td>
                <td class="text-right">
                    {$TotalCost.Nice}
                </td>
            </tr>
        </table>
    </div>
    <% else %>
    <p>
        <strong>
            <%t Checkout.CartIsEmpty 'Your cart is currently empty' %>
        </strong>
    </p>
<% end_if %>
<% end_with %>
