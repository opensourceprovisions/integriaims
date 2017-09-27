
/* Function to hide/unhide a specific Div id */
function toggleDiv (div, animate) {
	var item = (typeof div == "string") ? $("#" + div) : $(div);
	var id = div.replace("_div","");
	if (typeof animate !== "undefined" && animate == true) {
		item.slideToggle();
	}
	else {
		var class_img = $("#"+id+"_arrow").attr("class");
		if (class_img == 'arrow_down')
			$("#"+id+" #"+id+"_arrow").addClass('arrow_right').removeClass('arrow_down').attr("src",'images/arrow_right.png');
		else
			$("#"+id+" #"+id+"_arrow").addClass('arrow_down').removeClass('arrow_right').attr("src",'images/arrow_down.png');
		item.toggle();
	}
}

function winopeng (url, wid) {
	open (url, wid,"width=570,height=310,status=no,toolbar=no,menubar=no,scrollbar=no");
	// WARNING !! Internet Explorer DOESNT SUPPORT "-" CARACTERS IN WINDOW HANDLE VARIABLE
	status =wid;
}

function integria_help(help_id) {
	open ("general/integria_help.php?id="+help_id, "integriahelp", "width=770,height=500,status=0,toolbar=0,menubar=0,scrollbars=1,location=0");
}

/**
 * Decode HTML entities into characters. Useful when receiving something from AJAX
 *
 * @param str String to convert
 *
 * @retval str with entities decoded
 */
function html_entity_decode (str) {
	if (! str)
		return "";
	var ta = document.createElement ("textarea");
	ta.innerHTML = str.replace (/</g, "&lt;").replace (/>/g,"&gt;");
	return ta.value;
}

/**
 * Refresh odd an even rows in a table.
 *
 * @param table_id If of the table to refresh.
 */
function refresh_table (table_id) {
	$("#" + table_id + " > tbody > tr:odd td").removeClass("datos").addClass("datos2");
	$("#" + table_id + " > tbody > tr:even td").removeClass("datos2").addClass("datos");
}

/**
 * Get all values of an form into an array.
 *
 * @param form Form to get the values. It can be an object or an HTML id
 *
 * @retval The input values of the form into an array.
 */
function get_form_input_values (form) {
	if (typeof form == "string") {
		return $("#" + form).formToArray ();
	} else {
		return $(form).formToArray ();
	}
}

/**
 * Show an error message in result div.
 * 
 * @param string message Message to show
 */
function result_msg_error (message) {
	$(".result").fadeOut ("fast", function () {
		$(this).empty ().append ($("<h3></h3>").addClass ("error").append (message)).fadeIn ();
	});
}

/**
 * Show an success message in result div.
 * 
 * @param string message Message to show
 */
function result_msg_success (message) {
	$(".result").fadeOut ("fast", function () {
		$(this).empty ().append ($("<h3></h3>").addClass ("suc").append (message)).fadeIn ();
	});
}

/**
 * Show an message in result div.
 * 
 * @param string message Message to show
 */
function result_msg (message) {
	$(".result").fadeOut ("fast", function () {
		$(this).empty ().append ($("<h3></h3>").append (message)).fadeIn ();
	});
}

/**
 * Pulsate an HTML element to get user attention.
 *
 * @param element HTML element to animate.
 */
function pulsate (element) {
	$(element).fadeIn ("normal", function () {
		$(this).fadeOut ("normal", function () {
			$(this).fadeIn ("normal", function () {
				$(this).fadeOut ("normal", function () {
					$(this).fadeIn ().focus ();
				});
			});
		});
	});
}


function load_form_values (form_id, values) {
	$("#"+form_id+" :input").each (function () {
		if (values[this.name] != undefined) {
			if (this.type == 'checkbox' && values[this.name])
				this.checked = "1";
			else
				this.value = values[this.name];
		}
	});
}

function __(str) {
	var r = lang[str];
	
	if (r == undefined)
		return str
	return r;
}

function cancel_msg(id) {
	$('#msg_'+id).fadeOut('slow');

}

