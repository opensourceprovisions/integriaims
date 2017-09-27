<h1>Tipos de ticket</h1>
<p>
	A ticket can be assigned a 'type' (for example, instances, requests, complaints, etc.).
</p> 
<p>
	<?php print_image("images/help/Ticket_type_1.png", false, false); ?>
</p>
<p>
	Groups that have a specific ticket type available can be defined. By default the ticket left blank and is visible for all groups.
</p>
<p>
	<?php print_image("images/help/tipoticket2.png", false, false); ?>
</p>
<p>
	Each type of ticket han have custom fields associated at the user's convenience. These fields can be of different types: text, combined, text area, numeric or linked) If a combined or linked type field is chosen, the values for those controls will have to be specified. Next, we'll depict an example of how to create a combined type:
</p>
<p>
	<?php print_image("images/help/tipoticket3.png", false, false); ?>
</p>
<p>
	We now create a custom field associated to the previous type:
</p>
<p>
	<?php print_image("images/help/tipoticket4.png", false, false); ?>
</p>
<p>
	Linked fields are related amongst themselves. We'll explain them through an example:
	</br>
	TYPE, VEHICLE, BRAND, MODEL AND MOTOR
	</br>
	First, we'll create the TYPE field. In this case we won't select a fathering field because it's already first in our hierarchy. Values must be separated by commas.
</p>
<p>
	<?php print_image("images/help/ticket9.png", false, false); ?>
</p>
<p>
	Afterwards, we'll create the BRAND field. The fathering field TYPE must be selected. Once that is completed, we'll fill out the field with the values separated by commas, as in the previous case. As this field does respond to a fathering field, we'll have to relate the values. For this we'll place the fathering field's value separated by |
</p>
<p>
	<?php print_image("images/help/ticket10.png", false, false); ?>
</p>
<p>
	The next field we'll create will be MODEL. We select the fathering field BRAND and insert the values.
</p>
<p>
	<?php print_image("images/help/ticket11.png", false, false); ?>
</p>
<p>
	Lastly, we'll create the MOTOR field in the same fashion.
</p>
<p>
	<?php print_image("images/help/ticket12.png", false, false); ?>
</p>
<p>
	Fields that have the checkbox “Show in general view” marked are fields that can be used in searches and that are shown in the list view. In the text area type fields this checkbox cannot be marked.
</p>
<h1>General view:</h1>
<p>
	Here we can see all the custom fields a ticket type has. From this view fields can be added, edited, deleted and reorganized on the ticket.
</p>
<p>
	<?php print_image("images/help/tipoticket5.png", false, false); ?>
</p>