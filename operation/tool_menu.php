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

// Sponsors / Banner
echo "<nav id='menu_nav'>";
	echo "<ul id='menu_slide2'>";
		echo "<li title='".__('Links')."' data-status='closed' id='enlaces'>";
			//echo "<a title='".__('Links')."'href='#'>1</a>";
			echo "<ul>";
				echo "<li><h1>".__('Links')."</h1></li>";
				$sql1='SELECT * FROM tlink ORDER BY name';
				$result=mysql_query($sql1);
				if ($row=mysql_fetch_array($result)){
					$sql1='SELECT * FROM tlink ORDER BY name';
					$result2=mysql_query($sql1);
					while ($row2=mysql_fetch_array($result2)){
						echo "<li><a href='".$row2["link"]."' target='_new' class='mn'>".$row2["name"]."</a></li>";
					}
				}
			echo "</ul>";
		echo "</li>";
	echo "</ul>";
echo "</nav>";

// Banners
/*
echo '<div class="portlet">';
echo "<h3 class='system'>".__('Our sponsors')."</h3>";
echo "<p>";
echo "<img src='images/minilogoartica.jpg'>";
echo "<br><br>";
echo "<img src='images/sflogo.png'>";
echo "<br><br>";
echo "</p>";
echo "</div>";
*/

?>
