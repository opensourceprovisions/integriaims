<?php

// INTEGRIA IMS
// http://www.integriaims.com
// ===========================================================
// Copyright (c) 2007-2012 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.


global $config;

/**r
 * Prints the print_r with < pre > tags
 */
function debugPrint ($var, $file = '') {
	$more_info = '';
	if (is_string($var)) {
		$more_info = 'size: ' . strlen($var);
	}
	elseif (is_bool($var)) {
		$more_info = 'val: ' . 
			($var ? 'true' : 'false');
	}
	elseif (is_null($var)) {
		$more_info = 'is null';
	}
	elseif (is_array($var)) {
		$more_info = count($var);
	}
	
	if ($file === true)
		$file = '/tmp/logDebug';
	
	if (strlen($file) > 0) {
		$f = fopen($file, "a");
		ob_start();
		echo date("Y/m/d H:i:s") . " (" . gettype($var) . ") " . $more_info . "\n";
		print_r($var);
		echo "\n\n";
		$output = ob_get_clean();
		fprintf($f,"%s",$output);
		fclose($f);
	}
	else {
		echo "<pre>" .
			date("Y/m/d H:i:s") . " (" . gettype($var) . ") " . $more_info .
			"</pre>";
		echo "<pre>";print_r($var);echo "</pre>";
	}
}

/**
 * Convert a html color like #FF00FF into the rgb values like (255,0,255).
 *
 * @param string color in format #FFFFFF, FFFFFF, #FFF or FFF
 */
function html2rgb($htmlcolor)
{
	if ($htmlcolor[0] == '#') {
		$htmlcolor = substr($htmlcolor, 1);
	}

	if (strlen($htmlcolor) == 6) {
		$r = hexdec($htmlcolor[0].$htmlcolor[1]);
		$g = hexdec($htmlcolor[2].$htmlcolor[3]);
		$b = hexdec($htmlcolor[4].$htmlcolor[5]);
		return array($r, $g, $b);
	}
	elseif (strlen($htmlcolor) == 3) {
		$r = hexdec($htmlcolor[0].$htmlcolor[0]);
		$g = hexdec($htmlcolor[1].$htmlcolor[1]);
		$b = hexdec($htmlcolor[2].$htmlcolor[2]);
		return array($r, $g, $b);
	}
	else {
		return false;
	}
}

/**
 * Prints an array of fields in a popup menu of a form.
 * 
 * Based on choose_from_menu() from Moodle 
 * 
 * @param array Array with dropdown values. Example: $fields["value"] = "label"
 * @param string Select form name
 * @param variant Current selected value. Can be a single value or an
 *        array of selected values (in combination with multiple)
 * @param string Javascript onChange code.
 * @param string Label when nothing is selected.
 * @param variant Value when nothing is selected
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param bool Set the input to allow multiple selections (optional, single selection by default).
 * @param bool Whether to sort the options or not (optional, unsorted by default).
 * @param string $style The string of style.
 * @param mixed $size Max elements showed in the select or default (size=10).
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_select ($fields, $name, $selected = '', $script = '',
	$nothing = '', $nothing_value = 0, $return = false, $multiple = false,
	$sort = true, $class = '', $disabled = false, $style = false,
	$option_style = false, $size = false) {
	
	$output = "\n";
	
	static $idcounter = array ();
	
	//If duplicate names exist, it will start numbering. Otherwise it won't
	if (isset ($idcounter[$name])) {
		$idcounter[$name]++;
	}
	else {
		$idcounter[$name] = 0;
	}
	
	$id = preg_replace('/[^a-z0-9\:\;\-\_]/i', '', $name.($idcounter[$name] ? $idcounter[$name] : ''));
	
	$attributes = "";
	if (!empty ($script)) {
		$attributes .= ' onchange="'.$script.'"';
	}
	if (!empty ($multiple)) {
		if ($size !== false) {
			$attributes .= ' multiple="multiple" size="' . $size . '"';
		}
		else {
			$attributes .= ' multiple="multiple" size="10"';
		}
	}
	if (!empty ($class)) {
		$attributes .= ' class="'.$class.'"';
	}
	if (!empty ($disabled)) {
		$attributes .= ' disabled="disabled"';
	}
	
	if ($style === false) {
		$styleText = 'style=""';
	}
	else {
		$styleText = 'style="' .$style . '"';
	}
	
	$output .= '<select id="'.$id.'" name="'.$name.'"'.$attributes.' ' . $styleText . '>';
	
	if ($nothing != '' || empty ($fields)) {
		if ($nothing == '') {
			$nothing = __('None');
		}
		$output .= '<option value="'.$nothing_value.'"';
		
		if ($nothing_value == $selected) {
			$output .= ' selected="selected"';
		}
		else if (is_array ($selected)) {
			if (in_array ($nothing_value, $selected)) {
				$output .= ' selected="selected"';
			}
		}
		$output .= '>'.$nothing.'</option>';
	}
	
	if (is_array($fields) && !empty ($fields)) {
		if ($sort !== false) {
			// Sorting the fields in natural way and case insensitive preserving keys
			$first_elem = reset($fields);
			if (!is_array($first_elem))
				uasort($fields, "strnatcasecmp");
		}
		$lastopttype = '';
		foreach ($fields as $value => $label) {
			$optlabel = $label;
			if (is_array($label)) {
				if (isset($label['optgroup'])) {
					if ($label['optgroup'] != $lastopttype) {
						if ($lastopttype != '') {
							$output .=  '</optgroup>';
						}
						$output .=  '<optgroup label="'.$label['optgroup'].'">';
						$lastopttype = $label['optgroup'];
					}
				}
				$optlabel = $label['name'];
			}
			
			$output .= '<option value="'.$value.'"';
			if (is_array ($selected) && in_array ($value, $selected)) {
				$output .= ' selected="selected"';
			}
			elseif (is_numeric ($value) && is_numeric ($selected) &&
				$value == $selected) {
				//This fixes string ($value) to int ($selected) comparisons 
				$output .= ' selected="selected"';
			}
			elseif ($value === $selected) {
				//Needs type comparison otherwise if $selected = 0 and $value = "string" this would evaluate to true
				$output .= ' selected="selected"';
			}
			if (is_array ($option_style) &&
				in_array ($value, array_keys($option_style))) {
				$output .= ' style="'.$option_style[$value].'"';
			}
			if ($optlabel === '') {
				$output .= '>'.$value."</option>";
			}
			else {
				$output .= '>'.$optlabel."</option>";
			}
		}
		if (is_array($label)) {
			$output .= '</optgroup>';
		}
	}
	
	$output .= "</select>";
	
	if ($return)
		return $output;
	
	echo $output;
}

/**
 * Prints an array of fields in a popup menu of a form based on a SQL query.
 * The first and second columns of the query will be used.
 * 
 * The element will have an id like: "password-$value". Based on choose_from_menu() from Moodle.
 * 
 * @param string $sql SQL sentence, the first field will be the identifier of the option. 
 * The second field will be the shown value in the dropdown.
 * @param string $name Select form name
 * @param string $selected Current selected value.
 * @param string $script Javascript onChange code.
 * @param string $nothing Label when nothing is selected.
 * @param string $nothing_value Value when nothing is selected
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 * @param bool $multiple Whether to allow multiple selections or not. Single by default
 * @param bool $sort Whether to sort the options or not. Sorted by default.
 * @param bool $disabled if it's true, disable the select.
 * @param string $style The string of style.
 * @param mixed $size Max elements showed in select or default (size=10) 
 * @param int $truncante_size Truncate size of the element, by default is set to GENERIC_SIZE_TEXT constant
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_select_from_sql ($sql, $name, $selected = '',
	$script = '', $nothing = '', $nothing_value = '0', $return = false,
	$multiple = false, $sort = true, $disabled = false, $style = false, $size = false, $trucate_size = GENERIC_SIZE_TEXT) {
	global $config;
	
	$fields = array ();
	$result = get_db_all_rows_sql ($sql);
	if ($result === false)
		$result = array ();
	
	foreach ($result as $row) {
		$id = array_shift($row);
		$value = array_shift($row);
		$fields[$id] = ui_print_truncate_text(
			$value, $trucate_size, false, true, false);
	}
	
	return html_print_select ($fields, $name, $selected, $script,
		$nothing, $nothing_value, $return, $multiple, $sort, '',
		$disabled, $style,'', $size);
}

/**
 * OLD VERSION.
 * Prints an array of fields in a popup menu of a form.
 *
 * Based on choose_from_menu() from Moodle
 *
 * $fields Array with dropdown values. Example: $fields["value"] = "label"
 * $name Select form name
 * $selected Current selected value.
 * $script Javascript onChange code.
 * $nothing Label when nothing is selected.
 * $nothing_value Value when nothing is selected
 */

