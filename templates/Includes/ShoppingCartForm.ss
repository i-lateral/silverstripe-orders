<form $FormAttributes>
    <fieldset class="checkout-cart-items">
        $Fields.dataFieldByName(SecurityID)
		<div class="table-responsive">
			<table class="table table-hover">
				<thead>
					<tr>
						<th class="image"></th>
						<th class="description">
							<%t Checkout.Description "Description" %>
						</th>
						<th class="quantity">
							<%t Checkout.Qty "Qty" %>
						</th>
						<th class="price">
							<%t Checkout.Price "Price" %>
						</th>
						<th class="actions"></th>
					</tr>
				</thead>

				<tbody>
					<% loop $Controller.Items %>
						<tr>
							<td>
								$Image.CroppedImage(75,75)
							</td>
							<td>
								<strong>
									<% if $FindStockItem %><a href="{$FindStockItem.Link}">$Title</a>
									<% else %>$Title<% end_if %>
								</strong><br/>
								<% if $Content %>$Content.Summary(10)<br/><% end_if %>                            
								<% if $Customisations && $Customisations.exists %><div class="small">
									<% loop $Customisations %><div class="{$ClassName}">
										<strong>{$Title}:</strong> {$Value}
										<% if not $Last %></br><% end_if %>
									</div><% end_loop %>
								</div><% end_if %>
							</td>
							<td class="quantity">
								<input
									type="text"
									name="Quantity_{$Key}"
									value="{$Quantity}"
									<% if $Locked %>
									title="<%t Checkout.ItemCannotBeEdited "This item cannot be edited" %>"
									readonly
									<% end_if %>
								/>
							</td>
							<td class="price">
								{$Price.Nice}
							</td>
							<td class="remove">
								<a href="{$Top.Controller.Link('remove')}/{$Key}" class="btn btn-red btn-danger">
									x
								</a>
							</td>
						</tr>
					<% end_loop %>
				</tbody>
			</table>
		</div>
    </fieldset>

    <fieldset class="checkout-cart-actions Actions row">
		<div class="btn-group pull-right">
			<a href="$Controller.Link('emptycart')" class="btn btn-red btn-danger">
				<%t Checkout.CartEmpty "Empty Cart" %>
			</a>
			
			$Actions.dataFieldByName(action_doUpdate).Field
		</div>
    </fieldset>
</form>
