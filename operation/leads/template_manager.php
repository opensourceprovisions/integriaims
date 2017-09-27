<?php 

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
global $config;

check_login ();

include_once('include/functions_crm.php');

$operation = get_parameter ("operation");
$id = (int) get_parameter ("id");
$id_company = get_db_sql ("SELECT id_company FROM tcrm_template WHERE id = $id");

$manage_permission = check_crm_acl ('company', 'cm', false, $id_company);

if (!$manage_permission) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to template manager");
	include ("general/noaccess.php");
	exit;
}

// ---------------
// CREATE template
// ---------------

if (($operation == "insert") OR ($operation == "update")){
	$name = (string) get_parameter ("name");
	$subject = (string) get_parameter ("subject");
	$description = (string) get_parameter ("description"); 
	$id_language = (string) get_parameter ("id_language");
	$id_company = (int) get_parameter ("id_company");

	// Get company of current user if none provided.
	if ($id_company == 0){
		$id_company = get_db_value  ('id_company', 'tusuario', 'id_usuario', $config["id_user"]);
	}

	if ($operation == "insert") {
		$sql = sprintf ('INSERT INTO tcrm_template (name, description, id_language, id_company, subject)
		VALUES ("%s","%s","%s", %d, "%s")',
		$name, $description, $id_language, $id_company, $subject);
		$id = process_sql ($sql, 'insert_id');
	} else {
		$sql = sprintf ('UPDATE tcrm_template set name = "%s", description="%s", id_language = "%s", subject = "%s", id_company = %d WHERE id = %d', $name, $description, $id_language, $subject, $id_company, $id);
		process_sql ($sql);
	}

	if (! $id)
		echo ui_print_error_message (__('Not created. Error inserting data'), '', true, 'h3', true);
	else {
		if ($operation == "insert")
			echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
		else
			echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
	}
	$operation = "";
	$id = 0;
}


// ---------------
// DELETE template
// ---------------

// TODO: ACL Check. Should be only able to delete templates of their company or child companies

if ($operation == "delete") {
	$id = get_parameter ("id");
	$sql_delete= "DELETE FROM tcrm_template WHERE id = $id";
	$result=mysql_query($sql_delete);
	if (! $result)
		echo ui_print_error_message (__('Not deleted. Error deleting data'), '', true, 'h3', true);
	else
		echo ui_print_success_message (__("Successfully deleted"), '', true, 'h3', true);
	$operation = "";
}

// ---------------
// CREATE  (form)
// ---------------

if (($operation == "create") || ($operation == "edit")){
    echo "<h2>".__('CRM Template management')."</h2>";
	
	if ($operation == "create"){
		echo "<h4>".__('Create CRM Template')."</h4>";
    	$name = "";
    	$description = "";
    	$id_language = "";
    	$id_company = 0;
    	$subject = "";

    } else {
		echo "<h4>".__('Update CRM Template')."</h4>";
    	// TODO: Check ACL here. Dont allow to read Id not my company or child (or admin)
		$template = get_db_row ("tcrm_template", "id", $id);
		$name = $template["name"];
		$description = $template["description"];
		$id_language = $template["id_language"];
		$id_company = $template["id_company"];
		$subject = $template["subject"];
    }

	$table->width = '100%';
	$table->class = 'search-table-button';
	$table->colspan = array ();
	$table->colspan[3][0] = 2;
	$table->data = array ();
	
	$table->data[1][0] = print_input_text ('name', $name, '', 50, 100, true,
		__('Name'));

	$table->data[1][1] = print_select_from_sql ('SELECT id_language, name FROM tlanguage ORDER BY name',
	'id_language', $id_language, '', '', '', true, false, false,
	__('Language'));

	$sql2 = "SELECT id, name FROM tcompany ORDER by name";

	$table->data[2][0] = print_input_text ('subject', $subject, '', 70, 200, true,
		__('Subject'));

	$table->data[2][1] = print_select_from_sql ($sql2, 'id_company', $id_company, '', __("None"), 0, true, false, true, __("Managed by"));

	
	$table->data[3][0] = print_textarea ('description', 25, 60, $description, '', true,
		__('Contents'));
		
	if ($operation == "create"){
		$button = print_submit_button (__('Create'), 'crt', false, 'class="sub create"', true);
		$button .= print_input_hidden ('operation', 'insert', true);
	} else {
		$button = print_submit_button (__('Update'), 'crt', false, 'class="sub upd"', true);
		$button .= print_input_hidden ('operation', 'update', true);
		$button .= print_input_hidden ('id', $id, true);
	}
	
	//~ $table->data['button'][0] = $button;
	//~ $table->colspan['button'][0] = 2;
	
	echo '<form id="form-template_manager" method="post" action="index.php?sec=customers&sec2=operation/leads/template_manager">';
	print_table ($table);
	echo "<div class='button-form'>".$button."</div>";
	echo '</form>';
}