/**
 * Exclude in a input the not alphanumeric characters
 *
 * @param id identifier of the input element to control
 * @param exceptions string with the exceptions to exclusion
 * @param lower bool true if the lowercase are allowed, false otherwise
 * @param upper bool true if the uppercase are allowed, false otherwise
 * @param numbers bool true if the numbers are allowed, false otherwise
 */
function inputControl(id,exceptions,lower,upper,numbers) {
	if(typeof(exceptions) == 'undefined') {
		exceptions = '_.';
	}
	if(typeof(lower) == 'undefined') {
		lower = true;
	}
	if(typeof(upper) == 'undefined') {
		upper = true;
	}
	if(typeof(numbers) == 'undefined') {
		numbers = true;
	}
	
	var regexpStr = '';
	
	if(lower) {
		regexpStr+= 'a-z';
	}
	if(upper) {
		regexpStr+= 'A-Z';
	}
	if(numbers) {
		regexpStr+= '0-9';
	}
	
	// Scape the special chars
	exceptions = exceptions.replace(/\./gi,"\\.");
	exceptions = exceptions.replace(/\-/gi,"\\-");
	exceptions = exceptions.replace(/\^/gi,"\\^");
	exceptions = exceptions.replace(/\$/gi,"\\$");
	exceptions = exceptions.replace(/\[/gi,"\\[");
	exceptions = exceptions.replace(/\]/gi,"\\]");
	exceptions = exceptions.replace(/\(/gi,"\\(");
	exceptions = exceptions.replace(/\)/gi,"\\)");
	exceptions = exceptions.replace(/\+/gi,"\\+");
	exceptions = exceptions.replace(/\*/gi,"\\*");
	
	regexpStr+= exceptions;
	$("#"+id).keyup(function() {
		var text = $(this).val();
		var regexp = new RegExp("[^"+regexpStr+"]","g");
		text = text.replace(regexp,'');
		$("#"+id).val(text);
	});
}

function toggleInventoryInfo(id_inventory) {
	display = $('.inventory_more_info_' + id_inventory).css('display');
	
	if (display != 'none') {
		$('.inventory_more_info_' + id_inventory).css('display', 'none');
	}
	else {
		$('.inventory_more_info_' + id_inventory).css('display', '');
	}
}

/**
 * Binds autocomplete behaviour to an input tag 
 * 
 * !!!jquery.ui.autocomplete.js must be loaded in the page before use this function!!!
 *
 * @param idTag String tag's id to bind autocomplete behavior
 * @param idUser String user's id
 * @param byProject Boolean flag to search by users in a project
 */
function bindAutocomplete (idTag, idUser, idProject, onChange, ticket, idGroup) {
	
	var ajaxUrl = "ajax.php?page=include/ajax/users&search_users=1&id_user="+idUser;
	
	if (ticket) {
		ajaxUrl = "ajax.php?page=include/ajax/users&search_users_ticket=1&id_user="+idUser+"&id_group="+idGroup;
	}
	
	if (idProject) {
		ajaxUrl = "ajax.php?page=include/ajax/users&search_users_role=1&id_user="+idUser+"&id_project="+idProject;
	}
	
	$(idTag).autocomplete ({
		source: ajaxUrl,
		minLength: 2,
		delay: 200,
		change: onChange
	});
}

/**
 * Binds autocomplete behaviour to an input tag 
 * 
 * !!!jquery.ui.autocomplete.js must be loaded in the page before use this function!!!
 *
 */
