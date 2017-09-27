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
	
if (! dame_admin ($config["id_user"])) {
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], __("No administrator access"), __("Trying to access newsboard setup"));
	require ("general/noaccess.php");
	exit;
}

echo "<h2>".__('Newsboard management')."</h2>";

$operation = get_parameter ("operation","");
if ($operation == "create")
	echo "<h4>".__('Create Newsboard')."</h4>";
if ($operation == "update")
	echo "<h4>".__('Update Newsboard')."</h4>";
if ($operation == "" || $operation == "insert")
	echo "<h4>".__('List Newsboard')."</h4>";

// ---------------
// CREATE newsboard
// ---------------
if ($operation == "insert") {
	$title = (string) get_parameter ("title");
	$content = (string) get_parameter ("content"); 
	$date = date('Y-m-d H:i:s', time()); //current datetime
	$id_group = (int) get_parameter ("id_group", 0);
	$expire = (int) get_parameter ("expire");
	$expire_date = get_parameter ("expire_date");
	$expire_date = date('Y-m-d', strtotime($expire_date));
	$expire_time = get_parameter ("expire_time");
	$expire_timestamp = "$expire_date $expire_time";
	$creator = $config['id_user'];

	if (!$expire)
		$expire_timestamp = "0000-00-00 00:00:00";

	$sql = sprintf ('INSERT INTO tnewsboard (title, content, `date`, id_group, expire, `expire_timestamp`, creator)
		VALUES ("%s","%s","%s",%d,%d,"%s","%s")',
		$title, $content, $date, $id_group, $expire, $expire_timestamp, $creator);
		
	$id = process_sql ($sql, 'insert_id');
	if (! $id)
		echo ui_print_error_message (__('Not created. Error inserting data'), '', true, 'h3', true);
	else {
		echo ui_print_success_message (__('Successfully created'), '', true, 'h3', true);
	}
	$operation = "";
}

// ---------------
// UPDATE newsboard
// ---------------
if ($operation == "updated") {
	$id = get_parameter ("id");
	
	$values = array();
	$values['title'] = (string) get_parameter ("title");
	$values['content'] = (string) get_parameter ("content"); 
	$values['date'] = date('Y-m-d H:i:s', time()); //current datetime
	$values['id_group'] = (int) get_parameter ("id_group", 0);
	$values['expire'] = (int) get_parameter ("expire");
	$expire_date = get_parameter ("expire_date");
	$expire_date = date('Y-m-d', strtotime($expire_date));
	$expire_time = get_parameter ("expire_time");
	$values['expire_timestamp'] = "$expire_date $expire_time";
	//$creator = $config['id_user'];

	if (!isset($expire))
		$values['expire_timestamp'] = "0000-00-00 00:00:00";
	
	
	$where = "id = $id";
	$result = process_sql_update ('tnewsboard', $values, $where);
	
	if (! $result)
		echo ui_print_error_message (__('Not Updated. Nothing to updated'), '', true, 'h3', true);
	else {
		echo ui_print_success_message (__('Successfully updated'), '', true, 'h3', true);
	}
	$operation = "";
}


// ---------------
// DELETE newsboard
// ---------------
if ($operation == "delete") {
	$id = get_parameter ("id");
	$sql_delete= "DELETE FROM tnewsboard WHERE id = $id";
	$result=mysql_query($sql_delete);
	if (! $result)
		echo ui_print_error_message (__('Not deleted. Error deleting data'), '', true, 'h3', true);
	else
		echo ui_print_success_message (__('Successfully deleted'), '', true, 'h3', true);
	$operation = "";
}


// CREATE new newsboard(form) or Update
if ($operation == "create" OR $operation == "update") {
    
    $title = "";
    $content = "";
    $expire = 0;
    $date = date('Y-m-d', time() + 604800); //one week later
	$time = date('H:i:s', time());
	$id_grupo = 0;
	
	if ($operation == "update") {
		$id = get_parameter ("id");
		$news = get_db_row_filter('tnewsboard', array('id' => $id));
		$title = $news["title"];
		$content = $news["content"];
		$expire = $news["expire"];
		$date = explode(" ",$news["expire_timestamp"]);
		$time = $date[1];
		$date = $date[0];
		$id_grupo = $news['id_group'];
	}
	
	$table = new StdClass();
	$table->width = '100%';
	$table->class = 'search-table-button';
	$table->colspan = array ();
	$table->colspan[1][0] = 2;
	$table->colspan[2][0] = 4;
	$table->colspan[3][0] = 2;
	$table->colspan[4][0] = 2;
	
	$table->data = array ();
	
	$table->data[0][0] = print_input_text ('title', $title, '', 60, 100, true,
		__('Title'));
		
	$table->data[0][2] = "<div style='display:inline-block;'>" . print_input_text ('expire_date', $date, '', 11, 2, true, __('Date')) . "</div>";
	$table->data[0][2] .= "&nbsp;";
	$table->data[0][2] .= "<div style='display:inline-block;'>" . print_input_text ('expire_time', $time, '', 7, 20, true, __('Time')) . "</div>";
	

	$all_groups = group_get_groups();
	$table->data[1][0] = print_select ($all_groups, "id_group", $id_grupo, '', '', 0, true, false, false, __('Group'));
	
	$table->data[1][1] = print_checkbox ('expire', 1, $expire, true,  __('Expire'));
	
	$table->data[2][0] = print_textarea ("content", 10, 1, $content, '', true, __('Contents'));
	
	if ($operation == "update") {	
		$button = print_submit_button (__('Update'), 'crt', false, 'class="sub upd"', true);
		$button .= print_input_hidden ('operation', 'updated', true);
		$button .= print_input_hidden ('id', $id, true);
	}
	else {
		$button = print_submit_button (__('Create'), 'crt', false, 'class="sub create"', true);
		$button .= print_input_hidden ('operation', 'insert', true);
	}
	
	echo '<form method="post" action="index.php?sec=godmode&sec2=godmode/setup/newsboard">';
	print_table ($table);
		echo "<div class='button-form'>";
			echo $button;
		echo "</div>";
	echo '</form>';
}

