function add_datepicker (element, startDate) {
	$(document).ready (function () {
		$(element).datepicker ({ dateFormat: 'yy-mm-dd', minDate: startDate });
	});
}

function add_ranged_datepicker (element_start, element_end, range_callback) {
	$(document).ready (function () {
		$(element_start).datepicker ({
			beforeShow: function () {
				maxdate = null;
				if ($(element_end).datepicker ("getDate") > $(this).datepicker ("getDate")) {
					maxdate = $(element_end).datepicker ("getDate");
				}
				return {
					maxDate: maxdate
				};
			},
			onSelect: function (datetext) {
				end = $(element_end).datepicker ("getDate");
				start = $(this).datepicker ("getDate");
				if (end <= start) {
					pulsate ($(element_end));
				}
			},
			dateFormat: 'yy-mm-dd'
		});
		$(element_end).datepicker ({
			beforeShow: function () {
				return {
					minDate: $(element_start).datepicker ("getDate")
				};
			},
			onSelect: range_callback,
			dateFormat: 'yy-mm-dd'
		});
	});
}

function add_task_planning_datepicker () {
	$(document).ready (function () {
		$('input[name^="start"]').datepicker({
			beforeShow: function () {
				maxdate = null;
				name = $(this).attr('name');
				id = name.substr(6);
			
				if ($('input[name^="end_'+id+'"]').datepicker ("getDate") > $(this).datepicker ("getDate"))
					maxdate = $('input[name^="end_'+id+'"]').datepicker ("getDate");
				return {
					maxDate: maxdate
				};
			},
			onSelect: function (datetext) {
				name = $(this).attr('name');
				id = name.substr(6);
				end = $('input[name~="end_'+id+'"]').datepicker ("getDate");
				start = $(this).datepicker ("getDate");
				if (end <= start) {
					pulsate ($('input[name~="end_'+id+'"]'));
				}
			},
			dateFormat: 'yy-mm-dd'
		});
		
		$('input[name^="end"]').datepicker ({
			
			beforeShow: function () {
				name = $(this).attr('name');
				id = name.substr(4);
				
				return {
					minDate: $('input[name~="start_'+id+'"]').datepicker ("getDate")
				};
			},
			onSelect: function() {
				name = $(this).attr('name');
				id = name.substr(4);
				end = $(this).datepicker ("getDate");
				start = $('input[name~="start_'+id+'"]').datepicker ("getDate");
			},
			dateFormat: 'yy-mm-dd'
		});
	});
}
