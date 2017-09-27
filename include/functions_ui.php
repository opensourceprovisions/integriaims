<?php

// INTEGRIA IMS 
// http://www.integriaims.com
// ===========================================================
// Copyright (c) 2007-2009 Artica, info@artica.es
// Copyright (c) 2009 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

include_once ("functions.php");
include_once ("functions_groups.php");
include_once ("functions_html.php");

/**
 * Powerful debug function that also shows a backtrace.
 * 
 * This functions need to have active $config['debug'] variable to work.
 *
 * @param mixed Variable name to debug
 * @param bool Wheter to print the backtrace or not.
 * 
 * @return bool Tru if the debug was actived. False if not.
 */
function debug ($var, $backtrace = true) {
	global $config;
	if (! isset ($config['debug']))
		return false;
	
	static $id = 0;
	static $trace_id = 0;
	
	$id++;
	
	if ($backtrace) {
		echo '<div class="debug">';
		echo '<a href="#" onclick="$(\'#trace-'.$id.'\').toggle ();return false;">Backtrace</a>';
		echo '<div id="trace-'.$id.'" class="backtrace invisible">';
		echo '<ol>';
		$traces = debug_backtrace ();
		/* Ignore debug function */
		unset ($traces[0]);
		foreach ($traces as $trace) {
			$trace_id++;
		
			/* Many classes are used to allow better customization. Please, do not
			  remove them */
			echo '<li>';
			if (isset ($trace['class']))
				echo '<span class="class">'.$trace['class'].'</span>';
			if (isset ($trace['type']))
				echo '<span class="type">'.$trace['type'].'</span>';
			echo '<span class="function">';
			echo '<a href="#" onclick="$(\'#args-'.$trace_id.'\').toggle ();return false;">'.$trace['function'].'()</a>';
			echo '</span>';
			if (isset ($trace['file'])) {
				echo ' - <span class="filename">';
				echo str_replace ($config['homedir'].'/', '', $trace['file']);
				echo ':'.$trace['line'].'</span>';
			} else {
				echo ' - <span class="filename"><em>Unknown file</em></span>';
			}
			echo '<pre id="args-'.$trace_id.'" class="invisible">';
			echo '<div class="parameters">Parameter values:</div>';
			echo '<ol>';
			foreach ($trace['args'] as $arg) {
				echo '<li>';
				print_r ($arg);
				echo '</li>';
			}
			echo '</ol>';
			echo '</pre>';
			echo '</li>';
		}
		echo '</ol>';
		echo '</div></div>';
	}
	
	/* Actually print the variable given */
	echo '<pre class="debug">';
	print_r ($var);
	echo '</pre>';
	return true;
}

/** 
 * Prints a generic message between tags.
 * 
 * @param string The message string to be displayed
 * @param string the class to user
 * @param string Any other attributes to be set for the tag.
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 * @param boolean Add a cancel button or not
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_message ($message, $class = '', $attributes = '', $return = false, $tag = 'h3', $cancel_button = true) {
	$id = uniqid();
	
	if ($cancel_button) {
		$cancel_button = '<a href="javascript:cancel_msg(\''.$id.'\');"><img src="images/icono_cerrar.png" border=1 style="margin-top: -30px; float:right; margin-right: 6px;"></a>';
	}
	else {
		$cancel_button = "";
	}
	
	$output = '<'.$tag.(empty ($class) ? '' : ' id="msg_'.$id.'" class="'.$class.'" ').$attributes.'>'.$message.' '.$cancel_button.'</'.$tag.'>';
	
	if ($return)
		return $output;
	echo $output;
}

/** 
 * Prints an error message.
 * 
 * @param string The error message to be displayed
 * @param string Any other attributes to be set for the tag.
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 * @param boolean Add a cancel button or not
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_error_message ($message, $attributes = '', $return = false, $tag = 'h3', $cancel_button = true) {
	return ui_print_message ($message, 'error', $attributes, $return, $tag, $cancel_button);
}

/** 
 * Prints an operation success message.
 * 
 * @param string The message to be displayed
 * @param string Any other attributes to be set for the tag.
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 * @param boolean Add a cancel button or not
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_success_message ($message, $attributes = '', $return = false, $tag = 'h3', $cancel_button = true) {
	return ui_print_message ($message, 'suc', $attributes, $return, $tag, $cancel_button);
}

/** 
 * Evaluates a result using empty() and then prints an error or success message
 * 
 * @param mixed The results to evaluate. 0, NULL, false, '' or 
 * array() is bad, the rest is good
 * @param string The string to be displayed if the result was good
 * @param string The string to be displayed if the result was bad
 * @param string Any other attributes to be set for the h3
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 * @param boolean Add a cancel button or not
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_result_message ($result, $good = '', $bad = '', $attributes = '', $return = false, $tag = 'h3', $cancel_button = true) {
	if ($good == '' || $good === false)
		$good = __('Request successfully processed');
	
	if ($bad == '' || $bad === false)
		$bad = __('Error processing request');
	
	if (empty ($result)) {
		return ui_print_error_message ($bad, $attributes, $return, $tag, $cancel_button);
	}
	return ui_print_success_message ($good, $attributes, $return, $tag, $cancel_button);
}

/**
 * Print a code into a DIV and enable a toggle to show and hide it
 * 
 * @param string html code
 * @param string name of the link
 * @param string title of the link
 * @param bool if the div will be hidden by default (default: true)
 * @param bool Whether to return an output string or echo now (default: true)
 * 
 */

