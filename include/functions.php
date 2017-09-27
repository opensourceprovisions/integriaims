<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2016 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.



global $config;

define ('ENTERPRISE_NOT_HOOK', -1);

//Important: The menu modes codes must be incremental
define('MENU_HIDDEN', 0);
define('MENU_MINIMAL',1);
define('MENU_LIMITED', 2);
define('MENU_FULL',3);

define('GROUP_ALL',1);

/*

Note about clean_input, clean_output and other string functions
----------------------------------------------------------------

ALL Data stored in database SHOULD have been parsed with clean_input()
to encode all conflictive characters, like <, >, & or ' and ".

ALL Data used to output in a different way than HTML render, (in PDF,
Graphs or HTML input controls ) SHOULD parse before with clean_output()
to decode HTML characters.

*/

/**
* Returns a single string with HTML characters decoded
*
* $input    string  Input string
*/

function ascii_output ($string){
	return clean_output ($string);
}

function clean_output ($string) {
	return html_entity_decode ($string, ENT_QUOTES, "UTF-8");
}

function remove_locale_chars ($string){
	$filtro0 = utf8_decode($string);
	$filtro1 = str_replace ('&aacute;',"a", $filtro0);
	$filtro2 = str_replace ('&eacute;',"e", $filtro1);
	$filtro3 = str_replace ('&iacute;',"i", $filtro2);
	$filtro4 = str_replace ('&oacute;',"o", $filtro3);
	$filtro5 = str_replace ('&uacute;',"u", $filtro4);
	$filtro6 = str_replace ('&ntilde;',"n", $filtro5);
	return $filtro6;
}

/**
* Clean input text
*
* This function clean a user string to be used of SQL operations or
* other kind of sensible string operations (like XSS)
* This replace all conflictive characters.
*
* $value	string	Input string to be cleaned*/

function clean_input ($value) {
	if (is_numeric ($value))
		return $value;
	if (is_array ($value)) {
		array_walk ($value, 'clean_input');
		return $value;
	}
	return htmlentities (utf8_decode ($value), ENT_QUOTES);
}

/**
* Returns a single string replacing new lines for <br> HTML tag
*
* $input    string  Input string
*/

function clean_output_breaks ($string) {
	return preg_replace ('/&#x0a;/', "<br>", $string);
}

function replace_return_by_breaks ($string) {
	$string = clean_output_breaks ($string);
	return preg_replace ('/\n/', "<br>", $string);
}

function replace_breaks ($string) {
	$string = preg_replace ('/<br \/>/', "\n", $string);
	$string = preg_replace ('/<br\/>/', "\n", $string);
	$string = preg_replace ('/<br>/', "\n", $string);
	return $string;
}

function safe_input_array ($value) {
	return safe_input($value);
}

/** 
 * Cleans a string by decoding from UTF-8 and replacing the HTML
 * entities.
 * 
 * @param value String or array of strings to be cleaned.
 * 
 * @return The cleaned string.
 */
 
function safe_input_html($value) {
	//Stop!! Are you sure to modify this critical code? Because the older
	//versions are serius headache in many places of Pandora.
	
	if (is_numeric($value))
		return $value;
	
	if (is_array($value)) {
		array_walk($value, "safe_input");
		return $value;
	}
	
	//Clean the trash mix into string because of magic quotes.
	if (get_magic_quotes_gpc() == 1) {
		$value = stripslashes($value);
	}
	
	if (! mb_check_encoding ($value, 'UTF-8'))
		$value = utf8_encode ($value);
	
	return $value;
}

/** 
 * Cleans a string by encoding to UTF-8 and replacing the HTML
 * entities. UTF-8 is necessary for foreign chars like asian 
 * and our databases are (or should be) UTF-8
 * 
 * @param mixed String or array of strings to be cleaned.
 * 
 * @return mixed The cleaned string or array.
 */
function safe_input($value) {
	//Stop!! Are you sure to modify this critical code? Because the older
	//versions are serius headache in many places of Pandora.
	
	if (is_numeric($value))
		return $value;
	
	if (is_array($value)) {
		array_walk($value, "safe_input_array");
		return $value;
	}
	
	//Clean the trash mix into string because of magic quotes.
	if (get_magic_quotes_gpc() == 1) {
		$value = stripslashes($value);
	}
	
	if (! mb_check_encoding ($value, 'UTF-8'))
		$value = utf8_encode ($value);
	
	$valueHtmlEncode =  htmlentities ($value, ENT_QUOTES, "UTF-8");
	
	//Replace the character '\' for the equivalent html entitie
	$valueHtmlEncode = str_replace('\\', "&#92;", $valueHtmlEncode);
	
	// First attempt to avoid SQL Injection based on SQL comments
	// Specific for MySQL.
	$valueHtmlEncode = str_replace('/*', "&#47;&#42;", $valueHtmlEncode);
	$valueHtmlEncode = str_replace('*/', "&#42;&#47;", $valueHtmlEncode);
	
	//Replace ( for the html entitie
	$valueHtmlEncode = str_replace('(', "&#40;", $valueHtmlEncode);
	
	//Replace ( for the html entitie
	$valueHtmlEncode = str_replace(')', "&#41;", $valueHtmlEncode);	
	
	//Replace some characteres for html entities
	for ($i=0;$i<33;$i++) {
		$valueHtmlEncode = str_ireplace(chr($i),ascii_to_html($i), $valueHtmlEncode);			
	}
	
	return $valueHtmlEncode;
}


