<h1>Search view</h1>
<p>
	Ticket searching is the basic control tool for tickets.
	</br>
	</br>
	You can use the basic search view to look at the tickets we want, or access the ticket number directly on the left side menu.<b> Default searches show all unclosed tickets </b> and those that are unsolved. Searches show a list and basic statistic information on the results of this search. You can see, while in fullscreen mode, printable screens by pressing the option to generate HTML report view on the “Statistics” tab.
</p>
<p>
	<?php print_image("images/help/ticket21.png", false, false); ?>
</p>
<p>
	The advanced search is similar to the basic search, with the difference that it adds a multitude of additional search filters. Any search can be saved as a custom search, so that with the combination of custom search selection any previous search can be accessed. Custom searches are different for each user.
</p>
<p>
	<?php print_image("images/help/ticket22.png", false, false); ?>
</p>
<p>
	By clicking on a ticket all its details can be accessed. This will activate the upper tabs in the tickets section and we'll be able to see its details, the inventory of linked items, review changes, add workunits, etc. Then environment for this is based on AJAX, which means it's not necessary to “refresh” the page. We can simply return to the search screen and access another ticket.
	</br>
	</br>
	All columns on the ticket search view can be sorted automatically by clicking on the title. They can be arranged by: date, title, number of work hours assigned, group, status, etc.
	</br>
	</br>
	Let's see the information that each row on the ticket list shows:
	</br>
	</br>
	<?php print_image("images/help/ticket23.png", false, false); ?>
	</br>
	</br>
	<ul>
		<li><b>ID:</b> The first column refers to the ticket's numeric code which can be used to directly access a specific ticket.</li>
		<li><b>SLA:</b> an exclamation mark to let the user know that a ticket doesn't comply with the SLA.</li>
		<li><b>Ticket:</b> it's the ticket's title. Underneath the ticket type will appear and custom ticket type fields will appear between brackets and marked to be shown on the main view.</li>
		<li><b>Group:</b> The group to which the ticket belongs. Underneath it is the name of the company to which the user who created the ticket belongs (whenever such information is available).</li>
		<li><b>Status / Solution:</b>  the status (closed, assigned, pending on closure, new, solved and unconfirmed) and the solution level (solved, incomplete, “it works for me”, expired, etc.).</li>
		<li><b>Priority:</b> marks different colors depending on ticket priority. Optionally, an icon, such as a note, can appear underneath the color tag like on the previous screenshot. This indicates that the ticket has a WU from the ticket's original author, which means that we “should answer” or that the “ball is in our yard”.</li>
		<li><b>Updated / created:</b>  indicates when the last ticket was updated and when it was created. If an username appears, it belongs to the person who created the latest WU on the ticket.</li>
		<li><b>Author:</b>  shows the ticket author.</li>
		<li><b>Owner:</b> shows the name of the user assigned to the ticket, the owner and only user who can close it (except for users with more privileges: admins or managers).</li>
	</ul>
</p>