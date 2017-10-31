<% require css('checkout/css/checkout.css') %>

<div class="content-container container checkout-checkout typography">
    <h1><%t Checkout.SeelctPostageMethod "Select Postage Method" %></h1>
    
    <div class="row line">
        <div class="col-sm-4 unit size1of3">
            <h2><%t Checkout.DeliveryDetails "Delivery Details" %></h2>
            <% with $Customer %>
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
            <% end_with %>
            <p>
                <a href="{$Link}" class="btn btn-red btn-danger checkout-action-back">
                    <%t Checkout.Back 'Back' %>
                </a>
            </p>
        </div>
        <div class="col-sm-4 unit size1of3">
            $Form
        </div>
        <div class="col-sm-4 unit size1of3">
            <% with $ShoppingCart.Estimate %>
                <% include OrderSummary %>
            <% end_with %>        
        </div>
    </div>
</div>