// -------------------------
// LIST OF CRM TEMPLATES
// -------------------------
if ($operation == "") {
	echo "<h2>".__('CRM Template management')."</h2>";
	echo "<h4>".__('List CRM Template')."</h4>";

	//TODO: Show only my companies templates or my "child" companies tempaltes (and all if I'm admin)
	echo '<div class="divform">';
    echo '<form method="post" action="index.php?sec=customers&sec2=operation/leads/template_manager">';
	echo '<table class="search-table" style="width: 100%;">';
	echo '<tr>';
	echo '<td>';
	print_submit_button (__('Create'), 'crt', false, 'class="sub create"');
	print_input_hidden ('operation', 'create');
	echo '</td>';
	echo '</tr>';
	echo '</table></form>';
	echo "</div>";
	
	if (dame_admin($config["id_user"]))
		$sql = sprintf ('SELECT * FROM tcrm_template');
	else
		$sql = sprintf ('SELECT * FROM tcrm_template');
	
	
	$todos = get_db_all_rows_sql ($sql);
	echo "<div class='divresult'>";
	if ($todos === false) {
		echo "<h3>"._('No data to show')."</h3>";
	}
	else {
		echo '<table class="listing" width="100%">';
		echo "<th>".__('Name');
		echo "<th>".__('Language');
		echo "<th>".__('Company');
		echo "<th>".__('Operations');

		foreach ($todos as $todo) {
			
			echo "<tr><td>";
			echo '<a href="index.php?sec=customers&sec2=operation/leads/template_manager&operation=edit&id='.$todo["id"].'">';
			echo "<b>". $todo["name"]."</b></a>";
		
			echo "<td>";
			echo "<b>".$todo["id_language"]."</b>";
		
			echo "<td valign=top>";
			$company_name = get_db_value('name','tcompany','id',$todo["id_company"]);
			echo $company_name;
			
			echo '<td>';

			echo '<a href="index.php?sec=customers&sec2=operation/leads/template_manager&operation=edit&id='.$todo["id"].'"><img border=0 src="images/wrench.png"></a> ';

			echo '<a href="index.php?sec=customers&sec2=operation/leads/template_manager&operation=delete&id='.$todo["id"].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;"><img border=0 src="images/cross.png"></a>';

		}
		echo "</table>";
	}
	echo "</div>";
} // Fin bloque else

?>
</style>
<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>
<script type="text/javascript" src="include/js/tinymce/tinymce.min.js"></script>
<script type="text/javascript" src="include/js/tinymce/jquery.tinymce.min.js "></script>
<script type="text/javascript">
tinymce.init({
  selector: 'textarea',
force_br_newlines : true,
    force_p_newlines : false,
    forced_root_block : false,
  plugins: [
    'advlist autolink lists link image charmap print preview anchor',
    'searchreplace visualblocks code fullscreen',
    'insertdatetime media table contextmenu paste code'
  ],
  toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
  content_css: 'include/js/tinymce/integria.css',
  
});
	
</script>


<script type="text/javascript">
	
// Form validation
trim_element_on_submit('#text-name');
validate_form("#form-template_manager");
var rules, messages;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_crm_template: 1,
			crm_template_name: function() { return $('#text-name').val() },
			crm_template_id: "<?php echo $id?>"
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This template already exists')?>"
};
add_validate_form_element_rules('#text-name', rules, messages);

</script>