function ui_toggle($code, $name, $title = '', $hidde_default = true, $return = false) {
/*
	$hack_metaconsole = '';
	if (defined('METACONSOLE'))
		$hack_metaconsole = '../../';
*/
	
	// Generate unique Id
	$uniqid = uniqid('');
	
	// Options
	if ($hidde_default) {
		$style = 'display:none';
		$toggle_a = "$('#tgl_div_".$uniqid."').show();";
		$toggle_b = "$('#tgl_div_".$uniqid."').hide();";
		$image_a = print_image("images/go.png", true, false, true);
		$image_b = print_image("images/down.png", true, false, true);
		$original = "images/down.png";
	}
	else {
		$style = '';
		$toggle_a = "$('#tgl_div_".$uniqid."').hide();";
		$toggle_b = "$('#tgl_div_".$uniqid."').show();";
		$image_a = print_image("images/down.png", true, false, true);
		$image_b = print_image("images/go.png", true, false, true);
		$original = "images/go.png";
	}
	
	// Link to toggle
	$output = '';
	$output .= '<a href="#" id="tgl_ctrl_'.$uniqid.'"><b>'.$name.'</b>&nbsp;'.print_image ($original, true, array ("title" => $title, "id" => "image_".$uniqid)).'</a>';
	$output .= '<br /><br />';
	
	// Code into a div
	$output .= "<div id='tgl_div_".$uniqid."' style='".$style."'>\n";
	$output .= $code;
	$output .= "</div>";
	
	// JQuery Toggle
	$output .= '<script type="text/javascript">';
	$output .= '/* <![CDATA[ */';
	$output .= "$(document).ready (function () {";
	$output .= "$('#tgl_ctrl_".$uniqid."').toggle(function() {";
	$output .= $toggle_a;
	$output .= "$('#image_".$uniqid."').attr({src: '".$image_a."'});";
	$output .= "}, function() {";
	$output .= $toggle_b;
	$output .= "$('#image_".$uniqid."').attr({src: '".$image_b."'});";
	$output .= "});";
	$output .= "});";
	$output .= '/* ]]> */';
	$output .= '</script>';
	
	if (!$return) {
		echo $output;
	}
	else {
		return $output;
	}
}

function ui_print_truncate_text($text, $numChars = GENERIC_SIZE_TEXT, $showTextInAToopTip = true, $return = true, $showTextInTitle = true, $suffix = '&hellip;', $style = false) {
	global $config;
	
	if (is_string($numChars)) {
		switch ($numChars) {
			case 'agent_small':
				$numChars = $config['agent_size_text_small'];
				break;
			case 'agent_medium':
				$numChars = $config['agent_size_text_medium'];
				break;
			case 'module_small':
				$numChars = $config['module_size_text_small'];
				break;
			case 'module_medium':
				$numChars = $config['module_size_text_medium'];
				break;
			case 'description':
				$numChars = $config['description_size_text'];
				break;
			case 'item_title':
				$numChars = $config['item_title_size_text'];
				break;
			default:
				$numChars = (int)$numChars;
				break;
		}
	}
	
	
	if ($numChars == 0) {
		if ($return == true) {
			return $text;
		}
		else {
			echo $text;
		}
	} 
	
	$text = safe_output($text);
	if (mb_strlen($text, "UTF-8") > ($numChars)) {
		// '/2' because [...] is in the middle of the word.
		$half_length = intval(($numChars - 3) / 2);
		
		// Depending on the strange behavior of mb_strimwidth() itself,
		// the 3rd parameter is not to be $numChars but the length of
		// original text (just means 'large enough').
		$truncateText2 = mb_strimwidth($text,
			(mb_strlen($text, "UTF-8") - $half_length),
			mb_strlen($text, "UTF-8"), "", "UTF-8" );
		
		$truncateText = mb_strimwidth($text, 0,
			($numChars - $half_length), "", "UTF-8") . $suffix;
		
		$truncateText = $truncateText . $truncateText2;
		
		if ($showTextInTitle) {
			if ($style === null) {
				$truncateText = $truncateText;
			}
			else if ($style !== false) {
				$truncateText = '<span style="' . $style . '" title="' . $text . '">' .
					$truncateText . '</span>';
			}
			else {
				$truncateText = '<span title="' . $text . '">' . $truncateText . '</span>';
			}
		}
		if ($showTextInAToopTip) {
			$truncateText = $truncateText . print_help_tip($text, true);
		}
		else {
			if ($style !== false) {
				$truncateText = '<span style="' . $style . '">' . $truncateText . '</span>';
			}
		}
	}
	else {
		if ($style !== false) { 
			$truncateText = '<span style="' . $style . '">' . $text . '</span>';
		}
		else { 
			$truncateText = $text;
		}
	}
	
	if ($return == true) {
		return $truncateText;
	}
	else {
		echo $truncateText;
	}
}

?>