function bindCompanyAutocomplete (idTag, idUser, filterType) {

	var filter = $('#hidden-autocomplete_'+idTag+'_filter').val();
	if (filter) {
		filter = "&filter="+filter;
	} else {
		filter = "";
	}
	filter = filter+"&type="+filterType;
	var ajaxUrl = "ajax.php?page=include/ajax/companies&search_companies=1&id_user="+idUser;

	$('#'+idTag).autocomplete ({
		source: ajaxUrl + filter,
		minLength: 2,
		delay: 200,
		select: function(event, ui) {
			event.preventDefault();

			$('#hidden-'+idTag).val(ui.item.value);
			$('#'+idTag).val(ui.item.label);
		},
		change: function(event, ui) {
			$.ajax({
				type: "POST",
				url: "ajax.php",
				data: {
					page: "include/ajax/companies",
					get_company_id: 1,
					company_name: function() { return $('#'+idTag).val() },
					id_user: idUser,
					filter: function() { return $('#hidden-autocomplete_'+idTag+'_filter').val() }
				},
				dataType: "json",
				success: function(data) {
					if (data) {
						$('#hidden-'+idTag).val(data);
					} else {
						$('#'+idTag).val("");
						$('#hidden-'+idTag).val(0);
					}
				}
			});
		}
	});

	$('#'+idTag).keypress(function(e) {
		if ( e.which == 13 ) {
			e.preventDefault();
		}
	});
}

function beginReloadTimeout(seconds, form_id) {
	var reload;
	if (form_id) {
		reload = function () { document.getElementById(form_id).submit() };
	} else {
		reload = function () { window.location.reload() };
	}
	reloadTimeoutID = window.setTimeout(reload, seconds * 1000);
}

function clearReloadTimeout(token) {
	window.clearTimeout(reloadTimeoutID);
}

function setAutorefreshSeconds (token, seconds) {
	eraseCookie(token);
	createCookie(token, seconds, false);
}

function enableAutorefresh (id, token, form_id) {
	
	var button = $("#"+id);
	var seconds = readCookie(token);
	
	button.attr('reload_enabled', 1);
	//button.animate({ backgroundColor: "#238A1C" });
	button.html("Disable autorefresh");
	//~ $("#autorefresh_combo").show( "blind", { direction: "right" }, "slow" );
	$("#autorefresh_combo").css('visibility', 'visible');
	
	if (! seconds) {
		setAutorefreshSeconds(token, 60);
		beginReloadTimeout(60, form_id);
	} else {
		setAutorefreshSeconds(token, seconds);
		beginReloadTimeout(seconds, form_id);
	}
}

function disableAutorefresh (id, token) {
	
	var button = $("#"+id);
	
	button.attr('reload_enabled', 0);
	//button.animate({ backgroundColor: "#A82323"});
	button.html("Enable autorefresh");
	//~ $("#autorefresh_combo").hide( "blind", { direction: "left" }, "slow" );
	$("#autorefresh_combo").css('visibility', 'hidden');
	
	eraseCookie(token);
	clearReloadTimeout(token);
}

function toggleAutorefresh (id, token, form_id) {
	
	var button = $("#"+id);
	
	if (button.attr('reload_enabled') == 1) {
		disableAutorefresh(id, token);
	} else {
		enableAutorefresh(id, token, form_id);
	}
}

function changeAutorefreshTime (id, token, form_id) {
	
	var combo = $("#"+id);
	var seconds = combo.val();
	
	setAutorefreshSeconds(token, seconds);
	clearReloadTimeout(token);
	beginReloadTimeout(seconds, form_id);
	
}

function createCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    } else var expires = "";
    document.cookie = escape(name) + "=" + escape(value) + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = escape(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return unescape(c.substring(nameEQ.length, c.length));
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}

// Show the modal window of license info
function show_license_info(expiry_day, expiry_month, expiry_year, max_manager_users, max_regular_users) {

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/license&get_license_info=1&expiry_day="+expiry_day+"&expiry_month="+expiry_month+"&expiry_year="+expiry_year+"&max_manager_users="+max_manager_users+"&max_regular_users="+max_regular_users,
		dataType: "html",
		success: function(data){	
			$("#dialog_show_license").html (data);
			$("#dialog_show_license").show ();

			$("#dialog_show_license").dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 500,
					height: 350
				});
			$("#dialog_show_license").dialog('open');
			
		}
	});
}

function toggleUsersInfo(id_group) {

	display = $('.group_more_info_' + id_group).css('display');
	
	if (display != 'none') {
		$('.group_more_info_' + id_group).css('display', 'none');
	}
	else {
		$('.group_more_info_' + id_group).css('display', '');
	}
}

