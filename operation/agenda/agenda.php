<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;
require_once ('include/functions_user.php');

$result_msg = "";

check_login ();

$id_grupo = (int) get_parameter ('id_grupo');

if (! give_acl ($config['id_user'], $id_grupo, "AR")) {
 	// Doesn't have access to this page
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access agenda of group ".$id_grupo);
	include ("general/noaccess.php");
	exit;
}

echo '<h2>' . __('Agenda').'</h2>';
echo '<h4>' . __('Full calendar').'</h4>';

echo '<form method="post" action="index.php?sec=agenda&sec2=operation/agenda/agenda">';

$show_projects = get_parameter("show_projects", 0);
$show_tasks = get_parameter("show_tasks", 0);
$show_events = get_parameter("show_events",0);
$show_wu = get_parameter("show_wu",0);
$show_clients= get_parameter("show_clients",0);
$filter_btn = get_parameter("filter_btn",0);

//By default search for events and workorders
if (!$filter_btn) {
    $show_events = 1;
    $show_wo = 1;
}

$table = new StdClass();
$table->width = '100%';
$table->class = "search-table";
$table->data = array ();
$table->colspan = array ();

$table->data[0][0] = print_checkbox ('show_events', 1, $show_events, true, __('Show entries'));
$table->data[0][1] = print_checkbox ('show_wu', 1, $show_wu, true, __('Show Workunits'));
$table->data[0][2] = print_checkbox ('show_projects', 1, $show_projects, true, __('Show projects'));
$table->data[0][3] = print_checkbox ('show_tasks', 1, $show_tasks, true, __('Show tasks'));
$table->data[0][4] = print_checkbox ('show_clients', 1, $show_clients, true, __('Show customers'));

$button = print_submit_button (__('Filter'), "filter_btn", false, 'class="sub search"', true);
         
$table->data[0][5] = $button;
    
print_table ($table);
echo '</form>'; 

echo "<div id='calendar'></div>";

echo "<table class='calendar_legend'>";
echo "<tr>";
echo "<td class='legend_color_box legend_project'></td>";
echo "<td>".__("Projects")."</td>";
echo "<td class='legend_color_box legend_task'></td>";
echo "<td>".__("Tasks")."</td>";
echo "<td class='legend_color_box legend_wu'></td>";
echo "<td>".__("Workunits")."</td>";
echo "<td class='legend_color_box legend_event'></td>";
echo "<td class='legend_last_box'>".__("Entries")."</td>";
echo "</tr>";
echo "</table>";
?>

<link href='include/js/fullcalendar/fullcalendar.css' rel='stylesheet' />
<link href='include/js/fullcalendar/fullcalendar.print.css' rel='stylesheet' media='print' />
<script src='include/js/fullcalendar/fullcalendar.min.js'></script>

<script>
	
	function get_non_working_days (year) {
		var res;
		
		$.ajax({
			url: "ajax.php",
			data: {
				page: "include/ajax/calendar",
				get_non_working_days: true,
				year: year
			},
			dataType: 'json',
			async: false,
			type: "POST",
			success: function(data) {
				res = data;
			}
		});
		
		return res;
	}
	
	$(document).ready(function() {
	
        var show_projects = <?php echo $show_projects;?>;
        var show_tasks = <?php echo $show_tasks;?>;
        var show_wu = <?php echo $show_wu;?>;
        var show_events = <?php echo $show_events;?>;
        var show_clients = <?php echo $show_clients;?>;
        
        
        var non_working_days = null;
        var today = new Date();
        var year = today.getFullYear();
        var dateYmd;
        var dd;
        var mm;
        var yyyy;
        
        // Today date to 'Ymd'
        dd = today.getDate();
		if (dd < 10) dd = '0'+dd;
		mm = today.getMonth()+1;
		if (mm < 10) mm = '0'+mm;
		yyyy = today.getFullYear();
        today = yyyy+'-'+mm+'-'+dd;
        
		$('#calendar').fullCalendar({
			header: {
				left: 'today',
				center: 'prev,title,next',
				right: 'month,agendaWeek,agendaDay'
			},
			buttonText: {
				prev: '<img src="images/control_rewind_blue.png" title="<?php echo __("Prev");?>" class="calendar_arrow">',
   				next: '<img src="images/control_fastforward_blue.png" title="<?php echo __("Prev");?>" class="calendar_arrow">',
   			},
            firstDay: 1,
			editable: false,
			events: function(start, end, callback) {
				var date_aux = new Date(start);
        		var start_time = date_aux.getTime();

        		start_time = start_time/1000; //Convert from miliseconds to seconds
        			
        		date_aux = new Date(end);
        		var end_time = date_aux.getTime();

        		end_time = end_time/1000; //Convert from miliseconds to seconds

                var url_source = 'ajax.php?page=include/ajax/calendar&get_events=1&ajax=1&start_date='+start_time+'&end_date='+end_time;
                url_source += '&show_projects='+show_projects+'&show_events='+show_events+'&show_wu='+show_wu+'&show_tasks='+show_tasks+'&show_clients='+show_clients;
				
        		$.ajax({
            		url: url_source,
            		dataType: 'json',
            		type: "POST",
            		success: function(data) {
                		var events = [];
						
                		$(data).each(function() {
                			
                			var obj = $(this);
                			var title_str = obj[0].name;
                			var start_str = obj[0].start;
                			var end_str = obj[0].end;
                			var bgColor = obj[0].bgColor;
                			var allDayEvent = obj[0].allDay;
                			var link = obj[0].url;

                			//Convert dates to JS object date
                			start_date = new Date(start_str*1000);

                			var end_date = start_date;
                            
                			if (end_str && (end_str != "0000-00-00 00:00:00")) {
                				end_date = new Date(end_str*1000);                			
                			}
                            
                    		events.push({title: title_str, start: start_date, end: end_date, color: bgColor, allDay: allDayEvent, url: link});
                		});
                		callback(events);
            		}
        		});
    		},
    		dayRender: function (date, cell) {
				if (non_working_days == null || year != date.getFullYear()) {
					year = date.getFullYear();
					non_working_days = get_non_working_days(year);
				}
				// To 'Y-m-d' format
				dd = date.getDate();
				if (dd < 10) dd = '0'+dd;
				mm = date.getMonth()+1;
				if (mm < 10) mm = '0'+mm;
				yyyy = date.getFullYear();
				date = yyyy+'-'+mm+'-'+dd;
				
				if ($.inArray(date, non_working_days) >= 0 && date != today) {
					// Highlight the non working day
					cell.css('background', '#F3F3F3');
				}
			}
		});
	});

</script>
