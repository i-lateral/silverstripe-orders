<% require css('checkout/css/checkout.css') %>

<div class="content-container typography checkout-cart">
    <h1><%t Checkout.CartName 'Shopping Cart' %></h1>

    <% if $Items.exists %>
        <div class="checkout-cart-form">
            $CartForm
        </div>

        <hr/>

        <div class="units-row line">
            <div class="unit-66 unit size2of3">
                <div class="checkout-cart-discounts line units-row-end">
                    <% if $Discount || $ShowDiscountForm %>
                        <h2><%t Checkout.Discount "Discount" %></h2>
                    <% end_if %>

                    <% if $Discount %>
                        <p>
                            <%t Checkout.Discount "Discount" %>
                            $Discount.Title
                        </p>
                    <% end_if %>

                    <% if $ShowDiscountForm %>
                        $DiscountForm
                    <% end_if %>
                </div>

                <% if $Discount || $ShowDiscountForm %>
                    <hr/>
                <% end_if %>

                <% if $PostageForm %>
                    <div class="checkout-cart-postage">
                        <h2><%t Checkout.EstimateShipping "Estimate Shipping" %></h2>
                        $PostageForm
                    </div>
                <% else %>
                    <br/>
                <% end_if %>
            </div>

            <div class="unit-33 unit size1of3">
                <h2><%t Checkout.Total "Total" %></h2>

                <table class="checkout-tax-table width-100">
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
                                </strong>
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
                </table>

                <p class="checkout-cart-total">
                    <strong class="uppercase bold">
                        <%t Checkout.CartTotal 'Total' %>:
                    </strong>
                    {$TotalCost.Nice}
                </p>
            </div>
        </div>

        <hr/>

        <div class="checkout-cart-proceed line units-row-end">
            <div class="unit-push-right">
                <a href="{$BaseHref}checkout/checkout" class="btn btn-green btn-big">
                    <%t Checkout.CartProceed 'Proceed to Checkout' %>
                </a>
            </div>
        </div>
    <% else %>
        <p>
            <strong>
                <%t Checkout.CartIsEmpty 'Your cart is currently empty' %>
            </strong>
        </p>
    <% end_if %>
</div>
