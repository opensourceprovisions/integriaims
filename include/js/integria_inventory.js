
// Show the modal window of inventory search
function show_inventory_search(search_free, id_object_type_search, owner_search, id_manufacturer_search, id_contract_search, search, object_fields_search, last_update_search, offset, inventory_status_search, id_company_search,associated_user_search) {

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&get_inventory_search=1&search_free="+search_free+"&id_object_type_search="+id_object_type_search+"&owner_search="+owner_search+"&id_manufacturer_search="+id_manufacturer_search+"&id_contract_search="+id_contract_search+"&object_fields_search="+object_fields_search+"&search=1&offset="+offset+"&last_update_search="+last_update_search+"&inventory_status_search="+inventory_status_search+"&id_company="+id_company_search+"&associated_user_search="+associated_user_search,
		dataType: "html",
		success: function(data){
			
			$("#inventory_search_window").html (data);
			$("#inventory_search_window").show ();
			
			$("#inventory_search_window").dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 920,
					height: 600
				});
			$("#inventory_search_window").dialog('open');
			
			$("a[class^='modal_page']").click(function(e) {

				e.preventDefault();

				var id = $(this).attr("class");
								
				offset = id.substr(11,id.length);

				show_inventory_search(search_free, id_object_type_search, owner_search, id_manufacturer_search, id_contract_search, search, object_fields_search, last_update_search, offset, inventory_status_search, id_company_search, associated_user_search);
			});
			
			var idUser = "<?php echo $config['id_user'] ?>";

			bindAutocomplete ("#text-owner_search", idUser);
			bindAutocomplete ("#text-associated_user_search", idUser);

		}
	});
}

// Show the modal window of external table
function show_external_query(table_name, id_table, element_name, id_object_type_field, label_parent_enco, id_parent_table, external_label) {

	if (label_parent_enco != "") {
		id_parent_value = $("input[name='"+label_parent_enco+"']").val();
		id_parent = $("input[name='"+label_parent_enco+"']").attr("id");
	} else {
		id_parent_value = 0;
		id_parent = 0;
	}
			
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&get_external_data=1&table_name="+table_name+"&id_table="+id_table+"&element_name="+element_name+"&id_object_type_field="+id_object_type_field+"&id_parent_value="+id_parent_value+"&id_parent_table="+id_parent_table+"&external_label="+external_label+"&id_parent="+id_parent,
		dataType: "html",
		success: function(data){	
			$("#external_table_window").html (data);
			$("#external_table_window").show ();

			$("#external_table_window").dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					position: "center top",
					width: 620,
					height: 500
				});
			$("#external_table_window").dialog('open');
		}
	});
}

function refresh_external_id(id_object_type_field, id_inventory, id_value, data_name) {

	if (id_inventory != 0) {
		$.ajax({
			type: "POST",
			url: "ajax.php",
			data: "page=operation/inventories/inventory_detail&update_external_id=1&id_object_type_field=" + id_object_type_field +"&id_inventory=" + id_inventory+ "&id_value="+data_name,
			dataType: "json",
			success: function(data){
				//~ show_fields();
				$("#"+id_object_type_field).val(data_name);
							
				jQuery.each (data, function (id, value) {
					$("#"+value['id']).attr("value", "");
				});
			}
		});
	} else {
		$("#"+id_object_type_field).val(data_name);
		
		$.ajax({
			type: "POST",
			url: "ajax.php",
			data: "page=operation/inventories/inventory_detail&get_external_child=1&id_object_type_field=" + id_object_type_field, 
			dataType: "json",
			success: function(data){
				jQuery.each (data, function (id, value) {
					$("#"+value['id']).attr("value", "");
				});
			}
		});	
	}

}

function enviar(data, element_name, id_object_type_field, data_name) {
	$('#'+element_name).val(data);
	
	id_inventory = $('#text-id_object_hidden').val();
	
	refresh_external_id(id_object_type_field, id_inventory, data, data_name);
	
	$("#external_table_window").dialog('close');
} 

