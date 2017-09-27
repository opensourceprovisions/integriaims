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

global $config;

//-- Constants --//

// Types
define('LEAD', 'lead');

// Table identifiers
define('TAGS_TABLE', 'ttag');
define('TAGS_TABLE_ID_COL', 'id');
define('TAGS_TABLE_NAME_COL', 'name');
define('TAGS_TABLE_COLOUR_COL', 'colour');

define('LEADS_TABLE', 'tlead_tag');
define('LEADS_TABLE_ID_COL', 'id');
define('LEADS_TABLE_TAG_ID_COL', 'tag_id');
define('LEADS_TABLE_LEAD_ID_COL', 'lead_id');

// Available tag colours
define('TAG_BLUE', 'blue');
define('TAG_GREY', 'grey');
define('TAG_GREEN', 'green');
define('TAG_YELLOW', 'yellow');
define('TAG_ORANGE', 'orange');
define('TAG_RED', 'red');

//-- Data retrieving functions --//

/** 
 * Get all the available sections which use tags.
 * 
 * @return array The list of the sections.
 */
function get_available_tag_sections () {
	$sections = array(
			LEAD => __('Lead')
		);
	
	return $sections;
}

/** 
 * Get all the available tag colours.
 * 
 * @return array The list of the tag colours.
 */
function get_available_tag_colours () {
	$tag_colours = array(
			TAG_ORANGE => __('Orange'),
			TAG_BLUE => __('Blue'),
			TAG_GREY => __('Grey'),
			TAG_GREEN => __('Green'),
			TAG_YELLOW => __('Yellow'),
			TAG_RED => __('Red')
		);
	
	return $tag_colours;
}

/** 
 * Check if the name of the tag exists.
 * 
 * @param string Tag name.
 * 
 * @return bool Wether te tag exists or not.
 */
function exists_tag_name ($name) {
	if (empty($name))
		throw new InvalidArgumentException(__('The name cannot be empty'));
	
	$result = (bool) get_db_value(TAGS_TABLE_ID_COL, TAGS_TABLE, TAGS_TABLE_NAME_COL, $name);
	
	return $result;
}

/** 
 * Create a tag.
 * 
 * @param array Values of the tag.
 * 
 * @return mixed The id of the created item (int) of false (bool) on error.
 */
function create_tag ($values) {
	if (empty($values))
		throw new InvalidArgumentException(__('The values cannot be empty'));
	if (empty($values[TAGS_TABLE_NAME_COL]))
		throw new InvalidArgumentException(__('The name cannot be empty'));
	if (strlen($values[TAGS_TABLE_NAME_COL]) > 255)
		throw new InvalidArgumentException(__('The name is too big'));
	
	$result = process_sql_insert(TAGS_TABLE, $values);
	
	return $result;
}

/** 
 * Delete a tag.
 * 
 * @param int Id of the tag.
 * @param array Values of the tag.
 * 
 * @return mixed The number of the items updated (int) of false (bool) on error.
 */
function update_tag ($id, $values) {
	if (empty($id) || !is_numeric($id))
		throw new InvalidArgumentException(__('ID should be numeric'));
	if ($id <= 0)
		throw new RangeException(__('ID should be a number greater than 0'));
	if (isset($values[TAGS_TABLE_NAME_COL]) && empty($values[TAGS_TABLE_NAME_COL]))
		throw new InvalidArgumentException(__('The name cannot be empty'));
	if (isset($values[TAGS_TABLE_NAME_COL]) && strlen($values[TAGS_TABLE_NAME_COL]) > 255)
		throw new InvalidArgumentException(__('The name is too big'));
	
	$where = array(TAGS_TABLE_ID_COL => $id);
	$result = process_sql_update(TAGS_TABLE, $values, $where);
	
	return $result;
}

/** 
 * Delete a tag.
 * 
 * @param int Id of the tag.
 * 
 * @return mixed The number of the items deleted (int) of false (bool) on error.
 */