/** 
 * Convert ascii char to html entitines
 * 
 * @param int num of ascci char
 * 
 * @return string String of html entitie
 */
function ascii_to_html($num) {
	
	if ($num <= 15) {
		return "&#x0".dechex($num).";";
	}
	else {
		return "&#x".dechex($num).";";
	}
}

/** 
 * Convert hexadecimal html entity value to char
 * 
 * @param string String of html hexadecimal value
 * 
 * @return string String with char
 */
function html_to_ascii($hex) {
	
	$dec = hexdec($hex);
	
	return chr($dec);
}


/**
 * Convert the $value encode in html entity to clear char string. This function 
 * should be called always to "clean" HTML encoded data; to render to a text
 * plain ascii file, to render to console, or to put in any kind of data field
 * who doesn't make the HTML render by itself.
 * 
 * @param mixed String or array of strings to be cleaned.
 * @param boolean $utf8 Flag, set the output encoding in utf8, by default true.
 * 
 * @return unknown_type
 */
function safe_output($value, $utf8 = true)
{
	if (is_numeric($value))
		return $value;
	
	if (is_array($value)) {
		array_walk($value, "safe_output");
		return $value;
	}
	
	if (! mb_check_encoding ($value, 'UTF-8'))
		$value = utf8_encode ($value);
	
	if ($utf8) {
		$valueHtmlEncode =  html_entity_decode ($value, ENT_QUOTES, "UTF-8");
	}
	else {
		$valueHtmlEncode =  html_entity_decode ($value, ENT_QUOTES);
	}
	
	//Replace the html entitie of ( for the char
	$valueHtmlEncode = str_replace("&#40;", '(', $valueHtmlEncode);
	
	//Replace the html entitie of ) for the char
	$valueHtmlEncode = str_replace("&#41;", ')', $valueHtmlEncode);
	
	//Revert html entities to chars
	for ($i=0;$i<33;$i++) {
		$valueHtmlEncode = str_ireplace("&#x".dechex($i).";",html_to_ascii(dechex($i)), $valueHtmlEncode);
	}
	
	return $valueHtmlEncode;
}


/** 
 * Get a parameter from get request array.
 * 
 * @param name Name of the parameter
 * @param default Value returned if there were no parameter.
 * 
 * @return Parameter value.
 */
function get_parameter_get ($name, $default = "") {
	if ((isset ($_GET[$name])) && ($_GET[$name] != ""))
		return safe_input ($_GET[$name]);
	
	return $default;
}

/** 
 * Get a parameter from post request array.
 * 
 * @param name Name of the parameter
 * @param default Value returned if there were no parameter.
 * 
 * @return Parameter value.
 */
function get_parameter_post ($name, $default = "") {
	if ((isset ($_POST[$name])) && ($_POST[$name] != ""))
		return safe_input ($_POST[$name]);
	
	return $default;
}

/** 
 * Get a paramter from a request.
 *
 * It checks first on post request, if there were nothing defined, it
 * would return get request
 * 
 * @param name 
 * @param default 
 * 
 * @return 
 */
function get_parameter ($name, $default = '') {
	// POST has precedence
	if (isset($_POST[$name]))
		return get_parameter_post ($name, $default);
	
	if (isset($_GET[$name]))
		return get_parameter_get ($name, $default);
	
	return $default;
}

/** 
* Display a notice about no access and close output stream
*
*/

function no_permission () {
	global $config;
	
	include $config["homedir"]."/general/noaccess.php";
	
	exit;
}

/** 
 * List files in a directory in the local path.
 * 
 * @param directory Local path.
 * @param stringSearch String to match the values.
 * @param searchHandler Pattern of files to match.
 * @param return Flag to print or return the list.
 * 
 * @return The list if $return parameter is true.
 */
function list_files ($directory, $stringSearch, $searchHandler, $return = true, $inverse_filter = "") {
	$errorHandler = false;
	$result = array ();
	if (! $directoryHandler = @opendir ($directory)) {
		echo ("<pre>\nerror: directory \"$directory\" doesn't exist!\n</pre>\n");
		return $errorHandler = true;
	}
	if ($searchHandler == 0) {
		while (false !== ($fileName = @readdir ($directoryHandler))) {
			if (is_dir ($directory.'/'.$fileName))
				continue;
			if ($fileName[0] != ".")
				$result[$fileName] = $fileName;
		}
	}
	if ($searchHandler == 1) {
		while (false !== ($fileName = @readdir ($directoryHandler))) {
			if (is_dir ($directory.'/'.$fileName))
				continue;
			if(@substr_count ($fileName, $stringSearch) > 0) {
				if ($inverse_filter != "") {
					if (strpos($fileName, $inverse_filter) == 0)
						if ($fileName[0] != ".")
							$result[$fileName] = $fileName;
				}
				else {
					 if ($fileName[0] != ".")
						$result[$fileName] = $fileName;
				}
				
			}
		}
	}
	
	if (($errorHandler == true) &&  (@count ($result) === 0)) {
		echo ("<pre>\nerror: no filetype \"$fileExtension\" found!\n</pre>\n");
	}
	else {
		asort ($result);
		return $result;
	}
}

