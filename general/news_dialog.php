<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Integria uses icons from famfamfam, licensed under CC Atr. 2.5
// Silk icon set 1.3 (cc) Mark James, http://www.famfamfam.com/lab/icons/silk/
// Integria uses Pear Image::Graph code
// Integria shares much of it's code with project Babel Enterprise and Pandora FMS,
// also a Free Software Project coded by some of the people who makes Integria.

// Set to 1 to do not check for installer or config file (for development!).
// Activate gives more error information, not useful for production sites

global $config;
	
$options = array();
$options['id_user'] = $config['id_user'];
$options['modal'] = true;
$news = get_news($options);

// Clean subject entities
foreach($news as $k => $v) {
	$news[$k]['content'] = safe_output($v['content']);
	$news[$k]['title'] = safe_output($v['title']);
}

if (!empty($news)) {
	$options = array();
	$options['id'] = 'news_json';
	$options['hidden'] = 1;
	$options['content'] = base64_encode(json_encode($news));
	print_div($options);
}

// Prints news dialog template
echo '<div id="news_dialog" title="" style="display: none;">';
	
	echo '<div style="position:absolute; top:30px; left: 10px; text-align: left; right:0%; height:70px; min-width:560px; width: 95%; margin: 0 auto; border: 1px solid #FFF; line-height: 19px;">';
		echo '<span style="display: block; height: 260px; overflow: auto; text-align: justify; padding: 5px 15px 4px 10px; background: #ECECEC; border-radius: 4px;" id="new_content"></span>';
		echo '<span style="font-size: 12px; display: block; margin-top: 20px;" id="new_creator"></span>';
		echo '<span style="font-size: 12px; display: block; font-style: italic;" id="new_date"></span>';
	echo '</div>';
	
	echo '<div style="position:absolute; margin: 0 auto; top: 340px; right: 10px; width: 570px">';
		echo '<div style="float: right; width: 20%;">';
		print_submit_button("Ok", 'hide-news-help', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok" style="width:100px;"');  
		echo '</div>';
	echo '</div>';
	
echo '</div>';

?>
<script type="text/javascript" src="include/js/encode_decode_base64.js"></script>
<script type="text/javascript" language="javascript">
/* <![CDATA[ */

$(document).ready (function () {
	if (typeof($('#news_json').html()) != "undefined") {
		
		var news_raw = Base64.decode($('#news_json').html());
		var news = JSON.parse(news_raw);
		var inew = 0;
		
		function show_new () {
			if (news[inew] != undefined) {

				$('#new_content').html(news[inew].content);
				$('#new_date').html(news[inew].date);
				$('#new_creator').html(news[inew].creator);
				
				$("#news_dialog").dialog({
					resizable: true,
					draggable: true,
					modal: true,
					closeOnEscape: false,
					height: 450,
					width: 630,
					title: news[inew].title,
					overlay: {
							opacity: 0.5,
							background: "black"
						}
				});
					
				$('.ui-dialog-titlebar-close').hide();
			}
		}
		
		$("#submit-hide-news-help").click (function () {
			$("#news_dialog" ).dialog('close');
			inew++;
			show_new ();
		});
		
		show_new ();
	}
});

/* ]]> */
</script>
