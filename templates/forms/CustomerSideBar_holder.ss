<div
    class="cms-content-tools east cms-panel order-admin-sidebar order-customer-sidebar"
    data-expandOnClick="true"
    data-layout-type="border"
    id="order-customer-sidebar"
>
    <div class="cms-panel-content center">
        <div class="cms-content-view cms-tree-view-sidebar" id="order-customer-content">
            <h3 class="cms-panel-header">$Title</h3>
			<% loop $Children %>
				$FieldHolder
			<% end_loop %>
        </div>
    </div>
    <div class="cms-panel-content-collapsed">
        <h3 class="cms-panel-header">$Title</h3>
    </div>
    <div class="cms-panel-toggle south">
        <a class="toggle-expand" href="#"><span>&laquo;</span></a>
        <a class="toggle-collapse" href="#"><span>&raquo;</span></a>
    </div>
</div>