/**
 * Add magnitude to a byte quantity.
 *
 * @param int $bytes Bytes to add magnitude
 *
 * @retval Bytes amount in KiB, MiB, GiB, etc.
 */
function byte_convert ($bytes) {
	$symbol = array ('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
	if ($bytes < 0)
		return '0 B';
	$exp = 0;
	$converted_value = 0;
	if ($bytes > 0) {
		$exp = floor (log ($bytes) / log (1024));
		$converted_value = ($bytes / pow(1024, floor ($exp)));
	}
	
	return sprintf ('%.2f '.$symbol[$exp], $converted_value );
}

function pagination ($count, $url, $offset, $print_result_count=false, $aux_text='',$block_size = 0, $modal_id = false) {
	global $config;
	if($block_size == 0){
		$block_size = $config["block_size"];
	} else {
		$block_size = $block_size;
	}

	if($modal_id){
		$change_id = "modal_";
	} else {
		$change_id = "";
	}
	/* 	URL passed render links with some parameter
			&offset - Offset records passed to next page
	  		&counter - Number of items to be blocked
	   	Pagination needs $url to build the base URL to render links, its a base url, like
	   " http://pandora/index.php?sec=godmode&sec2=godmode/admin_access_logs "
	
	*/
	
	if ($count > $block_size){
		// If exists more registers than I can put in a page, calculate index markers
		$index_counter = ceil($count/$block_size); // Number of blocks of block_size with data
		$index_page = ceil($offset/$block_size)-(ceil($block_size/2)); // block to begin to show data;
		if ($index_page < 0)
			$index_page = 0;
		
		// This calculate index_limit, block limit for this search.
		if (($index_page + $block_size) > $index_counter)
			$index_limit = $index_counter;
		else
			$index_limit = $index_page + $block_size;
		
		// This calculate if there are more blocks than visible (more than $block_size blocks)
		if ($index_counter > $block_size )
			$paginacion_maxima = 1; // If maximum blocks ($block_size), show only 10 and "...."
		else
			$paginacion_maxima = 0;
		
		// This setup first block of query
		if ( $paginacion_maxima == 1)
			if ($index_page == 0)
				$inicio_pag = 0;
			else
				$inicio_pag = $index_page;
		else
			$inicio_pag = 0;
		
		echo "<div>";
		if ($print_result_count) {
			echo "<span style='font-size: 10px'>";
			echo sprintf(__('Results found:  %d'), $count);

			if ($aux_text) {
				echo "&nbsp;".$aux_text;
			}

			echo "</span>";
		}
		echo "<p class='pagination'>";
		// Show GOTO FIRST button
		echo '<a id="'.$change_id.'page_0" class= "'.$change_id.'page_0" href="'.$url.'&offset=0">';
		echo "<img src='".$config["base_url_images"]."/images/control_start_blue.png' border=0 valign='bottom'>";
		echo "</a>";
		echo "&nbsp;";
		// Show PREVIOUS button
		if ($index_page > 0){
			$index_page_prev= ($index_page-(floor($block_size/2)))*$block_size;
			if ($index_page_prev < 0)
				$index_page_prev = 0;
			echo '<a id="'.$change_id.'page_'.$index_page_prev.'" class="'.$change_id.'page_'.$index_page_prev.'" href="'.$url.'&offset='.$index_page_prev.'"><img src="'.$config["base_url_images"].'/images/control_rewind_blue.png" border=0 valign="bottom"></a>';
		}
		echo "&nbsp;";echo "&nbsp;";
		// Draw blocks markers
		// $i stores number of page
		for ($i = $inicio_pag; $i < $index_limit; $i++) {
			$inicio_bloque = ($i * $block_size);
			$final_bloque = $inicio_bloque + $block_size;
			if ($final_bloque > $count){ // if upper limit is beyond max, this shouldnt be possible !
				$final_bloque = ($i-1)*$block_size + $count-(($i-1) * $block_size);
			}
			echo "<span>";
			
			$inicio_bloque_fake = $inicio_bloque + 1;
			// To Calculate last block (doesnt end with round data,
			// it must be shown if not round to block limit)
			echo '<a id="'.$change_id.'page_'.$inicio_bloque.'" class="'.$change_id.'page_'.$inicio_bloque.'" href="'.$url.'&offset='.$inicio_bloque.'">';
			if ($inicio_bloque == $offset)
				echo "<b>[ $i ]</b>";
			else
				echo "[ $i ]";
			echo '</a> ';
			echo "</span>";
		}
		echo "&nbsp;";echo "&nbsp;";
		// Show NEXT PAGE (fast forward)
		// Index_counter stores max of blocks
		if (($paginacion_maxima == 1) AND (($index_counter - $i) > 0)) {
				$prox_bloque = ($i+ceil($block_limit/2))*$block_size;
				if ($prox_bloque > $count)
					$prox_bloque = ($count -1) - $block_size;
				echo '<a id="'.$change_id.'page_'.$prox_bloque.'" class="'.$change_id.'page_'.$prox_bloque.'" href="'.$url.'&offset='.$prox_bloque.'">';
				echo "<img border=0 valign='bottom' src='images/control_fastforward_blue.png'></a> ";
				$i = $index_counter;
		}
		// if exists more registers than i can put in a page (defined by $block_size config parameter)
		// get offset for index calculation
		// Draw "last" block link, ajust for last block will be the same
		// as painted in last block (last integer block).
		if (($count - $block_size) > 0){
			$myoffset = floor(($count-1)/ $block_size)* $block_size;
			echo '<a id="'.$change_id.'page_'.$myoffset.'" class="'.$change_id.'page_'.$myoffset.'" href="'.$url.'&offset='.$myoffset.'">';
			echo "<img border=0 valign='bottom' src='images/control_end_blue.png'>";
			echo "</a>";
		}
	// End div and layout
	echo "</p></div>";
	}
}

function print_array_pagination ($array, $url, $offset = 0){
	global $config;
	
	if (!is_array($array))
		return array();
	
	$count = sizeof($array);
	$offset = get_parameter ("offset", 0);	
	$output =  pagination ($count, $url, $offset );
	$array = array_slice ($array, $offset, $config["block_size"]);
	
	return $array;
}

// Render data in a fashion way :-)
function format_numeric ( $number, $decimals=1, $dec_point=",", $thousands_sep=" ") {
	if (is_numeric($number)){
		if ($number == 0)
			return 0;
		// If has decimals
		if (fmod($number , 1)> 0)
			return number_format ($number, $decimals, $dec_point, $thousands_sep);
		else
			return number_format ($number, 0, $dec_point, $thousands_sep);
	}
	else
		return 0;
}

/** 
 * Render numeric data for a graph. It adds magnitude suffix to the number 
 * (M for millions, K for thousands...) base-10
 *
 * TODO: base-2 multiplication
 * 
 * @param float $number Number to be rendered
 * @param int $decimals Numbers after comma. Default value: 1
 * @param dec_point Decimal separator character. Default value: .
 * @param thousands_sep Thousands separator character. Default value: ,
 * 
 * @return string A string with the number and the multiplier
 */
function format_for_graph ($number , $decimals = 1, $dec_point = ".", $thousands_sep = ",", $divisor = 1000) {
	$shorts = array ("","K","M","G","T","P");
	$pos = 0;
	while ($number >= $divisor) { //as long as the number can be divided by divisor
		$pos++; //Position in array starting with 0
		$number = $number / $divisor;
	}
	
	return format_numeric ($number, $decimals). $shorts[$pos]; //This will actually do the rounding and the decimals
}


/** 
 * Get a translated string
 * 
 * @param string String to translate
 * 
 * @return The translated string. If not defined, the same string will be returned
 */
function __ ($string) {	
	global $l10n;
	global $config;
	
	if (isset($config['enterprise_installed'])) {
		
		if (file_exists('enterprise/include/functions_translate_string.php')) {
			include_once('enterprise/include/functions_translate_string.php');
			
			$tranlateString = get_defined_translation($string);
			
			if ($tranlateString !== false) {
				return $tranlateString;
			}
		}
	}
	
	if ($string == '') {
		return $string;
	}
	
	if (is_null ($l10n))
		return $string;
	
	return $l10n->translate ($string);
}

function lang_string ($string) {
	return __($string);
}

function render_priority ($pri) {
	global $config;
	
	switch($pri) {
		//10 priority is "Maintenance so its priority 0"
		case 10:
			return 0;
		default:
			//Other priorities need to sum one!
			return $pri+1;
	}
	
	/**OLD WAY TO RENDER PRIORITY!!!**/
	/**DON'T REMOVE IS A COMMENT!!!**/
	/*switch ($pri) {
	case 0:
		return __('Very low');
	case 1:
		return __('Low');
	case 2:
		return __('Medium');
	case 3:
		return __('High');
	case 4:
		return __('Very high');
	case 10:
		return __('Maintenance');
	default:
		return __('Other');
	}*/
}


function integria_sendmail ($to, $subject = "[INTEGRIA]", $body,  $attachments = false, $code = "", $from = "", $remove_header_footer =0, $cc="", $extra_headers="", $images = false) {
	global $config;
	
	if ($to == '')
		return false;
	
	$to = trim(safe_output ($to));
	$from = trim(safe_output ($from));
	$cc = trim(safe_output($cc));
	
	$config["mail_from"] = trim($config["mail_from"]);
	
	$current_date = date ("Y/m/d H:i:s");
	
	// We need to convert to pure ASCII here to use carriage returns
	
	// CAUTION: Do not safe_output($body. Data here SHOULD have HTML encoding in place
	// to avoid bad rendering of contents

	$subject = ascii_output ($subject);
	
	if ($remove_header_footer == 0)
		// Add global header and footer to mail
		$body = safe_output($config["HEADER_EMAIL"]). "\r\n". html_entity_decode($body) . "\r\n". safe_output ($config["FOOTER_EMAIL"]);
	
	// Add custom code to the end of message subject (to put there ID's).
	if ($code != "") {
		$subject = "[$code] ".$subject;
		// $body = $body."\r\nNOTICE: Please don't alter the SUBJECT when answer to this mail, it contains a special code who makes reference to this issue.";
	}
	
	// This is a special scenario... we store all the information "ready" in the database, 
	// without HTML encoding. THis is because it is not to be rendered on a browser, 
	// it will be directly to a SMTP connection.
	
	
	
	
	//New check, if exist any data with same data, doesn't insert in DB.
	$check = array(
			'recipient' => $to,
			'body' => mysql_real_escape_string(safe_output($body)),
			'attachment_list' => $attachments,
			'from' => $from,
			'cc' => $cc,
			'image_list' => $images
		);
		
	$checked = get_db_row_filter('tpending_mail', $check, '*');
	if (!$checked) {
		$values = array(
				'date' => $current_date,
				'attempts' => 0,
				'status' => 0,
				'recipient' => $to,
				'subject' => mysql_real_escape_string($subject),
				'body' => mysql_real_escape_string(safe_output($body)),
				//~ 'body' => html_entity_decode($body),
				'attachment_list' => $attachments,
				'from' => $from,
				'cc' => $cc,
				'extra_headers' => $extra_headers,
				'image_list' => $images
			);
		process_sql_insert('tpending_mail', $values);
	}
}

function topi_rndcode ($length = 6) {
	$chars = " abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789 ";
	$code = "";
	$clen = strlen ($chars) - 1;  //a variable with the fixed length of chars correct for the fence post issue
	while (strlen ($code) < $length) {
		$code .= $chars[mt_rand (0, $clen)];  //mt_rand's range is inclusive - this is why we need 0 to n-1
	}
	
	return $code;
}

/* Given a local URL, compose a internet valid URL
   with quicklogin HASH data, and enter it on DB
*/
function topi_quicksession ($url, $id_user = "") {
	global $config;
	if ($id_user == "")
		$id_user = $config["id_user"];
	$today = date ('Y-m-d H:i:s');
	
	// Build quicksession data and URL
	$id_user = $config["id_user"];
	$cadena = topi_rndcode (16).$id_user.$today;
	$cadena_md5 = substr (md5 ($cadena), 1, 8);
	$param = "&quicksession=$cadena_md5&quickuser=$id_user";
	$myurl = $config["base_url"].$url.$param;
	//Insert quicksession data in DB
	$sql = sprintf ('INSERT INTO tquicksession (id_user, timestamp, pwdhash)
		VALUES ("%s", "%s", "%s")',
		$id_user, $today, $cadena_md5);
	process_sql ($sql);
	
	return $myurl;
}


function return_value ($var) {
	if (isset ($var))
		return $var;
	
	return "";
}

function get_priorities ($only_name = false) {
	$incidents = array ();
	
	if (! $only_name) {
		$incidents[10] = 0 . " (".__('Maintenance'). ")";
		$incidents[0] = 1 . " (".__('Informative'). ")";
		$incidents[1] = 2 . " (".__('Low'). ")";
		$incidents[2] = 3 . " (".__('Medium'). ")";
		$incidents[3] = 4 . " (".__('Serious'). ")";
		$incidents[4] = 5 . " (".__('Very serious'). ")";
	} else {
		$incidents[10] = __('Maintenance');
		$incidents[0] = __('Informative');
		$incidents[1] = __('Low');
		$incidents[2] = __('Medium');
		$incidents[3] = __('Serious');
		$incidents[4] = __('Very serious');
	}
	
	return $incidents;
}

function get_priority_name ($priority) {
	switch ($priority) {
		case 10:
			return __('Maintenance');
		case 0:
			return __('Informative');
		case 1:
			return __('Low');
		case 2:
			return __('Medium');
		case 3:
			return __('Serious');
		case 4:
			return __('Very serious');
		default:
			return __('Other');
	}
}

function get_periodicities () {
	$periodicites = array ();
	
	//$periodicites['none'] = __('None');
	$periodicites['weekly'] = __('Weekly');
	$periodicites['15days'] = __('15 days');
	$periodicites['monthly'] = __('Monthly');
	$periodicites['60days'] = __('60 days');
	$periodicites['90days'] = __('90 days');
	$periodicites['year'] = __('Annual');
	
	return $periodicites;
}

function get_days_of_week () {
	$days = array ();
	
	$days[0] = __('Sunday');
	$days[1] = __('Monday');
	$days[2] = __('Tuesday');
	$days[3] = __('Wednesday');
	$days[4] = __('Thursday');
	$days[5] = __('Friday');
	$days[6] = __('Saturday');
	
	return $days;
}

function get_periodicity ($recurrence) {
	$recurrences = get_periodicities ();
	
	return isset ($recurrences[$recurrence]) ? $recurrences[$recurrence] : __('Unknown');
}

function ellipsize_string ($string, $len = 25) {
	$string = ascii_output($string);
	
	return substr ($string, 0, $len).'(..)'.substr ($string, strlen ($string) - $len, $len);
}

/** Cut string if bigger than $len. Put three dots after point of cut in the string
*/
function short_string ($string, $len = 15) {
	$string = ascii_output($string);
	
	if (strlen($string) > $len)
		return substr ($string, 0, $len).'...';
	else
		return $string;
}

/** Clean FLASH string strips non-valid characters for flashchart
*/
function clean_flash_string ($string) {
	$string = ascii_output($string);
	$temp =  str_replace("&", "", $string);
	
	return str_replace ("\"", "", $temp);
}

function print_priority_flag_image ($priority, $return = false, $basedir = "", $class = "") {
	$output = '';


	/* NOTE that priority for the user interface is
            0 - maintance, 1 - Informative... 5 - Very serious
            Values stored in the datbase are different, so: 
              Maintance in DB is 10 and visualized as 0
              Others are stored like X and are visualized as X+1
        */
	
	$output .= '<img class="priority-color '.$class.'" ';
	switch ($priority) {
	case 0:
		// Informative
		$output .= 'src="'.$basedir.'images/priority_informative.png" title="'.__('Informative').' (1)" ';
		break;
	case 1:
		// Low
		$output .= 'src="'.$basedir.'images/priority_low.png" title="'.__('Low').' (2)" ';
		break;
	case 2:
		// Medium
		$output .= 'src="'.$basedir.'images/priority_medium.png" title="'.__('Medium').' (3)" ';
		break;
	case 3:
		// Serious
		$output .= 'src="'.$basedir.'images/priority_serious.png" title="'.__('Serious').' (4)" ';
		break;
	case 4:
		// Very serious
		$output .= 'src="'.$basedir.'images/priority_critical.png" title="'.__('Very serious').' (5)" ';
		break;
	case 10:
		// Maintance
		$output .= 'src="'.$basedir.'images/priority_maintenance.png" title="'.__('Maintance').' (0)" ';
		break;
	default:
		// Default
		$output .= 'src="'.$basedir.'images/pixel_gray.png" title="'.__('Unknown').'" ';
	}
	
	$output .= ' />';
	
	if ($return)
		return $output;
	
	echo $output;
}

function get_project_tracking_state ($state) {
	switch ($state) {
		case PROJECT_CREATED:
			return __('Project created');
		case PROJECT_UPDATED:
			return __('Project updated');
		case PROJECT_DISABLED:
			return __('Project disabled');
		case PROJECT_ACTIVATED:
			return __('Project activated');
		case PROJECT_DELETED:
			return __('Project deleted');
		case PROJECT_TASK_ADDED:
			return __('Task added');
		case PROJECT_TASK_DELETED:
			return __('Task deleted');
		default:
			return __('Unknown');
	}
}

function enterprise_hook ($function_name, $parameters = false) {
	if (function_exists ($function_name)) {
		if (!is_array ($parameters))
			return call_user_func ($function_name);
		return call_user_func_array ($function_name, $parameters);
	}
	return ENTERPRISE_NOT_HOOK;
}

function enterprise_include ($filename, $once = false) {
	global $config;
	
	// Load enterprise extensions
	$filepath = realpath ($config["homedir"].'/'.ENTERPRISE_DIR.'/'.$filename);
	if ($filepath === false)
		return ENTERPRISE_NOT_HOOK;
	if (file_exists ($filepath)) {
		if ($once) {
			include_once ($filepath);
		} else {
			include ($filepath);
		}
		return true;
	}
	return ENTERPRISE_NOT_HOOK;
}

function round_number ($number, $rounder = 5) {
	return (int) ($number / $rounder + 0.5) * $rounder;
}

function template_process ($filename, $macroarray, $contents = false) {

	/* USAGE:
	
	$MACROS["_fullname_"] = "My taylor is rich";
	$msg = template_process ( "messages/mytemplate.tpl", $MACROS);
	
	Will replace all _fullname_ with "My taylor is rich" in the template and return the template
	contents altered on function return
	
	*/
	if (!$contents) { //file
		$fh = fopen ($filename, "r");
		
		// Empty string
		if (! $fh) {
			return "";
		}

		$contents = fread($fh, filesize($filename));
		fclose ($fh);
	}	
	foreach ($macroarray as $key => $value) {
		$contents = str_replace($key, $value, $contents);
	}

	return $contents;
}

function update_config_token ($cfgtoken, $cfgvalue) {
	global $config;
	
	process_sql ("DELETE FROM tconfig WHERE token = '$cfgtoken'");
	process_sql ("INSERT INTO tconfig (token, value) VALUES ('$cfgtoken', '$cfgvalue')");
}

/**
 * Avoid magic_quotes protection
 *
 * @param string Text string to be stripped of magic_quotes protection
 */

function unsafe_string ($string){
	if (get_magic_quotes_gpc() == 1) 
    	$string = stripslashes ($string);
	return $string;
}

function returnMIMEType($filename){
	
	preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);
	
	if (!isset($fileSuffix[1]))
		$fileSuffix[1]="";
	if (!isset($fileSuffix[0]))
		$fileSuffix[0]="";
	
	switch(strtolower($fileSuffix[1]))
	{
		case "js" :
			return "application/x-javascript";
			break;
		case "json" :
			return "application/json";
			break;
		case "jpg" :
		case "jpeg" :
		case "jpe" :
			return "image/jpg";
			break;
		case "png" :
		case "gif" :
		case "bmp" :
		case "tiff" :
			return "image/".strtolower($fileSuffix[1]);
			break;
		case "css" :
			return "text/css";
			break;
		case "xml" :
			return "application/xml";
			break;
		case "doc" :
		case "docx" :
			return "application/msword";
			break;
		case "xls" :
		case "xlt" :
		case "xlm" :
		case "xld" :
		case "xla" :
		case "xlc" :
		case "xlw" :
		case "xll" :
			return "application/vnd.ms-excel";
			break;
		case "ppt" :
		case "pps" :
			return "application/vnd.ms-powerpoint";
			break;
		case "rtf" :
			return "application/rtf";
			break;
		case "pdf" :
			return "application/pdf";
			break;
		case "html" :
		case "htm" :
		case "php" :
			return "text/html";
			break;
		case "txt" :
			return "text/plain";
			break;
		case "mpeg" :
		case "mpg" :
		case "mpe" :
			return "video/mpeg";
			break;
		case "mp3" :
			return "audio/mpeg3";
			break;
		case "wav" :
			return "audio/wav";
			break;
		case "aiff" :
		case "aif" :
			return "audio/aiff";
			break;
		case "avi" :
			return "video/msvideo";
			break;
		case "wmv" :
			return "video/x-ms-wmv";
			break;
		case "mov" :
			return "video/quicktime";
			break;
		case "zip" :
			return "application/zip";
			break;
		case "tar" :
			return "application/x-tar";
			break;
		case "swf" :
			return "application/x-shockwave-flash";
			break;
		case "iso" :
			return "application/iso-image";
			break;
		default :
			return "text/plain";
			break;
	}
	
	return "text/plain";
}

