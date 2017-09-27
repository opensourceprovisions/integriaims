function process_massive_operation (operation) {
	var checked_ids = new Array();
	var id;
	var checked;
	
	$(".user_checkbox").each(function () {
		id = $(this).val();
		checked = $(this).attr('checked');
		if(checked) {
			$(this).attr('checked', false);
			checked_ids.push(id);
		}
	});

	if(checked_ids.length == 0) {
		alert(__("No items selected"));
	} else {
		values = Array ();
		values.push ({
				name: "page",
				value: "include/ajax/users"
			});
		values.push ({
				name: operation,
				value: true
			});
		values.push ({
				name: "ids",
				value: checked_ids
			});
		
		jQuery.get ("ajax.php", values, function (data, status) {
				//alert(data + " " + __("rows affected"));
				if (data !== false) {
					var msg = "Successfully deleted"
				}
				else {
					var msg = "Error in delete user";
				}
				location.reload();
				alerta(msg);
			}, "json"
		);
	}
	function alerta (msg) {
		alert(msg);
	}
}
