<?php

//Config parameters
$integria_url = "http://localhost/integria-code";
$user = "user";
$pass = "integria";
$group = 12;
$inventory = 0;


//Get post parameters
$title = $_POST["title"];
$priority = $_POST["priority"];
$email = $_POST["email"];
$description = $_POST["description"];


if (isset($_POST["submit"])) {
	//Create url to call the API
	$myurl = $integria_url."/include/api.php?user=".$user."&pass=".$pass."&op=create_incident&"."params=".urlencode($title).",".urlencode($group).",";
	$myurl .= urlencode($priority).",".urlencode($description).",".urlencode($inventory).",".urlencode($email);

	// Configure curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $myurl);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

	// Send curl request and close
	$ret = curl_exec($ch);
	curl_close ($ch);

	if ($ret == false) {
		echo "<h3><font color='red'>Error creating ticket</font></h3>";
	} else {
		echo "<h3><font color='green'>Created ticket ".$ret."</font></h3>";
	}
}

?>

<form method="post">
<div style="width: 666px">
	<h2>Integria IMS. Ticket Creation Form.</h2>
	<hr style="color: #FF9933;" />
</div>

<table style="width: 40%" cellspacing="10" bgcolor="#e6e6e6">
	<tr>
		<td style="width: 200px" bgcolor="#e6e6e6">
			<b>Title</b>
		</td> 
		<td style="width: 400px" bgcolor="#e6e6e6">
			<input type="text" name="title" style="width: 400px" >
		</td>
	</tr>
	<tr>
		<td style="width: 200px">
			<b>Priority</b>
		</td> 
		<td style="width: 400px">
			<select name="priority" style="width: 170px" >
				<option value="10">0 (Maintenance)</option>
				<option value="0">1 (Informative)</option>
				<option value="1">2 (Low)</option>
				<option value="2" selected="">3 (Medium)</option>
				<option value="3">4 (Serious)</option>
				<option value="4">5 (Very serious)</option>
			</select>
		</td>
	</tr>
	<tr>
		<td style="width: 200px">
			<b>Email</b>
		</td> 
		<td style="width: 400px">
			<input type="text" name="email" style="width: 400px" >
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<b>Description</b>
			<br>
			<textarea name="description" cols="80" rows="8"></textarea>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="width: 320px" align="right">
			<div  >
				<input type="submit" name="submit" value="Submit">
			</div>
		</td>
	</tr>
</table>
</form>