//function to pass the clicked parameter in the modal to the input
function loadInventory(id_inventory) {
	
	$('#hidden-id_parent').val(id_inventory);
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&get_inventory_name=1&id_inventory="+ id_inventory,
		dataType: "text",
		success: function (name) {
			$('#text-parent_name').val(name);
		}
	});	

	$("#inventory_search_window").dialog('close');
}

function show_fields() {

	only_read = 0;
	
	if ($("#text-show_object_hidden").val() == 1) { //users with only read permissions
		only_read = 1;
		id_object_type = $("#text-id_object_type_hidden").val();
	} else { //users with write permissions
		id_object_type = $("#id_object_type").val();
	}

	id_inventory = $("#text-id_object_hidden").val();

	$('#table_fields').remove();

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=operation/inventories/inventory_detail&show_type_fields=1&id_object_type=" + id_object_type +"&id_inventory=" +id_inventory,
		dataType: "json",
		success: function(data){
			
			fi=document.getElementById('table1-4-0');
			var table = document.createElement("table"); //create table
			table.id='table_fields';
			table.className = 'databox_color_without_line';
			table.width='98%';
			fi.appendChild(table); //append table to row
			
			var i = 0;
			var resto = 0;
			jQuery.each (data, function (id, value) {
				
				resto = i % 2;

				if (value['type'] == "combo") {
					if (resto == 0) {
						var objTr = document.createElement("tr"); //create row
						objTr.id = 'new_row_'+i;
						objTr.width='98%';
						table.appendChild(objTr);
					} else {
						pos = i-1;
						objTr = document.getElementById('new_row_'+pos);
					}
					
					var objTd1 = document.createElement("td"); //create column for label
					objTd1.width='50%';
					lbl = document.createElement('label');
					lbl.innerHTML = value['label']+' ';
					
					objTr.appendChild(objTd1);
					objTd1.appendChild(lbl);
					
					txt = document.createElement('br');
					lbl.appendChild(txt);
					
					element=document.createElement('select');
					element.id=value['label']; 
					element.name=value['label_enco'];
					element.value=value['label'];
					element.style.width="170px";
					element.class="type";
					
					if (only_read) {
						element.disabled = true;
					}
					
					var new_text = value['combo_value'].split(',');
					jQuery.each (new_text, function (id, val) {
						element.options[id] = new Option(val);
						element.options[id].setAttribute("value",val);
						if (value['data'] == val) {
							element.options[id].setAttribute("selected",'');
						}
					});
			
					lbl.appendChild(element);
					i++;
				}
				
				if ((value['type'] == "text") || (value['type'] == "numeric") || (value['type'] == "external") || (value['type'] == "date")) {
				
					if (resto == 0) {
						var objTr = document.createElement("tr"); //create row
						objTr.id = 'new_row_'+i;
						objTr.width='98%';
						table.appendChild(objTr);
					} else {
						pos = i-1;
						objTr = document.getElementById('new_row_'+pos);
					}
					
					var objTd1 = document.createElement("td"); //create column for label
					objTd1.width='50%';
					lbl = document.createElement('label');
					lbl.innerHTML = value['label']+' ';
					objTr.appendChild(objTd1);
					objTd1.appendChild(lbl);
					
					txt = document.createElement('br');
					lbl.appendChild(txt);

					
					element=document.createElement('input');
					element.id=value['id'];
					element.className="object_type_field";
					//element.id=i;
					//element.id=value['label_enco'];
					element.name=value['label_enco'];
					
					element.value=value['data'];
					if ((value['type'] == 'text') || (value['type'] == 'external')) {
						element.type='text';

					} else if (value['type'] == 'numeric') {
						element.type='number';
					} else if (value['type'] == 'date') {
						element.type='date';
					}
					
					if (only_read) {
						element.disabled = true;
					}
					
					element.size=40;
					lbl.appendChild(element);
				
					if (value['type'] == 'external') {
						
						element.readOnly = true;
						id_object_type_field = value['id'];
						
						a = document.createElement('a');
						a.title = __("Show table");
						a2 = document.createElement('a');
						a2.title = __("Delete");
						table_name = value['external_table_name'];
						id_table = value['external_reference_field'];
						parent_name_enco = value['label_parent_enco'];
						external_label = value['external_label'];
						if (value['id_parent_table'] == false) {
							id_parent_table = "";
						} else {
							id_parent_table = value['id_parent_table'];
						}
					
						//~ a.href = 'javascript: show_external_query("'+table_name+'","'+id_table+'","'+i+'", "'+id_object_type_field+'",'+'"'+parent_name_enco+'",'+'"'+id_parent_table+'")';
						a.href = 'javascript: show_external_query("'+table_name+'","'+id_table+'","'+i+'", "'+id_object_type_field+'",'+'"'+parent_name_enco+'",'+'"'+id_parent_table+'","'+external_label+'")';
						//~ a2.href = 'javascript: removeExternal(id="'+element.id+'")';
						a2.href = 'javascript: removeExternal('+element.id+')';
						
						img=document.createElement('img');
						img.id='img_show_external_table';
						img.height='16';
						img.width='16';
						img.src='images/lupa.gif';
						
						img2=document.createElement('img');
						img2.id='img_delete_external_table';
						img2.src='images/cross.png';
						
						a.appendChild(img);
						lbl.appendChild(a);
						
						a2.appendChild(img2);
						lbl.appendChild(a2);
						
						id_inventory = $('#text-id_object_hidden').val(value['data']);
					}
					
					i++;
					/*
					if (value['type'] == 'external') {
						if (value['data'] != '') {
							
							external_table_name = value['external_table_name'];
							external_reference_field = value['external_reference_field'];
							id_external_table = value['data'];
							
							$.ajax({
								type: "POST",
								url: "ajax.php",
								data: "page=operation/inventories/inventory_detail&show_external_data=1&external_table_name=" + external_table_name +"&external_reference_field=" + external_reference_field +'&id_external_table='+id_external_table, 
								dataType: "json",
								success: function(data_external){
									resto_ext = 0;
									
									jQuery.each (data_external, function (id_ext, value_ext) {
										resto_ext = i % 2;
										
										if (resto_ext == 0) {
											var objTr = document.createElement("tr"); //create row
											objTr.id = 'new_row_'+i;
											objTr.width='98%';
											table.appendChild(objTr);
										} else {
											pos = i-1;
											objTr = document.getElementById('new_row_'+pos);
										}
										
										var objTd1 = document.createElement("td"); //create column for label
										objTd1.width='50%';
										lbl = document.createElement('label');
										lbl.innerHTML = value_ext['label']+' ';
										objTr.appendChild(objTd1);
										objTd1.appendChild(lbl);
										
										txt = document.createElement('br');
										lbl.appendChild(txt);

										
										element=document.createElement('input');
										element.id=value_ext['label'];
										element.name=value_ext['label_enco'];
										element.value=value_ext['data'];
										element.type='text';
										element.readOnly=true
										
										element.size=40;
										lbl.appendChild(element);
										i++;
									});
								}
							});
						}
					}
					*/	
				}
				
				if ((value['type'] == "textarea")) {
					
					if (resto == 0) {
						var objTr = document.createElement("tr"); //create row
						objTr.id = 'new_row_'+i;
						table.appendChild(objTr);
					} else {
						pos = i-1;
						objTr = document.getElementById('new_row_'+pos);
					}
					
					var objTd1 = document.createElement("td"); //create column for label
					
					lbl = document.createElement('label');
					lbl.innerHTML = value['label']+' ';
					objTr.appendChild(objTd1);
					objTd1.appendChild(lbl);
					
					element=document.createElement("textarea");
					element.id=value['label'];
					element.name=value['label_enco'];
					element.value=value['data'];
					element.type='text';
					element.rows='3';
					
					if (only_read) {
						element.disabled = true;
					}
					
					lbl.appendChild(element);
					i++;
				}
			});
		add_datepicker ("input[type=date]");
		}
	});
}

