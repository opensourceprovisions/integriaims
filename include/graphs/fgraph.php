<?php
// Copyright (c) 2011-2011 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

$ttl = 1;
$homeurl = '';

if (isset($_GET['homeurl'])) {
	$homeurl = $_GET['homeurl']; 	
}
else $homeurl = '';

$homeurl = ((bool)filter_var($homeurl, FILTER_VALIDATE_URL) == 1) ? '' : $homeurl;

if (isset($_GET['ttl'])) {
	$ttl = $_GET['ttl']; 	
}
else $ttl_param = 1;

if (isset($_GET['graph_type'])) {
	$graph_type = $_GET['graph_type']; 	
}
else $graph_type = '';

//$graph_type = get_parameter('graph_type', '');
//$ttl_param = get_parameter('ttl', 1);
//$homeurl_param = get_parameter('homeurl', '');

ob_start ();

if (!empty($graph_type)) {
	include_once($homeurl . 'include/functions.php');
	include_once($homeurl . 'include/functions_html.php');
	include_once($homeurl . 'include/graphs/functions_fsgraph.php');
	include_once($homeurl . 'include/graphs/functions_gd.php');
	include_once($homeurl . 'include/graphs/functions_utils.php');
	include_once($homeurl . 'include/graphs/functions_flot.php');
}

// Clean the output buffer and turn off output buffering
ob_end_clean ();

switch($graph_type) {
	case 'histogram': 
		$width = get_parameter('width');
		$height = get_parameter('height');
		$font = get_parameter('font');
		$data = json_decode(safe_output(get_parameter('data')), true);

		$max = get_parameter('max');
		$title = get_parameter('title');
		$mode = get_parameter ('mode', 1);
		gd_histogram ($width, $height, $mode, $data, $max, $font, $title);
		break;
	case 'progressbar':
		$width = get_parameter('width');
		$height = get_parameter('height');
		$progress = get_parameter('progress');
		
		$out_of_lim_str = get_parameter('out_of_lim_str', false);
		$out_of_lim_image = get_parameter('out_of_lim_image', false);

		$font = get_parameter('font');
		$title = get_parameter('title');
		
		$mode = get_parameter('mode', 1);
		
		gd_progress_bar ($width, $height, $progress, $title, $font, $out_of_lim_str, $out_of_lim_image, $mode);
		break;
}

function histogram($chart_data, $width, $height, $font, $max, $title, $mode, $ttl = 1) {
	$graph = array();
	$graph['data'] = $chart_data;
	$graph['width'] = $width;
	$graph['height'] = $height;
	$graph['font'] = $font;
	$graph['max'] = $max;
	$graph['title'] = $title;
	$graph['mode'] = $mode;

	$id_graph = serialize_in_temp($graph, null, $ttl);
		
	return "<img title='".$graph["title"]."' src='include/graphs/functions_gd.php?graph_type=histogram&ttl=".$ttl."&id_graph=".$id_graph."'>";
}

function funnel($data, $font, $ttl = 1, $homedir="") {
	$graph = array();
	
	$graph['font'] = $font;
	$graph["data"] = $data;

	$id_graph = serialize_in_temp($graph, null, $ttl);
	
	return "<img src='include/graphs/functions_gd.php?graph_type=funnel&ttl=".$ttl."&id_graph=".$id_graph."'>";
}

function progressbar($progress, $width, $height, $title, $font, $mode = 1, 
					$out_of_lim_str = false, $out_of_lim_image = false, $ttl = 1) {
	$graph = array();

	$graph['progress'] = $progress;
	$graph['width'] = $width;
	$graph['height'] = $height;
	$graph['out_of_lim_str'] = $out_of_lim_str;
	$graph['out_of_lim_image'] = $out_of_lim_image;
	$graph['title'] = $title;
	$graph['font'] = $font;
	$graph['mode'] = $mode;

	$id_graph = serialize_in_temp($graph, null, $ttl);
		
	return "<img src='include/graphs/functions_gd.php?graph_type=progressbar&ttl=".$ttl."&id_graph=".$id_graph."'>";
}


