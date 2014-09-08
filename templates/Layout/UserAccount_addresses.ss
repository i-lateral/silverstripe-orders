<div class="units-row users-account line">
    <% include Users_Profile_SideBar %>

    <div class="content-container typography checkout-account unit-75 unit size3of4 lastUnit">
        <h1><%t Checkout.YourAddresses "Your Addresses" %></h1>

        <% with $CurrentMember %>
            <div class="units-row line">
                <% loop $Addresses %>
                    <div class="unit-33 unit size1of3<% if $MultipleOf(3) %> lastUnit<% end_if %>">
                        <p>
                            <strong>$FirstName $Surname</strong><br/>
                            $Address1<br/>
                            <% if $Address2 %>$Address2<br/><% end_if %>
                            $City<br/>
                            $PostCode<br/>
                            $Country<br/>
                            <% if not $Address2 %><br/><% end_if %>
                        </p>
                        <p>
                            <a href="{$Top.Link('editaddress')}/{$ID}" class="btn btn-green">
                                <%t Checkout.Edit "Edit" %>
                            </a>
                            <a href="{$Top.Link('removeaddress')}/{$ID}" class="btn btn-red">
                                <%t Checkout.Remove "Remove" %>
                            </a>
                        </p>
                    </div>
                    <% if MultipleOf(3) %></div><div class="units-row line"><% end_if %>
                <% end_loop %>
            </div>

            <% if $Addresses.exists %>
                <hr/>
            <% else %>
                <p>
                    <%t Checkout.NoSavedAddresses "You have no saved addresses." %>
                </p>
            <% end_if %>

        <% end_with %>

        <p>
            <a href="{$Link('addaddress')}" class="btn btn-green">
                <%t Checkout.AddAddress "Add Address" %>
            </a>
        </p>

    </div>
</div>