function get_user_language ($id_user = false) {
	global $config;
	
	$quick_language = get_parameter('quick_language_change', 0);
	
	if($quick_language) {
		$language = get_parameter('language', 0);
		
		if($language === 'default') {
			return $config['language'];
		}
		
		if($language !== 0) {
			return $language;
		}
	}
	
	if($id_user === false && isset($config['id_user'])) {
		$id_user = $config['id_user'];
	}
	
	if($id_user != false) {
		$language = get_db_value ("lang", "tusuario", "id_usuario", $id_user);
		if ($language != 'default'){
			return $language;
		}
	}
	
	return $config['language'];
}

// This function delete all files present in directory.
// Is not recursive. It only delete the files

function delete_all_files_in_dir ($tmp_path){
	
	$handle = opendir ($tmp_path); 
	while ($tmp = readdir ($handle)){
		if ($tmp != '..' && $tmp!='.' && $tmp != ''){
			$myfile = $tmp_path."/".$tmp;
			if (is_writeable ($myfile) && is_file($myfile)){
				unlink ($myfile);
			}
		}
	}
	closedir($handle); 
}


// This function deletes all files and directories recursively

function delete_directory ($dirname) {
	if (is_dir($dirname))
		$dir_handle = opendir($dirname);
	if (!$dir_handle)
		return false;
	
	while ($file = readdir($dir_handle)) {
		if ($file != "." && $file != "..") {
			if (!is_dir($dirname."/".$file))
				@unlink($dirname."/".$file);
			else
				delete_directory($dirname . '/' . $file);
		}
	}
	closedir($dir_handle);
	@rmdir($dirname);
	
	return true;
}