function slicesbar_graph($chart_data, $period, $width, $height, $colors, $font, 
						$round_corner, $home_url = '', $ttl = 1) {
	$graph = array();
	$graph['data'] = $chart_data;
	$graph['period'] = $period;
	$graph['width'] = $width;
	$graph['height'] = $height;
	$graph['font'] = $font;
	$graph['round_corner'] = $round_corner;
	$graph['color'] = $colors;

	$id_graph = serialize_in_temp($graph, null, $ttl);
	
	return "<img src='".$home_url."include/graphs/functions_pchart.php?graph_type=slicebar&ttl=".$ttl."&id_graph=".$id_graph."'>";
}

function vbar_graph($flash_chart, $chart_data, $width, $height, $color = array(),
	$legend = array(), $xaxisname = "", $yaxisname = "", $homedir="",
	$water_mark = '', $font = '', $font_size = '', $force_steps = true, $ttl = 1, $reduce_data_columns = false) {
		
	if($flash_chart) {
		echo flot_vcolumn_chart ($chart_data, $width, $height, $homedir, $reduce_data_columns, $xaxisname, $yaxisname);
	}
	else {
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		$graph['xaxisname'] = $xaxisname;
		$graph['yaxisname'] = $yaxisname;
		$graph['water_mark'] = $water_mark;
		$graph['font'] = $font;
		$graph['font_size'] = $font_size;
		$graph['force_steps'] = $force_steps;

		$id_graph = serialize_in_temp($graph, null, $ttl);
	
		return "<img src='" . $homedir . "include/graphs/functions_pchart.php?graph_type=vbar&ttl=".$ttl."&id_graph=".$id_graph."'>";
	}
}

function threshold_graph($flash_chart, $chart_data, $width, $height, $ttl = 1) {
	if($flash_chart) {
		echo fs_area_chart ($chart_data, $width, $height);
	}
	else {
		echo "<img src='include/graphs/functions_pchart.php?graph_type=threshold&ttl=".$ttl."&data=".json_encode($chart_data)."&width=".$width."&height=".$height."'>";
	}
}

function area_graph($flash_chart, $chart_data, $width, $height, $color, $legend,
	$long_index, $no_data_image, $xaxisname = "", $yaxisname = "", $homedir="",
	$water_mark = "", $font = '', $font_size = '', $unit = '', $ttl = 1) {

	global $config;

	if (empty($chart_data)) {
		return '<img src="' . $no_data_image . '" />';
	}
	
	if($flash_chart) {
		//~ return fs_area_graph($chart_data, $width, $height, $color, $legend, $long_index, $homedir, $unit);
		return flot_vcolumn_chart($chart_data, $width, $height, $color, $legend, $long_index, $homedir, $unit, '','',$xaxisname, $yaxisname);
	}
	else {
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		$graph['xaxisname'] = $xaxisname;
		$graph['yaxisname'] = $yaxisname;
		$graph['water_mark'] = $water_mark;
		$graph['font'] = $font;
		$graph['font_size'] = $font_size;
				
		$id_graph = serialize_in_temp($graph, null, $ttl);
		return "<img src='".$homedir."include/graphs/functions_pchart.php?graph_type=area&ttl=".$ttl."&id_graph=" . $id_graph . "'>";
	}	
}

function stacked_area_graph($flash_chart, $chart_data, $width, $height, $color,
	$legend, $long_index, $no_data_image, $xaxisname = "", $yaxisname = "",
	$water_mark = "", $font = '', $font_size = '', $unit = '', $ttl = 1, $homedir='') {

	if (empty($chart_data)) {
		return '<img src="' . $no_data_image . '" />';
	}
	
	if($flash_chart) {
		return fs_stacked_graph($chart_data, $width, $height, $color, $legend, $long_index);
	}
	else {
		//Stack the data
		stack_data($chart_data, $legend, $color);
		
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		$graph['xaxisname'] = $xaxisname;
		$graph['yaxisname'] = $yaxisname;
		$graph['water_mark'] = $water_mark;
		$graph['font'] = $font;
		$graph['font_size'] = $font_size;
		
		$id_graph = serialize_in_temp($graph, null, $ttl);
		
		return "<img src='".$homedir."include/graphs/functions_pchart.php?graph_type=stacked_area&ttl=".$ttl."&id_graph=" . $id_graph . "' />";
	}	
}