// Helper function that formats the file sizes
function formatFileSize(bytes) {
	if (typeof bytes !== 'number') {
		return '';
	}

	if (bytes >= 1000000000) {
		return (bytes / 1000000000).toFixed(2) + ' GB';
	}

	if (bytes >= 1000000) {
		return (bytes / 1000000).toFixed(2) + ' MB';
	}

	return (bytes / 1000).toFixed(2) + ' KB';
}

$(document).ready (function () {
	

	if ($('#license_error_msg_dialog').length) {

		$( "#license_error_msg_dialog" ).dialog({
					resizable: true,
					draggable: true,
					modal: true,
					height: 290,
					width: 800,
					overlay: {
								opacity: 0.5,
								background: "black"
							}
		});
		
		$("#submit-hide-license-error-msg").click (function () {
			$("#license_error_msg_dialog" ).dialog('close')
		});
	
	}
	
});

$(document).ready(function() {
	// Containers open/close logic
	$('tr.clickable').click(function() {
		var arrow = $('#' + $(this).attr('id') + ' th.img_arrow' + ' img').attr('src');
		var arrow_class = $('#' + $(this).attr('id') + ' img').attr('class');
		var new_arrow = '';

		if($('#' + $(this).attr('id') + '_div').css('display') == 'none') {
			new_arrow = arrow.replace(/_down/gi, "_right");
			$('#' + $(this).attr('id')+ ' th.img_arrow' + ' img').attr('class', 'arrow_right');
		}
		else {
			new_arrow = arrow.replace(/_right/gi, "_down");
			$('#' + $(this).attr('id')+ ' th.img_arrow' + ' img').attr('class', 'arrow_down');
		}
		
		$('#' + $(this).attr('id')+ ' th.img_arrow' + ' img').attr('src', new_arrow);
	});
});

function openInventoryMoreInfo (id_inventory) {
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&id_inventory=" + id_inventory + "&printTableMoreInfo=1",
		success: function(data){
			$("#info_inventory_window").html (data);
			$("#info_inventory_window").show ();

			$("#info_inventory_window").dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					title: "Extended info",
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 620,
					height: 300
				});
			$("#info_inventory_window").dialog('open');
		}
	});
}

function isValidImg (url, callback) {
	var img = new Image();
	img.onerror = function() {
		callback(url, false);
	}
	img.onload = function() {
		callback(url, img);
	}
	img.src = url;
}

function parseURLSearch (searchStr) {
	var search = {};
	
	if (searchStr.length > 0) {
		// Remove the ? character
		searchStr = searchStr.substring(1);
		
		var searches = searchStr.split('&');
		
		for (var i = 0; i < searches.length; i++) {
			var aux = searches[i].split('=');
			var key = aux.shift();
			var value = aux.shift();
			
			if (typeof key !== 'undefined') {
				if (typeof value !== 'undefined') {
					if (typeof search[key] !== 'undefined') {
						if (search[key] instanceof Array) {
							if (value.length > 0)
								search[key].push(value);
						}
						else {
							var lastVal = search[key];
							search[key] = [];
							if (lastVal.length > 0)
								search[key].push(lastVal);
							if (value.length > 0)
								search[key].push(value);
							if (search[key].length <= 0)
								search[key] = '';
						}
					}
					else {
						search[key] = value;
					}
				}
				else if (!(search[key] instanceof Array)) {
					search[key] = '';
				}
			}
		}
	}
	
	return search;
}

function parseURL (URL) {
	// parser.href		=> "http://foo.bar:8080/pathname/?search=test#hash"
	// parser.protocol	=> "http:"
	// parser.hostname	=> "foo.bar"
	// parser.port		=> "8080"
	// parser.pathname	=> "/pathname/"
	// parser.search	=> "?search=test"
	// parser.hash		=> "#hash"
	// parser.host		=> "foo.bar:8080"
	var parser = document.createElement('a');
	parser.href = URL;
	
	return {
		href: parser.href,
		protocol: parser.protocol,
		hostname: parser.hostname,
		port: parser.port,
		pathname: parser.pathname,
		search: parseURLSearch(parser.search),
		hash: parser.hash,
		host: parser.host
	}
}