// Writes string in integria log file (at integria.log)

function integria_logwrite ($string){
	global $config;
	
	$current_date = date ("Y/m/d H:i:s");
	
	$logfile = $config["homedir"]."/integria.log";
	file_put_contents ( $logfile, "$current_date ".safe_output($string) ."\n", FILE_APPEND);
}

// Check using regexp if a given string is a valid email address

function check_email_address($email) {
	
	// First, we check that there's one @ symbol, 
	// and that the lengths are right.
	
	if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
		// Email invalid because wrong number of characters 
		// in one section or wrong number of @ symbols.
		return false;  	
	}
	
	// Split it into sections to make life easier
	
	$email_array = explode("@", $email);
	$local_array = explode(".", $email_array[0]);
	
	for ($i = 0; $i < sizeof($local_array); $i++) {
		if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
			return false;
		}
	}
	
	// Check if domain is IP. If not, 
	// it should be valid domain name
	
	if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
		$domain_array = explode(".", $email_array[1]);
		if (sizeof($domain_array) < 2) {
			return false; // Not enough parts to domain
		}
		
		for ($i = 0; $i < sizeof($domain_array); $i++) {
			if 	(!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$",$domain_array[$i])) {
				return false;
			}
		}
	}
	
	return true;
}

// Generate a ASCII random string (useful for hashes or similar)
function random_string (){
	
	for ($i = 0; $i < 25; $i++) {
		$randstring .= chr (rand(48, 122));
	}
	
	return $randstring;
}