function delete_tag ($id) {
	if (empty($id) || !is_numeric($id))
		throw new InvalidArgumentException(__('ID should be numeric'));
	if ($id <= 0)
		throw new RangeException(__('ID should be a number greater than 0'));
	
	$where = array(TAGS_TABLE_ID_COL => $id);
	$result = process_sql_delete(TAGS_TABLE, $where);
	
	return $result;
}

/** 
 * Get the all the available tags.
 * 
 * @param array (optional) Filter of the tags.
 * 
 * @return array The list of the tags with all the rows.
 */
function get_available_tags ($filter = array()) {
	global $config;
	
	$id = isset($filter[TAGS_TABLE_ID_COL]) ? $filter[TAGS_TABLE_ID_COL] : 0;
	$name = isset($filter[TAGS_TABLE_NAME_COL]) ? $filter[TAGS_TABLE_NAME_COL] : '';
	$colour = isset($filter[TAGS_TABLE_COLOUR_COL]) ? $filter[TAGS_TABLE_COLOUR_COL] : '';
	
	$id_filter = '';
	if (empty($id)) {
		$id_filter = '1=1';
	}
	else if (is_array($id)) {
		$id_filter = sprintf(
				'tt.%s IN (%s)',
				TAGS_TABLE_ID_COL,
				implode(',', $id)
			);
	}
	else {
		$id_filter = sprintf(
				'tt.%s = %d',
				TAGS_TABLE_ID_COL,
				$id
			);
	}
	
	$name_filter = '';
	if (empty($name)) {
		$name_filter = '1=1';
	}
	else if (is_array($name)) {
		$name_filter = sprintf(
				'tt.%s IN (\'%s\')',
				TAGS_TABLE_NAME_COL,
				implode('\',\'', $name)
			);
	}
	else {
		$name_filter = sprintf(
				'tt.%s = \'%s\'',
				TAGS_TABLE_NAME_COL,
				$name
			);
	}
	
	$colour_filter = '';
	if (empty($colour)) {
		$colour_filter = '1=1';
	}
	else if (is_array($colour)) {
		$colour_filter = sprintf(
				'tt.%s IN (\'%s\')',
				TAGS_TABLE_COLOUR_COL,
				implode('\',\'', $colour)
			);
	}
	else {
		$colour_filter = sprintf(
				'tt.%s = \'%s\'',
				TAGS_TABLE_COLOUR_COL,
				$colour
			);
	}	
	
	$sql = sprintf('SELECT tt.*
					FROM %s tt
					WHERE %s
						AND %s
						AND %s',
					TAGS_TABLE,
					$id_filter,
					$name_filter,
					$colour_filter);
	$tags = get_db_all_rows_sql($sql);
	if (empty($tags)) $tags = array();
	
	return $tags;
}

/** 
 * Get the all the available tag indexed.
 * 
 * @param array (optional) Filter of the tags.
 * 
 * @return array The list of the tag indexed.
 */
function get_available_tags_indexed ($filter = array()) {
	global $config;
	
	$tags = get_available_tags($filter);
	$tag_ids = array_map(function ($tag) {
		return $tag[TAGS_TABLE_ID_COL];
	}, $tags);
	$tag_names = array_map(function ($tag) {
		return $tag[TAGS_TABLE_NAME_COL];
	}, $tags);
	$tags_indexed = array_combine($tag_ids, $tag_names);
	
	return $tags_indexed;
}

/** 
 * Get the all the available tag names.
 * 
 * @param array (optional) Filter of the tags.
 * 
 * @return array The list of the tag names.
 */
function get_available_tag_names ($filter = array()) {
	global $config;
	
	$tags = get_available_tags($filter);
	$tag_names = array_map(function ($tag) {
		return $tag[TAGS_TABLE_NAME_COL];
	}, $tags);
	
	return $tag_names;
}

