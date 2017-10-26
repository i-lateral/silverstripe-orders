<table class="table width-100">    
    <% loop $Items %>
        <tr>
            <td style="vertical-align: middle;">$Image.CroppedImage(45,45)</div>
            <td style="vertical-align: middle;">
                <p>$Quantity x $Title</p>
            </td>
            <td class="text-right" style="vertical-align: middle;">        
                <p>$UnitPrice.Nice</p>
            </td>  
        </tr>
    <% end_loop %>
</table>
<br />
<table class="checkout-total-table table width-100">
<tr class="subtotal">
    <td class="text-right">
        <strong>
            <%t Checkout.SubTotal 'Sub Total' %>
        </strong>
    </td>
    <td class="text-right">
        {$SubTotal.Nice}
    </td>
</tr>

<% if $hasDiscount %>
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

<% if $PostageCost.RAW > 0 %>
    <tr class="shipping">
        <td class="text-right">
            <strong>
                <%t Checkout.Shipping 'Shipping' %>
            </strong>
        </td>
        <td class="text-right">
                $PostageType ({$PostageCost.Nice})
        </td>
    </tr>
<% end_if %>

    <tr class="tax">
        <td class="text-right">
            <strong>
                <%t Checkout.Tax 'Tax' %>
            </strong>
        </td>
        <td class="text-right">
            {$TaxTotal.Nice}
        </td>
    </tr>

    <tr class="total">
        <td class="text-right">
            <strong class="uppercase bold">
                <%t Checkout.CartTotal 'Total' %>
            </strong>
        </td>
        <td class="text-right">
            {$Total.Nice}
        </td>
    </tr>
</table>