function check_last_cron_execution($get_mins = false) {
	
	$now = strtotime(date('Y/m/d H:i:s'));
	
	$last_exec = get_db_value('value', 'tconfig', 'token', 'crontask');
	
	if (($last_exec === false) || ($last_exec == '')){
		$last_exec = '';
	}
	
	$unix = strtotime($last_exec);
	
	if ($last_exec == 0) {
		$minutes = '';
	}
	else {
		$minutes = ($now - $unix) / 60;
	}
	
	if ($get_mins) {
		return $minutes;
	}
	
	if (($minutes) < 10) {
		return true;
	}
	
	return false;
}

function check_email_queue ($get_count = false) {
	global $config;
	
	$sql = "SELECT COUNT(*) as total FROM tpending_mail";
	
	$count_aux = process_sql ($sql);
	
	$count = $count_aux[0]["total"];

	if ($get_count) {
		return $count;
	}
	
	if (!isset($config['max_pending_mail'])) {
		$config['max_pending_mail'] = 15;
	}
	
	if ($count < $config['max_pending_mail']) {
		return true;
	}
	return false;
}

function check_alarm_calendar ($count=true, $id=false) {
	global $config;
	
	$now = strtotime(date('Y-m-d H:i:s'));

	if ($count) {
		$sql = "SELECT count(`id`) as num_alarms FROM tagenda 
			WHERE id_user='".$config['id_user']."'
			AND alarm <> 0
			AND ((UNIX_TIMESTAMP(`timestamp`) - $now) > 0)
			AND ((UNIX_TIMESTAMP(`timestamp`) - (`alarm` * 60)) <= $now)";
		
		$alarms = get_db_value_sql($sql);
		
	} else if (!$count && !$id) {
		$sql = "SELECT * FROM tagenda 
			WHERE id_user='".$config['id_user']."'
			AND alarm <> 0
			AND ((UNIX_TIMESTAMP(`timestamp`) - $now) > 0)
			AND ((UNIX_TIMESTAMP(`timestamp`) - (`alarm` * 60)) <= $now)";
		
		$alarms = get_db_all_rows_sql($sql);
		
	} else {
		$sql = "SELECT * FROM tagenda 
			WHERE id_user='".$config['id_user']."'
			AND alarm <> 0
			AND id=$id
			AND ((UNIX_TIMESTAMP(`timestamp`) - $now) > 0)
			AND ((UNIX_TIMESTAMP(`timestamp`) - (`alarm` * 60)) <= $now)";
			
		$alarms = get_db_row_sql($sql);
	}

	return $alarms;
}

