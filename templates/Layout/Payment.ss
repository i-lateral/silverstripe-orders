<div class="content-container checkout-payment typography">
    <h1><%t Checkout.Summary "Summary" %></h1>
    <p><%t Checkout.SummaryCopy "Please review your personal information before proceeding and entering your payment details." %></p>

    <% with $ShoppingCart %>
        <div class="checkout-payment-summary line">
            <h2><%t Checkout.Order "Order" %></h2>
            <p>
                <strong><%t Checkout.SubTotal "Sub Total" %>:</strong>
                $SubTotalCost.Nice
                <br/>

                <% if $Discount %>
                    <strong><%t Checkout.Discount "Discount" %>:</strong>
                    $DiscountAmount.Nice
                    <br/>
                <% end_if %>

                <% if $Postage.RAW > 0 %>
                    <strong><%t Checkout.Postage "Postage" %>:</strong>
                    $Top.Order.PostageType ($PostageCost.Nice)
                    <br/>
                <% end_if %>

                <% if $TaxCost.RAW > 0 %>
                    <strong><%t Checkout.Tax "Tax" %>:</strong>
                    $TaxCost.Nice
                    <br/>
                <% end_if %>

                <strong><%t Checkout.Total "Total" %>:</strong>
                $TotalCost.Nice
            </p>
        </div>
    <% end_with %>

    <hr/>
    
    <% with $Order %>
        <% if $FirstName && $Email %>
            <div class="checkout-payment-summary units-row line">
                <div class="unit-50 unit size1of2">
                    <h2><%t Checkout.BillingDetails "Billing Details" %></h2>
                    <p>
                        <strong><%t Checkout.Name "Name" %>:</strong> $FirstName $Surname<br/>
                        <strong><%t Checkout.Email "Email" %>:</strong> $Email<br/>
                        <% if $Company %><strong><%t Checkout.Company "Company" %>:</strong> $Company<br/><% end_if %>
                        <% if $PhoneNumber %><strong><%t Checkout.Phone "Phone Number" %>:</strong> $PhoneNumber<br/><% end_if %>
                        <strong><%t Checkout.Address "Address" %>:</strong><br/>
                        $Address1<br/>
                        <% if $Address2 %>$Address2<br/><% end_if %>
                        $City<br/>
                        <strong><%t Checkout.PostCode "Post Code" %>:</strong> $PostCode<br/>
                        <strong><%t Checkout.Country "Country" %>:</strong> $Country
                    </p>
                </div>

                <div class="unit-50 unit size1of2">
                    <h2><%t Checkout.DeliveryDetails "Delivery Details" %></h2>
                    <p>
                        <strong><%t Checkout.Name "Name" %>:</strong> $DeliveryFirstnames $DeliverySurname<br/>
                        <strong><%t Checkout.Address "Address" %></strong><br/>
                        $DeliveryAddress1<br/>
                        <% if $DeliveryAddress2 %>$DeliveryAddress2<br/><% end_if %>
                        $DeliveryCity<br/>
                        <strong><%t Checkout.PostCode "Post Code" %>:</strong> $DeliveryPostCode<br/>
                        <strong><%t Checkout.Country "Country" %>:</strong> $DeliveryCountry
                    </p>
                </div>
            </div>
        <% end_if %>
    <% end_with %>

    <% if $PaymentInfo %>
        <hr/>
        $PaymentInfo
    <% end_if %>

    $Form
</div>
