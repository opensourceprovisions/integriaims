/**
 * loadTasksSubTree asincronous load ajax the tasks and workorders (pass id to search and binary structure of branch),
 * change the [+] or [-] image (with same more or less div id) of tree and anime (for show or hide)
 * the div with id "tree_div[id_father]_task_[div_id]"
 *
 * div_id int use in js and ajax php
 * branches_json json string with a boolean array of branches
 * id_father int use in js and ajax php, its useful when you have a two subtrees with same agent for diferent each one
 */

function loadTasksSubTree(id_project, div_id, branches_json, id_father, sql_search, orden_tree) {
	
	// Content div
	var div = $('#tree_div'+id_father+'_task_'+div_id);
	// Tree image
	var image = $('#tree_image'+id_father+'_task_'+div_id);
	// Content div visibility
	var hiddenDiv = div.attr('hiddenDiv');
	// Content div load status
	var loadDiv = div.attr('loadDiv');
	// Level position
	var pos = parseInt(image.attr('pos_tree'));
	
	//If has no data
	if (loadDiv == -1)
		return;
	
	if (loadDiv == 0) {
		
		//Put an spinner to simulate loading process
		div.html("<img style='padding-top:10px;padding-bottom:10px;padding-left:20px;' src=images/spinner.gif>");
		div.attr('loadDiv', 2);
		
		$.ajax({
			type: "POST",
			url: "ajax.php",
			data: "page=operation/projects/task&print_subtree=1&id_project=" + id_project
			+ "&id_item=" + div_id + "&branches_json=" + branches_json + "&sql_search=" + sql_search + '&orden_tree=' + (orden_tree + 1),
			success: function(msg) {
				if (msg.length != 0) {
					
					div.html(msg);
					div.attr('hiddendiv',1);
					div.attr('loadDiv', 1);
					
				} else {
					
					var icon_path = 'images/tree';
					
					switch (pos) {
						case 0:
							image.attr('src',icon_path+'/first_leaf.png');
							break;
						case 1:
							image.attr('src',icon_path+'/no_branch.png');
							break;
						case 2:
							image.attr('src',icon_path+'/leaf.png');
							break;
						case 3:
							image.attr('src',icon_path+'/last_leaf.png');
							break;
					}
					
					div.html("");
					div.attr('hiddendiv', 1);
					div.attr('loadDiv', -1);
				}
				
			}
		});
	}
	else {

		var icon_path = 'images/tree';
		
		if (hiddenDiv == 0) {
			
			div.slideUp()
			div.attr('hiddenDiv',1);
			
			//change image of tree [-] to [+]
			switch (pos) {
				case 0:
					image.attr('src',icon_path+'/first_closed.png');
					break;
				case 1:
					image.attr('src',icon_path+'/one_closed.png');
					break;
				case 2:
					image.attr('src',icon_path+'/closed.png');
					break;
				case 3:
					image.attr('src',icon_path+'/last_closed.png');
					break;
			}
		}
		else {
			//change image of tree [+] to [-]
			switch (pos) {
				case 0:
					image.attr('src',icon_path+'/first_expanded.png');
					break;
				case 1:
					image.attr('src',icon_path+'/one_expanded.png');
					break;
				case 2:
					image.attr('src',icon_path+'/expanded.png');
					break;
				case 3:
					image.attr('src',icon_path+'/last_expanded.png');
					break;
			}

			div.attr('hiddenDiv',0);
			div.slideDown();
		}
	}
}

