<div class="units-row row users-account line container">
    <% include Users_Profile_SideBar %>

    <div class="users-content-container typography col-xs-12 col-sm-9 unit-75 unit size3of4 lastUnit">
        <% if $RequireVerification %>
            <div class="message message-bad">
                <p>
                    <%t Users.NotVerified "You have not verified your email address" %>
                    <a href="{$BaseHref}users/register/sendverification">
                        <%t Users.Send "Send now" %>
                    </a>
                </p>
            </div>
        <% end_if %>

        <h1>$Title</h1>
        
        $Content

        <% include UserAccount_orderlist %>
    </div>
</div>