function print_select ($fields, $name, $selected = '', $script = '', 
		$nothing = 'select', $nothing_value = '0', $return = false, 
		$multiple = 0, $sort = true, $label = false, 
		$disabled = false, $style='', $selected_all = false, $id_new= false) {

	$output = "\n";
	
	if ($label) {
		$output .= print_label ($label, $name, 'select', true);
	}
	
	if($id_new){
		$id = $id_new;
	} else {
		$id = preg_replace('/[^a-z0-9\:\;\-\_]/i', '', $name);
	}
	
	$attributes = ($script) ? 'onchange="'. $script .'"' : '';
	if ($multiple) {
		$attributes .= ' multiple="yes" size="'.$multiple.'" ';
	}
	
	if ($disabled) {
		$disabledText = 'disabled';
	}
	else {
		$disabledText = '';
	}

	if ($style == "")
		$output .= '<select style="width: 218px" ' . $disabledText . ' id="'.$id.'" name="'.$name.'" '.$attributes.">\n";
	else
		$output .= '<select style="'.$style.'" ' . $disabledText . ' id="'.$id.'" name="'.$name.'" '.$attributes.">\n";

	if ($nothing != '') {
		$output .= '   <option value="'.$nothing_value.'"';
		if ($nothing_value == $selected) {
			$output .= " selected";
		}
		$output .= '>'.$nothing."</option>\n";
	}

	if (!empty ($fields)) {
		if ($sort)
			asort ($fields);
		foreach ($fields as $value => $label) {
			$optlabel = $label;
			if(is_array($label)){
				if(!isset($lastopttype) || ($label['optgroup'] != $lastopttype)) {
					if(isset($lastopttype) && ($lastopttype != '')) {
						$output .=  '</optgroup>';
					}
					$output .=  '<optgroup label="'.$label['optgroup'].'">';
					$lastopttype = $label['optgroup'];
				}				
				$optlabel = $label['name'];
			}
			
			$output .= '   <option value="'. $value .'"';
			if ($selected_all){
				$output .= ' selected';
			}else if (is_array($selected)) {
				if (in_array($value,$selected)) {
					$output .= ' selected';
				}
			}
			else {
				if (safe_output($value) == safe_output($selected)) {
					$output .= ' selected';
				}
			}
			if ($optlabel === '') {
				$output .= '>'. $value ."</option>\n";
			} else {
				$output .= '>'. $optlabel ."</option>\n";
			}
		}
	}

	$output .= "</select>\n";
	if ($return)
		return $output;

	echo $output;
}

/**
 * OLD VERSION.
 * Prints an array of fields in a popup menu of a form based on a SQL query.
 * The first and second columns of the query will be used.
 *
 * Based on choose_from_menu() from Moodle
 *
 * $sql SQL sentence, the first field will be the identifier of the option.
 *      The second field will be the shown value in the dropdown.
 * $name Select form name
 * $selected Current selected value.
 * $script Javascript onChange code.
 * $nothing Label when nothing is selected.
 * $nothing_value Value when nothing is selected
 */
function print_select_from_sql ($sql, $name, $selected = '', $script = '', $nothing = 'select', $nothing_value = '0', $return = false, $multiple = false, $sort = true, $label = false, $disabled = false) {

	$fields = array ();
	$result = mysql_query ($sql);
	if (! $result) {
		echo mysql_error ();
		return "";
	}

	while ($row = mysql_fetch_array ($result)) {
		$fields[$row[0]] = $row[1];
	}

	$output = print_select ($fields, $name, $selected, $script, $nothing, $nothing_value, true, $multiple, $sort, $label, $disabled);

	if ($return)
		return $output;

	echo $output;
}

/**
 * Render an input text element. Extended version, use print_input_text() to simplify.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Alternative HTML string.
 * @param int Size of the input.
 * @param int Maximum length allowed.
 * @param bool Disable the button (optional, button enabled by default).
 * @param string Alternative HTML string.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 */
