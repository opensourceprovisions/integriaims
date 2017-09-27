// Show the modal window of an agenda entry
function show_agenda_entry(id_entry, selected_date, min_date, refresh) {
	
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: {
			page: "operation/agenda/entry",
			show_agenda_entry: 1,
			id: id_entry,
			date: selected_date
		},
		dataType: "html",
		success: function(data) {	
			var $agendaEntry = $("#agenda_entry");
			
			$agendaEntry.html(data);
			
			// -- Privacy controls -- //
			if ($agendaEntry.find('select#groups').length > 0) {
				var $publicCheckbox = $agendaEntry.find('input#checkbox-entry_public');
				var $groupsSelect = $agendaEntry.find('select#groups');
				var $groupsSelectLabel = $('label[for="groups"]');
				
				$groupsSelect.change(function (event) {
					var values = $(this).val();
					
					// Select or unselect the value '0' when the user clicks on 'None'
					if (values.indexOf('0') !== -1) {
						$(this).children('option:selected').prop('selected', false);
						$(this).children('option[value="0"]').prop('selected', true);
					}
					else {
						$(this).children('option[value="0"]').prop('selected', false);
					}
				});
				
				// Bind and trigger change
				$publicCheckbox.change(function (event) {
					var isChecked = this.checked;
					// Hide the groups select when public is checked
					if (isChecked) {
						$groupsSelect
							.css('visibility', 'hidden')
							.prop('disabled', true);
						$groupsSelectLabel.css('visibility', 'hidden');
					}
					else {
						$groupsSelect
							.prop('disabled', false)
							.css('visibility', 'visible');
						$groupsSelectLabel.css('visibility', 'visible');
					}
				}).change();
			}
			// -- End privacy controls -- //
			
			add_datepicker ("#text-entry_date", min_date);
			
			$agendaEntry.dialog({
				title: "Agenda",
				resizable: true,
				draggable: true,
				modal: true,
				overlay: {
					opacity: 0.5,
					background: "black"
				},
				width: 600
			});
			
			$agendaEntry.dialog('open');
			
			$("#button-cancel").click(function(e) {
				$agendaEntry.dialog('close');
			});
			
			$("#button-delete").click(function(e) {
				$.ajax({
					type: "POST",
					url: "ajax.php",
					data: {
						page: "operation/agenda/entry",
						delete_agenda_entry: 1,
						id: id_entry
					},
					dataType: "html",
					success: function(data) {
						$agendaEntry.html(data);
						
						$agendaEntry.on("dialogclose", function(event, ui) {
							if (refresh == true) {
								location.reload();
							}
						});
						$("#button-OK").click(function(e) {
							$agendaEntry.dialog('close');
							if (refresh == true) {
								location.reload();
							}
						});
					}
				});
				return false;
			});
			
			$("#calendar_entry").submit(function() {
				$.ajax({
					type: "POST",
					url: "ajax.php",
					data: {
						page: "operation/agenda/entry",
						update_agenda_entry: 1,
						id: id_entry,
						title: $("#text-entry_title").val(),
						duration: $("#text-entry_duration").val(),
						alarm: $("#entry_alarm").val(),
						public: function () {
							if ($("#checkbox-entry_public").is(":checked"))
								return 1;
							else
								return 0;
						},
						date: $("#text-entry_date").val(),
						time: $("#text-entry_time").val(),
						description: $("#textarea-entry_description").val(),
						groups: $('select#groups').val()
					},
					dataType: "html",
					success: function(data) {
						$agendaEntry.html(data);
						
						$agendaEntry.on("dialogclose", function(event, ui) {
							if (refresh == true) {
								location.reload();
							}
						});
						$("#button-OK").click(function(e) {
							$agendaEntry.dialog('close');
							if (refresh == true) {
								location.reload();
							}
						});
					}
				});
				return false;
			});
			
		}
	});
}
