<h1>Workflow rule conditions</h1>
<p>
	If we click on the “Conditions” tab, we access the list where we'll be able to define in which cases the rule will be applied. In our example we have a condition that is already defined. We can have one or more conditions per rule. In case we have more than one, once one of them is met the rule will automatically activate and run the action or actions that have been programmed as a response. Conditions are composed by a group of filters or “subconditions” which are described as follows:
	<ul>
		<li><b>Condition:</b> how conditions will apply to the rest of filter fields, all within the condition's definition (whether all, some or none coincide in all fields).</li>
		<li><b>Owner: </b> user responsable for a ticket</li>
		<li><b>Priority:</b> A higher priority doesn't engulf lower priorities.</li>
		<li><b>Update date:</b> time passes since the ticket was last updated.</li>
		<li><b>Text to search for in the title or description of the incident:</b> works for a string inside the WU, the title or the description.</li>
		<li><b>Task:</b> Task associated to the ticket.</li>
		<li><b>Ticket type: </b> by selecting the ticket type custom fields will appear and we can make use of them to add them to the group of filters for this “Condition”.</li>
		<li>Ticket<b>Group</b>.</li>
		<li>Ticket<b>Status</b>.</li>
		<li><b>Creation date:</b> Time passed since creation.</li>
		<li><b>SLA:</b> If the SLA was triggered or not.</li>
		<li>Ticket<b>Resolution</b>.</li>
		<li><b>SLA compliance (%): </b> a ticket's SLA compliance percentage.</li>
	</ul>
</p>
<p>
	<?php print_image("images/help/ticket41.png", false); ?>
</p>
<p>
	In this example condition we've defined the characteristics a ticket must comply with. The group has to be “Support”, the priority “Medium”, and the status “Assigned”. The condition field chosen is Coincide with all fields because we want all of them to be fulfilled. If we wanted for only some to be accomplished, the chosen condition field would be “Coincide with some fields”, and if we don't want any to be accomplished the set value has to be “No coincidence made”.
</p>
<p>
	<?php print_image("images/help/ticket42.png", false, false); ?>
</p>

