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

class Calendars {
	
	private $offset;
	private $operation;
	private $month;
	private $year;
	private $user;
	
	private $acl = 'AR';
	private $permission = false;
	
	function __construct () {
		$system = System::getInstance();
		
		$this->offset = (int) $system->getRequest('offset', 1);
		$this->operation = (string) $system->getRequest('operation', "");
		$this->month = (int) $system->getRequest('month', date('n', time()));
		$this->year = (int) $system->getRequest('year', date('y', time()));
		$this->user = (string) $system->getRequest('user', $system->getConfig('id_user'));
		
		// ACL
		$this->permission = $this->checkPermission($system->getConfig('id_user'), $this->acl, $this->operation);
	}
	
	public function getPermission () {
		return $this->permission;
	}
	
	public function checkPermission ($id_user, $acl = 'AR', $operation = '') {
		$system = System::getInstance();
		
		$permission = false;
		if (dame_admin($id_user)) {
			$permission = true;
		} else {
			$this->user = $system->getConfig('id_user');
			// Section access
			if ($system->checkACL($acl)) {
				$permission = true;
			}
		}
		
		return $permission;
	}
	
	public function addCalendarsLoader () {
		$ui = Ui::getInstance();
		
		$script = "<script type=\"text/javascript\">
						
						var load_min_month = 1;
						var load_max_month = 1;
						var minYear = ".$this->year.";
						var maxYear = ".$this->year.";
						var minMonth = ".$this->month.";
						var maxMonth = ".$this->month.";

						function loadMinMonth () {

							if (load_min_month == 1) {
								
								load_min_month = 0;

								if (minMonth > 1) {
									minMonth -= 1;
								} else {
									minMonth = 12;
									minYear -= 1;
								}
								
								postvars = {};
								postvars[\"action\"] = \"ajax\";
								postvars[\"page\"] = \"calendars\";
								postvars[\"method\"] = \"get_calendar\";
								postvars[\"year\"] = minYear;
								postvars[\"month\"] = minMonth;
								postvars[\"user\"] = \"" . $this->user . "\";
								
								$.post(\"index.php\",
									postvars,
									function (data) {
										if (data.length < 3) {
											$(\"#min_month_button\").hide();
										} else {
											load_min_month = 1;
											$(\"#min_month\").prepend(data + '<br>');
										}
									},
									\"html\");
							}
						}

						function loadMaxMonth() {
							
							if (load_max_month == 1) {

								load_max_month = 0;

								if (maxMonth <= 11) {
									maxMonth += 1;
								} else {
									maxMonth = 1;
									maxYear += 1;
								}
								
								postvars = {};
								postvars[\"action\"] = \"ajax\";
								postvars[\"page\"] = \"calendars\";
								postvars[\"method\"] = \"get_calendar\";
								postvars[\"year\"] = maxYear;
								postvars[\"month\"] = maxMonth;
								postvars[\"user\"] = \"" . $this->user . "\";
								
								$.post(\"index.php\",
									postvars,
									function (data) {
										if (data.length < 3) {
											$(\"#max_month_button\").hide();
										} else {
											load_max_month = 1;
											$(\"#max_month\").append('<br>' + data);
										}
									},
									\"html\");
							}
						}

						$(document).ready(function() {
							$(window).bind(\"scroll\", function () {
								if ($(this).scrollTop() + $(this).height()
									>= ($(document).height() - 100)) {
								
								}
								if ($(this).scrollTop() <= - 100) {

								}
							});
						});
					</script>";
		
		$ui->contentAddHtml($script);
	}
	
	public function getCalendar ($year = false, $month = false, $user = false) {
		$system = System::getInstance();

		if (!$year) {
			$year = $this->year;
		}
		if (!$month) {
			$month = $this->month;
		}
		if (!$user) {
			$user = $this->user;
		}

		$first_of_month = gmmktime(0, 0, 0, $month, 1, $year);
		list($year, $month_name) = explode(',', gmstrftime('%Y,%B', $first_of_month));
		$html = "<h1 class='title'>" . __(strtoupper(htmlentities(ucfirst($month_name)))) . " " . $year . "</h1>";

		$html .= generate_work_calendar ($year, $month, array(), 1, NULL, $system->getConfig('first_day_week'), "", $user, false);
		
		return $html;
	}

	public function getCalendars () {
		$system = System::getInstance();
		$ui = Ui::getInstance();

		$html = '';
		
		$html .= "<a id=\"min_month_button\" href=\"javascript:\" onclick=\"loadMinMonth()\" data-role=\"button\" data-icon=\"arrow-u\" data-mini=\"true\">" . __("Previous month") . "</a><br>";
		
		$html .= "<div id='min_month'></div>";

		$html .= $this->getCalendar($this->year, $this->month);

		$html .= "<div id='max_month'></div>";

		$html .= "<br><a id=\"max_month_button\" href=\"javascript:\" onclick=\"loadMaxMonth()\" data-role=\"button\" data-icon=\"arrow-d\" data-mini=\"true\">" . __("Next month") . "</a>";
		
		return $html;
	}
	
	public function showCalendars ($message = "") {
		$system = System::getInstance();
		$ui = Ui::getInstance();
		
		$ui->createPage();
		
		// Header
		$back_href = 'index.php?page=home';
		$ui->createDefaultHeader(__("Calendars"),
			$ui->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Back'),
					'href' => $back_href)));
					
		// Content
		$ui->beginContent();
			// Message popup
			if ($message != "") {
				$options = array(
					'popup_id' => 'message_popup',
					'popup_custom' => true,
					'popup_content' => $message
					);
				$ui->addPopup($options);
				$ui->contentAddHtml("<script type=\"text/javascript\">
										$(document).on('pageshow', function() {
											$(\"div.popup-back\")
												.click(function (e) {
													e.preventDefault();
													$(this).remove();
												})
												.show();
										});
									</script>");
			}

			$html = $this->getCalendars();
			$this->addCalendarsLoader();

			$ui->contentAddHtml($html);
		$ui->endContent();

		if (dame_admin($system->getConfig("id_user"))) {
			$options = array (
				'id' => 'form-user_calendar',
				'action' => "index.php?page=calendars",
				'method' => "POST",
				'data-ajax' => 'false'
				);
			$ui->beginForm($options);
			// User
			$options = array(
				'name' => 'user',
				'id' => 'text-user',
				'value' => $this->user,
				'placeholder' => __('User'),
				'autocomplete' => 'off'
				);
			$ui->formAddInputText($options);
			// User autocompletion
			// List
			$ui->formAddHtml("<ul id=\"ul-autocomplete_user\" data-role=\"listview\" data-inset=\"true\"></ul>");
			// Autocomplete binding
			$callbackF = "$('#form-user_calendar').submit();";
			$ui->bindMobileAutocomplete("#text-user", "#ul-autocomplete_user", false, $callbackF);
			$html = $ui->getEndForm();

			$options = array (
				'popup_id' => 'popup-user_calendar',
				'popup_class' => 'ui-content',
				'popup_content' => $html
				);
			$ui->addPopup($options);

			$html = "<a href=\"javascript:\" onclick=\"$('#popup-user_calendar').popup('open');\" data-role=\"button\" data-inline=\"false\" data-icon=\"search\">" . $this->user . "</a>";
			$ui->createFooter($html);
			$ui->showFooter();
		} else {
			$ui->showFooter(false);
		}
		
		$ui->showPage();
	}

	public function getAgenda () {
		$system = System::getInstance();
		$ui = Ui::getInstance();

		$html = "<div id='calendar'></div>";
		$html .= "	<link href='" . $system->getConfig("homedir") . "/include/js/fullcalendar/fullcalendar.css' rel='stylesheet' />
					<link href='" . $system->getConfig("homedir") . "/include/js/fullcalendar/fullcalendar.print.css' rel='stylesheet' media='print' />
					<script src='" . $system->getConfig("homedir") . "/include/js/fullcalendar/fullcalendar.min.js'></script>

					<script>
						
						function get_non_working_days (year) {
							var res;
							
							$.ajax({
								url: \"" . $system->getConfig("homedir") . "/ajax.php\",
								data: {
									page: \"include/ajax/calendar\",
									get_non_working_days: true,
									year: year
								},
								dataType: 'json',
								async: false,
								type: \"POST\",
								success: function(data) {
									res = data;
								}
							});
							
							return res;
						}
						
						$(document).ready(function() {
						
					        var show_projects = $show_projects;
					        var show_tasks = $show_tasks;
					        var show_wo = $show_wo;
					        var show_events = $show_events;
					        
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
									prev: '<img src=\"" . $system->getConfig("homedir") . "/images/control_rewind_blue.png\" title=\"" . __("Prev") . "\" class=\"calendar_arrow\">',
					   				next: '<img src=\"" . $system->getConfig("homedir") . "/images/control_fastforward_blue.png\" title=\"" . __("Prev") . "\" class=\"calendar_arrow\">',
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

					                var url_source = '" . $system->getConfig("homedir") . "/ajax.php?page=include/ajax/calendar&get_events=1&ajax=1&start_date='+start_time+'&end_date='+end_time;
					                url_source += '&show_projects='+show_projects+'&show_events='+show_events+'&show_wo='+show_wo+'&show_tasks='+show_tasks;
									
					        		$.ajax({
					            		url: url_source,
					            		dataType: 'json',
					            		type: \"POST\",
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
					                            
					                			if (end_str && (end_str != \"0000-00-00 00:00:00\")) {
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

					</script>";

		return $html;
	}
	
	public function show () {
		if ($this->permission) {
			$system = System::getInstance();
			$message = "";
			switch ($this->operation) {
				case '':
					$this->showCalendars();
					break;
				default:
					$this->showCalendars();
			}
		} else {
			$this->showNoPermission();
		}
	}
	
	private function showNoPermission () {
		$system = System::getInstance();
		
		audit_db ($system->getConfig('id_user'), $REMOTE_ADDR, "ACL Violation",
			"Trying to access to workunits section");
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = __('Access to this page is restricted to 
			authorized users only, please contact to system administrator 
			if you need assistance. <br><br>Please know that all attempts 
			to access this page are recorded in security logs of Integria 
			System Database');
		$home = new Home();
		$home->show($error);
	}
	
	public function ajax ($method = false) {
		$system = System::getInstance();
		
		if (!$this->permission) {
			return;
		}
		else {
			switch ($method) {
				case 'get_calendar':
					echo $this->getCalendar();
					return;
			}
		}
	}
	
}

?>
