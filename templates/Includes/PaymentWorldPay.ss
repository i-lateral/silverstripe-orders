<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<!-- layout.templ $Revision$ -->
<html lang="en">
<head>
    <meta http-equiv="refresh" content="3;url={$RedirectURL}" />

    <title>$SiteConfig.Title <%t Checkout.Processing "Processing" %></title>

    <link rel="stylesheet" href="/i/<wpdisplay item=instId>/stylesheet.css" type="text/css" />

</head>

<WPDISPLAY FILE="header.html">

    <div class="checkout-payment-status">
        <h1><%t Checkout.Processing "Processing" %></h1>

        <p>
            <%t Checkout.RedirectingToStore "We are now redirecting you, if you are not redirected automatically then click the link below." %>
        </p>

        <p>
            <a href="{$RedirectURL}">$SiteConfig.Title</a>
        </p>
    </div>

    <WPDISPLAY ITEM="banner">
<WPDISPLAY FILE="footer.html">

</html>
