function showIncidentTypeFields(elementId) {
	
	element = $(elementId);
	id_incident_type = $("#select-id_incident_type").val();
	id_incident = $("#hidden-id_incident").val();
	
	element.html('');
	
	if (!(id_incident_type > 0)) {
		return;
	}
	
	postvars = {};
	postvars["action"] = "ajax";
	postvars["page"] = "incident";
	postvars["method"] = "getIncidentTypeFields";
	postvars["id_incident_type"] = id_incident_type;
	postvars["id_incident"] = id_incident;
	
	$.post("index.php",
			postvars,
			function (data) {
				var textarea_elements = new Array();
				var lbl;
				var txt;
				var input;
				
				jQuery.each (data, function (id, value) {
					
					//This loops prints combos and text fields
					//textare fields are printed later.
					if (value["type"] == "textarea") {
						textarea_elements.push(value);
						return true; //Skip this iteration;
					}
					
					//Create label and only add content if the element is not a texarea
					lbl = document.createElement('label');
					lbl.innerHTML = value['label']+' ';

					txt = document.createElement('br');
					lbl.appendChild(txt);
					
					if (value['type'] == "combo") {
									
						input = document.createElement('select');
						input.id = value['label']; 
						input.name = value['label_enco'];
						input.value = value['label'];
						input.class = "type";
						
						var new_text = value['combo_value'].split(',');
						jQuery.each (new_text, function (id, val) {
							input.options[id] = new Option(val);
							input.options[id].setAttribute("value",val);
							if (value['data'] == val) {
								input.options[id].setAttribute("selected",'');
							}
						});
				
					}
					
					if ((value['type'] == "text")) {
					
						input = document.createElement('input');
						input.id = value['label'];
						input.name = value['label_enco'];
						input.value = value['data'];
						input.type = 'text';
						
					}
					
					lbl.appendChild(input);
					element.append(lbl).trigger("create");
				});
				
				//Now we print text areas
				jQuery.each (textarea_elements, function (id, value) {
							
					//Create label
					lbl = document.createElement('label');
					lbl.innerHTML = value['label']+' ';
					
					//Create text area element
					input = document.createElement("textarea");
					input.id = value['label'];
					input.name = value['label_enco'];
					input.value = value['data'];
					input.type = 'text';
					
					lbl.appendChild(input);
					element.append(lbl).trigger("create");
				});
			},
			"json");
}
