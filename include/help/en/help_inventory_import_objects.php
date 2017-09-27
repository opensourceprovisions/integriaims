<h1>Importing inventory data from a CSV file</h1>
<p>
	There is an option to import inventory data from a CSV file. For this, the file is uploaded with a line per inventory and values separated by commas. The order must be the following:
</p>
<p>
	ID Item type, Owner, Name, Viewers, Description, Contract, Manufacturer ID, Parent ID, Related company 1;…;Related company N;RelatedUser1;RelatedUserN,Status,Field1 for item type,…,FieldN for item type.
</p>
<p>
	Statuses an inventory can adopt are:
	</br>
	</br>
	<ul>
		<li><b>new:</b></li>
		<li><b>inuse:</b></li>
		<li><b>unused:</b></li>
		<li><b>issued:</b></li>
	</ul>
</p>
<p>
	<i><b>Example:</b> 19,admin,test inventory,1,describing inventory,5,1,0,6;7,admin;user,inuse,Linux,CentOS</i>
</p>