// -------------------------
// TODO VIEW of my OWN items
// -------------------------
if ($operation == "") {
	
	$sql = sprintf ('SELECT * FROM tnewsboard');
	$todos = get_db_all_rows_sql ($sql);
	if ($todos === false)
		$todos = array ();

	echo '<table class="listing" width="100%">';
	echo "<th>".__('Title');
	echo "<th>".__('Expire');
	echo "<th>".__('Expire date');
	echo "<th>".__('Delete');

	foreach ($todos as $todo) {
		
		echo "<tr><td>";
		echo "<b>".$todo["title"]."</b>";
    
		echo "<td>";
		if ($todo['expire'])
			echo __('Yes');
		else
			echo __('No');
		
	    echo "<td>";
	    if ($todo["expire_timestamp"] == "0000-00-00 00:00:00")
			echo __('No expiration date');
		else 
			echo $todo["expire_timestamp"];
		
		echo '<td>';
		echo '<a href="index.php?sec=godmode&sec2=godmode/setup/newsboard&operation=update&id=' . 
				$todo["id"].'"><img src="images/editor.png"></a>';
		echo '<a href="index.php?sec=godmode&sec2=godmode/setup/newsboard&operation=delete&id=' . 
				$todo["id"].'" onClick="if (!confirm(\' ' . 
					__('Are you sure?').'\')) return false;">
					<img border=0 src="images/cross.png"></a>';

        echo "<tr><td colspan=4 style=''>";
		echo print_container_div('news_'.$todo["id"], __("Content"), clean_output($todo["content"]), 'closed', true, false, '', '', 1, '', "margin:0px");
	}
	echo "</table>";

    echo '<form method="post" action="index.php?sec=godmode&sec2=godmode/setup/newsboard&operation=create">';
	echo '<div class="button-form">';
	print_submit_button (__('Create'), 'crt', false, 'class="sub create');
	echo '</div></form>';

} // Fin bloque else

?>
<script type="text/javascript" src="include/js/jquery.ui.slider.js"></script>
<script type="text/javascript" src="include/js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="include/languages/date_<?php echo $config['language_code']; ?>.js"></script>
<script type="text/javascript" src="include/js/integria_date.js"></script>
<script type="text/javascript" src="include/js/tinymce/tinymce.min.js"></script>
<script type="text/javascript" src="include/js/tinymce/jquery.tinymce.min.js "></script>


<script type="text/javascript">
$(document).ready (function () {
	tinymce.init({
		selector: 'textarea',
		fontsize_formats: '8pt 9pt 10pt 11pt 12pt 26pt 36pt',
		height: 300,	
		forced_root_block: false,
		plugins: [
			'advlist autolink lists link image charmap print preview anchor',
			'searchreplace visualblocks code fullscreen',
			'insertdatetime media table contextmenu paste code'
		],
		menubar: true,
		toolbar: 'undo redo | styleselect | bold italic fontsizeselect | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | table code visualblocks',
		content_css: 'include/js/tinymce/integria.css',
	});
	
	$("#slider").slider ({
		min: 0,
		max: 100,
		stepping: 5,
		slide: function (event, ui) {
			$("#progress").empty ().append (ui.value+"%");
		},
		change: function (event, ui) {
			$("#hidden-progress").attr ("value", ui.value);
		}
	});
<?php if ($progress)
	echo '$("#slider").slider ("moveTo", '.$progress.');';
?>

	$("#checkbox-expire").click(function() {
		check_expire();
	});
});

check_expire();

add_datepicker ("#text-expire_date");

function check_expire() {
	if ($("#checkbox-expire").is(":checked")) {
		$('#label-text-expire_date').css('visibility', '');
		$('#label-text-expire_time').css('visibility', '');
		$('#text-expire_date').css('visibility', '');
		$('#text-expire_time').css('visibility', '');
	}
	else {
		$('#label-text-expire_date').css('visibility', 'hidden');
		$('#label-text-expire_time').css('visibility', 'hidden');
		$('#text-expire_date').css('visibility', 'hidden');
		$('#text-expire_time').css('visibility', 'hidden');
	}
}
</script>
