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
    <td class="col-xs-6 size1of2">
        <strong>
            <%t Checkout.SubTotal 'Sub Total' %>
        </strong>
    </td>
    <td class="col-xs-6 size1of2">
        {$SubTotal.Nice}
    </td>
</tr>

<% if $hasDiscount %>
    <tr class="discount">
        <td class="col-xs-6 size1of2">
            <strong>
                <%t Checkout.Discount 'Discount' %>
            </strong><br/>
            ($Discount.Title)
        </td>
        <td class="col-xs-6 size1of2">
            {$DiscountAmount.Nice}
        </td>
    </tr>
<% end_if %>

<% if $PostageCost.RAW > 0 %>
    <tr class="shipping">
        <td class="col-xs-6 size1of2">
            <strong>
                <%t Checkout.Shipping 'Shipping' %>
            </strong>
        </td>
        <td class="col-xs-6 size1of2">
                $PostageType ({$PostageCost.Nice})
        </td>
    </tr>
<% end_if %>

    <tr class="tax">
        <td class="col-xs-6 size1of2">
            <strong>
                <%t Checkout.Tax 'Tax' %>
            </strong>
        </td>
        <td class="col-xs-6 size1of2">
            {$TaxTotal.Nice}
        </td>
    </tr>

    <tr class="total">
        <td class="col-xs-6 size1of2">
            <strong class="uppercase bold">
                <%t Checkout.CartTotal 'Total' %>
            </strong>
        </td>
        <td class="col-xs-6 size1of2">
            {$Total.Nice}
        </td>
    </tr>
</table>