function stacked_line_graph($flash_chart, $chart_data, $width, $height, $color,
	$legend, $long_index, $no_data_image, $xaxisname = "", $yaxisname = "",
	$water_mark = "", $font = '', $font_size = '', $unit = '', $ttl = 1) {
		
	if (empty($chart_data)) {
		return '<img src="' . $no_data_image . '" />';
	}
	
	//Stack the data
	stack_data($chart_data, $legend, $color);
	
	if($flash_chart) {
		return fs_line_graph($chart_data, $width, $height, $color, $legend, $long_index);
	}
	else {		
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		$graph['xaxisname'] = $xaxisname;
		$graph['yaxisname'] = $yaxisname;
		$graph['water_mark'] = $water_mark;
		$graph['font'] = $font;
		$graph['font_size'] = $font_size;
		
		$id_graph = serialize_in_temp($graph, null, $ttl);
		
		return "<img src='include/graphs/functions_pchart.php?graph_type=line&ttl=".$ttl."&id_graph=" . $id_graph . "' />";
	}
}

function line_graph($flash_chart, $chart_data, $width, $height, $color, $legend,
	$long_index, $no_data_image, $xaxisname = "", $yaxisname = "",
	$water_mark = "", $font = '', $font_size = '', $unit = '', $ttl = 1) {
		
	if (empty($chart_data)) {
		return '<img src="' . $no_data_image . '" />';
	}
	
	if($flash_chart) {
		return fs_line_graph($chart_data, $width, $height, $color, $legend, $long_index);
	}
	else {
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		$graph['xaxisname'] = $xaxisname;
		$graph['yaxisname'] = $yaxisname;
		$graph['water_mark'] = $water_mark;
		$graph['font'] = $font;
		$graph['font_size'] = $font_size;
		
		$id_graph = serialize_in_temp($graph, null, $ttl);
		
		return "<img src='include/graphs/functions_pchart.php?graph_type=line&ttl=".$ttl."&id_graph=" . $id_graph . "' />";
	}	
}

function kiviat_graph($graph_type, $flash_chart, $chart_data, $width, $height, $no_data_image, $ttl = 1, $homedir="") {
	if (empty($chart_data)) {
		return '<img src="' . $no_data_image . '" />';
	}
	
	$graph = array();
	$graph['data'] = $chart_data;
	$graph['width'] = $width;
	$graph['height'] = $height;
		
	$id_graph = serialize_in_temp($graph, null, $ttl);
		
	return "<img src='".$homedir."include/graphs/functions_pchart.php?graph_type=".$graph_type."&ttl=".$ttl."&id_graph=" . $id_graph . "' />";
}

function radar_graph($flash_chart, $chart_data, $width, $height, $no_data_image, $ttl = 1, $homedir="") {
	return kiviat_graph('radar', $flash_chart, $chart_data, $width, $height, $no_data_image, $ttl, $homedir);
}

function polar_graph($flash_chart, $chart_data, $width, $height, $no_data_image, $ttl = 1, $homedir="") {
	return kiviat_graph('polar', $flash_chart, $chart_data, $width, $height, $no_data_image, $ttl, $homedir="");
}