//Send modal parameters that need inventory
function loadParams() {
	
	search_free = $('#text-search_free_modal').val();
	id_object_type_search = $('#id_object_type_search_modal').val();
	owner_search = $('#text-owner_search').val();
	id_manufacturer_search = $('#id_manufacturer_search_modal').val();
	id_contract_search = $('#id_contract_search_modal').val();
	inventory_status_search = $('#inventory_status_search_modal').val();
	id_company_search = $('#id_company_modal').val();
	associated_user_search = $('#text-associated_user_search').val();

	if ($("#checkbox-last_update_search_modal").is(":checked")) {
		last_update_search = 1;
	} else {
		last_update_search = 0;
	}
	
	offset = 0;
	search = 1;
	
	object_fields_search = $("select[name='object_fields_search[]']").val();
	
	show_inventory_search(search_free, id_object_type_search, owner_search, id_manufacturer_search, id_contract_search, search, object_fields_search, last_update_search, offset, inventory_status_search, id_company_search, associated_user_search);

}

//Show custom fields for inventory modal
function show_type_fields() {

	$("select[name='object_fields_search[]']").empty();
	
	id_object_type = $("#id_object_type_search_modal").val();

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&select_fields=1&id_object_type=" + id_object_type,
		dataType: "json",
		success: function(data){
				$("#object_fields_search").empty();
				jQuery.each (data, function (id, value) {
					field = value;
					$("select[name='object_fields_search[]']").append($("<option>").val(id).html(field));
				});	
			}
	});
}