/** 
 * Get the item ids with ALL the tags of the filter assigned.
 * If the tag filter is empty, it will return the item ids with any tag assigned.
 *
 * It's important to notice this:
 * tag_filter -> [1,2,3]
 * Item with tags 1 and 2 -> Not returned
 * Item with tags 1, 2 and 3 -> Returned
 * Item with tags 1, 2, 3 and 4 -> Returned
 * 
 * @param string Type of the item.
 * @param array [Optional] Items to filter the tags.
 * 
 * @return array The list of the item ids.
 */
function get_items_with_tags ($item_type, $tag_filter = array()) {
	global $config;
	
	$item_table_name = '';
	$item_table_tag_id_column = '';
	$item_table_item_id_column = '';
	
	switch ($item_type) {
		case LEAD:
			$item_table_name = LEADS_TABLE;
			$item_table_tag_id_column = LEADS_TABLE_TAG_ID_COL;
			$item_table_item_id_column = LEADS_TABLE_LEAD_ID_COL;
			break;
		default:
			break;
	}
	
	// Tag filter
	$tag_id = isset($tag_filter[TAGS_TABLE_ID_COL]) ? $tag_filter[TAGS_TABLE_ID_COL] : 0;
	$tag_name = isset($tag_filter[TAGS_TABLE_NAME_COL]) ? $tag_filter[TAGS_TABLE_NAME_COL] : '';
	$tag_colour = isset($tag_filter[TAGS_TABLE_COLOUR_COL]) ? $tag_filter[TAGS_TABLE_COLOUR_COL] : '';
	
	$item_ids_id_filtered = array();
	if (! empty($tag_id)) {
		if (!is_array($tag_id))
			$tag_id = array($tag_id);
		
		$num_tags = count($tag_id);
		
		$tag_id_filter = sprintf(
				'ti.%s IN (%s)',
				TAGS_TABLE_ID_COL,
				implode(',', $tag_id)
			);
		
		$sql = sprintf('SELECT ti.%s, COUNT(ti.%s) AS num_tags
						FROM %s ti
						WHERE ti.%s IN (%s)
						GROUP BY ti.%s
						HAVING num_tags >= %d',
						$item_table_item_id_column,
						$item_table_item_id_column,
						$item_table_name,
						$item_table_tag_id_column,
						implode(',', $tag_id),
						$item_table_item_id_column,
						$num_tags);
		$items_id_filtered = get_db_all_rows_sql($sql);
		if (empty($items_id_filtered)) $items_id_filtered = array();
		
		$item_ids_id_filtered = array_map(function ($item) use ($item_table_item_id_column) {
			return $item[$item_table_item_id_column];
		}, $items_id_filtered);
	}
	
	// Only supported filtering by id for now
	
	$sql = sprintf('SELECT ti.%s
					FROM %s ti
					INNER JOIN %s tt
						ON ti.%s = tt.%s',
					$item_table_item_id_column,
					$item_table_name,
					TAGS_TABLE,
					$item_table_tag_id_column,
					TAGS_TABLE_ID_COL);
	$items = get_db_all_rows_sql($sql);
	if (empty($items)) $items = array();
	
	$item_ids = array_map(function ($item) use ($item_table_item_id_column) {
		return $item[$item_table_item_id_column];
	}, $items);
	
	// Get the intersection with the filtered items
	if (! empty($tag_id)) {
		$item_ids = array_intersect($item_ids_id_filtered, $item_ids);
	}
	
	return $item_ids;
}

/** 
 * Get the tags assigned to an item.
 * If the item id is empty, it will return the tags assigned to any item.
 * 
 * @param string Type of the item.
 * @param array [Optional] Items to filter the items.
 * @param array [Optional] Items to filter the tags.
 * 
 * @return array The list of the tags with all the rows.
 */
