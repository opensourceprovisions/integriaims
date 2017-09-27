<?php 

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2007-2011 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

// @package OBJECTS
// Inventory objects functions library


/**
 * Calculate task completion porcentage and set on task
 *
 * @param int Id of the task to calculate.
 */
 
 function objects_get_icon ($id_object, $return = false) {
	$output = '';
	
	$icon = (string) get_db_value ('icon', 'tobject_type', 'id', $id_object);

	$output .= '<img id="product-icon" width="16" height="16" ';
	if ($icon != '') {
		$output .= 'src="images/objects/'.$icon.'"';
	} else {
		$output .= 'src="images/pixel_gray.png" style="display:none"';
	}
	$output .= ' />';
	
	if ($return)
		return $output;
	echo $output;
}
 
/**
 * Count all fields in one object
 *
 * @param int Id of the object.
 */
 
function objects_count_fields ($id_object) {	
	$number = (int) get_db_value ('COUNT(*)', 'tobject_type_field', 'id_object_type', $id_object);
	
	return $number;
}

/**
 * Get all types of objects
 *
 */
 
function object_get_types () {	
	$object_types = array();
	
	$object_types['numeric'] = __('Numeric');
	$object_types['text'] = __('Text');
	$object_types['combo'] = __('Combo');
	$object_types['external'] = __('External');
	$object_types['date'] = __('Date');

	return $object_types;
}

 
?>
