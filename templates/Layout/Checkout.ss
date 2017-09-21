<% require css('checkout/css/checkout.css') %>

<div class="content-container container checkout-checkout typography">
    <h1>$Title</h1>

    <div class="units-row row line">
        <div class="unit-50 unit size1of2 col-xs-12 col-sm-6">
            $LoginForm
        </div>
        
        <div class="unit-50 unit size1of2 col-xs-12 col-sm-6">
            <p class="units-row line">
                <%t Checkout.DontHaveAccount "Don't have an account?" %>
            </p>

            <p class="units-row line">
                <a href="{$BaseHref}users/register?BackURL={$Link}" class="btn btn-primary text-centered unit-push-right width-100">
                    <%t Checkout.Register "Register" %>
                </a>
            </p>

            <% if $Checkout.GuestCheckout %>
                <p class="units-row line text-centered">
                    <strong><%t Checkout.Or "Or" %></strong>
                </p>

                <p class="units-row line">
                    <a href="{$Link('billing')}" class="btn btn-primary text-centered unit-push-right width-100">
                        <%t Checkout.ContinueAsGuest "Continue as a Guest" %>
                    </a>
                </p>
            <% end_if %>
        </div>
    </div>
</div>