function get_tags ($item_type, $item_filter = array(), $tag_filter = array()) {
	global $config;
	
	$item_table_name = '';
	$item_table_tag_id_column = '';
	$item_table_item_id_column = '';
	
	switch ($item_type) {
		case LEAD:
			$item_table_name = LEADS_TABLE;
			$item_table_tag_id_column = LEADS_TABLE_TAG_ID_COL;
			$item_table_item_id_column = LEADS_TABLE_LEAD_ID_COL;
			break;
		default:
			break;
	}
	
	// Item filter
	$item_id = isset($item_filter[$item_table_item_id_column]) ? $item_filter[$item_table_item_id_column] : 0;
	if (empty($item_id)) {
		$item_id_filter = '1=1';
	}
	else if (is_array($item_id)) {
		$item_id_filter = sprintf(
				'ti.%s IN (%s)',
				$item_table_item_id_column,
				implode(',', $item_id)
			);
	}
	else {
		$item_id_filter = sprintf(
				'ti.%s = %d',
				$item_table_item_id_column,
				$item_id
			);
	}
	
	// Tag filter
	$tag_id = isset($tag_filter[TAGS_TABLE_ID_COL]) ? $tag_filter[TAGS_TABLE_ID_COL] : 0;
	$tag_name = isset($tag_filter[TAGS_TABLE_NAME_COL]) ? $tag_filter[TAGS_TABLE_NAME_COL] : '';
	$tag_colour = isset($tag_filter[TAGS_TABLE_COLOUR_COL]) ? $tag_filter[TAGS_TABLE_COLOUR_COL] : '';
	
	$tag_id_filter = '';
	if (empty($tag_id)) {
		$tag_id_filter = '1=1';
	}
	else if (is_array($tag_id)) {
		$tag_id_filter = sprintf(
				'tt.%s IN (%s)',
				TAGS_TABLE_ID_COL,
				implode(',', $tag_id)
			);
	}
	else {
		$tag_id_filter = sprintf(
				'tt.%s = %d',
				TAGS_TABLE_ID_COL,
				$tag_id
			);
	}
	
	$tag_name_filter = '';
	if (empty($tag_name)) {
		$tag_name_filter = '1=1';
	}
	else if (is_array($tag_name)) {
		$tag_name_filter = sprintf(
				'tt.%s IN (\'%s\')',
				TAGS_TABLE_NAME_COL,
				implode('\',\'', $tag_name)
			);
	}
	else {
		$tag_name_filter = sprintf(
				'tt.%s = \'%s\'',
				TAGS_TABLE_NAME_COL,
				$tag_name
			);
	}
	
	$tag_colour_filter = '';
	if (empty($tag_colour)) {
		$tag_colour_filter = '1=1';
	}
	else if (is_array($tag_colour)) {
		$tag_colour_filter = sprintf(
				'tt.%s IN (\'%s\')',
				TAGS_TABLE_COLOUR_COL,
				implode('\',\'', $tag_colour)
			);
	}
	else {
		$tag_colour_filter = sprintf(
				'tt.%s = \'%s\'',
				TAGS_TABLE_COLOUR_COL,
				$tag_colour
			);
	}
	
	$sql = sprintf('SELECT tt.*
					FROM %s tt
					INNER JOIN %s ti
						ON tt.%s = ti.%s
							AND %s
					WHERE %s
						AND %s
						AND %s',
					TAGS_TABLE,
					$item_table_name,
					TAGS_TABLE_ID_COL,
					$item_table_tag_id_column,
					$item_id_filter,
					$tag_id_filter,
					$tag_name_filter,
					$tag_colour_filter);
	$tags = get_db_all_rows_sql($sql);
	if (empty($tags)) $tags = array();
	
	return $tags;
}

/** 
 * Get the tags assigned to an item as a pair of index => name.
 * 
 * @param string Type of the item.
 * @param array [Optional] Items to filter the items.
 * @param array [Optional] Items to filter the tags.
 * 
 * @return array The list of the tag indexed.
 */
function get_tags_indexed ($item_type, $item_filter = array(), $tag_filter = array()) {
	global $config;
	
	$tags = get_tags($item_type, $filter);
	$tag_ids = array_map(function ($tag) {
		return $tag[TAGS_TABLE_ID_COL];
	}, $tags);
	$tag_names = array_map(function ($tag) {
		return $tag[TAGS_TABLE_NAME_COL];
	}, $tags);
	$tags_indexed = array_combine($tag_ids, $tag_names);
	
	return $tags_indexed;
}