//function to sort x field
function enable_table_ajax_headers(pure){
	$('#inventory_list th').each(function (column) {
		type_column = $('#hidden-sort_mode').val();
		num_column = $('#hidden-sort_field').val();
		field_value = this.className.split(" ")[1].replace('c','');
		if (field_value == 0 || field_value == 1 || field_value == 8 || field_value == 7) {
			if(!pure){
				if (field_value == num_column && type_column == 'asc'){
					$(this).addClass('sortable sorted-asc');
					$(this).attr("onclick","ajax_headers_sort(this);");

				} else if (field_value == num_column && type_column == 'desc'){
					$(this).addClass('sortable sorted-desc');
					$(this).attr("onclick","ajax_headers_sort(this);");
				} else {
					$(this).addClass('sortable');
					$(this).attr("onclick","ajax_headers_sort(this);");
				}
			}
		}
    });
}

//changes the values ​​of the inputs to the ordering is maintained for the search
function ajax_headers_sort(field){
    $(field).removeClass('sortable');
    var field_value = field.className.split(" ")[1].replace('c','');
    if ($('#hidden-sort_mode').val() == 'asc'){
    	$('#hidden-sort_mode').val('desc');
    }
    else {
    	$('#hidden-sort_mode').val('asc');
    }
    $('#hidden-sort_field').val(field_value);
    tree_search_submit();
}

//changes to tree view
function change_view_tree(){
	$('#hidden-mode').val('tree');
	
	tree_search_submit();
}

//switch to list view
function change_view_list(){
	$('#hidden-mode').val('list');
	tree_search_submit();
}

//Pure view changes	
function change_view_pure() {
	$('#header').hide();
	$('#sidebar').hide();
	$('.inventory_type_object_container').hide();
	$('.inventory_column_container').hide();
	$('.inventory_form_container').hide();
	$('.view_normal_button').css('display', 'none');
	$('.view_pure_button').css('display', 'inline');
	pure = 1;
	tree_search_submit(pure);
}

//Return to pure a list_view
function change_return_view() {
	$('#sidebar').show();
	$('#header').show();
	$('.inventory_type_object_container').show();
	$('.inventory_column_container').show();
	$('.inventory_form_container').show();
	$('.view_normal_button').css('display', 'inline');
	$('.view_pure_button').css('display', 'none');
	pure = 0;
	tree_search_submit(pure);
}

//function to change the object type
function change_object_type(){
	$('#hidden-offset').val(0);
	pr = $(".checkbox_object_field").removeAttr('checked');
	inventory_form();
	tree_search_submit();
}

// function change checkbox
function inventory_form(){
	id_object_type = $("#id_object_type_search").val();
	$.ajax({	
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&form_inventory=1&id_object_type=" + id_object_type,
		dataType: "html",
		success: function(data){
			$("#pr").html(data);
		}
	});
}

//function select all chackbox object fields
function select_all_object_field(){
	 $(".checkbox_object_field").attr('checked', true);
}

