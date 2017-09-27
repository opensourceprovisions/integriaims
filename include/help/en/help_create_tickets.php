<h1>Ticket creation</h1>
<p>
	Creating a ticket isn't so simple. To reach this step we must have been able to understand and configure the following aspects:
	</br>
	</br>
	<ul>
		<li> <b>What's an user on Integria.</b>
			<ul>
				<li>Type of user (external, normal)</li>
			</ul>
		</li>
		<li> <b>What's a group in Integria.</b>
			<ul>
				<li>What relation does the group have with an user's visibility over the rest of Integria elements.</li>
				<li>What relation is there between the group, the user access profile, and the type of user it's been defined as (external, normal)</li>
			</ul>
		</li>
		<li> <b>The inventory:</b>
			<ul>
				<li>Contract</li>
				<li>SLA</li>
			</ul>
		</li>
	</ul>
</p>
<p>
	Once we suppose all these concepts are clear, creating a ticket will be very simple. We must remember, as a review, that the following properties alter Integria's behaviour when creating a ticket:
	</br>
	</br>
	<ul>
		<li><b>External user:</b> external users can only see their own reports so the group concept, profile and other elements aren't as relevant for this type of users. External users cannot change some ticket properties that are set by default, such as assigned user or default object, since these are linked to the group.</li>
		<li><b>Normal user:</b>  just like external users, there are some ticket properties that are configured by default and cannot be changed. Items such as assigned user or default objects, since these are already linked to the group. Nevertheless, an user with management access (IM) will be able to change the status of the ticket or fields such as “original author” (so it doesn't repeat, may the case come), or other aspects.</li>
	</ul>
</p>
<p>
	<b>By default a new ticket will appear in “New” status.</b>
</p>
<p>
	There are some fields that are automatically assigned as “deactivated SLA” or “Automatic email notification”, which initially are linked to the default values that group has (the group to which the ticket has been assigned).These values, if management permissions are available (IM), can be altered. Otherwise, they'll remain with default values.
</p>

<h1>Ticket limitations</h1>
<p>
	When creating a ticket there are two values defined on a group, just like it was shown in the chapter on users and groups, that allow defining how many tickets can be included in the same group (open or closed) for each user (total) and how many tickets can be simultaneously open for a single user from the group. A reminder on where this behaviour can be configured:
</p>
<p>
	<?php print_image("images/help/user17.png", false, false); ?>
</p>

<p>
	If these values are surpassed a warning dialogue will be shown on Integria and we won't be able to create the ticket, as can be seen on this screenshot:
</p>
<p>
	<?php print_image("images/help/ticket17.png", false, false); ?>
</p>

<h1>Normal user's view when creating a ticket:</h1>
<p>
	<?php print_image("images/help/ticket18.png", false, false); ?>
</p>

<h1>Manager view when creating (or modifying) a ticket:</h1>
<p>
	<?php print_image("images/help/ticket19.png", false, false); ?>
</p>

<h1>Creating the first WU</h1>
<p>
	Well, the ticket has been created by an user “A” and has after been assigned to another user “B”. Now what?.
</p>
<p>
	When creating a ticket, the system will have sent an email to user A and another to user B, reporting on the ticket's creation. For user A this message has the purpose of confirming that the ticket has been registered by the system.
</p>
<p>
	The user “B” can reply to that same email, which carries a special code, and send his or her first WU. The message can be something like “ticket received” or it can add extra information to the ticket, for example. The user can also connect to the system using the URL administered to ve able to manually add a WU to a ticket.
</p>
<p>
	When a ticket has “New” status it receives an automatic WU that changes its status to “Assigned”, which is a way of indicating that said ticket is being worked on.
</p>