/** 
 * Get the tag ids assigned to an item.
 * 
 * @param string Type of the item.
 * @param array [Optional] Items to filter the items.
 * @param array [Optional] Items to filter the tags.
 * 
 * @return array The list of the tag ids.
 */
function get_tag_ids ($item_type, $item_filter = array(), $tag_filter = array()) {
	global $config;
	
	$tags = get_tags($item_type, $item_filter, $tag_filter);
	$tag_ids = array_map(function ($tag) {
		return $tag[TAGS_TABLE_ID_COL];
	}, $tags);
	
	return $tag_ids;
}

/** 
 * Get the tags names assigned to an item.
 * 
 * @param string Type of the item.
 * @param array [Optional] Items to filter the items.
 * @param array [Optional] Items to filter the tags.
 * 
 * @return array The list of the tag names.
 */
function get_tag_names ($item_type, $item_filter = array(), $tag_filter = array()) {
	global $config;
	
	$tags = get_tags($item_type, $item_filter, $tag_filter);
	$tag_names = array_map(function ($tag) {
		return $tag[TAGS_TABLE_NAME_COL];
	}, $tags);
	
	return $tag_names;
}

// Leads

/** 
 * Check if a tag is assigned to a lead.
 * 
 * @param int Id of the lead.
 * @param int Id of the tag.
 * 
 * @return bool Wether the tag is assigned or not.
 */
function exists_lead_tag ($lead_id, $tag_id) {
	if (empty($lead_id))
		throw new InvalidArgumentException(__('The lead id cannot be empty'));
	if (empty($tag_id))
		throw new InvalidArgumentException(__('The tag id cannot be empty'));
	
	$filter = array(LEADS_TABLE_LEAD_ID_COL => $lead_id, LEADS_TABLE_TAG_ID_COL => $tag_id);
	return (bool)get_db_value_filter(LEADS_TABLE_ID_COL, LEADS_TABLE, $filter);
}

/** 
 * Assign a tag to a lead.
 * This process will delete the lead tags and assign the new.
 * 
 * @param mixed Id (int) or ids (array) of the lead.
 * @param mixed Id (int) or ids (array) of the tag.
 * 
 * @return mixed The number of assigned tags of false (bool) on error.
 */
function create_lead_tag ($lead_id, $tag_id) {
	if (empty($lead_id))
		throw new InvalidArgumentException(__('The lead id cannot be empty'));
	if (empty($tag_id))
		throw new InvalidArgumentException(__('The tag id cannot be empty'));
	
	if (!is_array($lead_id))
		$lead_id = array($lead_id);
	if (!is_array($tag_id))
		$tag_id = array($tag_id);
	
	$expected_assingments = count($lead_id) * count($tag_id);
	$successfull_assingments = 0;
	
	// Delete the old tags
	$delete_res = process_sql_delete(LEADS_TABLE, array(LEADS_TABLE_LEAD_ID_COL => $lead_id));
	
	if ($delete_res !== false) {
		foreach ($lead_id as $l_id) {
			if (is_numeric($l_id) && $l_id > 0) {
				foreach ($tag_id as $t_id) {
					if (is_numeric($t_id) && $t_id > 0) {
						$values = array(
								LEADS_TABLE_LEAD_ID_COL => $l_id,
								LEADS_TABLE_TAG_ID_COL => $t_id
							);
						$result = process_sql_insert(LEADS_TABLE, $values);
						
						if ($result !== false)
							$successfull_assingments++;
					}
				}
			}
		}
	}
	
	if ($delete_res === false || ($expected_assingments > 0 && $successfull_assingments === 0))
		$successfull_assingments = false;
	
	return $successfull_assingments;
}

