<h1>Leads</h1>
<p>
	Through Integria's lead management we can perform a followup on possible customers. Generally these leads come in through “external” routes (through API), although they can also be created manually from the editor. Just like other items on Integria, a Lead has an “Owner” which is the person who manages it.
</p>
<p>
	The system allows users to create notes on that lead's activity, and modify its status, so to make it progress from an “unclassified” lead to a closed sale (or loss). Leads can be forwarded (via email) or managed directly from the tool via email. If these actions are performed directly from Integria, the delivery and reception of email replies can be managed, and will be reflected in the lead's followup process, since it includes Integria's address in the CC field to “capture” the lead's email replies.
</p>
<p>
	<?php print_image("images/help/company17.png", false, false); ?>
</p>
<h2>Managing lead activity via email</h2>
<p>
	Integria IMS allows the user to manage the commercial activity of leads using email.s This <b>Enterprise</b> featur, will allow you to update the conversation between both parties and upload files automatically.
</p>
<p>
	For it an email inbox is used as a reference from which Integria will read the emails to extract the information and the attachments. In the section named Email Configuration all details on the inbox's configuration can be seen.
</p>
<p>
	This feature is used through the option <b>Email Reply</b> available for leads. With this option Integria will send an email message adding a <b>[Lead#35]</b> type token at the beginning of the email's subject. Apart from sending the emails the address for the referencial mailbox can be found in the <b>CC</b> field of the email.
</p>
<p>
	This way, when a customer or salesperson reply to the email, a copy will reach Integria's referencial inbox and Integria will identify the message and process it. To update the lead, information will be added to the email's body in the lead activity, and all attached files will be uploaded to the server and be linked to the corresponding lead.
</p>
<p>
	<i>
		It's very important that both your customers and your salesforce use the **Reply to All** function in their email client to guarantee that a copy of the email reaches the referencial inbox listed in the CC field. 
	</i>
</p>