function show_validation_delete_general (name, id, id2, offset, search_params) {
	//console.log('1 -> '+name);
	//console.log('2 -> '+id);
	//console.log('3 -> '+id2);
	//console.log('4 -> '+offset);
	//console.log('5 -> '+search_params);
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/delete_item_general&get_delete_validation=1",
		dataType: "html",
		success: function(data){
			$("#item_delete_window").html (data);
			$("#item_delete_window").show ();
			$("#item_delete_window").dialog ({
					resizable: false,
					draggable: false,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 440,
					height: 195
				});
			$("#item_delete_window").dialog('open');
			$("#validation_delete_form").submit(function (e){
				e.preventDefault();
				delete_item_general (name, id, id2, offset, search_params);
			});
			$("#button-modal_cancel").click(function (e){
				e.preventDefault();
				$("#item_delete_window").dialog('close');
			});
			$('.ui-widget-overlay').click(function(e){
				e.preventDefault();
				$("#item_delete_window").dialog('close');
			});

		}
	});
}

function delete_item_general (name, id, id2, offset, search_params) {
	console.log(id);
	console.log(id2);
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/delete_item_general&delete_item=1&name="+ name+"&id="+id,
		dataType: "html",
		async:false,
		success: function (data) {
			console.log(name);
			switch (name) {
				case 'delete_project':
					window.location.assign("index.php?sec=projects&sec2=operation/projects/project&view_disabled=1&delete_project=1&id="+id+"&offset="+offset+"&search_params="+search_params);
				break;
				case 'delete_role_user_global':
					window.location.assign("index.php?sec=projects&sec2=operation/projects/role_user_global&id_user="+id2+"&delete="+id);
				break;
				case 'delete_task_panning':
					window.location.assign("index.php?sec=projects&sec2=operation/projects/task_planning&id_project="+id+"&delete="+id2);
				break;
				case 'delete_milestones':
					window.location.assign("index.php?sec=projects&sec2=operation/projects/milestones&id_project="+id+"&operation=delete&id="+id2);
				break;
				case 'delete_people_manager':
					window.location.assign("index.php?sec=projects&sec2=operation/projects/people_manager&action=delete&id="+id+search_params);
				break;
				case 'delete_template':
					window.location.assign("index.php?sec=godmode&sec2=godmode/setup/setup_mailtemplates&search=1&delete_template=1&id_template="+id+search_params);
				break;
				case 'delete_people_task_human':
					window.location.assign("index.php?sec=projects&sec2=operation/projects/people_manager&action=delete&id_project="+id+"&id_task"+id2+"&id="+offset);
				break;
				case 'delete_project_group_detail':
					window.location.assign("index.php?sec=projects&sec2=operation/projects/project_group_detail&delete_group=1&id="+id);
				break;
				case 'delete_task_wu':
					window.location.assign("index.php?sec=users&sec2=operation/users/user_spare_workunit&operation=delete&id_workunit="+id);
				break;
				

				case 'delete_company':
					window.location.assign("index.php?sec=customers&sec2=operation/companies/company_detail&id=0&offset="+offset+"&search_params="+search_params+"message="+data);
				break;
				case 'delete_contract':
					window.location.assign("index.php?sec=customers&sec2=operation/contracts/contract_detail&"+search_params+"&message="+data);
				break;
				case 'delete_invoice':
					window.location.assign("index.php?sec=customers&sec2=operation/invoices/invoice_detail&offset="+offset+"&search_params="+search_params+"&message="+data);
				break;
				case 'delete_company_invoice':
					window.location.assign("index.php?sec=customers&sec2=operation/companies/company_detail&id="+id_company+"&op=invoices&offset="+offset+"&message="+data);
				break;
				case 'delete_lead':
					window.location.assign("index.php?sec=customers&sec2=operation/leads/lead&tab=search&offset="+offset+"&message="+data);
				break;
			}
		}
	});
}