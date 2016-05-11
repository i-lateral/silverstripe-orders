<% if $IncludeFormTag %>
<form $addExtraClass('forms columnar').AttributesHTML>
<% end_if %>

    <% if $Message %>
    <p id="{$FormName}_error" class="message $MessageType">$Message</p>
    <% else %>
    <p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
    <% end_if %>

    <fieldset>
        <% if $Legend %><legend>$Legend</legend><% end_if %>

        <div class="Fields row units-row line">
            <% with $Fields.fieldByName("DeliveryFields") %>
                <$Tag class="CompositeField line row units-row $extraClass <% if ColumnCount %>multicolumn<% end_if %>">
                    <% if $Tag == 'fieldset' && $Legend %>
                        <legend>$Legend</legend>
                    <% end_if %>
                    
                    <% loop $FieldList %>
                        <% if $Up.ColumnCount %>
                            <div class="column-{$Up.ColumnCount} unit <% if $Up.ColumnCount == 2 %>unit-50 half size1of2 col-sm-6<% else_if $Up.ColumnCount == 3 %>unit-33 third size1of3 col-sm-4<% end_if %> $FirstLast">
                                $FieldHolder
                            </div>
                        <% else %>
                            $FieldHolder
                        <% end_if %>
                    <% end_loop %>

                    <% if $Description %><span class="description">$Description</span><% end_if %>
                </$Tag>
            <% end_with %>
            
            <div class="line row units-row">
                $Fields.fieldByName("SaveAddressHolder").FieldHolder
            </div>
            
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
                        <% if $Name == "action_doContinue" %>
                            $addExtraClass("btn btn-green btn-success").Field
                        <% else %>
                            $addExtraClass('btn btn-primary').Field
                        <% end_if %>
                    <% end_loop %>
                </div>
            </div>
        <% end_if %>
    </fieldset>

<% if $IncludeFormTag %>
</form>
<% end_if %>