//validate form block size
function checkForm() {
	var block_size = $('#text-block_size').val();
	if(block_size < 2 || block_size > 1000 || block_size == "" || isNaN(block_size)){		
		return false;
	} 
	return true;
}

//form search inventory
function tree_search_submit(pure){
	var validate = checkForm();
	if(validate){
		if(pure==1){
			pure = 1;
		} else {
			pure = 0;
		}
		
		var id_object = $('#tree_search').serialize();

		$("#inventory_list_table").html("<img id='inventory_loading' src='images/carga.gif' />");
		$("#inventory_tree_table").html("<img id='inventory_loading' src='images/carga.gif' />");
		$.ajax({	
			type: "POST",
			url: "ajax.php",
			data: "page=include/ajax/inventories&pure="+ pure +"&change_table=1&" + id_object,
			dataType: "html",
			success: function(data){
				mode = $('#hidden-mode').val();
				if(mode = 'list'){
					$("#inventory_list_table").html(data);
					if(pure == 1){
						$('.inventory_type_object_container').hide();
						$('.inventory_column_container').hide();
						$('.inventory_form_container').hide();
					}
					//sort the table
					enable_table_ajax_headers(pure);

					//JS for massive operations
					$("#checkbox-inventorycb-all").change(function() {
						$(".cb_inventory").prop('checked', $("#checkbox-inventorycb-all").prop('checked'));
					});

					$(".cb_inventory").click(function(event) {
						event.stopPropagation();
					});

					//outocomplete name owner and associated user
					var idUser = "<?php echo $config['id_user']; ?>";
					bindAutocomplete ("#text-owner", idUser);
					bindAutocomplete ("#text-associated_user", idUser);

					// Form validation
					trim_element_on_submit('#text-search_free');
					if ($("#tree_search").length > 0) {
						validate_user ("#tree_search", "#text-owner", "<?php echo __('Invalid user')?>");
					}

				} else {
					$('#inventory_tree_table').show();
				}
			}
		});
	}
}

//function delete one elements from inventory
function delete_object_inventory (id_inventory) {	
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&quick_delete=1&id_inventory=" + id_inventory,
		dataType: "html",
	    success: function(data){
        	tree_search_submit();
        	$("#tmp_data").html(data);
    		$("#tmp_data").css('margin-bottom', '30px');
    		$("#tmp_data").show('fast').delay(3000).hide('fast');
    	}
	});
}

//add diferents company
function show_company_associated (filter) {	
$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&get_company_associated=1&filter=" + filter,
		dataType: "html",
		success: function(data){	
			$("#company_search_modal").html (data);
			$("#company_search_modal").show ();
			$("#company_search_modal").dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 535,
					height: 350
				});
			$("#company_search_modal").dialog('open');
			
			$("#company_search_modal").ready (function () {
				$('.pass').click(function() { return !$('#origin option:selected').remove().appendTo('#destiny').attr('selected', 'selected'); });  
				$('.remove').click(function() { return !$('#destiny option:selected').remove().appendTo('#origin').attr('selected', false);});
				$('.remove').click(function() { return $('#destiny option').attr('selected', 'selected');});
				$('.passall').click(function() { $('#origin option').each(function() { $(this).remove().appendTo('#destiny').attr('selected', 'selected'); }); });
				$('.removeall').click(function() { $('#destiny option').each(function() { $(this).remove().appendTo('#origin'); }); });
				$('.submit').click(function() { $('#destiny').attr('selected', 'selected'); });	
			});
		}
	});	
}

//copy diferents company in field
function load_company_groups() {
	selText = {};
	selId = '';
	$i=0;
	$("#destiny option:selected").each(function () {
	   	var $this = $(this);
	   	if ($this.length) {
	   		selId = selId + $this.val() + ', ';
	    	selText[$this.val()] = $this.text();
	   		$i++;
	   	}
	});
	$("#inventory_companies option").remove();
	if(!selId){
		$('#inventory_companies').append($('<option>', {
	    	value: 0,
	    	text: 'None'
		}))
	} else {
		$.each(selText, function(key, value){
			$('#inventory_companies').append($('<option>', {
	    		value: key,
	    		text: value
			}))
		});
	}
	$("#hidden-companies").val(selId);
	$("#company_search_modal").dialog("close");
}

