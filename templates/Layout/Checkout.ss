<% require css('orders/css/checkout.css') %>
<% require javascript('orders/js/checkout.js') %>

<div class="content-container container checkout-checkout typography">
    <h1>$Title</h1>

    <div class="units-row row line">
        <div class="unit-50 unit size2of3 col-xs-12 col-sm-8">
            <% if $ShowLoginForm %>
                $LoginForm
                <h4 class="text-center legend">OR<span></span></h4>
            <% end_if %>
            $Form
        </div>
        <div class="unit-50 unit size1of3 col-xs-12 col-sm-4">
            <% include CheckoutSummary %>
        </div>                
    </div>
</div>
