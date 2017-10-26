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
                    <% include OrderSummary %>
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
