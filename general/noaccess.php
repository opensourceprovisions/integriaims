<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2010 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

	
global $config;

	
echo "<center>";

echo "<div class= 'dialog ui-dialog-content' title='".__("Acces denied")."' id='noaccess_window'></div>";
?>

<script type="text/javascript">
	var parameters = {};
	parameters['page'] = "include/ajax/users";
	parameters['noaccess_table'] = 1;

	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: parameters,
		dataType: "html",
		success: function(data){	
			$("#noaccess_window").html (data);
			$("#noaccess_window").show ();

			$("#noaccess_window").dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 620,
					height: 180
				});
			$("#noaccess_window").dialog('open');
			
		}
	});
</script>

<?php
echo "</center>";

exit;
?>


