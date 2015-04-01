<div
    class="cms-content-tools east order-admin-sidebar"
    id="order-admin-sidebar"
>
    <div class="cms-panel-content center">
        <div class="cms-content-view cms-tree-view-sidebar" id="order-admin-content">
            <h3 class="cms-panel-header">$Title</h3>
			<% loop $Children %>
				$FieldHolder
			<% end_loop %>
        </div>
    </div>
</div>