//remove text diferents company field
function clean_company_groups () {
	var selId='';
	//option selected
	s= $("#inventory_companies").prop ("selectedIndex");
	//value option selected
	selected_id = $("#inventory_companies").children (":eq("+s+")").attr ("value");
	//delete option selected
	$("#inventory_companies").children (":eq("+s+")").remove ();
	//chnge ids for option
	$("#inventory_companies option").each(function () {
	   	var $this = $(this);
	   	if ($this.length) {
	   		selId = selId + $this.val() + ', ';
	   	}
	});	
	//if there is not option
	if(!selId){ 
		$('#inventory_companies').append($('<option>', {
	    	value: 0,
	    	text: 'None'
		}));
	}
	$("#hidden-companies").attr("value", selId);
}


function cleanParentInventory() {
	$("#text-parent_name").val(__("None"));
	$("#hidden-id_parent").attr("value", "");	
}

function loadCompany() {

	id_company = $('#id_company').val();
	$('#inventory_status_form').append ($('<input type="hidden" value="'+id_company+'" class="selected-companies" name="companies[]" />'));

	$("#company_search_modal").dialog('close');

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=operation/inventories/inventory_detail&get_company_name=1&id_company="+ id_company,
		dataType: "json",
		success: function (name) {
			$('#inventory_companies').append($('<option></option>').html(name).attr("value", id_company));
		}
	});
}

// Show the modal window of company associated
function show_user_associated(filter) {
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&get_user_associated=1&filter=" + filter,
		dataType: "html",
		success: function(data){	
			$("#user_search_modal").html (data);
			$("#user_search_modal").show ();

			$("#user_search_modal").dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 535,
					height: 350
				});
			$("#user_search_modal").dialog('open');
			
			$("#user_search_modal").ready (function () {
				$('.pass').click(function() { return !$('#origin_users option:selected').remove().appendTo('#destiny_users').attr('selected', 'selected'); });  
				$('.remove').click(function() { return !$('#destiny_users option:selected').remove().appendTo('#origin_users').attr('selected', false);});
				$('.remove').click(function() { return $('#destiny_users option').attr('selected', 'selected');});
				$('.passall').click(function() { $('#origin_users option').each(function() { $(this).remove().appendTo('#destiny_users').attr('selected', 'selected'); }); });
				$('.removeall').click(function() { $('#destiny_users option').each(function() { $(this).remove().appendTo('#origin_users'); }); });
				$('.submit').click(function() { $('#destiny_users').attr('selected', 'selected'); });	
			});
		}
	});
}

function load_users_groups() {
	selText = {};
	selId = '';
	$i=0;
	$("#destiny_users option:selected").each(function () {
	   	var $this = $(this);
	   	if ($this.length) {
	   		selId = selId + $this.val() + ', ';
	    	selText[$this.val()] = $this.text();
	   		$i++;
	   	}
	});
	$("#inventory_users option").remove();
	if(!selId){
		$('#inventory_users').append($('<option>', {
	    	value: 0,
	    	text: 'None'
		}))
	} else {
		$.each(selText, function(key, value){
			$('#inventory_users').append($('<option>', {
	    		value: key,
	    		text: value
			}))
		});
	}
	$("#hidden-users").val(selId);
	$("#user_search_modal").dialog("close");
}

function clean_users_groups() {

	var selId='';
	//option selected
	s= $("#inventory_users").prop ("selectedIndex");
	//value option selected
	selected_id = $("#inventory_users").children (":eq("+s+")").attr ("value");
	//delete option selected
	$("#inventory_users").children (":eq("+s+")").remove ();
	//chnge ids for option
	$("#inventory_users option").each(function () {
	   	var $this = $(this);
	   	if ($this.length) {
	   		selId = selId + $this.val() + ', ';
	   	}
	});	
	//if there is not option
	if(!selId){ 
		$('#inventory_users').append($('<option>', {
	    	value: 0,
	    	text: 'None'
		}));
	}
	$("#hidden-users").attr("value", selId);
}

