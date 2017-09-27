// Changes the value of the element for a trimmed value when the form is submitted
function trim_element_on_submit(element, form) {
	
	if (typeof(form) == 'undefined') {
		form = "form";
	}
	
	$(document).ready( function() {
		$(form).submit( function() {
			$(element).val( $.trim( $(element).val() ) )
		});
	});
	
}

// Activates the jQuery validation plugin, with some default validations
function validate_form(form) {
	
	if (typeof(form) == 'undefined') {
		form = "form";
	}
	
	$(document).ready( function() {
		$(form).validate( {
			onkeyup: false,
			highlight: function(element, errorClass) {
				pulsate(element);
			}
		});
	});
	
}

// Adds a rule of validation over an input element, with an optional message.
// @element: Selector of the element
// @type: Type of the rule (email, required, minlength, etc.).
// @value: Value of the rule. If the value is an string, it is needed to add
// aditional double comas ("").
// @message: Optional. Message to show in case of reach the rule.
function add_validate_form_element_rules(element, rule, message) {
    
	$(document).ready( function() {
		if ( $(element).length > 0 ) {
			$(element).rules("add", rule);
		}
	});
	
	if (typeof(message) != 'undefined')
		add_validate_form_element_message(element, message);
	
}

// Adds a message to a rule, that will be showed in case of reach the rule.
// @element: Selector of the element
// @type: Type of the rule (required, minlength, etc.).
// @message: Message to show.
function add_validate_form_element_message(element, message) {
	$(document).ready( function() {
		if ( $(element).length > 0 ) {
			$(element).rules("add", { messages: message });
		}
	});
	
}

// Removes all the rules of an input element, or an especific rule or rules.
// @rules is an optional string with the name of the rules to remove divided
// by blank spaces. If is set, removes only that rules, otherwise,
// this function will removes all the rules
function remove_validate_form_element_rules(element, rules) {
	
	if (typeof(rules) != 'undefined') {
		$(document).ready( function() {
			$(element).rules("remove", rules);
		});
	} else {
		$(document).ready( function() {
			$(element).rules("remove");
		});
	}
	
}

function validate_user (form, input, message) {
	var rules;
	var messages;
	rules = {
		remote: {
			url: "ajax.php",
			type: "POST",
			data: {
				page: "include/ajax/remote_validations",
				search_non_existing_user_id: 1,
				user_id: function() { return $(input).val() }
			}
		}
	};
	messages = {
		remote: message
	};
	validate_form(form);
	add_validate_form_element_rules(input, rules, messages);
}

function validate_ticket_user (form, input, message, idGroup) {
	var rules;
	var messages;

	rules = {
		remote: {
			url: "ajax.php",
			type: "POST",
			data: {
				page: "include/ajax/remote_validations",
				check_allowed_users: 1,
				id_group: idGroup,
				user_id: function() { return $(input).val() }
			}
		}
	};
	messages = {
		remote: message
	};
	validate_form(form);
	add_validate_form_element_rules(input, rules, messages);
}

