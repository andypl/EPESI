<table cellspacing="0" cellpadding="0" width='100%'>
	<tr>
		<td>
			<table cellspacing="2" cellpadding="2" width='100%'>
				<tbody>
				<tr>
					<td width='10%' align='center'><span>{$query_label}</span></td>
					<td width='90%'><div style='width:100%'>{$query_text}<input type='hidden' id='search_id' value='{$search_id}' /></div></td>
				</tr>		
				</tbody>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<table cellspacing="0" cellpadding="0" width="100%" class="Utils_GenericBrowser">	
				<thead>
					<tr class="nonselectable">
						<th style="width:40%"><span> Identifier</span></th>
						<th style="width:40%"><span>Result(s)</span></th>
						<th style="width:20%"><span>Recordset</span></th>
					</tr>		
				</thead>
				<tbody id="tableID_{$search_id}">
				</tbody>
			</table>
		</td>
	</tr>	
</table>	



