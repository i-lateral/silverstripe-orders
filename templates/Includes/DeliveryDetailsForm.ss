<% if $IncludeFormTag %>
<form $addExtraClass('forms columnar row').AttributesHTML>
<% end_if %>

    <% if $Message %>
    <p id="{$FormName}_error" class="message $MessageType">$Message</p>
    <% else %>
    <p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
    <% end_if %>

    <fieldset>
        <% if $Legend %><legend>$Legend</legend><% end_if %>

        <div class="Fields">
            $Fields.dataFieldByName(SecurityID)
            
            <% with $Fields.fieldByName("DeliveryFields") %>
                <$Tag class="CompositeField line row units-row $extraClass <% if ColumnCount %>multicolumn<% end_if %>">
                    <% if $Tag == 'fieldset' && $Legend %>
                        <legend>$Legend</legend>
                    <% end_if %>
                    
                    <% loop $FieldList %>
                        <% if $Up.ColumnCount %>
                            <div class="column-{$Up.ColumnCount} <% if $Up.ColumnCount == 2 %>unit-50 col-sm-6<% else_if $Up.ColumnCount == 3 %>unit-33 col-sm-4<% end_if %> $FirstLast">
                                $FieldHolder
                            </div>
                        <% else %>
                            $FieldHolder
                        <% end_if %>
                    <% end_loop %>

                    <% if $Description %><span class="description">$Description</span><% end_if %>
                </$Tag>
            <% end_with %>
        </div>

        <div class="clear"><!-- --></div>

        <% if $Actions %>
            <div class="Actions row units-row line">
                <div class="unit-25 col-sm-4 text-left">
                    <a href="{$BackURL}" class="btn btn-red checkout-action-back">
                        <%t Checkout.Back 'Back' %>
                    </a>
                </div>
                
                <div class="unit-75 col-sm-8 text-right">
                    <% loop $Actions %>
                        <% if $Name == "action_doContinue" %>
                            $addExtraClass("btn btn-green").Field
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
