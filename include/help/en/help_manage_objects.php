<h1>Creating an inventory item type</h1>
<p>
	Inventory item types are meant to customize the different elements that will be used in the inventory. It can manage object types by clicking the option <b>Object Types.</b>
</p>
<p>
	We'll explain the functioning of object types through an example: we're going to create an inventory item type named “Software” which will have two fields, Version and Description linked to it.
</p>
<p>
	<?php print_image("images/help/inventory1.png", false, false); ?>
</p>
<p>
	An inventory item type has a name, icon, minimum stock value and a description. Plus, we'll be able to define if this type will appear or not as “root” in the inventory's 'tree' view. For each type custom fields can be added.
</p>
<p>
	<?php print_image("images/help/inventory2.png", false, false); ?>
</p>
<p>
	These fields can have an unique value that acts as an identifier and that can be marked for inheritance by other items. Another feature is that they can be marked to be shown on the search. This way, viewing the inventory on a search can also be flexible.
</p>
<p>
	Custom fields can be numeric, text only, combined or external.
</p>
<p>
	<?php print_image("images/help/inventory3.png", false, false); ?>
</p>
<p>
	External type fields allude to an external chart found in the database and that can be related to other charts. When created, the chart name and ID field for it must be detailed. In case of it being related with another chart, the name of the fathering chart and the name of the field where the ID value for said fathering chart can be found, will also need to be specified.
</p>
<p>
	<?php print_image("images/help/inventory4.png", false, false); ?>
</p>
<p>
	When it comes to selecting the value of these fields, a modal window will appear with all the information on the external chart.
</p>
<p>
	<?php print_image("images/help/inventory5.png", false, false); ?>
</p>
<p><i>
	To add an external chart and afterwards use it on inventory items, you'll only need to add the chart you want along with the corresponding data from Integria's database. Maintenance on this chart is done manually, operating on it with SQL sentences, or using an external chart editor provided by Integria IMS.
</i></p>