function print_input_text_extended ($name, $value, $id, $alt, $size, $maxlength, $disabled, $script, $attributes, $return = false, $password = false, $label = false, $type = false, $readonly = false, $autofocus = false) {
	if (!$type) {
		$type = $password ? 'password' : 'text';
	}
	
	$output = '';
	
	if ($label) {
		$output .= print_label ($label, $id, '', true);
	}
	
	if (empty ($name)) {
		$name = 'unnamed';
	}

	if (empty ($alt)) {
		$alt = 'textfield';
	}

	if (! empty ($maxlength)) {
		$maxlength = ' maxlength="'.$maxlength.'" ';
	}
	
	if (! empty ($script)) {
		$script = ' onClick="'.$script.'" ';
	}

	$output .= '<input name="'.$name.'" type="'.$type.'" value="'.$value.'" size="'.$size.'" '.$maxlength.' alt="'.$alt.'" '.$script;
	$output .= ' id="'.$id.'"';
	
	if ($disabled)
		$output .= ' disabled';
		
	if ($readonly)
		$output .= ' readonly';
	
	if ($autofocus)
		$output .= ' autofocus';

	if (is_array($attributes)) {
		foreach ($attributes as $name => $value) {
			$output .= ' ' . $name . '="' . $value . '"';
		}
	}
	else {
		if ($attributes != '')
			$output .= ' '.  $attributes;
	}
	$output .= ' />';
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Prints an image HTML element.
 *
 * @param string $src Image source filename.
 * @param bool $return Whether to return or print
 * @param array $options Array with optional HTML options to set. At this moment, the 
 * following options are supported: alt, style, title, width, height, class, pos_tree.
 * @param bool $return_src Whether to return src field of image ('images/*.*') or complete html img tag ('<img src="..." alt="...">'). 
 *
 * @return string HTML code if return parameter is true.
 */
function print_image ($src, $return = false, $options = false, $return_src = false) {
	global $config;
	
	// path to image 
	//~ $src = $config["base_url"] . '/' . $src;
	$src = $config["base_url_images"] . '/' . $src;
	
	// Only return src field of image
	if ($return_src){
		if (!$return){ 
			echo safe_input($src); 
			return; 
		}
		return safe_input($src);
	}
	
	$output = '<img src="'.safe_input ($src).'" '; //safe input necessary to strip out html entities correctly
	$style = '';
	
	if (!empty ($options)) {
		//Deprecated or value-less attributes
		if (isset ($options["align"])) {
			$style .= 'align:'.$options["align"].';'; //Align is deprecated, use styles.
		}
		
		if (isset ($options["border"])) {
			$style .= 'border:'.$options["border"].'px;'; //Border is deprecated, use styles
		}
				
		if (isset ($options["hspace"])) {
			$style .= 'margin-left:'.$options["hspace"].'px;'; //hspace is deprecated, use styles
			$style .= 'margin-right:'.$options["hspace"].'px;';
		}
		
		if (isset ($options["ismap"])) {
			$output .= 'ismap="ismap" '; //Defines the image as a server-side image map
		}
		
		if (isset ($options["vspace"])) {
			$style .= 'margin-top:'.$options["vspace"].'px;'; //hspace is deprecated, use styles
			$style .= 'margin-bottom:'.$options["vspace"].'px;';
		}
				
		if (isset ($options["style"])) {
			$style .= $options["style"]; 
		}
		
		//Valid attributes (invalid attributes get skipped)
		$attrs = array ("height", "longdesc", "usemap","width","id","class","title","lang","xml:lang", 
						"onclick", "ondblclick", "onmousedown", "onmouseup", "onmouseover", "onmousemove", 
						"onmouseout", "onkeypress", "onkeydown", "onkeyup","pos_tree");
		
		foreach ($attrs as $attribute) {
			if (isset ($options[$attribute])) {
				$output .= $attribute.'="'.safe_input ($options[$attribute]).'" ';
			}
		}
	} else {
		$options = array ();
	}
	
	if (!isset ($options["alt"]) && isset ($options["title"])) {
		$options["alt"] = safe_input($options["title"]); //Set alt to title if it's not set
	} elseif (!isset ($options["alt"])) {
		$options["alt"] = "";
	}

	if (!empty ($style)) {
		$output .= 'style="'.$style.'" ';
	}
	
	$output .= 'alt="'.safe_input ($options['alt']).'" />';
	
	if (!$return) {
		echo $output;
	}

	return $output;
}

/**
 * Render an input text element. Extended version, use print_input_text() to simplify.
 *
 * @param string Input name.
 * @param int Size of the input.
 * @param bool Wheter to disable the input or not.
 * @param string Optional HTML attributes.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param string HTML label element to add (none by default).
 */
function print_input_file ($name, $size, $disabled = false, $attributes = '', $return = false, $label = false) {
	$output = '';
	
	if ($label) {
		$output .= print_label ($label, $name, 'file', true);
	}
	
	if (empty ($name)) {
		$name = 'unnamed';
	}
	
	$output .= '<input name="'.$name.'" type="file" value="" size="'.$size.'"  ';
	$output .= ' id="file-'.$name.'"';
	
	if ($disabled)
		$output .= ' disabled';

	if ($attributes != '')
		$output .= ' '.$attributes;
	$output .= ' />';

	if ($return)
		return $output;
	echo $output;
}

/**
 * Render an input file element with progress bar system using jquery
 * This function uses jQuery, the library AXuploader and the file include/file_uploader.php
 *
 * @param string form action where the uploading will be processed and copied the file from temp to destiny.
 * @param string code to print into the form
 * @param string attributes extra to form
 * @param string default button extra class
 * @param string button id of the submit button
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param string macro to place the upload control. I will be placed at first of all with no macro (false)
 */
function print_input_file_progress($form_action, $into_form = '', $attr = '', $extra_button_class = '', $button = false,  $return = false, $control_macro = false) {
	global $config;
	$output = "";
	
	$control_layer = "<div class='upfile'></div>";
	
	// If no control macro defined, we put the upload control first of all
	if($control_macro === false) {
		// Layer to the input control through jquery
		$output .= $control_layer;
	}
	
	// Form to fill and submit from javascript
	$output .= "<form method='post' $attr class='upfile_form' action='$form_action' enctype='multipart/form-data'>";
	$output .= "<input type='hidden' id='upfile' name='upfile' value='' class='upfile_input'>";
	$output .= $into_form;
	$output .= "</form>";
	
	$output .= "<script type='text/javascript'>";

	$output .= "$(document).ready(function(){";	
		$output .= "$('.upfile').axuploader({";
			$output .= "url:'include/file_uploader.php',";
			$output .= "chunkSize:'".(1024*1024*$config["max_file_size"])."',";
			$output .= "finish:function(x,files){";
				$output .= "$('#upfile').val(files[0]);";
				$output .= "$('.upfile_form').submit();";
			$output .=  "},";
			$output .= "enable:true,";
			$output .= "showSize:'Kb',";
			$output .= "remotePath:function(){";
				$output .= "return '".sys_get_temp_dir()."/';";
			$output .= "}";
		$output .= "});";
				
		$output .= "$('.ax-clear').hide();";
		$output .= "$('#ax-table-header').hide();";
		$output .= "$('.ax-uploadall').val('".__('Upload')."');";
		$output .= "$('.ax-uploadall').addClass('".$extra_button_class."');";
		$output .= "$('input[type=\"file\"]').addClass('sub file');";
		// If a button is defined, hide the default button and trigger the action to the
		// defined button. If file upload is empty, the form is sended without it
		if($button !== false) {
			$output .= "$('.ax-uploadall').hide();";
			$output .= "$('#$button').click(function() { $('.ax-uploadall').trigger('click');if($('.ax-file-name').html() == null)$('.upfile_form').submit();});";
		}
	$output .= "});";
	$output .= "</script>";

	if($control_macro !== false) {
		$output = str_replace($control_macro,$control_layer,$output);
	}

	if ($return) {
		return $output;
	}
	
	echo $output;
}

/**
 * Render an input password element.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Alternative HTML string (optional).
 * @param int Size of the input (optional).
 * @param int Maximum length allowed (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 */

function print_input_password ($name, $value, $alt = '', $size = 50, $maxlength = 0, $return = false, $label = false) {
	$output = print_input_text_extended ($name, $value, 'password-'.$name, $alt, $size, $maxlength, false, '', '', true, true, $label);

	if ($return)
		return $output;
	echo $output;
}

/**
 * Render an input text element.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Alternative HTML string (optional).
 * @param int Size of the input (optional).
 * @param int Maximum length allowed (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 */
function print_input_text ($name, $value, $alt = '', $size = 50, $maxlength = 0, $return = false, $label = false, $disabled = false, $type = false, $readonly = false, $autofocus = false) {
	$output = print_input_text_extended ($name, $value, 'text-'.$name, $alt, $size, $maxlength, $disabled, '', '', true, false, $label, $type, $readonly, $autofocus);

	if ($return)
		return $output;
	echo $output;
}


/**
 * Render an input hidden element.
 *
 * @param string Input name.
 * @param string Input value.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param string HTML class to be added. Useful in javascript code.
 */
function print_input_hidden ($name, $value, $return = false, $class = '', $label = false, $id = '') {
	if ($label) {
		$output = print_label ($label, $name, 'hidden', true);
	}

	if (!$id) {
		$id = "hidden-".$name;
	}
	
	$output = '<input id="'.$id.'" name="'.$name.'" type="hidden"';
	if ($class != '')
		$output .= ' class="'.$class.'"';
	$output .=' value="'.$value.'" />';

	if ($return)
		return $output;
	echo $output;
}

function print_submit_button ($value = 'OK', $name = '', $disabled = false, $attributes = '', $return = false, $label = false) {
	$output = '';
	
	if ($label) {
		$output .= print_label ($label, $name, 'submit', true);
	}
	
	$output .= '<input type="submit" id="submit-'.$name.'" name="'.$name.'" value="'. $value .'" '. $attributes;
	if ($disabled)
		$output .= ' disabled="disabled"';
	$output .= ' />';
	if ($return)
		return $output;

	echo $output;
}

/**
 * Render an input image element.
 * 
 * @param string Input name.
 * @param string Image source.
 * @param string Input value.
 * @param string HTML style property.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 */
function print_input_image ($name, $src, $value, $style = '', $return = false, $label = false, $options = false) {
	$output = '';
	
	//Valid attributes (invalid attributes get skipped)
	$attrs = array ("alt", "accesskey", "lang", "tabindex",
		"title", "xml:lang", "onclick", "ondblclick", "onmousedown",
		"onmouseup", "onmouseover", "onmousemove", "onmouseout",
		"onkeypress", "onkeydown", "onkeyup");	
	
	if ($label) {
		$output .= print_label ($label, $name, 'image', true);
	}
	$output .= '<input id="image-'.$name.'" src="'.$src.'" style="'.$style.'" name="'.$name.'" type="image"';
	
	
	foreach ($attrs as $attribute) {
		if (isset ($options[$attribute])) {
			$output .= ' '.$attribute.'="'.safe_input_html ($options[$attribute]).'"';
		}
	}	
	
	$output .= ' value="'.$value.'" />';
	
	if ($return)
		return $output;
	echo $output;
}

function print_button ($value = 'OK', $name = '', $disabled = false, $script = '', $attributes = '', $return = false, $label = false) {
	$output = '';
	
	if ($label) {
		$output .= print_label ($label, $name, 'button', true);
	}
	
	$output .= '<input type="button" id="button-'.$name.'" name="'.$name.'" value="'. $value .'" onClick="'. $script.'" '.$attributes;
	if ($disabled)
		$output .= ' disabled="disabled"';
	$output .= ' />';
	if ($return)
		return $output;

	echo $output;
}

function print_textarea ($name, $rows, $columns, $value = '', $attributes = '', $return = false, $label = false, $disabled = false) {
	$output = '';
	
	if ($label) {
		$output .= print_label ($label, $name, 'textarea', true);
	}
	
	if ($disabled) {
		$disabledText = 'disabled';
	} else {
		$disabledText = '';
	}
	
	$output .= '<textarea id="textarea-'.$name.'" name="'.$name.'" cols="'.$columns.'" rows="'.$rows.'" '.$attributes.'" '.$disabledText.'>';
	$output .= $value;
	$output .= '</textarea>';

	if ($return)
		return $output;
	echo $output;
}

function print_input_number ($name, $value = '', $min =0, $max =1000000, $attributes = '', $return = false, $label = false, $disabled = false) {
	$output = '';
	
	if ($label) {
		$output .= print_label ($label, $name, 'number', true);
	}
	
	if ($disabled) {
		$disabledText = 'disabled';
	} else {
		$disabledText = '';
	}
	
	$output .= '<input type="number" min="'.$min.'" max = "'.$max.'" value="'.$value.'"  id="number-'.$name.'" name="'.$name.'" '.$attributes.'" '.$disabledText.'>';

	if ($return)
		return $output;
	echo $output;
}

function print_input_date ($name, $value = '', $min ='', $max ='', $attributes = '', $return = false, $label = false, $disabled = false) {
	$output = '';
	
	if ($label) {
		$output .= print_label ($label, $name, 'date', true);
	}
	
	if ($disabled) {
		$disabledText = 'disabled';
	} else {
		$disabledText = '';
	}
	
	$output .= '<input type="date" min="'.$min.'" max = "'.$max.'" value='.$value.'  id="date-'.$name.'" name="'.$name.'" '.$attributes.'" '.$disabledText.'>';

	if ($return)
		return $output;
	echo $output;
}

/**
 * Print a nicely formatted table. Code taken from moodle.
 *
 * @param object is an object with several properties:
 *     $table->head - An array of heading names.
 *     $table->align - An array of column alignments
 *     $table->valign - An array of column alignments
 *     $table->size  - An array of column sizes
 *     $table->wrap - An array of "nowrap"s or nothing
 *     $table->style  - An array of personalized style for each column.
 *     $table->rowstyle  - An array of personalized style of each row.
 *     $table->rowclass  - An array of personalized classes of each row (odd-evens classes will be ignored).
 *     $table->colspan  - An array of colspans of each column.
 *     $table->rowspan  - An array of rowspans of each column.
 *     $table->data[] - An array of arrays containing the data.
 *     $table->width  - A percentage of the page
 *     $table->border  - Border of the table.
 *     $table->tablealign  - Align the whole table
 *     $table->cellpadding  - Padding on each cell
 *     $table->cellspacing  - Spacing between cells
 *     $table->class  - CSS table class
 * @param  bool whether to return an output string or echo now
 */
function print_table (&$table, $return = false) {
	$output = '';
	static $table_count = 0;
	
	$table_count++;
	if (isset ($table->align)) {
		foreach ($table->align as $key => $aa) {
			if ($aa) {
				$align[$key] = ' text-align:'. $aa.';';
			} else {
				$align[$key] = '';
			}
		}
	}
	if (isset ($table->valign)) {
		foreach ($table->valign as $key => $aa) {
			if ($aa) {
				$valign[$key] = ' vertical-align:'. $aa.';';
			} else {
				$valign[$key] = '';
			}
		}
	}
	if (isset ($table->size)) {
		foreach ($table->size as $key => $ss) {
			if ($ss) {
				$size[$key] = ' width:'. $ss .';';
			} else {
				$size[$key] = '';
			}
		}
	}
	if (isset ($table->style)) {
		foreach ($table->style as $key => $st) {
			if ($st) {
				$style[$key] = ' '. $st .';';
			} else {
				$style[$key] = '';
			}
		}
	}
	if (isset ($table->rowstyle)) {
		foreach ($table->rowstyle as $key => $st) {
			$rowstyle[$key] = ' '. $st .';';
		}
	}
	if (isset ($table->rowclass)) {
		foreach ($table->rowclass as $key => $class) {
			$rowclass[$key] = $class;
		}
	}
	if (isset ($table->colspan)) {
		foreach ($table->colspan as $keyrow => $cspan) {
			foreach ($cspan as $key => $span) {
				$colspan[$keyrow][$key] = ' colspan="'.$span.'"';
			}
		}
	}
	if (isset ($table->rowspan)) {
		foreach ($table->rowspan as $keyrow => $cspan) {
			foreach ($cspan as $key => $span) {
				$rowspan[$keyrow][$key] = ' rowspan="'.$span.'"';
			}
		}
	}
	
	if (isset ($table->cellstyle)) {
		foreach ($table->cellstyle as $keyrow => $cstyle) {
			foreach ($cstyle as $key => $cst) {
				$cellstyle[$keyrow][$key] = $cst;
			}
		}
	}
	
	if (empty ($table->width)) {
		$table->width = '80%';
	}

	if (empty ($table->border)) {
		$table->border = '0px';
	}


	if (empty ($table->tablealign)) {
		$table->tablealign = 'center';
	}

	if (empty ($table->cellpadding)) {
		$table->cellpadding = '0';
	}

	if (empty ($table->cellspacing)) {
		$table->cellspacing = '0';
	}

	if (empty ($table->class)) {
		$table->class = 'databox';
	}

	$tableid = empty ($table->id) ? 'table'.$table_count : $table->id;

	$output .= '<table width="'.$table->width.'" ';
	$output .= " cellpadding=\"$table->cellpadding\" cellspacing=\"$table->cellspacing\" ";
	$output .= " border=\"$table->border\" class=\"$table->class\" id=\"$tableid\" >\n";
	$countcols = 0;

	$output .= '<thead>';
	if (!empty ($table->head)) {
		$countcols = count ($table->head);
		$output .= '<tr>';
		foreach ($table->head as $key => $heading) {
			if (!isset ($size[$key])) {
				$size[$key] = '';
			}
			if (!isset ($align[$key])) {
				$align[$key] = '';
			}
			if (isset ($table->head_colspan[$key])) {
				$headColspan = 'colspan = "' . $table->head_colspan[$key] . '"';
			}
			else $headColspan = '';

			$output .= '<th class="header c'.$key. '" '.$headColspan.' scope="col">'. $heading .'</th>';
		}
		$output .= '</tr>'."\n";
	}
	$output .= "</thead>\n<tbody>\n";
	if (!empty ($table->data)) {
		$oddeven = 1;
		foreach ($table->data as $keyrow => $row) {

			if (!isset ($rowstyle[$keyrow])) {
				$rowstyle[$keyrow] = '';
			}
			$oddeven = $oddeven ? 0 : 1;
			$class = 'datos'.($oddeven ? "" : "2");
			if (isset ($rowclass[$keyrow])) {
				$class = $rowclass[$keyrow];
			}
			$output .= '<tr id="'.$tableid."-".$keyrow.'" style="'.$rowstyle[$keyrow].'" class="'.$class.'">'."\n";
			/* Special separator rows */
			if ($row == 'hr' and $countcols) {
				$output .= '<td colspan="'. $countcols .'"><div class="tabledivider"></div></td>';
				continue;
			}
			/* It's a normal row */
			foreach ($row as $key => $item) {
				if (!isset ($size[$key])) {
					$size[$key] = '';
				}
				if (!isset ($colspan[$keyrow][$key])) {
					$colspan[$keyrow][$key] = '';
				}
				if (!isset ($rowspan[$keyrow][$key])) {
					$rowspan[$keyrow][$key] = '';
				}
				if (!isset ($align[$key])) {
					$align[$key] = '';
				}
				if (!isset ($valign[$key])) {
					$valign[$key] = '';
				}
				if (!isset ($wrap[$key])) {
					$wrap[$key] = '';
				}
				if (!isset ($style[$key])) {
					$style[$key] = '';
				}
				
				if (!isset ($cellstyle[$keyrow][$key])) {
					$cellstyle[$keyrow][$key] = '';
				}
				
				$output .= '<td id="'.$tableid.'-'.$keyrow.'-'.$key.
					'" style="'. $cellstyle[$keyrow][$key].$style[$key].$valign[$key].$align[$key].$size[$key].$wrap[$key].
					'" '.$colspan[$keyrow][$key].' '.$rowspan[$keyrow][$key].
					' class="'.$class.'">'. $item .'</td>'."\n";
			}
			$output .= '</tr>'."\n";
		}
	}
	$output .= '</tbody>'."\n";
	$output .= '</table>'."\n";

	if ($return)
		return $output;

	echo $output;
}

/**
 * Render a radio button input. Extended version, use print_radio_button() to simplify.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Set the button to be marked (optional, unmarked by default).
 * @param bool Disable the button (optional, button enabled by default).
 * @param string Script to execute when onClick event is triggered (optional).
 * @param string Optional HTML attributes. It's a free string which will be
 *	inserted into the HTML tag, use it carefully (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 */
function print_radio_button_extended ($name, $value, $label, $checkedvalue, $disabled, $script, $attributes, $return = false) {
	static $idcounter = 0;

	$output = '';

	$output = '<input type="radio" name="'.$name.'" value="'.$value.'"';
	$htmlid = 'radiobtn'.sprintf ('%04d', ++$idcounter);
	$output .= ' id="'.$htmlid.'"';

	if ($value == $checkedvalue) {
		 $output .= ' checked="checked"';
	}
	if ($disabled) {
		 $output .= ' disabled';
	}
	if ($script != '') {
		 $output .= ' onClick="'. $script . '"';
	}
	$output .= ' ' . $attributes ;
	$output .= ' />';

	if ($label != '') {
		$output .= '<label for="'.$htmlid.'">'.  $label .'</label>' . "\n";
	}
	
	if ($return)
		return $output;
	
	echo $output;
}

/**
 * Render a radio button input.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string  Label to add after the radio button (optional).
 * @param string Checked and selected value, the button will be selected if it matches $value (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 */
function print_radio_button ($name, $value, $label = '', $checkedvalue = '', $return = false, $label = false) {
	$output = print_radio_button_extended ($name, $value, $label, $checkedvalue, false, '', '', true, $label);

	if ($return)
		return $output;

	echo $output;
}

/**
 * Render a label for a input elemennt.
 *
 * @param string Label to add.
 * @param string Input id to refer.
 * @param string Input type of the element. The id of the elements using print_* functions add a prefix, this
 * variable helps with that. Values: text, password, textarea, button, submit, hidden, select. Default: text.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param string Extra HTML to add after the label.
 */
function print_label ($label, $id, $input_type = 'text', $return = false, $html = false) {
	$output = '';
	
	switch ($input_type) {
	case 'text':
		$id = 'text-'.$id;
		break;
	case 'password':
		$id = 'password-'.$id;
		break;
	case 'textarea':
		$id = 'textarea-'.$id;
		break;
	case 'button':
		$id = 'button-'.$id;
		break;
	case 'submit':
		$id = 'submit-'.$id;
		break;
	case 'hidden':
		$id = 'hidden-'.$id;
		break;
	case 'checkbox':
		$id = 'checkbox-'.$id;
		break;
	case 'file':
		$id = 'file-'.$id;
		break;
	case 'image':
		$id = 'image-'.$id;
		break;
	case 'select':
	default:
		break;
	}
	
	$output .= '<label id="label-'.$id.'" for="'.$id.'">';
	$output .= $label;
	$output .= '</label>';
	
	if ($html)
		$output .= $html;
	
	if ($return)
		return $output;
	
	echo $output;
}

/**
 * Render a checkbox button input. Extended version, use print_checkbox() to simplify.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Set the button to be marked (optional, unmarked by default).
 * @param bool Disable the button  (optional, button enabled by default).
 * @param string Script to execute when onClick event is triggered (optional).
 * @param string Optional HTML attributes. It's a free string which will be
 * inserted into the HTML tag, use it carefully (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 */
function print_checkbox_extended ($name, $value, $checked, $disabled, $script, $attributes, $return = false, $label = false) {
	$output = '';

	if ($label) {
		$output .= ' ';
		$output .= print_label ($label, $name, 'checkbox', true);
	}

	$output .= '<input name="'.$name.'" type="checkbox" '.$attributes.' value="'.$value.'" '. ($checked ? 'checked="1"': '');
	$output .= ' id="checkbox-'.$name.'"';

	if ($script != '') {
		 $output .= ' onClick="'. $script . '"';
	}

	if ($disabled) {
		 $output .= ' disabled="1"';
	}

	$output .= ' />';
	$output .= "\n";

	if ($return)
		return $output;
	echo $output;
}

/**
 * Render a checkbox button input.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Set the button to be marked (optional, unmarked by default).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 */
function print_checkbox ($name, $value, $checked = false, $return = false, $label = false, $disabled = false) {
	$output = print_checkbox_extended ($name, $value, (bool) $checked, $disabled, '', '', true, $label);

	if ($return)
		return $output;
	echo $output;
}

/**
 * Prints only a tip button which shows a text when the user puts the mouse over it.
 *
 * @param string Complete text to show in the tip
 * @param bool whether to return an output string or echo now
 *
 * @return
 */
function print_help_tip ($text, $return = false, $tip_class = 'tip') {
	$output = '<a href="#" class="'.$tip_class.'">&nbsp;<span>'.$text.'</span></a>';
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Prints a help tip icon.
 *
 * @param int Help id
 * @param bool Flag to return or output the result
 *
 * @return string The help tip if return flag was active.
 */
function integria_help ($help_id, $return = false) {
	global $config;
	$output = '&nbsp;<img class="img_help" src="images/help.png" onClick="integria_help(\''.$help_id.'\')">';
	if ($return)
		return $output;
	echo $output;
}


function print_container($id, $title, $content, $open = 'open', $return = true, $margin = true, $h2_clases='', $div_classes= '', $numcolspan = 1, $class_extra = '') {
	$container_div_style = '';
	$container_style = '';
	$h2_class_extra = ' clickable';
	$arrow = '';
	$onclick = 'toggleDiv (\'' . $id . '_div\')';

	switch($open) {
		case 'open':
			$arrow = print_image('images/arrow_down.png', true, array('class' => 'arrow_down')) . '</th>';
			break;
		case 'closed':
			$arrow = print_image('images/arrow_right.png', true, array('class' => 'arrow_right')) . '</th>';
			$container_div_style = 'display: none;';
			break;
		case 'no':
		default:
			$onclick = '';
			$h2_class_extra = '';
			break;
	}
	/*
	$container = '<div class="container ' . $id . '_container" style="' . $container_style . '">';
	$container .= '<h2 id="' . $id . '" class="dashboard_h2 ' . $h2_class_extra . ' ' . '" onclick="' . $onclick . '">' . $title;
	$container .= $arrow;
	$container .= '</h2>';
	$container .= '<div id="' . $id . '_div" class="container_div '.$div_classes.'" style="' . $container_div_style . '">';
	$container .= $content;
	$container .= '</div>';
	$container .= '</div>'; // container
	*/
	
	$container = '<table class="listing '.$class_extra.'"><thead><tr id="' . $id . '" class="' . $h2_class_extra . ' ' . '" onclick="' . $onclick . '">';
	$container .= '<th class="head_clickleft" colspan = '.$numcolspan.'>' . $title . '</th>';
	$container .= '<th class = "img_arrow head_clickright">'. $arrow;
	$container .= '</tr></thead>';
	$container .= '<tbody id="' . $id . '_div" class="container_div '.$div_classes.'" style="' . $container_div_style . '">';
	$container .= $content;
	$container .= '</tbody>';
	$container .= '</table>'; // container
	
	if ($return) {
		return $container;
	}
	else {
		echo $container;
	}
}

function print_container_div($id, $title, $content, $open = 'open', $return = true, $margin = true, $h2_clases='', $div_classes= '', $numcolspan = 1, $class_extra = '', $container_style = '') {
	$container_div_style = '';
	$h2_class_extra = ' clickable';
	$arrow = '';
	$onclick = 'toggleDiv (\'' . $id . '_div\')';

	switch($open) {
		case 'open':
			$arrow = print_image('images/arrow_down.png', true, array('class' => 'arrow_down', 'id' => $id."_arrow"));
			break;
		case 'closed':
			$arrow = print_image('images/arrow_right.png', true, array('class' => 'arrow_right', 'id' => $id."_arrow"));
			$container_div_style .= ' display: none;';
			break;
		case 'no':
		default:
			$onclick = '';
			$h2_class_extra = '';
			break;
	}
	
	if ($margin) {
		$container_style .= " margin-right: 10px;";
	}
	
	$container = '<div class="container ' . $id . '_container" style="' . $container_style . '">';
	$container .= '<h2 id="' . $id . '" class="dashboard_h2 ' . $h2_class_extra . ' ' . '" onclick="' . $onclick . '">' . $title;
	$container .= $arrow;
	$container .= '</h2>';
	$container .= '<div id="' . $id . '_div" class="container_div '.$div_classes.'" style="' . $container_div_style . '">';
	$container .= $content;
	$container .= '</div>';
	$container .= '</div>'; // container
	
	if ($return) {
		return $container;
	}
	else {
		echo $container;
	}
}

function print_autorefresh_button ($name = "autorefresh", $text = "", $return = false, $token = "incidents_autorefresh", $form_id = "saved-searches-form") {
	global $config;
	
	$html .= "<script type='text/javascript'>
				$(document).ready (function () {
					var seconds = readCookie('$token');
					if (seconds) {
						enableAutorefresh (\"button-$name\", \"$token\", \"$form_id\");
						$('#".$name."_time').val(seconds);
					} else {
						$('#".$name."_time').val(60);
					}
				});
			</script>";
	
	if ($text == "") {
		$text = __("Enable autorefresh");
	}
	
	$values = array();
	$values[5] = '5 '.__('seconds');
	$values[15] = '15 '.__('seconds');
	$values[30] = '30 '.__('seconds');
	$values[60] = '1 '.__('minute');
	$values[300] = '5 '.__('minutes');
	$values[900] = '15 '.__('minutes');
	$values[1800] = '30 '.__('minutes');
	$values[3600] = '1 '.__('hour');
	
	$html .= "<div style='float: right;'>";
	$html .= "<div id='button-bar-title' style=''>";
	$html .= "<ul>";	
	$html .= "<li style=''>";
	$html .= "<a reload_enabled='0' name='$name' id='button-$name' href='javascript:' onclick='toggleAutorefresh (\"button-$name\", \"$token\", \"$form_id\")'>$text</a>";
	$html .= "</li>";
	$html .= "</ul>";
	$html .= "</div>";
	$html .= "<div id='autorefresh_combo' style='float: left; display: none;margin-right: 5px; padding-bottom: 3px; margin-top: -12px;'>";
	$html .= print_select ($values, $name."_time", $selected_value, "changeAutorefreshTime ('".$name."_time', '$token')", "", "", true, 0, false, false, false, "min-width: 50px;");
	$html .= "</div>";
	$html .= "</div>";
	
	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function print_autorefresh_button_ticket ($name = "autorefresh", $text = "", $return = false, $token = "incidents_autorefresh", $form_id = "saved-searches-form") {
	global $config;
	
	$html = "<script type='text/javascript'>
				$(document).ready (function () {
					var seconds = readCookie('$token');
					if (seconds) {
						enableAutorefresh (\"button-$name\", \"$token\", \"$form_id\");
						$('#".$name."_time').val(seconds);
					} else {
						$('#".$name."_time').val(60);
					}
				});
			</script>";
	
	if ($text == "") {
		$text = __("Enable autorefresh");
	}
	
	$values = array();
	$values[5] = '5 '.__('seconds');
	$values[15] = '15 '.__('seconds');
	$values[30] = '30 '.__('seconds');
	$values[60] = '1 '.__('minute');
	$values[300] = '5 '.__('minutes');
	$values[900] = '15 '.__('minutes');
	$values[1800] = '30 '.__('minutes');
	$values[3600] = '1 '.__('hour');
	
	$html .= "<li style=''>";
	$html .= "<a reload_enabled='0' name='$name' id='button-$name' href='javascript:' onclick='toggleAutorefresh (\"button-$name\", \"$token\", \"$form_id\")'>$text</a>";
	$html .= "<div id='autorefresh_combo' style='float: left; visibility: hidden; margin-right: 5px;'>";
	$html .= print_select ($values, $name."_time", "", "changeAutorefreshTime ('".$name."_time', '$token')", "", "", true, 0, false, false, false, "min-width: 50px;");
	$html .= "</div>";
	$html .= "</li>";
	
	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function print_report_image ($href, $title = "PDF report", $id = "", $attr = "") {
	global $config;

	enterprise_include ('include/functions_reporting_pdf.php', true);

	$return = enterprise_hook ('print_report_image_extra', array($href, $title, $id, $attr));
	
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	else
		return "";
}

function print_html_report_image ($href, $title = "HTML report", $id = "", $attr = "", $pure = 0) {
	global $config;

	enterprise_include ('include/functions_reporting_pdf.php', true);

	$return = enterprise_hook ('print_html_report_image_extra', array($href, $title, $id, $attr, $pure));
	
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	else
		return "";
}

function print_report_button ($href, $value = "PDF report", $id = "", $attr = "") {
	global $config;

	enterprise_include ('include/functions_reporting_pdf.php', true);

	$return = enterprise_hook ('print_report_button_extra', array($href, $value, $id, $attr));
	
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	else
		return "";
}

function print_html_report_button ($href, $value = "HTML report", $id = "", $attr = "") {
	global $config;

	enterprise_include ('include/functions_reporting_pdf.php', true);

	$return = enterprise_hook ('print_html_report_button_extra', array($href, $value, $id, $attr));
	
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	else
		return "";
}

function get_last_date_control ($last_date = 0, $id = 'last_date_search', $label = '', $start_date = '', $start_date_name = 'start_date_search', $start_date_label = '', $end_date = '', $end_date_name = 'end_date_search', $end_date_label = '') {

	if ($label == '') {
		$label = __('Date');
	}
	if ($start_date_label == '') {
		$start_date_label = __('Start date');
	}
	if ($end_date_label == '') {
		$end_date_label = __('End date');
	}

	$script = "javascript:
		if ($('#$id').val() > 0) {
			$('#start_end_dates').slideUp();
		} else {
			$('#start_end_dates').slideDown();
		}
	;";

	$hidden = "";
	if ($last_date > 0) {
		$hidden = "style='display: none;'";
	}

	$html  = print_select (get_last_dates(), $id, $last_date, $script, '', '', true, 0, false, $label);
	$html .= "<br>";
	$html .= "<div id='start_end_dates' $hidden>";
	$html .= 	"<div id='$start_date_name' style='display: inline-block;'>" . print_input_text ($start_date_name, $start_date, "", 8, 100, true, $start_date_label) . "</div>";
	$html .= 	"&nbsp;";
	$html .= 	"<div id='$end_date_name' style='display: inline-block;'>" . print_input_text ($end_date_name, $end_date, "", 8, 100, true, $end_date_label) . "</div>";
	$html .= "</div>";

	return $html;

}

function get_last_date_control_div ($last_date = 0, $id = 'last_date_search', $label = '', $start_date = '', 
		$start_date_name = 'start_date_search', $start_date_label = '',
		$end_date = '', $end_date_name = 'end_date_search', 
		$end_date_label = '', $id_div = 'start_end_dates') {

	if ($label == '') {
		$label = __('Date');
	}
	if ($start_date_label == '') {
		$start_date_label = __('Start date');
	}
	if ($end_date_label == '') {
		$end_date_label = __('End date');
	}

	$script = "javascript:
		if ($('#$id').val() > 0) {
			$('#$id_div').slideUp();
		} else {
			$('#$id_div').slideDown();
		}
	;";

	$hidden = "";
	if ($last_date > 0) {
		$hidden = "style='display: none;'";
	}

	$html  = print_select (get_last_dates(), $id, $last_date, $script, '', '', true, 0, false, $label);
	$html .= "<br>";
	$html .= "<div id='$id_div' $hidden>";
	$html .= 	"<div id='$start_date_name' style='display: inline-block;'>" . print_input_text ($start_date_name, $start_date, "", 8, 100, true, $start_date_label) . "</div>";
	$html .= 	"&nbsp;";
	$html .= 	"<div id='$end_date_name' style='display: inline-block;'>" . print_input_text ($end_date_name, $end_date, "", 8, 100, true, $end_date_label) . "</div>";
	$html .= "</div>";

	return $html;

}


function print_company_autocomplete_input ($parameters) {
	if (isset($parameters['input_name'])) {
		$input_name = $parameters['input_name'];
	}
	
	$input_value = '';
	$company_name = '';
	if (isset($parameters['input_value'])) {
		$input_value = $parameters['input_value'];
		$company_name = get_db_value("name", "tcompany", "id", $input_value);
		if (!$company_name) {
			$company_name = "";
		}
	}
	
	if (isset($parameters['input_id'])) {
		$input_id = $parameters['input_id'];
	}
	
	$return = false;
	if (isset($parameters['return'])) {
		$return = $parameters['return'];
	}
	//$input_size = 15;
	if (isset($parameters['size'])) {
		$input_size = $parameters['size'];
	}
	
	//$input_maxlength = 50;
	if (isset($parameters['maxlength'])) {
		$input_maxlength = $parameters['maxlength'];
	}
	
	$title = '';
	if (isset($parameters['title'])) {
		$title = $parameters['title'];
	}
	
	$help_message = "Type at least two characters to search";
	if (isset($parameters['help_message'])) {
		$help_message = $parameters['help_message'];
	}
	$return_help = true;
	if (isset($parameters['return_help'])) {
		$return_help = $parameters['return_help'];
	}

	$filter = "";
	if (isset($parameters['filter'])) {
		$filter = $parameters['filter'];
	}
	
	$attributes = 'class="company_autocomplete"';
	if (isset($parameters['attributes'])) {
		if (!is_array($parameters['attributes']))
			$attributes .= $parameters['attributes'];
	}
	$html = "";
	if(!isset($input_size)){
		$input_size = '';
	}
	if(!isset($input_maxlength)){
		$input_maxlength = '';
	}
	if($return_help){
		
		$html .= print_input_text_extended ("autocomplete_".$input_name, $company_name, $input_id, '', $input_size, $input_maxlength, false, '', $attributes, true, '', __($title). print_help_tip (__($help_message, $return_help), true));
	} else {
		$html .= print_input_text_extended ("autocomplete_".$input_name, $company_name, $input_id, '', $input_size, $input_maxlength, false, '', $attributes, true, '');
	}
	$html .= print_input_hidden ($input_name, $input_value, true);
	
	if ($filter) {
		$html .= print_input_hidden ("autocomplete_".$input_name."_filter", $filter, true);
	}

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

/**
 * Render an input password element.
 *
 * The element will have an id like: "password-$name"
 * 
 * @param mixed parameters:
 * 			- id: string
 * 			- style: string
 * 			- hidden: boolean
 * 			- content: string
 * @param bool return or echo flag
 *
 * @return string HTML code if return parameter is true.
 */
function print_div ($options, $return = false) {
	$output = '<div';
	
	//Valid attributes (invalid attributes get skipped)
	$attrs = array ("id", "style", "class");
	
	if (isset ($options['hidden'])) {
		if (isset($options['style'])) {
			$options['style'] .= 'display:none;';
		}
		else {
			$options['style'] = 'display:none;';
		}
	}
	
	foreach ($attrs as $attribute) {
		if (isset ($options[$attribute])) {
			$output .= ' '.$attribute.'="'.safe_input_html ($options[$attribute]).'"';
		}
	}
	
	$output .= '>';
	
	$output .= isset ($options['content']) ? $options['content'] : '';
	
	$output .= '</div>';
	
	if ($return) {
		return $output;
	}
	else {
		echo $output;
	}
}

/**
 * Print the title, subtitle and the menu into a page
 *
 * The element will have an id like: "password-$name"
 * 
 * @param title
 * @param subtitle
 * @param sec
 * @param menu (associative array)
 * 			- title: title when hover
 * 			- link: sec2 (undef to avoid link and only print image)
 * 			- img: image
 * @param selected_tab
 * @param bool return or echo flag
 *
 * @return echoes the header.
 */
function print_title_with_menu ($title = "", $subtitle = "", $help_tip = false, $sec = 'projects', $menu = false, $selected_tab = false) {
	
	echo "<h2>" . $title . "</h2>";
	echo "<h4>" . $subtitle;
	if ($help_tip !== false) echo integria_help ($help_tip, true);
	echo "<div id='button-bar-title'>";
	echo '<ul>';
	
	if ($menu !== false) {
		foreach ($menu as $section => $info) {
			$class = ($section == $selected_tab) ? 'button-bar-selected' : '';
			
			$anchor = '';
			if (isset ($info['link'])) {
				$anchor = '<a href="index.php?sec=' . $sec . '&sec2=' . $info['link'] . '"';
				if (isset ($info['target'])) $anchor .= ' target="' . $info['target'] . '"';
				$anchor .= '>';
			}
			
			echo '<li class="' . $class . '">' . $anchor . '<span><img src="'.$info['img'].'" title="'.$info['title'].'"></span></a></li>';
		}
	}
		
	echo '</ul>';

	echo '</div>';
	echo "</h4>";
}

?>