/*
function loadTasksSubTree(id_project, div_id, branches_json, id_father, sql_search) {
	
	var div = $('#tree_div'+id_father+'_task_'+div_id);
	// Tree image
	var image = $('#tree_image'+id_father+'_task_'+div_id);
	// Content div visibility
	var hiddenDiv = div.attr('hiddenDiv');
	// Content div load status
	var loadDiv = div.attr('loadDiv');

	//If has no data
	if (loadDiv == -1)
		return;
	
	if (loadDiv == 0) {
		div.attr('loadDiv', 2);
		$.ajax({
			type: "POST",
			url: "ajax.php",
			data: "page=operation/projects/task&print_subtree=1&id_project=" + id_project
			+ "&id_item=" + div_id + "&branches_json=" + branches_json + "&sql_search=" + sql_search,
			success: function(msg) {
				var icon_path = 'images';
				if (msg.length != 0) {
					div.html(msg);
					div.attr('hiddendiv',1);
					div.attr('loadDiv', 1);
				} else {
					div.html("");
					div.attr('hiddendiv', 1);
					div.attr('loadDiv', -1);
				}
			}
		});
	}
	else {
		var icon_path = 'images';
		if (hiddenDiv == 0) {
			div.slideUp()
			div.attr('hiddenDiv',1);
			image.attr('src',icon_path+'/arrow_right.png');
		}
		else {
			image.attr('src',icon_path+'/arrow_down.png');
			div.attr('hiddenDiv',0);
			div.slideDown();
		}
	}
}
*/
function get_gantt_data (id_project, show_actual, scale) {
	show_actual = show_actual || 0;
	scale = scale || "month";

	var ret;

	$.ajax({
		url: 'ajax.php?page=include/ajax/projects&project_tasks='+id_project+'&show_actual='+show_actual+'&scale='+scale,
		data: '',
		success: function(data) {
			ret = data;
		},
		error: function() {
			alert(__('There was an error procesing gantt data'));
		},
		dataType: "json",
		async: false
	});

	return ret;
}

//Updates task information to db
function update_gantt_task (task) {
	$.ajax({
		url: 'ajax.php?page=include/ajax/projects&update_task=1',
		data: task,
		success: function(data) {
			
			var msg_class = "suc";

			if(!data.res) {
				msg_class = "error";
			}

			var message = "<h3 class='"+msg_class+"'>"+data.msg+"</h3>";

			$("#msg_box").html(message);
			$("#msg_box").fadeToggle(1500);
			$("#msg_box").fadeToggle(1000);

		},
		error: function() {
			alert(__('There was an error updating task'));
		},
		dataType: "json",
		async: false
	});
}

//Calculates task limites to allow drag only for this range
function get_task_limit_gantt (task) {
	var left_limit_target = "";
	var left_limit_source = "";
	var right_limit_target = "";
	var right_limit_source = "";

	//Find most restricted limits
	var max = task.$source.length;
	for (var i=0; i<max; i++) {
		var l = task.$source[i];
		var link = gantt.getLink(l);
		var task_aux = gantt.getTask(link.target);
		var type = parseInt(link.type);

		/**
			link types:
				0: start to finish
				1: start to start
				2: finish to finish
		**/
		switch (type) {
			
			case 0:

				if (!right_limit_source) {
					right_limit_source = new Date(task_aux.start_date);
					
				} else {
					//Get most restricted left limit
					if (+task_aux.end_date > +right_limit_source) {
						right_limit_source = new Date(task_aux.start_date);	
					}
				}

				break;

			case 1:

				if (!left_limit_source) {
					left_limit_source = new Date(task_aux.start_date);
					
				} else {
					//Get most restricted left limit
					if (+task_aux.start_date > +left_limit_source) {
						left_limit_source = new Date(task_aux.start_date);
						
					}
				}
				break;

			case 2:
	
				if (!right_limit_source) {
					right_limit_source = new Date(task_aux.end_date);
				} else {
					//Get most restricted left limit
					if (+task_aux.end_date > +right_limit_source) {
						right_limit_source = new Date(task_aux.end_date);
						
					}
				}
				break;
		}
	}

	//Find most restricted limits
	max = task.$target.length;
	for (var i=0; i < max; i++){
		var l = task.$target[i];
		var link = gantt.getLink(l);
		var task_aux = task_aux = gantt.getTask(link.source);
		var type = parseInt(link.type);
		
		/**
			link types:
				0: start to finish
				1: start to start
				2: finish to finish
		**/
		switch (type) {
			
			case 0:

				if (!left_limit_target) {
					left_limit_target = new Date(task_aux.end_date);
				} else {
					//Get most restricted left limit
					if (+task_aux.end_date >= +left_limit_target) {
						left_limit_target = new Date(task_aux.end_date);
					}
				}

				break;

			case 1:
				if (!left_limit_target) {
					left_limit_target = new Date(task_aux.start_date);
				} else {
					//Get most restricted left limit
					if (+task_aux.start_date <= +left_limit_target) {
						left_limit_target = new Date(task_aux.start_date);
					}
				}
				break;

			case 2:
				
				if (!right_limit_target) {
					right_limit_target = new Date(task_aux.end_date);
				} else {
					//Get most restricted left limit
					if (+task_aux.end_date <= +right_limit_target) {
						right_limit_target = new Date(task_aux.end_date);
					}
				}
				break;
		}
	}

	var ret = {
		"right_limit_target": right_limit_target,
		"left_limit_target": left_limit_target,
		"right_limit_source": right_limit_source,
		"left_limit_source": left_limit_source
	};

	return ret;
}

