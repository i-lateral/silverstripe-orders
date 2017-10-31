<% require css('orders/css/checkout.css') %>
<% require javascript('orders/js/checkout.js') %>

<div class="content-container container checkout-checkout typography">
    <h1>$Title</h1>

    <div class="units-row row line">
        <div class="unit-66 unit size2of3 col-xs-12 col-sm-8">
            <% if $ShowLoginForm %>
                <h3><%t Framework.Login "Login" %></h3>
                $LoginForm
                <h4 class="clearfix text-center legend">OR</h4>
                <hr/>
            <% end_if %>
            $Form
        </div>
        <div class="unit-33 unit size1of3 col-xs-12 col-sm-4">
            <% with $ShoppingCart.Estimate %>
                <% include OrderSummary %>
            <% end_with %>
        </div>                
    </div>
</div>
