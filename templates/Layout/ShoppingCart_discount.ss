<div class="content-container container typography checkout-cart">

    <% if $Discount %>
        <h1><%t Checkout.DiscountAdded 'Discount Added' %></h1>

        <% if $Discount.Type == 'Percentage' %>
            <p><%t Checkout.DiscountFixedText "{title} will be deducted from your next order" title=$Discount.Title  %></p>
        <% else_if $Discount.Type == 'Fixed' %>
            <p><%t Checkout.DiscountPercentText "A credit of £{amount} will be applied to your order" amount=$Discount.Amount  %></p>
        <% end_if %>

        <p>
            <a class="btn btn-primary" href="$BaseHref">
                <%t Checkout.StartShopping "Start shopping" %>
            </a>
        </p>
    <% else %>
        <h1><%t Checkout.DiscountNotValid 'Discount Not Valid' %></h1>

        <p><%t Checkout.DiscountNotValidText "This discount is either not valid or has expired"  %>.</p>

        <p>
            <a class="btn btn-primary" href="{$BaseHref}">
                <%t Checkout.StartShopping "Start shopping" %>
            </a>
        </p>
    <% end_if %>
</div>