function removeExternal(id) {
	$("#"+id).attr("value", "");
	
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=operation/inventories/inventory_detail&get_external_child=1&id_object_type_field=" + id, 
		dataType: "json",
		success: function(data){
			jQuery.each (data, function (id, value) {
				$("#"+value['id']).attr("value", "");
			});
		}
	});	
}

/**
 * loadSubTree asincronous load ajax the agents or modules (pass type, id to search and binary structure of branch),
 * change the [+] or [-] image (with same more or less div id) of tree and anime (for show or hide)
 * the div with id "div[id_father]_[type]_[div_id]"
 *
 * type string use in js and ajax php
 * div_id int use in js and ajax php
 * less_branchs int use in ajax php as binary structure 0b00, 0b01, 0b10 and 0b11
 * id_father int use in js and ajax php, its useful when you have a two subtrees with same agent for diferent each one
 */
function loadSubTree(type, div_id, less_branchs, id_father, sql_search, ref_tree, end, last_update) {
	
	hiddenDiv = $('#tree_div'+ref_tree+'_'+type+'_'+div_id).attr('hiddenDiv');
	loadDiv = $('#tree_div'+ref_tree+'_'+type+'_'+div_id).attr('loadDiv');
	pos = parseInt($('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('pos_tree'));

	//If has yet ajax request running
	if (loadDiv == 2)
		return;
	
	if (loadDiv == 0) {

		//Put an spinner to simulate loading process

		$('#tree_div'+ref_tree+'_'+type+'_'+div_id).html("<img style='padding-top:10px;padding-bottom:10px;padding-left:20px;' src=images/spinner.gif>");
		$('#tree_div'+ref_tree+'_'+type+'_'+div_id).slideDown();
		$('#tree_div'+ref_tree+'_'+type+'_'+div_id).attr('loadDiv', 2);

		$.ajax({
			type: "POST",
			url: "ajax.php",
			data: "page=operation/inventories/inventory_search&print_subtree=1&type=" + 
				type + "&id_item=" + div_id + "&less_branchs=" + less_branchs+ "&sql_search=" + sql_search + "&id_father=" + id_father + "&ref_tree=" + ref_tree + "&end=" + end + "&last_update=" + last_update,
			success: function(msg){
				if (msg.length != 0) {
					
					$('#tree_div'+ref_tree+'_'+type+'_'+div_id).hide();
					$('#tree_div'+ref_tree+'_'+type+'_'+div_id).html(msg);
					$('#tree_div'+ref_tree+'_'+type+'_'+div_id).slideDown();
					
					//change image of tree [+] to [-]
					
					var icon_path = 'images/tree';
					
					switch (pos) {
						case 0:
							$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/first_expanded.png');
							break;
						case 1:
							$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/one_expanded.png');
							break;
						case 2:
							$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/expanded.png');
							break;
						case 3:
							$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/last_expanded.png');
							break;
					}

					$('#tree_div'+ref_tree+'_'+type+'_'+div_id).attr('hiddendiv',0);
					$('#tree_div'+ref_tree+'_'+type+'_'+div_id).attr('loadDiv', 1);
				}
				
			}
		});
	}
	else {

		var icon_path = 'images/tree';
		
		if (hiddenDiv == 0) {

			$('#tree_div'+ref_tree+'_'+type+'_'+div_id).slideUp();
			$('#tree_div'+ref_tree+'_'+type+'_'+div_id).attr('hiddenDiv',1);
			
			//change image of tree [-] to [+]
			switch (pos) {
				case 0:
					$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/first_closed.png');
					break;
				case 1:
					$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/one_closed.png');
					break;
				case 2:
					$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/closed.png');
					break;
				case 3:
					$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/last_closed.png');
					break;
			}
		}
		else {
			//change image of tree [+] to [-]
			switch (pos) {
				case 0:
					$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/first_expanded.png');
					break;
				case 1:
					$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/one_expanded.png');
					break;
				case 2:
					$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/expanded.png');
					break;
				case 3:
					$('#tree_image'+ref_tree+'_'+type+'_'+div_id).attr('src',icon_path+'/last_expanded.png');
					break;
			}

			$('#tree_div'+ref_tree+'_'+type+'_'+div_id).slideDown();
			$('#tree_div'+ref_tree+'_'+type+'_'+div_id).attr('hiddenDiv',0);
		}
	}
}

function loadTable(type, div_id, less_branchs, id_father, sql_search, ref_tree, end, last_update) {
	id_item = div_id;
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: {
			'page': 'include/ajax/inventories',
			'get_item_info': 1,
			'id_item': id_item,
			'id_father': id_father
		},
		success: function (data) {
			data = JSON.parse(data);
			
			var name = data.name || 'N/A';
			var items = data.data;
			
			var editImg = '<a href="index.php?sec=inventory&sec2=operation/inventories/inventory_detail&id=' + id_item + '">'
							+ '<img class="inventory_table_edit" src="images/application_edit_white.png">'
						+ '</a>';
			
			var rows = '';
			items.forEach(function (item) {
				rows += '<tr><td><strong>' + item.label + '</strong></td>' + '<td>' + item.data + '</td></tr>';
			});
			var table = '<table class="clean">' + rows + '</table>';
			
			// Assing the new variable to the window scope
			if (typeof window.inventoryInfoBox === 'undefined')
				window.inventoryInfoBox = $.fixedBottomBox({ width: 320 });
			var width = $(table).width();
			window.inventoryInfoBox
				.render(name + ' ' + editImg, table)
				.open();
		}
	});

	loadSubTree(type, div_id, less_branchs, id_father, sql_search, ref_tree, end, last_update);		
}

