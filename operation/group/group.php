<?php
// Integria 1.1 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2010 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

if (defined ('AJAX')) {
	
	ob_clean();
	
	$id_group = get_parameter('id_group');
	$id_user = get_parameter('id_user');

	if (($id_group == null) || ($id_user == null)) {
		echo "//";
		echo "null";
		return;
	}
	
	$group = get_db_row_filter('tgrupo', array('id_grupo' => $id_group));
	//soft limit is open incidents.
	//hard limit is count all incidents.

	if (($group['hard_limit'] == 0) && ($group['soft_limit'] == 0)) {
		echo "correct"; //type			
		$inventoryObject = get_db_row_sql('SELECT * FROM tinventory
			WHERE id IN (
			SELECT id_inventory_default
			FROM tgrupo
			WHERE id_grupo = ' . $id_group . ')');
		
		if ($inventoryObject !== false) {
			echo "//";
			echo $inventoryObject['id'];
			echo "//";
			echo $inventoryObject['name'];
		}
		else {
			echo "//";
			echo "null";
		}
	}
	else {
		
		$external = get_db_value ("nivel", "tusuario", "id_usuario", $id_user);

		$now = print_mysql_timestamp();
		$year_in_seconds = 3600 * 24 * 365;

		$year_ago_unix = time() - $year_in_seconds;

		$year_ago = date("Y-m-d H:i:s", $year_ago_unix);

		//If external user check for group and user's incidents
		if ($external == -1) {
		
			$countOpen = get_db_all_rows_sql('SELECT COUNT(*) AS c
				FROM tincidencia WHERE estado IN (1,2,3,4,5) AND id_grupo = ' . $id_group . ' AND id_creator = "' . $id_user . '" AND inicio <= "' . $now . '" AND inicio >= "' . $year_ago . '"');
			$countAll = get_db_all_rows_sql('SELECT COUNT(*) AS c
				FROM tincidencia WHERE id_grupo = ' . $id_group . ' AND id_creator = "' . $id_user . '" AND inicio <= "' . $now . '" AND inicio >= "' . $year_ago . '"');
			$countOpen = $countOpen[0]['c'];
			$countAll = $countAll[0]['c'];
			
		} else {
			//If not external check only for group's incidents
			
			$countOpen = get_db_all_rows_sql('SELECT COUNT(*) AS c
				FROM tincidencia WHERE estado IN (1,2,3,4,5) AND id_grupo = ' . $id_group . ' AND inicio <= "' . $now . '" AND inicio >= "' . $year_ago . '"');
			$countAll = get_db_all_rows_sql('SELECT COUNT(*) AS c
				FROM tincidencia WHERE id_grupo = ' . $id_group . ' AND inicio <= "' . $now . '" AND inicio >= "' . $year_ago . '"');
			$countOpen = $countOpen[0]['c'];
			$countAll = $countAll[0]['c'];
		}
			
		if (($group['hard_limit'] != 0) && ($group['hard_limit'] <= $countAll)) {
			echo "incident_limit"; //type
			echo "//";
			echo __('Limit of tickets reached'); //title
			echo "//";
			echo __('You have reached the limit of tickets for this group for a year') . "(".$group['hard_limit'] . "). ". __('You cannot create more tickets.'); //content
			echo "//";
			echo "disable_button";
		}
		else if (($group['soft_limit'] != 0) && ($group['soft_limit'] <= $countOpen)) {
			echo "open_limit"; //type
			echo "//";
			echo __('Warning: Soft limit reached'); //title
			echo "//";
			echo __('You have ') . $countOpen . __(' opened tickets') . ".". __("Soft limit for a year for this group is "). " ( ".$group['soft_limit'] . " ) ". __(' tickets'). ".". __("Please close some tickets before create more"); //content
			
			if ($group['enforce_soft_limit'] == 0) {
				echo "//";
				echo "enable_button";
			}
			else {
				echo ".<br><br> ". __('You cannot create more tickets in this group until you close an active ticket.');
				echo "//";
				echo "disable_button";
			}
		} 
		else {
			echo "correct";

			$inventoryObject = get_db_row_sql('SELECT * FROM tinventory
				WHERE id IN (
				SELECT id_inventory_default
				FROM tgrupo
				WHERE id_grupo = ' . $id_group . ')');
			
			if ($inventoryObject !== false) {
				echo "//";
				echo $inventoryObject['id'];
				echo "//";
				echo $inventoryObject['name'];
			}
			else {
				echo "//";
				echo "null";
			}
		}
	}

	return;
}
?>
