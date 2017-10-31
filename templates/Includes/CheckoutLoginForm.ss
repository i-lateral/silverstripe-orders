<% if $IncludeFormTag %>
<form $AttributesHTML>
<% end_if %>
	<% if $Message %>
        <div id="{$FormName}_error" class="message $MessageType alert alert-<% if $MessageType == "good" %>success<% else %>danger<% end_if %>">
            $Message
        </div>
	<% else %>
	    <div id="{$FormName}_error" class="message $MessageType alert alert-<% if $MessageType == "good" %>success<% else %>danger<% end_if %>" style="display: none"></div>
    <% end_if %>
    
    <fieldset>
		<% if Legend %><legend>$Legend</legend><% end_if %>
        
        <% loop HiddenFields %>
            $FieldHolder
        <% end_loop %>

        <div class="line row">
            <% loop VisibleFields %>
                <div class="unit size1of2 col-md-6">
                    $FieldHolder
                </div>
                <% if $MultipleOf(2) %></div><div class="line row"><% end_if %>
            <% end_loop %>
        
            <% if Actions %>
                <div class="unit size1of2 col-md-6">
                    <% loop Actions %>
                        $Field
                    <% end_loop %>
                </div>
            <% end_if %>
        </div>

        <div class="clear"><!-- --></div>
	</fieldset>
<% if $IncludeFormTag %>
</form>
<% end_if %>

