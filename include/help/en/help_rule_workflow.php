<h1>Workflow rules</h1>
<p>
	This is an Enterprise feature and is meant to define custom rules that will be applied to ticket management. 
	This means that when a ticket that isn't closed complies with any condition associated to a rule, certain predefined actions are performed. 
	For example: when a ticket from the Support group has been left unattended for 48 hours or more, an email is sent to the user responsible informing on the situation. 
</p>
<p>
	These rules can only be defined and managed by “administrator” users.
	The rules will be applied a single time. An exception to this behaviour are those rules that take into account a tickets update date.
	In this case the rule will be launched accordingly whenever an update is performed. 
</p>
<p>
	<strong >Workflow rule management</strong>
</p>
<p>
	Rules have a description field that'll help identify their behaviour. 
	Next, a list of system-created rules is shown. In this case, 
	we have a rule to add WUs and send an email to the person responsible when the ticket has remained unanswered for 48hs.  
</p>
<p>
	<?php print_image("images/help/workflow_rule.png", false); ?>
</p>

<p>
	Each rule has conditions related that must be complied with to be able to perform the actions. 
	If a rule has more than one condition, all conditions for the action to go off need to be met. 
	To be able to modify the rule or access its conditions and actions you'll have to click on the description. 
	In the editing section apart from the description we can choose “Run Mode”: 
</p>
<p>
	<ul>
		<li><b>Cron:</b> this will check every x amount of time (default 5 minutes) if the workflow rules are being followed. </li>
		<li><b>Realtime:</b> checks immediately if workflow rules are followed.</li>
	</ul>
</p>
<p>
	<?php print_image("images/help/workflow_rule_2.png", false); ?>
</p>