function check_directory_permissions() {	
	if (!is_writable("attachment")){
		return true;
	}
	if (!is_writable("attachment/tmp")){	
		return true;
	}	
	if (file_exists("extras/mr") && !is_writable("extras/mr")){
		return true;
	}	
	return false;
}

function check_writable_attachment() {
	$output = '';
	
	if (!is_writable("attachment")) {
		$output .= __('Attachment directory is not writtable by HTTP Server');
		$output .= '<p>';
		$output .= __('Please check that {HOMEDIR}/attachment directory has write rights for HTTP server');
		$output .= '</p>';
	}
	return $output;
}

function check_writable_tmp() {
	$output = '';
	
	if (!is_writable("attachment/tmp")){
		$output .= __('Temporal directory is not writtable by HTTP Server');
		$output .= '<p>';
		$output .= __('Please check that {HOMEDIR}/attachment/tmp directory has write rights for HTTP server');
		$output .= '</p>';

	}
	return $output;
}

function check_writable_mr() {
	$output = '';
	if (file_exists("extras/mr") && !is_writable("extras/mr")){
		$output .= __('Minor releases directory is not writtable by HTTP Server');
		$output .= '<p>';
		$output .= __('Please check that {HOMEDIR}/extras/mr directory has write rights for HTTP server');
		$output .= '</p>';
	}
	return $output;
}

