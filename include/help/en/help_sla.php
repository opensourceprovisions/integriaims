
<h1>SLA management</h1>
<p>
The SLA is the way to “prove” that the ticket management is working under certain criteria. SLAs are managed automatically by Integria, that tests them periodically using a programmed task activated then Integria is installed.
</p>
<p><b>The SLA is processed according to certain parameters:</b>
	<ul>
		<li>    
			<b>Name:</b> the text that'll appear on the selection combinations to identify the SLA.
		</li> 
		<li>
			<b>Enforced:</b> makes the SLA send out emails when something is unaccomplished (enforced) or just warn the user with a bright indicator. 
		</li>
		<li>    
			<b>Base SLA:</b> indicates that one SLA is related to another (only on an informative level).
		</li>
		<li>    
			<b>SLA type:</b> an SLA can be of three types:
			<ul>
				<li><b>Normal SLA:</b> When making the calculation, it'll be taken into account whether tickets have a Closed or Pending status.</li>
				<li><b>Third party SLA:</b> This will take into account those tickets whose status pends on third parties.</li>
				<li><b>Both:</b> This will take into account those tickets that have any status but 'Closed'.</li>
			</ul>
		</li>
		<li>    
			<b>Max. response time:</b> indicates, in hours, the minimum response time that has to be respected when a Notification (new ticket or WU) from the ticket author. Past this time a SLA will go off. For example, if it's 4 hours, and a new ticket has 4.1 hours of life, the SLA will go off. If, for example, it's an old ticket (1 week or more) and the last WU is from the ticket author and has over 4 hours of life, the ticket will also go off.
		</li>
			<li>    
			<b>Max. solution time:</b> it indicates, in hours, the maximum lifetime for a ticket. If a ticket has more time than what was initially defined and isn't close or solved, the SLA will go off.
		</li>
		<li>    
			<b>Max # of parallely open tickets:</b> indicates the total number of tickets that can remain open simultaneously. If there are more, the SLA will go off.
		</li>
		<li>    
			<b>Max. amount of inactive time: </b> Defines the maximum amount of time a ticket can remain inactive. When this time limit is surpassed an email will be sent to the person assigned as responsible for the ticket to remind him or her that the ticket is open.
		</li>
		<li>    
			<b>Start time for SLA activation:</b> time from which the SLA starts to be calculated (for example: 9:00 AM).
		</li>
		<li>    
			<b>End time for a SLA:</b> time from which the SLA is no longer calculated (for example: 6:00PM).
		</li>
		<li>    
			<b>Disabling SLA on the weekend:</b>  if this option is marked on the SLA it'll only calculate the weekdays, excluding weekends.
		</li>
		<li>    
			<b>Disabling the SLA on vacations:</b> if this option is marked on the SLA it won't calculate days defined as vacations.
		</li>
		<li>    
			<b>Description:</b> informative text used to describe the SLA.
		</li>	
	</ul>
</p>
<h1> What does it mean for the SLA to "go off"? </h1>
<p>
	It means that the system will send an email notification to the ticket owner, advising that the ticket isn't complying with parameters established on the SLA to which the ticket belongs. A ticket can be tied to different SLAs simultaneously, if it's related to different inventory elements. A SLA that has already gone off won't deactivate by itself just because, for example, it's the weekend. This means that any tickets that don't comply with an SLA, until all their conditions aren't met, won't return to a “normal status”.
</p>
<p>
	We can see the historic evolution for a ticket's SLA compliance, or the total compliance values in general reports. A low SLA will generally mean that the ticket hasn't been managed correctly. It's a largely used indicator to offer summaries on the quality of ticket management.
</p>
<p>
	When an SLA goes off a lit indicator will appear on the ticket view.
</p>
<p>
	Let's see an example of SLA definition:
</p>
<p>
	<?php print_image("images/help/ticket37.png", false); ?>
</p>
<p>
	SLA's are linked to ticket status. This means that, if the chosen type of SLA is Normal <b>the SLA won't apply for those tickets that are in either Closed or Pending on third party status.</b> If the type chosen is third party SLA <b> the SLA will only apply for tickets that are pending on third parties.</b> If Both are chosen <b>the SLA will apply for all tickets except those with a Closed status.</b>
</p>
<h1>SLA based ticket evaluation</h1>
<p>
	Using the SLA system and, on a ticket's “followup” report we can see, on a time scale, when a ticket hasn't complied (marked in red) and when it has (marked in green). Plus it includes % markers on the ticket's compliance along its entire life.
</p>
<p>
	<?php print_image("images/help/ticket38.png", false); ?>
</p>
