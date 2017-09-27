<h1>Workflow rule actions</h1>
<p>
	Next. we'll look at the actions that will be performed if previous conditions are met.
</p>
<p>
	<?php print_image("images/help/ticket43.png", false); ?>
</p>
<p>
	Actions are composed by the following fields:
</p>
<p>
	<?php print_image("images/help/ticket44.png", false, false); ?>
</p>
<p>
	Actions are defined accordint to the “Action Type” field:
	</br>
	<ul>
		<li><b>Change priority:</b> the new priority status will be updated on the ticket.</li>
		<li><b>Change owner:</b> the new user values will be updated on the ticket.</li>
		<li><b>Change groups:</b> the new group value will be updated on the ticket.</li>
		<li><b>Change status:</b> the new status value will be updated on the ticket.</li>
		<li><b>Send email:</b>  the email's addressee will be stored in the “For” field and the email's body in the “Text” field</li>
		<li><b>Add a WU:</b>the WU's text will be added to the instance along with the user specified.</li>
		<li><b>Change update date:</b> modifies the date on which the ticket was last updated.</li>
		<li><b>Change solution:</b>updates the value for the ticket's closing solution.</li>
		<li><b>Execute command line: </b>allows executing a custom command including macros for dynamic variable replacement such as ticket ID, group, user, etc.</li>
		<li><b>Ticket Lock:</b>field values on a ticket won't be able to be modified.</li>
		<li><b>Ticket Unlock:</b>the values on ticket fields will be able to be modified again.</li>
	</ul>
	</br>
	When creating the first action a default action is created which replaces a ticket's previous update date with the latest one. This action can be manually deleted on the actions list.
</p>
<p>
	<b>Examples:</b>
	</br>
	<ul>
		<li>
			“For any instance in the “Customer” group that has remained over 10 hours without being updated, an email is sent to the ticket's owner. The email's body will be something like this “The instance (title) on the Customer group is being left unattended. Review this subject immediately”.
		</li>
		<li>
			“For any instance that has been open for over 21 days, and is under “High” priority, its priority will change to “Medium” and another WU is added that says “automatically reprioritized by the system”.
		</li>
	</ul>
</p>
<p>
	In general workflow rules can only be triggered once. This means if a rule is scheduled for change, an user X assigned to this instance -when said instance is over 30 days old- can be changed manually. If someone places that same user manually, the rule won't be triggered again.
</p>
<p>
	The only exception to this behaviour is when the condition is update time. If a rule es set up to go off when the ticket has remained more than x amount of time without updates an automatic default action will be created to “Update the ticket”. This means that the condition won't go off constantly. Once that x amount of time has passed the system will be able to perform the same WorkFlow rule. This is the exception considering that for no other condition (Priority, Ownership, Status, Creation or Group) will a rule be executed again.
</p>
<p>
	<b>A typical example case for this type of condition:</b>
	</br>
	<ul>
		<li>
			“ You need to send a warning email to a coordinator as soon as a “very high” priority instance from a specific group has been left unattended for over 5 days (no updates).”
		</li>
	</ul>
	</br>
	Simply the condition “Coincide with all fields” along with the specific group and the -in this case- very high priority all need to be marked, all for assigned instances. In the “Update date” field we'll be choosing a specific week.
</p>
<p>
	<?php print_image("images/help/ticket45.png", false, false); ?>
</p>
<p>
	When adding a command to send an email, a ticket update command will be automatically created, which we will leave as is, in order to update the ticket and avoid the rule from being overridden constantly.
</p>
<p>
	<?php print_image("images/help/ticket46.png", false); ?>
</p>