function getFileUploadStatus ($file_input_name) {
	return $_FILES[$file_input_name]['error'];
}

function translateFileUploadStatus ($status) {
	switch ($status) {
		case UPLOAD_ERR_OK:
			$message = true;
			break;
		case UPLOAD_ERR_INI_SIZE:
			$message = __('The file exceeds the maximum size');
			break;
		case UPLOAD_ERR_FORM_SIZE:
			$message = __('The file exceeds the maximum size');
			break;
		case UPLOAD_ERR_PARTIAL:
			$message = __('The uploaded file was only partially uploaded');
			break;
		case UPLOAD_ERR_NO_FILE:
			$message = __('No file was uploaded');
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$message = __('Missing a temporary folder');
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$message = __('Failed to write file to disk');
			break;
		case UPLOAD_ERR_EXTENSION:
			$message = __('File upload stopped by extension');
			break;
		
		default:
			$message = __('Unknown upload error');
			break;
	}
	
	return $message;
}

function get_news($arguments) {
	global $config;
	
	if (isset($arguments['id_user'])) {
		$id_user = $arguments['id_user'];
	} else {
		$id_user = $config['id_user'];
	}
	
	if (isset($arguments['limit'])) {
		$limit = $arguments['limit'];
	} else {
		$limit = 99999999;
	}

	$groups = get_user_groups ($id_user);
	
	if (empty($groups)) {
		$id_group = 0;
	} else {
		$groups = array_keys($groups);
		$id_group = implode(',',$groups);
	}
	
	$current_datetime = date('Y-m-d H:i:s', time());

	$sql = sprintf("SELECT title,content,`date`,creator
				FROM tnewsboard WHERE id_group IN (%s) AND 
								(expire = 0 OR (expire = 1 AND expire_timestamp > '%s'))
				ORDER BY `date` DESC
				LIMIT %s", $id_group, $current_datetime, $limit);

	$news = get_db_all_rows_sql ($sql);
	
	if (empty($news)) {
		$news = array();
	}
	
	return $news;
}

function check_browser() {
	$browser_info = getenv("HTTP_USER_AGENT");
	
	if (preg_match("/Firefox/i",$browser_info) || preg_match("/Chrome/i",$browser_info)) {
		return false;
	} else {
		return true;
	}
}

function is_ajax () {
	if (defined('AJAX') && AJAX)
		return true;
	else
		return false;
}
//
function to_traslate(){
	__('inuse');
	__('new');
	__('unused');
	__('issued');
}

function check_correct_mail($mail) {
	if (preg_match('/[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+/', $mail)) {
		return true;
	}
	else {
		return false;
	}
}

/**
 * Returns a reliable base url which points to the root dir.
 * TODO: Replace all the $config['base_url'] and $config['base_url_dir'] with this function.
 */
function base_url () {
	global $config;
	return empty($config['access_public']) ? $config["base_url"] : $config['public_url'];
}

?>