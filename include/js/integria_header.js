
// Show the modal window of alerts
function openAlerts() {
	update_manager_msg = $("#hidden-result_check_update_manager").val();
	if (update_manager_msg == undefined) {
		update_manager_msg = '';
	}

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/header&get_alerts=1&update_manager_msg="+update_manager_msg,
		dataType: "html",
		success: function(data){	
			
			$("#alert_window").html (data);
			$("#alert_window").show ();

			$("#alert_window").dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 620
					//height: 400
				});
			$("#alert_window").dialog('open');
			
		}
	});
}

function closeAlertDialog() {
	$("#alert_window").dialog('close');
}




