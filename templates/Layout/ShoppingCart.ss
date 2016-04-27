<% require css('checkout/css/checkout.css') %>

$SessionMessage

<div class="content-container typography checkout-cart">
    <h1><%t Checkout.CartName 'Shopping Cart' %></h1>

    <% if $Items.exists %>
        <div class="checkout-cart-form">
            $CartForm
        </div>

        <hr/>

        <div class="units-row row line">
            <div class="unit-66 unit size2of3 col-xs-12 col-md-8">
                <% if $Discount || $ShowDiscountForm %>
                    <div class="checkout-cart-discounts line units-row end">
                        <% if $Discount %>
                            <h2>
                                <%t Checkout.Discount "Discount" %>
                                $Discount.Title
                            </h2>
                        <% end_if %>

                        <% if $ShowDiscountForm %>
                            $DiscountForm
                        <% end_if %>
                    </div>
                    
                    <hr/>
                <% end_if %>

                <div class="units-row row line">
                    <% if Checkout.ClickAndCollect %>
                        <div class="unit-50 size10f2 col-xs-12 col-sm-6 checkout-cart-clickandcollect">
                            <h3>
                                <%t Checkout.ReceiveGoods "How would you like to receive your goods?" %>
                            </h3>
                            
                            <div class="checkout-delivery-buttons">
                                <a class="btn btn-primary<% if not isCollection %> btn-active active<% end_if %> width-100" href="{$Link(setdeliverytype)}/deliver">
                                    <%t Checkout.Delivered "Delivered" %>
                                </a>
                                <a class="btn btn-primary<% if isCollection %> btn-active active<% end_if %> width-100" href="{$Link(setdeliverytype)}/collect">
                                    <%t Checkout.CollectInstore "Collect Instore" %>
                                </a>
                            </div>
                        </div>
                    <% end_if %>
                    
                    <% if $PostageForm && not $isCollection %>
                        <div class="unit-50 size10f2 col-xs-12 col-sm-6 checkout-cart-postage">
                            $PostageForm
                        </div>
                    <% else %>
                        <br/>
                    <% end_if %>
                </div>
            </div>

            <div class="unit-33 unit size1of3 col-xs-12 col-md-4">
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
                
                <p class="checkout-cart-proceed line units-row end">
                    <a href="{$BaseHref}checkout/checkout" class="btn btn-green btn-big btn-lg btn-success">
                        <%t Checkout.CartProceed 'Proceed to Checkout' %>
                    </a>
                </p>
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