/** 
 * Assign a tag to a lead.
 * This process will delete the lead tags and assign the new.
 * 
 * @param mixed Id (int) or ids (array) of the lead.
 * @param mixed Name (string) or names (array) of the tag.
 * @param bool 	Wether html encode the names or not.
 * 
 * @return mixed The number of assigned tags of false (bool) on error.
 */
function create_lead_tag_with_names ($lead_id, $tag_name, $encode_names = false) {
	if (empty($lead_id))
		throw new InvalidArgumentException(__('The lead id cannot be empty'));
	if (empty($tag_name))
		throw new InvalidArgumentException(__('The tag name cannot be empty'));
	
	if (!is_array($lead_id))
		$lead_id = array($lead_id);
	if (!is_array($tag_name))
		$tag_name = array($tag_name);
	
	if ($encode_names)
		$tag_name = safe_input($tag_name);
	
	$expected_assingments = count($lead_id) * count($tag_name);
	$successfull_assingments = 0;
	
	// Delete the old tags
	$delete_res = process_sql_delete(LEADS_TABLE, array(LEADS_TABLE_LEAD_ID_COL => $lead_id));
	
	if ($delete_res !== false) {
		foreach ($lead_id as $l_id) {
			if (is_numeric($l_id) && $l_id > 0) {
				foreach ($tag_name as $t_name) {
					if (!empty($t_name)) {
						$tag_id = get_db_value(TAGS_TABLE_ID_COL, TAGS_TABLE, TAGS_TABLE_NAME_COL, $t_name);
						
						if (is_numeric($tag_id) && $tag_id > 0) {
							$values = array(
									LEADS_TABLE_LEAD_ID_COL => $l_id,
									LEADS_TABLE_TAG_ID_COL => $tag_id
								);
							$result = process_sql_insert(LEADS_TABLE, $values);
							
							if ($result !== false)
								$successfull_assingments++;
						}
					}
				}
			}
		}
	}
	
	if ($delete_res === false || ($expected_assingments > 0 && $successfull_assingments === 0))
		$successfull_assingments = false;
	
	return $successfull_assingments;
}

/** 
 * Get the tags assigned to a lead.
 * 
 * @param mixed Id (int) or ids (array) of the lead/s.
 * @param array [Optional] Items to filter the tags.
 * 
 * @return array The list of the tags with all the rows.
 */
function get_lead_tags ($lead_id = false, $tag_filter = array()) {
	$lead_filter = array();
	if (empty($lead_id))
		$lead_filter = array(LEADS_TABLE_LEAD_ID_COL => $lead_id);
	
	return get_tags(LEAD, $lead_filter, $tag_filter);
}

/** 
 * Get the tags assigned to a lead as a pair of index => name.
 * 
 * @param mixed Id (int) or ids (array) of the lead/s.
 * @param array [Optional] Items to filter the tags.
 * 
 * @return array The list of the tag indexed.
 */
function get_lead_tags_indexed ($lead_id = false, $tag_filter = array()) {
	$lead_filter = array();
	if (empty($lead_id))
		$lead_filter = array(LEADS_TABLE_LEAD_ID_COL => $lead_id);
	
	return get_tags_indexed(LEAD, $lead_filter, $tag_filter);
}

/** 
 * Get the tag tds assigned to a lead.
 * 
 * @param mixed Id (int) or ids (array) of the lead/s.
 * @param array [Optional] Items to filter the tags.
 * 
 * @return array The list of the tag ids.
 */
function get_lead_tag_ids ($lead_id = false, $tag_filter = array()) {
	$lead_filter = array();
	if (!empty($lead_id))
		$lead_filter = array(LEADS_TABLE_LEAD_ID_COL => $lead_id);
	
	return get_tag_ids(LEAD, $lead_filter, $tag_filter);
}

/** 
 * Get the tags names assigned to a lead.
 * 
 * @param mixed Id (int) or ids (array) of the lead/s.
 * @param array [Optional] Items to filter the tags.
 * 
 * @return array The list of the tag names.
 */
