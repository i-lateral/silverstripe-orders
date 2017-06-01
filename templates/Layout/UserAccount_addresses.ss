<div class="units-row row users-account line">
    <% include Users_Profile_SideBar %>

    <div class="content-container typography checkout-account unit-75 unit size3of4 lastUnit col-xs-12 col-md-8">
        $SessionMessage
        
        <h1><%t Checkout.YourAddresses "Your Addresses" %></h1>

        <% with $CurrentMember %>
            <div class="units-row row line">
                <% loop $Addresses %>
                    <div class="unit-33 <% if $Default %>default <% end_if %>col-xs-12 col-sm-4 unit size1of3<% if $MultipleOf(3) %> lastUnit<% end_if %>">
                        <p>
                            <strong>$FirstName $Surname</strong><br/>
                            <% if $Default %><em>(<%t Checkout.DefaultAddress "Default Address" %>)</em><br/><% end_if %>
                            $Address1<br/>
                            <% if $Address2 %>$Address2<br/><% end_if %>
                            $City<br/>
                            <% if $State %>$State<br/><% end_if %>
                            $PostCode<br/>
                            $Country<br/>
                            <% if not $Address2 %><br/><% end_if %>
                            <% if not $Default %><br/><% end_if %>
                        </p>
                        <p>
                            <a href="{$Top.Link('editaddress')}/{$ID}" class="btn btn-green btn-success">
                                <%t Checkout.Edit "Edit" %>
                            </a>
                            <a href="{$Top.Link('removeaddress')}/{$ID}" class="btn btn-red btn-danger">
                                <%t Checkout.Remove "Remove" %>
                            </a>
                        </p>
                    </div>
                    <% if MultipleOf(3) %></div><div class="units-row row line"><% end_if %>
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
            <a href="{$Link('addaddress')}" class="btn btn-green btn-success">
                <%t Checkout.AddAddress "Add Address" %>
            </a>
        </p>

    </div>
</div>
