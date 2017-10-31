<% if $IncludeFormTag %>
<form $addExtraClass('forms').AttributesHTML>
<% end_if %>

    <% if $Message %>
    <p id="{$FormName}_error" class="message $MessageType">$Message</p>
    <% else %>
    <p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
    <% end_if %>

    <fieldset>
        <% if $Legend %>
            <legend>$Legend</legend>
        <% end_if %>

        <div class="Fields">
            <% if $Fields.fieldByName("SavedBilling") %>
                $Fields.fieldByName("SavedBilling").FieldHolder
            <% end_if %>

            <% if $Fields.fieldByName("BillingFields") %>
                $Fields.fieldByName("BillingFields").FieldHolder
            <% end_if %>
            
            <div class="line">
                $Fields.fieldByName("DuplicateDelivery").FieldHolder
            </div>

            <% if $Fields.fieldByName("SavedShipping") %>
                $Fields.fieldByName("SavedShipping").FieldHolder
            <% end_if %>
            
            <% if $Fields.fieldByName("DeliveryFields") %>
                $Fields.fieldByName("DeliveryFields").FieldHolder
            <% end_if %>
        
            <div class="line">
                $Fields.fieldByName("SaveShippingAddressHolder").FieldHolder
            </div>

            <% if $Fields.fieldByName("PasswordFields") %>
                $Fields.fieldByName("PasswordFields").FieldHolder
            <% end_if %>
            
            $Fields.dataFieldByName("SecurityID")
        </div>

        <div class="clear"><!-- --></div>

        <% if $Actions %>
            <div class="Actions row units-row line">
                <div class="unit-25 col-sm-4 text-left">
                    <a href="{$BackURL}" class="btn btn-red btn-danger checkout-action-back">
                        <%t Checkout.Back 'Back' %>
                    </a>
                </div>
                
                <div class="unit-75 col-sm-8 text-right">
                    <% loop $Actions %>
                        <% if $Up.ShoppingCart.isCollection && $Name == "action_doSetDelivery" %>
                            $addExtraClass("btn btn-green btn-success").Field
                        <% else_if $Name == "action_doContinue" %>
                            $addExtraClass("btn btn-green btn-success").Field
                        <% else %>
                            $addExtraClass('btn').Field
                        <% end_if %>
                    <% end_loop %>
                </div>
            </div>
        <% end_if %>
    </fieldset>

<% if $IncludeFormTag %>
</form>
<% end_if %>