function get_lead_tag_names ($lead_id = false, $tag_filter = array()) {
	$lead_filter = array();
	if (empty($lead_id))
		$lead_filter = array(LEADS_TABLE_LEAD_ID_COL => $lead_id);
	
	return get_tag_names(LEAD, $lead_filter, $tag_filter);
}


/** 
 * Get the leads with the selected tags assigned.
 * 
 * @param array Tag filter.
 * 
 * @return array The lead ids with the tags assigned.
 */
function get_leads_with_tags ($tag_filter = array()) {
	return get_items_with_tags(LEAD, $tag_filter);
}

//-- HTML elements functions --//

function html_render_tags_editor ($props, $return = false) {
	// Defaults
	$tags;
	$selected_tags = array();
	$select_name = 'tags[]';
	$any = false;
	$label = __('Selected tags');
	$disabled = false;
	$visible = false;
	
	if (!isset($props))
		$props = array();
	
	if (isset($props['tags'])) {
		$tags = $props['tags'];
	}
	else {
		$tags = get_available_tags_indexed();
		
		$tags = get_available_tags();
		$tag_ids = array_map(function ($tag) {
			return $tag[TAGS_TABLE_ID_COL];
		}, $tags);
		$tags = array_combine($tag_ids, $tags);
	}
	
	// Selected tags
	if (isset($props['selected_tags']))
		$selected_tags = $props['selected_tags'];
	// Select name
	if (isset($props['select_name']))
		$select_name = $props['select_name'];
	
	// Tags multi selector
	$tag_ids = array_map(function ($tag) {
		return $tag[TAGS_TABLE_ID_COL];
	}, $tags);
	$tag_names = array_map(function ($tag) {
		return $tag[TAGS_TABLE_NAME_COL];
	}, $tags);
	$tags_for_select = array_combine($tag_ids, $tag_names);
	$select_selected_tags = html_print_select($tags_for_select, $select_name, $selected_tags, '', '', 0,
		true, true, true, '', $disabled, 'display:none;');
	
	// Tags simple selector
	if (!empty($selected_tags))
		$selected_tags_comb = array_combine($selected_tags, $selected_tags);
	else
		$selected_tags_comb = array();
	$not_added_tags = array_diff_key($tags, $selected_tags_comb);
	
	$select_add_tags = '<div class="tags-select">';
	$select_add_tags .= html_print_select($not_added_tags, 'add-tags-select',
		array(), '', __('Select'), 0, true, false, true, '', $disabled);
	$select_add_tags .= '</div>';
	
	// Tags view
	$view_tags_selected = '<div class="tags-view"></div>';
	
	ob_start();
	
	echo '<div class="tags-editor">';
	echo 	$select_selected_tags;
	echo 	$select_add_tags;
	echo 	$view_tags_selected;
	echo '</div>';
?>
	<script type="text/javascript">
	(function ($) {
		
		var TAGS_TABLE_ID_COL = '<?php echo TAGS_TABLE_ID_COL; ?>';
		var TAGS_TABLE_NAME_COL = '<?php echo TAGS_TABLE_NAME_COL; ?>';
		var TAGS_TABLE_COLOUR_COL = '<?php echo TAGS_TABLE_COLOUR_COL; ?>';
		var availableTags = <?php echo json_encode($tags); ?>;
		
		var $selectSelectedTags = $('select[name="<?php echo $select_name; ?>"]');
		var $selectAddTags = $('select[name="add-tags-select"]');
		var $tagsView = $('div.tags-view');
		
		var addTag = function (id) {
			if (typeof availableTags[id] === 'undefined')
				return;
			
			var name = availableTags[id][TAGS_TABLE_NAME_COL];
			var colour = availableTags[id][TAGS_TABLE_COLOUR_COL];
			
			var $tagName = $('<span></span>');
			$tagName.html(name);
			var $tagBtn = $('<a></a>');
			var $tag = $('<span></span>');
			$tag.append($tagName, $tagBtn)
				.prop('id', 'tag-'+id)
				.addClass('tag')
				.addClass('label')
				.addClass(colour)
				.data('id', id)
				.data('name', name)
				.data('colour', colour);
			$tagsView.append($tag);
			
			// Remove the label from the 'add select'
			$selectAddTags
				.children('option[value="' + id + '"]')
					.remove();
			
			// Select the item of the tags select
			$selectSelectedTags
				.children('option[value="' + id + '"]')
					.prop('selected', true);
		}
		
		var removeTag = function (id) {
			if (typeof availableTags[id] === 'undefined')
				return;
			
			var name = availableTags[id][TAGS_TABLE_NAME_COL];
			var colour = availableTags[id][TAGS_TABLE_COLOUR_COL];
			
			// Add the deleted item to the 'add select'
			var $option = $('<option></option>');
			$option
				.val(id)
				.html(name);
			$selectAddTags.append($option).val(0).change();
			
			// Unselect the item of the tags select
			$selectSelectedTags
				.children('option[value="' + id + '"]')
					.prop('selected', false);
			
			// Remove the tag
			$('span#tag-'+id).remove();
		}
		
		// Handler to add a new label with the 'add select'
		$selectAddTags.change(function (event) {
			event.preventDefault();
			
			// Retrieve the label info from the 'add select'
			var id = this.value;
			
			if (id != 0) {
				// Add the tag
				addTag(id);
			}
		});
		
		// Handler to delete a label selection
		$tagsView.on('click', 'span.tag>a', function (event) {
			event.preventDefault();
			
			if (typeof event.target !== 'undefined') {
				// Get the label info from the target element
				var id = $(event.target).parent().data('id');
				
				// Remove the tag
				removeTag(id);
			}
		});
		
		// Fill the tags view
		$selectSelectedTags
			.children('option:selected')
				.each(function(index, el) {
					addTag(el.value);
				});
		
	})(window.jQuery);
	</script>
<?php
	$html = ob_get_clean();
	
	if ($return)
		return $html;
	echo $html;
}

