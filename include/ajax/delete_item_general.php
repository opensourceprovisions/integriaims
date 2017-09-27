<?php
	global $config;
	global $REMOTE_ADDR;

	$delete_item = get_parameter('delete_item', 0);
	$get_delete_validation = get_parameter ('get_delete_validation', 0);

	if ($get_delete_validation) {
		echo "<div>";
			echo "<div style='float: left;'><img style='padding:10px;' src='images/icon_delete.png' alt='".__('Delete')."'></div>";
			echo "<div style='float: left; font-size:15px; font-weight: bold; margin-top:32px;'><b>".__('Are you sure you want to delete?')."</b></br>";
			echo "<span style='font-size:13px; font-weight: normal; line-height: 1.5em;'>" . __('This action can not be undone'). "</span></div>";
			echo '<form id="validation_delete_form" method="post">';
				echo print_submit_button (__('Delete'), "delete_btn", false, 'class="sub close" width="160px;"', true);
				echo print_button (__('Cancel'), 'modal_cancel', false, '', '', false);
			echo '</form>';
		echo "</div>";
	}

	if ($delete_item) {
		$mode = get_parameter('name');
		$id = get_parameter('id');
		
		switch ($mode) {
			case 'delete_project':
			
			return;	
			break;
			case 'delete_role_user_global':
			
			return;
			break;
			
			case 'delete_task_panning':
				
			return;
			break;



			case 'delete_contract':
				
				return;
			break;
			
			case 'delete_company_invoice':

				return;
			break;
			
			case 'delete_invoice':
				
				return;
			break;
			case 'delete_lead':
				
				return;
			break;
		}
		
	}
?>