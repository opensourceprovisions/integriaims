<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


$sql1='SELECT * FROM tlink ORDER BY name';
$result=mysql_query($sql1);
if ($row=mysql_fetch_array($result)){
?>
<div class="bg4">
	<div class="imgl"><img src="images/upper-left-corner.gif" width="5" height="5" alt=""></div>
	<div class="tit">:: <?php echo __('Links') ?> ::</div>
	<div class="imgr"><img src="images/upper-right-corner.gif" width="5" height="5" alt=""></div>
</div>
	<div id="menul">
	<div id="link">
<?php
	$sql1='SELECT * FROM tlink ORDER BY name';
	$result2=mysql_query($sql1);
		while ($row2=mysql_fetch_array($result2)){
			echo "<div class='linkli'><ul class='mn'><li><a href='".$row2["link"]."' target='_new' class='mn'>".$row2["name"]."</a></li></ul></div>";
		}
	echo "</div></div>";
}
?>