function html_render_tag ($tag, $return = false) {
	$tag_view = '';
	
	if (!empty($tag) && !empty($tag[TAGS_TABLE_ID_COL]) && !empty($tag[TAGS_TABLE_NAME_COL]) && !empty($tag[TAGS_TABLE_COLOUR_COL])) {
		$tag_view = sprintf('<span title="" class="tag label %s" data-id="%s" data-name="%s" data-colour="%s">%s</span>',
			$tag[TAGS_TABLE_COLOUR_COL], $tag[TAGS_TABLE_ID_COL], $tag[TAGS_TABLE_NAME_COL],
			$tag[TAGS_TABLE_COLOUR_COL], $tag[TAGS_TABLE_NAME_COL]);
	}
	
	if ($return)
		return $tag_view;
	echo $tag_view;
}

function html_render_tags_view ($tags, $return = false) {
	$tags_view = '<div class="tags-view">';
	
	if (!empty($tags) && is_array($tags)) {
		foreach ($tags as $tag) {
			if (empty($tag[TAGS_TABLE_ID_COL]) || empty($tag[TAGS_TABLE_NAME_COL]) || empty($tag[TAGS_TABLE_COLOUR_COL]))
				continue;
			
			$tags_view .= html_render_tag($tag, true);
		}
	}
	
	$tags_view .= '</div>';
	
	if ($return)
		return $tags_view;
	echo $tags_view;
}

function html_render_tags_view_manage ($tags, $return = false) {
	$tags_view = '<div class="divresult">';
	$tags_view .= '<div class="tags-view">';
	
	if (!empty($tags) && is_array($tags)) {
		foreach ($tags as $tag) {
			if (empty($tag[TAGS_TABLE_ID_COL]) || empty($tag[TAGS_TABLE_NAME_COL]) || empty($tag[TAGS_TABLE_COLOUR_COL]))
				continue;
			
			$tags_view .= html_render_tag($tag, true);
		}
	}
	
	$tags_view .= '</div>';
	$tags_view .= '</div>';
	
	if ($return)
		return $tags_view;
	echo $tags_view;
}
?>