function update_link_gantt(link) {
	var task_source = gantt.getTask(link.source);
	var task_target = gantt.getTask(link.target);

	if (gantt.isLinkAllowed(link)) {

		var data = {"target": task_target.id, "source": task_source.id, "type": link.type};

		$.ajax({
			url: 'ajax.php?page=include/ajax/projects&update_task_parent=1',
			data: data,
			success: function(data) {
				
				var msg_class = "suc";

				if(!data.res) {
					msg_class = "error";
				}

				var message = "<h3 class='"+msg_class+"'>"+data.msg+"</h3>";

				$("#msg_box").html(message);
				$("#msg_box").fadeToggle(1500);
				$("#msg_box").fadeToggle(1000);
			},
			error: function() {
				alert(__('There was an error updating task'));
			},
			dataType: "json",
			async: false
		});
	} else {
		var message = "<h3 class='error'>"+__("Invalid link")+"</h3>";

		$("#msg_box").html(message);
		$("#msg_box").fadeToggle(1500);
		$("#msg_box").fadeToggle(1000);
	}
}

//Binds events to gantt plugin
function bind_event_gantt (tasks) {

	//Deny draggin for actual_data pseudo tasks
	gantt.attachEvent("onBeforeTaskDrag", function(id, mode, e){
		var task = gantt.getTask(id);

	    if(task.actual_data){
	        return false;      
	    }

	    return true;
	});

	//Deny drag out of thresholds
	gantt.attachEvent("onTaskDrag", function(id, mode, task, original){
		var modes = gantt.config.drag_mode;
		
		//contro limits and drag and resize movements
    	if(mode == modes.move || mode == modes.resize) {
    		
    		var limits = get_task_limit_gantt(task);
    		
    		var right_limit_target = limits.right_limit_target;
			var left_limit_target =  limits.left_limit_target;
			var right_limit_source = limits.right_limit_source;
			var left_limit_source = limits.left_limit_source;

    		//Apply right limits if any
			if (right_limit_source && (+task.end_date > +right_limit_source)) {
				task.end_date = new Date(right_limit_source);
			} else if (right_limit_target && (+task.end_date < +right_limit_target)) {
				task.end_date = new Date(right_limit_target);
			}
    		
    		//Apply left limits if any
			if (left_limit_source && (+task.start_date > +left_limit_source)) {
				task.start_date = new Date(left_limit_source);
			} else if (left_limit_target && (+task.start_date < +left_limit_target)) {
				task.start_date = new Date(left_limit_target);
			}
    	}	

    	return true;
	});

	//Update task after drag
	gantt.attachEvent("onAfterTaskDrag", function(id, mode, e){
    	var task = gantt.getTask(id);

    	update_gantt_task (task);

	    return true;
	});

	//Update task when a link is added
	gantt.attachEvent("onAfterLinkAdd", function(id, item){ 

		update_link_gantt(item);

		return true;
	});
}


function task_tooltip_gantt (id, e){

	if (id) {
		var task = gantt.getTask(id);

		var text = "";

		if (task) {
			
			var progress = task.progress * 100;

			progress = Math.round(progress);

			var auxDate = new Date(task.start_date);
			var start = auxDate.getFullYear()+"-"+(auxDate.getMonth()+1)+"-"+auxDate.getDate();

			var auxDate = new Date(task.end_date);
			var end = auxDate.getFullYear()+"-"+(auxDate.getMonth()+1)+"-"+auxDate.getDate();

			text = "<b>"+__("Task")+":</b> "+task.text+
				"<br/><b>Start date:</b> "+start+ 
	    		"<br/><b>"+__("End date")+":</b> "+end+
	    		"<br><b>"+__("Estamated hours")+":</b> "+task.estimated_hours+" "+__("hours")+
	    		"<br><b>"+__("Worked hours")+":</b> "+task.worked_hours+" "+__("hours")+
	    		"<br><b>"+__("Progress")+":</b> "+progress+" %"+

	    		"<br><b>"+__("People involved")+":</b> ";
		
			task.people.forEach(function (item) {
				text += "<br>&nbsp;&nbsp;&nbsp;"+item.name+" <em>("+item.role+")</em>";
			});
		}

		$("#task_tooltip").html(text);
		
		$("div[task_id='"+id+"'] > .gantt_task_content, div[task_id='"+id+"'] > .gantt_task_drag").mousemove(function() {
			var left = event.pageX + 10;
			var top = event.pageY + 10;

			$("#task_tooltip").css("position", "absolute");
			$("#task_tooltip").css("left", left);
			$("#task_tooltip").css("top", top);
			$("#task_tooltip").show();
		});

		$("div[task_id='"+id+"'] > .gantt_task_content, div[task_id='"+id+"'] > .gantt_task_drag").mouseout(function() {
			$("#task_tooltip").css("display", "none");
		});
	}
};

