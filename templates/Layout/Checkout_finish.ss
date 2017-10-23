<% require css('checkout/css/checkout.css') %>

<div class="content-container container checkout-checkout typography">
    <h1>$Title</h1>
    <div class="row line units-row">
        <div class="col-sm-4 unit size1of3">
            <h2>Shipping Summary</h2>

        </div>
        <div class="col-sm-4 unit size1of3">
            $Form
        </div>
        <div class="col-sm-4 unit size1of3">
            <% include CheckoutSummary %>                
        </div>
    </div>
</div>
