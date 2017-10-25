<% require css('orders/css/checkout.css') %>
<% require javascript('orders/js/checkout.js') %>

<div class="content-container container checkout-payment typography">
    <h1><%t Checkout.Summary "Summary" %></h1>
    <p><%t Checkout.SummaryCopy "Please review your personal information before proceeding and entering your payment details." %></p>

    <div class="checkout-payment-summary row units-row line">
        <% with $Order %>
                <div class="unit-25 unit size1of3 col-xs-12 col-sm-4">
                    <h2><%t Checkout.BillingDetails "Billing Details" %></h2>
                    <p>
                        <% if $Company %>
                            <strong><%t Checkout.Company "Company" %>:</strong> $Company<br/>
                        <% end_if %>
                        <strong><%t Checkout.Name "Name" %>:</strong> $FirstName $Surname<br/>
                        <strong><%t Checkout.Email "Email" %>:</strong> $Email<br/>
                        <% if $PhoneNumber %><strong><%t Checkout.Phone "Phone Number" %>:</strong> $PhoneNumber<br/><% end_if %>
                        <strong><%t Checkout.Address "Address" %>:</strong><br/>
                        $Address1<br/>
                        <% if $Address2 %>$Address2<br/><% end_if %>
                        $City<br/>
                        <% if $State %>$State<br/><% end_if %>
                        <strong><%t Checkout.PostCode "Post Code" %>:</strong> $PostCode<br/>
                        <strong><%t Checkout.Country "Country" %>:</strong> <% if $CountryFull %>$CountryFull<% else %>$Country<% end_if %>
                    </p>
                    <% if $Top.ShoppingCart.isDeliverable %>                    
                        <h3><%t Checkout.DeliveryDetails "Delivery Details" %></h3>
                        <% if $Top.ShoppingCart.isCollection %>
                            <p><%t Checkout.ItemsReservedInstore "Your items will be held instore until you collect them" %></p>
                        <% else %>
                            <p>
                                <% if $DeliveryCompany %>
                                    <strong><%t Checkout.Company "Company" %>:</strong> $DeliveryCompany<br/>
                                <% end_if %>
                                <strong><%t Checkout.Name "Name" %>:</strong> $DeliveryFirstName $DeliverySurname<br/>
                                <strong><%t Checkout.Address "Address" %>:</strong><br/>
                                $DeliveryAddress1<br/>
                                <% if $DeliveryAddress2 %>$DeliveryAddress2<br/><% end_if %>
                                $DeliveryCity<br/>
                                <% if $DeliveryState %>$DeliveryState<br/><% end_if %>
                                <strong><%t Checkout.PostCode "Post Code" %>:</strong> $DeliveryPostCode<br/>
                                <strong><%t Checkout.Country "Country" %>:</strong> <% if $DeliveryCountryFull %>$DeliveryCountryFull<% else %>$DeliveryCountry<% end_if %>
                            </p>
                        <% end_if %>
                    <% end_if %>                        
                </div>
        <% end_with %>

        <div class="unit-33 unit size1of3 col-xs-12 col-sm-4">
            <h2><%t Checkout.Payment "Payment" %></h2>
            <% if $PaymentInfo %>
                <hr/>
                $PaymentInfo
            <% end_if %>
            $GatewayForm
            $Form
        </div>
        <% with $Order %>
            <div class="unit-25 unit size1of3 col-sm-4 col-xs-12">
                <h2><%t Checkout.Order "Order" %></h2>
                <% if $Items.Exists %>
                <div>
                    <% loop $Items %>
                        <div class="row units-row">
                            <div class="col-xs-3 size1of4 unit">$Image.CroppedImage(45,45)</div>
                            <div class="col-xs-9 size3of4 unit">
                                <h3 class="h4">$Title</h3>
                                <p>$Quantity x $UnitPrice.Nice</p>
                            </div>
                        </div>
                    <% end_loop %>
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
                                        $Top.Order.PostageType ({$PostageCost.Nice})
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
                </div>
                <% else %>
                    <p>
                        <strong>
                            <%t Checkout.CartIsEmpty 'Your cart is currently empty' %>
                        </strong>
                    </p>
                <% end_if %>
            </div>
        <% end_with %>
    </div>
</div>