function show_task_link_selector(type, id_project, id_task) {
	
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/projects&get_task_links=1&type="+type+"&id_project="+id_project+"&id_task="+id_task,
		dataType: "html",
		success: function(data){	
			$("#task_search_modal").html (data);
			$("#task_search_modal").show ();

			$("#task_search_modal").dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 520,
					height: 350
				});
			$("#task_search_modal").dialog('open');
		}
	});
}

function load_link(type) {
	id_task = $('#id_task').val();
	name = $('#id_task option:selected').text();

	$('#form-task_detail').append ($('<input type="hidden" value="'+id_task+'"name="links_'+type+'[]" />'));

	$("#task_search_modal").dialog('close');

	$('#link_'+type).append($('<option></option>').html(name).attr("value", id_task));
}

function remove_link(type) {
	id_task = $('#link_'+type).val();

	$("#link_"+type).children('option[value="'+id_task+'"]').remove();
	
	$('input[name^="links_'+type+'"][value="'+id_task+'"]').remove();
}

function show_task_editor_gantt (id, e) {
	var task = gantt.getTask(id);

	var id_task = task.id;
	var id_project = task.id_project;
	var title = __("Task")+" &raquo; "+task.text;
	
	$.ajax({
	type: "GET",
	url: "ajax.php",
	data: "page=include/ajax/projects&get_task_editor=1&id_project="+id_project+"&id_task="+id_task+"&operation=view&gantt_editor=1",
	dataType: "html",
	success: function(data){
			
			$("#task_editor").html (data);
			$("#task_editor").show ();

			$("#task_editor").dialog ({
					title: title,
					resizable: false,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 875,
					height: 670
				});
			$("#task_editor").dialog('open');
		}
	});
}

function submit_task_editor_form_gantt (id_project, id_task) {
	var form_data = $("#form-new_project").serialize();
	$.ajax({
	type: "POST",
	url: "ajax.php?page=include/ajax/projects&get_task_editor=1&id_project="+id_project+"&id_task="+id_task+"&gantt_editor=1&operation=update",
	data: form_data,
	dataType: "html",
	success: function(data){ 
			$("#task_editor").dialog('close');
			data += __("Loading data...");
			$("#msg_box").html(data);
			$("#msg_box").fadeToggle(1500, "easeOutQuint");
			$("#msg_box").fadeToggle(1000, "easeInQuint");

			refresh_gantt ();
		}
	});
}

function delete_task_editor_form_gantt() {
	var id_task = $("#hidden-id_task").attr("value");
	var id_project = $("#hidden-id_project").attr("value");

	var res = confirm('Are you sure?');

	if (res) {
		$.ajax({
		type: "POST",
		url: "ajax.php?page=include/ajax/projects&delete_task="+id_task,
		data: '',
		dataType: "json",
		success: function(data){ 
				$("#task_editor").dialog('close');
				
				var msg_class = "suc";

				if(!data.res) {
					msg_class = "error";
				}
				var message = "<h3 class='"+msg_class+"'>"+data.msg+"</h3>";
				message += __("Loading data...");

				$("#msg_box").html(message);
				$("#msg_box").fadeToggle(1500);
				$("#msg_box").fadeToggle(1000);

				refresh_gantt ();
			}
		});
	} else {
		$("#task_editor").dialog('close');
	}
}

