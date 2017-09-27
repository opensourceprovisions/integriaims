//Adding prototype for date objects, this code is from
//http://javascript.about.com/library/blweekyear.htm
Date.prototype.getWeek = function() {
var onejan = new Date(this.getFullYear(),0,1);
return Math.ceil((((this - onejan) / 86400000) + onejan.getDay()+1)/7);
}

// This function detects if a day has a milestone
function gantt_has_milestone (scale, date, milestones, return_values) {
	var day = date.getDate();

	return_values = return_values || false; //This command adds a default value for return_values if this variable is undefined

	var scale = gantt.config.scale_unit;

	if (day < 10) {
		day = "0"+day;
	}

	var month = date.getMonth();

	month++; //Correct month error (starts in zero)

	if (month < 10) {
		month = "0"+month;
	}

	var year = date.getFullYear();

   	var dateStr = day+"/"+month+"/"+year;
   	
   	if (scale == "week") {
		
		var week = date.getWeek();

   		if (milestones.week.hasOwnProperty(week)) {

   			if (return_values) {
   				return milestones.week[week];
   			} else {
   				return true;
   			}
   		}


   	} else if (scale == "day") {

   		if (milestones.day.hasOwnProperty(dateStr)) {

   			if (return_values) {
   				return milestones.day[dateStr];
   			} else {
   				return true;
   			}
   		}
   	} else if (scale == "month") { 
   		if (milestones.month.hasOwnProperty(month)) {

   			if (return_values) {
   				return milestones.month[month];
   			} else {
   				return true;
   			}
   		}
   	}	

   	return false;
}

//Marks a cell as a milestone
function gantt_mark_milestone (scale, date, milestones, type) {

	var hasMilestone = gantt_has_milestone(scale, date, milestones);
   	
   	if (hasMilestone) {
   		return "gantt_"+type+"_cell_milestone";
   	}
   	
   	return "";
}

//Prints a tip with milestone information
function gantt_milestones_explanation (date) {

	var hasMilestone = gantt_has_milestone(scale, date, milestones, true);

	var scale = gantt.config.scale_unit;
	
	if (hasMilestone) {
		if (scale == "month" || scale == "week") {
			var values = "";
			hasMilestone.forEach (function (e){
				values += "<b>"+e.date+":</b> "+e.name+"<br>";
			});

			return "<img class='milestone_info' src='images/star_white.png' style='width:15px; margin-top:2px;'><span style='display:none'>"+values+"</span></a>";

		} else if(scale == "day") {
			var values = "";
			hasMilestone.forEach (function (e){
				values += "<b>"+e.name+"</b><br>";

				//Add description if any
				if (e.desc) {
					values += e.desc+"<br>";
				}
			});

			return "<img class='milestone_info' src='images/star_white.png' style='width:15px; margin-top:2px;'><span style='display:none'>"+values+"</span></a>";
		}
	}

	return "";
}

//This function configure chart grids
function configure_gantt(scale, min_scale, max_scale, fn_tooltip_msg, fn_task_editor, 
						fn_task_creation, fn_link_validation) {

	//Configure scale
	gantt.config.scale_unit = scale;
	if (scale == "month") {
		gantt.config.subscales = [
			{unit:"year", step:1, date: "%Y", css:function() {return "";}},
    		{unit:"month", step:1, template:gantt_milestones_explanation}
		];

		gantt.config.date_scale = "%F"; 
	} else if(scale == "week") {

		gantt.config.subscales = [
			{unit:"month", step:1, date: "%M, %Y", css:function() {return "";}},
    		{unit:"week", step:1, template:gantt_milestones_explanation}
		];

		gantt.config.date_scale = "%M, %j"; 
	} else if(scale == "day") {

		gantt.config.subscales = [
			{unit:"month", step:1, date: "%M, %Y", css:function() {return "";}},
    		{unit:"day", step:1, template:gantt_milestones_explanation}
		];
		gantt.config.date_scale = "%D, %d"; 
	}

	//Configure grids and columns
	gantt.config.grid_width = 200;
	gantt.config.scale_height = 54; 
	gantt.config.fit_tasks = true;

	//Configure date limits
	var a_month_in_mili = 3600 * 24 * 30 * 1000;
	var a_week_in_mili = 3600 * 24 * 7 * 1000;
	if (scale == "month") {
		min_scale = min_scale - a_month_in_mili;
		max_scale = max_scale + a_month_in_mili;
	} else {
		min_scale = min_scale - a_week_in_mili;
		max_scale = max_scale + a_week_in_mili;
	}

	gantt.config.start_date = new Date(min_scale);
	gantt.config.end_date = new Date(max_scale);
	
	//Configure columns
	gantt.config.columns = [
	    {name:"text", label:"Task name",  width:200, tree:true },
	    {name:"add",        label:"",           width:56 }
	];	

	//Marks milestone cells
	gantt.templates.task_cell_class = function(item,date){

		var scale = gantt.config.scale_unit;

		return gantt_mark_milestone (scale, date, milestones, "task", scale);
	};

	//Marks milestone for scale cells
	gantt.templates.scale_cell_class = function(date){

		var scale = gantt.config.scale_unit;

		return gantt_mark_milestone (scale, date, milestones, "scale", scale);
	};

	//Color task based on priority
	gantt.templates.task_class  = function(start, end, task){
		if (task.actual_data) {
			return "gantt_task_actual_details";
		} else {
			return "";
		}
	};

	//Configure popup form
	gantt.config.details_on_dblclick = false;
	gantt.config.details_on_create = false;

	gantt.attachEvent("onTaskDblClick", fn_task_editor);

	gantt.attachEvent("onTaskCreated", fn_task_creation);

	gantt.attachEvent("onLinkValidation", fn_link_validation);

	gantt.attachEvent("onMouseMove", fn_tooltip_msg);
}

//Binds jquery actions to show and hide milestone tooltip 
function load_milestone_tooltip_generator() {
	//Show milestone explanation
	$(".milestone_info").mouseover(function (){
		var val = $(this).siblings("span").html();

		var offset = $(this).offset();
		$("#milestone_explanation").html(val);
		$("#milestone_explanation").css("display", "");

		var left = offset.left + 20;
		var top = offset.top + 20;

		$("#milestone_explanation").css("position", "absolute");
		$("#milestone_explanation").css("left", left);
		$("#milestone_explanation").css("top", top);	
	});

	//Hide milestone explanation
	$(".milestone_info").mouseout(function (){
		$("#milestone_explanation").html("");
		$("#milestone_explanation").css("display", "none");
	});
}

//Open all branches
function gantt_open_branches(tasks) {
	tasks.data.forEach(function(e){
		gantt.open(""+e.id+"");
	});	
}