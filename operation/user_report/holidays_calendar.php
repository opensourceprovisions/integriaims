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

// Load global vars

global $config;
check_login ();

require_once('include/functions_user.php');


echo "<h2>".__("People")."</h2>";
echo "<h4>".__("Holidays calendar")."</h4>";
echo "<div id='calendar'></div>";

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
			firstDay: 1,
			buttonText: {
				prev: '<img src="images/control_rewind_blue.png" title="<?php echo __("Prev");?>" class="calendar_arrow">',
   				next: '<img src="images/control_fastforward_blue.png" title="<?php echo __("Prev");?>" class="calendar_arrow">',
   			},
			editable: false,
			events: function(start, end, callback) {
	
        		var user_filter="<?php echo get_parameter("id_user", "");?>";

                var date_aux = new Date(start);
                var start_time = date_aux.getTime();

                start_time = start_time/1000; //Convert from miliseconds to seconds
                    
                date_aux = new Date(end);
                var end_time = date_aux.getTime();

                end_time = end_time/1000; //Convert from miliseconds to seconds

                start_date_aux = $("#calendar").fullCalendar('getDate');

        		$.ajax({
            		url: 'ajax.php?page=include/ajax/calendar&get_holidays=1&ajax=1&start_date='+start_time+'&end_date='+end_time+'&id_user='+user_filter,
            		dataType: 'json',
            		type: "POST",
            		success: function(data) {

                		var events = [];
                		
                		$(data).each(function() {
                			
                			var obj = $(this);
                			var title_str = obj[0].name;
                			var days = obj[0].dates;
                			var bgColor = obj[0].bgColor;
                			var link = obj[0].link;

                			//Print holidays
                			days.forEach(function (element, index, array) {
                				start_date = new Date(element.start*1000);
                				end_date = new Date(element.end*1000);

                				link += "&start_date="+start_time+"&end_date="+end_time;

                    			events.push({title: title_str, start: start_date, end: end_date, color: bgColor, url:link});
                			});
                		});
                		callback(events);

            		}
        		});
    		},
            eventClick: function(event, element) {
                //Get current calendar date and redirect to new page updating this date
                var current_date = $("#calendar").fullCalendar('getDate');
                
                redirect_url = event.url+"&calendar_focus="+current_date;
                
                window.location.href = redirect_url;
                return false;
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

        //start_date_aux = $("#calendar").fullCalendar('getDate');
        var calendar_focus = "<?php echo safe_output(get_parameter("calendar_focus", ""));?>";

        var date = new Date();
        if (calendar_focus) {
            
            date = new Date(calendar_focus);
        }

        var d = date.getDate();
        var m = date.getMonth();
        var y = date.getFullYear();

        $("#calendar").fullCalendar( 'gotoDate', y, m, d);		
	});

</script>