function task_creation_gantt(task) {
	
	if (task.parent) {
		var parent = gantt.getTask(task.parent);
		task.id_parent = parent.id;
	} 
	
	task.id_project = id_project;
	
	// Say to PHP date there is not another task
	if (!$(".gantt_task_row")[0]) {
		task.start_date = "__NEW__";
 	} 
 	
	$.ajax({
	type: "POST",
	url: "ajax.php?page=include/ajax/projects&create_task=1",
	data: task,
	dataType: "json",
	success: function(data){ 
	
			var msg_class = "suc";

			if(!data.res) {
				msg_class = "error";
			}
			var message = "<h3 class='"+msg_class+"'>"+data.msg+"</h3>";
			message += __("Loading data...");

			$("#msg_box").html(message);
			$("#msg_box").fadeToggle(1500);
			$("#msg_box").fadeToggle(1000);

			refresh_gantt();
		},	
	});
}

function toggle_editor_gantt(id_project, id_task, option) {

	var url = "";
	var height = 610;
	if (option == "stats") {
		url = "ajax.php?page=include/ajax/projects&get_task_statistics=1&id_project="+id_project+"&id_task="+id_task+"&gantt_editor=1";
		height = 640;
	} else if (option == "editor") {
		url = "ajax.php?page=include/ajax/projects&get_task_editor=1&id_project="+id_project+"&id_task="+id_task+"&operation=view&gantt_editor=1";
		height = 610;
	}


	$.ajax({
		type: "GET",
		url: url,
		dataType: "html",
		success: function(data){ 
	
			$("#task_editor").html (data);
			$("#task_editor").css("height", height);
		},
	});
}

function refresh_gantt () {
	
	setTimeout(function () {
		$("#gantt_form").submit();
	}, 1200);
}

function toggle_count_hours_checkbox (id_task) {

	var val = $("#checkbox-count_hours").attr("checked") || "nochecked"; //Avoid undefined values
		
	if (val != "checked") {
		$("#slider").slider( "option", "disabled", false );
	} else {
		$.ajax({
			type: "GET",
			url: "ajax.php?page=include/ajax/projects&get_task_completion_hours="+id_task,
			dataType: "json",
			success: function(data){ 
				$(".ui-slider-handle").css("left", data.percentage+"%");
				$("#completion").html(data.percentage+"%");
				$("#hidden-completion").attr("value", data.percentage);
			},
			async: false
		});	

		$('#slider').slider( "option", "disabled", true );
	}	
}

function validate_link_gantt (link) {

	var valid = true;

	$.ajax({
		type: "POST",
		url: "ajax.php?page=include/ajax/projects&check_link=1",
		data: link,
		dataType: "json",
		success: function(data){
			valid = data.result;	
		},
		async: false
	});	

	return valid;
}

function show_calculation (mode) {

	if (mode == 'calculate') {
		var days = $('#text-days').val();
		var people = $('#text-people').val();
	} else {
		var days = 0;
		var people = 0;
	}
	
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/projects&get_calculator=1&days="+days+"&people="+people,
		dataType: "html",
		success: function(data){
			$("#calculator_window").html (data);
			$("#calculator_window").show ();

			$("#calculator_window").dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 520,
					height: 250
				});
			$("#calculator_window").dialog('open');
			
			//catch calculator submit request
			$("#submit-calculate").click(function(){
				show_calculation('calculate');
			})
			
			//catch set value
			$("#submit-set_value").click(function(){
				var total = $('#text-total').val();
				$('#text-hours').val(total);
				$("#calculator_window").dialog('close');	
			})
		}
	});
}

function workunits_task(id_task, filter) {
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: 'page=include/ajax/projects&get_workunits_task=1&id_task='+id_task+filter,
		dataType: "html",
		success: function(data){	
			$("#workunits_task_window").html (data);
			$("#workunits_task_window").show ();
			$("#workunits_task_window").dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 1024,
					height: 768
				});
			$("#workunits_task_window").dialog('open');
			
			$(function() {
				// Init the tooltip
				$('div.tooltip_title').tooltip({
					track: true,
					open: function (event, ui) {
						ui.tooltip.css('max-width', '800px');
					}
				});
			});
			
			$("a[id^='page']").click(function(e) {

				e.preventDefault();
				var id = $(this).attr("id");
						
				offset = id.substr(5,id.length);
				var filter = "&offset="+offset;
				
				workunits_task(id_task, filter);
			});

		}
	});
}