function hbar_graph($flash_chart, $chart_data, $width, $height, $color = array(),
	$legend = array(), $xaxisname = "", $yaxisname = "", $force_height = true,
	$homedir="", $water_mark = '', $font = '', $font_size = '', $force_steps = true, $ttl = 1) {
	if($flash_chart) {
		//~ return fs_2d_hcolumn_chart ($chart_data, $width, $height);
		return flot_hcolumn_chart ($chart_data, $width, $height, $water_mark);
	}
	else {
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['color'] = $color;
		$graph['legend'] = $legend;
		$graph['xaxisname'] = $xaxisname;
		$graph['yaxisname'] = $yaxisname;
		$graph['force_height'] = $force_height;
		$graph['water_mark'] = $water_mark;
		$graph['font'] = $font;
		$graph['font_size'] = $font_size;
		$graph['force_steps'] = $force_steps;

		$id_graph = serialize_in_temp($graph, null, $ttl);
	
		return "<img src='".$homedir."include/graphs/functions_pchart.php?graph_type=hbar&ttl=".$ttl."&id_graph=".$id_graph."'>";
	}
}

function pie3d_graph($flash_chart, $chart_data, $width, $height,
	$others_str = "other", $homedir="", $water_mark = "", $font = '', $font_size = '', $ttl = 1) {
	return pie_graph('3d', $flash_chart, $chart_data, $width, $height,
		$others_str, $homedir, $water_mark, $font, $font_size, $ttl);
}

function pie2d_graph($flash_chart, $chart_data, $width, $height,
	$others_str = "other", $homedir="", $water_mark = "", $font = '', $font_size = '', $ttl = 1) {
	return pie_graph('2d', $flash_chart, $chart_data, $width, $height,
		$others_str, $homedir, $water_mark, $font, $font_size, $ttl);
}

function pie_graph($graph_type, $flash_chart, $chart_data, $width, $height,
	$others_str = "other", $homedir="", $water_mark = "", $font = '', $font_size = '', $ttl = 1) {
	// This library allows only 8 colors
	include_once('functions_flot.php');
	$max_values = 8;

	// Process data and decode it from HTML encoding
	$data_temp = array();
	foreach($chart_data as $key => $value) {
		$data_temp[safe_output($key)]=safe_output($value);
	}
	$chart_data = $data_temp;

	if(count($chart_data) > $max_values) {
		$chart_data_trunc = array();
		$n = 1;
		foreach($chart_data as $key => $value) {
			if($n < $max_values) {
				$chart_data_trunc[$key] = $value;
			}
			else {
				if (!isset($chart_data_trunc[$others_str])) {
					$chart_data_trunc[$others_str] = 0;
				}
				$chart_data_trunc[$others_str] += $value;
			}
			$n++;
		}
		$chart_data = $chart_data_trunc;
	}
	
	if($flash_chart) {
		switch($graph_type) {
			case "2d":
					//return fs_2d_pie_chart (array_values($chart_data), array_keys($chart_data), $width, $height);
				break;
			case "3d":
					return flot_pie_chart(array_values($chart_data),
							array_keys($chart_data), $width, $height, $water_mark,
							$font, $font_size);
					//return fs_3d_pie_chart(array_values($chart_data), array_keys($chart_data), $width, $height);
				break;
		}
	}
	else {
		$graph = array();
		$graph['data'] = $chart_data;
		$graph['width'] = $width;
		$graph['height'] = $height;
		$graph['water_mark'] = $water_mark;
		$graph['font'] = $font;
		$graph['font_size'] = $font_size;

		$id_graph = serialize_in_temp($graph, null, $ttl);
		
		switch($graph_type) {
			case "2d":
					return "<img src='" . $homedir . "include/graphs/functions_pchart.php?graph_type=pie2d&ttl=".$ttl."&id_graph=".$id_graph."'>";
				break;
			case "3d":				
					return "<img src='" . $homedir . "include/graphs/functions_pchart.php?graph_type=pie3d&ttl=".$ttl."&id_graph=".$id_graph."'>";
				break;
		}
	}
}

function gantt_graph($project_name, $from, $to, $tasks, $milestones, $width, $height, $ttl = 1) {
	return fs_gantt_chart ($project_name, $from, $to, $tasks, $milestones, $width, $height, $ttl);
}

function include_flash_chart_script($homeurl = '') {
	echo '<script language="JavaScript" src="' . $homeurl . 'include/graphs/FusionCharts/FusionCharts.js"></script>';
}

?>