function show_issue_date() {
	
	if ($("#inventory_status option:selected").val() === 'issued') {
		$("#label-text-issue_date").css("display", "block");
		$("#text-issue_date").css("display", "block");
	} else {
		$("#label-text-issue_date").css("display", "none");
		$("#text-issue_date").css("display", "none");
		
	}
}

//add
function delete_massive_inventory () {
	var checked_ids = new Array();
	$(".cb_inventory").each(function() {
		id = this.id.split ("-").pop ();
		checked = $(this).attr('checked');
		if(checked) {
			$(this).attr('checked', false);
			checked_ids.push(id);
		}
	});

	if(checked_ids.length == 0) {
		alert(__("No items selected"));
	}
	else {
		for(var i=0;i<checked_ids.length;i++){
				values = Array ();
				values.push ({name: "page",
							value: "operation/inventories/inventory"});
				values.push ({name: "quick_delete",
							value: checked_ids[i]});
				values.push ({name: "massive_number_loop",
							value: i});
				jQuery.get ("ajax.php",
					values,
					function (data, status) {
						// We refresh the interface in the last loop
						if(data >= (checked_ids.length - 1)) {
							// This takes the user to the top of the page
							//window.location.href="index.php?sec=inventory&sec2=operation/inventories/inventory";
							// This takes the user to the same place before reload
							tree_search_submit();
						}
					},
					"json"
				);
			}
	}
}


/**
 * Send formulary through AJAX instead of submit
 */

function send_form_ajax(id_form, id_response, url, operation){
	$.ajax({
		type: "POST",
		url: "ajax.php?" + $(id_form).serialize()+"&"+operation,
		data: {
			'page': url,
		},
		dataType: "json",
		statusCode: {
			200: function(data){
				$("#"+id_response.id).html(data.responseText);
			}
		}
	});
}

function onchange_owner_company(){
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=include/ajax/inventories&change_owner=1&id_name="+ id_name,
		dataType: "json",
		success: function (name_company) {
			if(name_company[0]){
				$("#hidden-companies").val(name_company[0]["id"]);
				$("#inventory_companies option").empty();
				$("#inventory_companies option").val(name_company[0]["id"]);
				$("#inventory_companies option").append(name_company[0]["name"]);
			} else {
				$("#inventory_companies option").empty();
				$("#inventory_companies option").val(0);
				$("#inventory_companies option").append('None');
				$("#hidden-companies").empty();
			} 
		}
	});	
}