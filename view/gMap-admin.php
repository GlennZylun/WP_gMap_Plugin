<?php

function gmap_admin_menu() 
{
	add_object_page('gMap', 'gMap', 'edit_posts', 'gmap', 'gmap_process');
}
add_action('admin_menu', 'gmap_admin_menu');

function gmap_ui() {
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_script('gmap-datatable', GMAP_URL.'/view/js/datatable/media/js/jquery.dataTables.js');
	wp_enqueue_script('gmap-edit-field', GMAP_URL.'/view/js/functions.js');
	
	wp_enqueue_style("wp-jquery-ui-tabs");
	wp_enqueue_style("wp-jquery-ui-dialog");
	wp_enqueue_style('gmap-ui-all', GMAP_URL.'/view/css/themes/base/jquery.ui.all.css');
	wp_enqueue_style('gmap-ui-table', GMAP_URL.'/view/js/datatable/media/css/demo_table_jui.css');
	wp_enqueue_style('gmap-ui-table', GMAP_URL.'/view/js/datatable/media/css/demo_table.css');
	wp_enqueue_style('gmap-ui-table', GMAP_URL.'/view/js/datatable/media/css/jquery.dataTables.css');
	wp_enqueue_style('gmap-ui-table', GMAP_URL.'/view/js/datatable/media/css/jquery.dataTables_themeroller.css');
}

add_action( 'admin_enqueue_scripts', 'gmap_ui' );

function gmap_process(){
	global $gmap_class, $gmail_username, $gmap_model;
	
	echo '<h2>gMap</h2>';
	
	$get_user_detail = $gmap_class->get_user_detail();
	$user_detail = isset($get_user_detail) && !empty($get_user_detail) ? $get_user_detail : array();
	
	//$get_user_spreadsheets = $gmap_class->get_user_spreadsheets();
	$get_user_spreadsheets = array();
	
	if(isset($_REQUEST['login_account'])) {
		$params = array(
					'gmail_username' => $_REQUEST['gmail_username'],
					'gmail_password' => $_REQUEST['gmail_password']
				);
		$result = $gmap_class->insert_gmap_user($params);
		
		if($result){
			$get_user_detail = $gmap_class->get_user_detail();
			$user_detail = isset($get_user_detail) && !empty($get_user_detail) ? $get_user_detail : array();
			echo '<h3>Successfully Log In and Getting Spreadsheets</h3>';
		}else{
			echo '<h3>Failed to get Spreadsheets</h3>';
		}
	} else if(isset($_REQUEST['crawl_spreadsheet'])) {
		$result = $gmap_class->update_site_services();
		if($result){
			$value = json_encode(array(
							'width' => 800, 
							'height' => 800
							));
			$params = array(
							'option' => 'gmap_display',
							'value' => $value
						);
			$gmap_class->gmap_option($params);
			echo '<h3>Successfully Crawled Spreadsheets</h3>';
		}else{
			echo '<h3>Failed to Crawl Spreadsheet</h3>';
		}
	} else if(isset($_REQUEST['update_geolocation'])) {
		$get_fields = $gmap_class->get_all_service_fields();
		$result = $gmap_class->update_long_lang();
		
		if(count($get_fields) <= 1 || !$result){
			echo '<h3>Failed to get Geo Location</h3>';
		} else {
			echo '<h3>Geo Location Successfully Update</h3>';
		}
	} else if(isset($_REQUEST['gmap_display_submit'])) {
		$sort_value = json_encode($_REQUEST['fields']);
		$show_value = json_encode($_REQUEST['show_field']);
		$search_value = json_encode($_REQUEST['search_field']);
		$field_names = json_encode($_REQUEST['field_names']);
		
		$value = json_encode(array(
							'width' => $_REQUEST['gmap_width'], 
							'height' => $_REQUEST['gmap_height'],
							'show_legend' =>  $_REQUEST['gmap_legend'],
							'show_search' =>  $_REQUEST['gmap_search'],
							'gmap_scrollzoom' =>  $_REQUEST['gmap_scrollzoom'],
							'gmap_hoverdetails' =>  $_REQUEST['gmap_hoverdetails']
							));
		$params = array(
						'option' => 'gmap_display',
						'value' => $value
					);
		
		$display_result = $gmap_class->gmap_option($params);
		
		$gmap_title_desc = json_encode(array(
							'gmap_title' 		=> isset($_REQUEST['gmap_title']) ? $_REQUEST['gmap_title'] : '',
							'gmap_description' 	=> isset($_REQUEST['gmap_description']) ? $_REQUEST['gmap_description'] : ''
							));
		$params_title_desc = array(
							'option' => 'gmap_title_desc',
							'value' => $gmap_title_desc
						);
						
		$title_desc_result = $gmap_class->gmap_option($params_title_desc);

		$sort_params = array(
						'option' => 'gmap_fields',
						'value' => $sort_value
					);
		$sort_result = $gmap_class->gmap_option($sort_params);
		
		
		$show_params = array(
							'option' => 'gmap_show_field',
							'value' => $show_value
						);
		$show_result = $gmap_class->gmap_option($show_params);
		
		$search_params = array(
							'option' => 'gmap_search_field',
							'value' => $search_value
						);
		$search_result = $gmap_class->gmap_option($search_params);
		
		$fields_params = array(
							'option' => 'gmap_field_names',
							'value' => $field_names
						);
		$fields_result = $gmap_class->gmap_option($fields_params);
		
		if($display_result || $title_desc_result || $sort_result || $show_result || $fields_result || $search_result){
			echo '<h3>Successfully Updated Options</h3>';
		} else {
			echo '<h3>Failed To Update Options</h3>';
		}
		
	}
?>
	<script type="text/javascript">

		var SITE_URL = "<?php bloginfo('url'); ?>/";

		
		jQuery(document).ready(function() {
			jQuery("#gmap-tabs").tabs();
			
			jQuery('.dataTable').dataTable({
				"bJQueryUI": true,
				"sPaginationType": "full_numbers",
				"bScrollCollapse": true
			});

			jQuery('#refresh-data').bind("click",function(e){
				e.preventDefault();
				jQuery(this).unbind("click");
				jQuery('#refresh_gmap_data').dialog('open');
			});
			
			jQuery('#refresh_gmap_data').dialog({
				autoOpen: false,
				resizable: false,
				height:200,
				width: 300,
				modal: true,
				buttons: {
					"Refresh": function(e) {
						jQuery( this ).dialog( "close" );
						jQuery("#crawl-spreadsheet").trigger("click");
					},
					"Cancel": function() {
					  jQuery( this ).dialog( "close" );
					}
				}
			});
			
			jQuery('#gmap_display').submit(function(){
				var enable_search = jQuery('#gmap_search:checkbox:checked').length;
				var search_fields = jQuery('.search_field:checkbox:checked').length;
				
				if(enable_search) {
					if(search_fields > 0) {
						return true;
					} else {
						alert('Error: Unable to show Search. Please add atleast 1 field in Search Option.');
						return false;
					}
				}
			});
						
									
			jQuery('#edit_record').dialog({
				autoOpen: false,
				height: 500,
				width: 500,
				modal: true,
				buttons: {
					"Update": function() {
						var id = jQuery('#data_id').val();
						
						var data_info = jQuery('form[name="edit_record_form"]').serialize();
						var data = {
							action: 'update_record',
							id: id,
							data_info: data_info
						};
						
						jQuery.post(SITE_URL + 'wp-admin/admin-ajax.php', data, function(response) {
							if(response == 'success'){
								alert('Record successfully updated...');
								window.location.href = SITE_URL + 'wp-admin/admin.php?page=gmap';
							} else {
								alert('Record not successfully updated...');
							}
						});
					},
					"Cancel": function() {
						jQuery( this ).dialog( "close" );
					}
				}
			});
			
			jQuery('#more_info').dialog({
				autoOpen: false,
				height: 500,
				width: 500,
				modal: true
			});
			
			jQuery("#delete_record").dialog({
				autoOpen: false,
				resizable: false,
				height:200,
				width: 300,
				modal: true,
				buttons: {
					"Delete": function() {
						var id = jQuery('#record_id').val();
						var data = {
							action: 'delete_record',
							id: id
						};
						jQuery.post(SITE_URL + 'wp-admin/admin-ajax.php', data, function(response) {
							if(response == 'success'){
								alert('Record successfully deleted...');		
								window.location.href = SITE_URL + 'wp-admin/admin.php?page=gmap';
							} else {
								alert('Record not successfully deleted...');
							}
						});
					},
					"Cancel": function() {
					  jQuery( this ).dialog( "close" );
					}
				}
			});
			
			jQuery('#selectall_show').on('click', function() {
				jQuery('.show_field').attr('checked', jQuery(this).is(":checked"));
			});
			
			jQuery('#selectall_search').on('click', function() {
				jQuery('.search_field').attr('checked', jQuery(this).is(":checked"));
			});
			
		});
		
		function delete_data(id) {
			jQuery('#record_id').val(id);
			jQuery("#delete_record").dialog('open');
		}
		
		function edit_data(id, info){
			var $form = jQuery('form[name="edit_record_form"]');
			var $data_id = jQuery('#data_id');
			<?php
				$record_fields = $gmap_class->get_all_service_fields();

				foreach($record_fields as $key) :
					if($key['Field'] == 'id')
						continue;
					
					echo 'var $'.$key['Field'].' = $form.find(\'[name="'.$key['Field'].'"]\');'."\n";
					
				endforeach;
			?>
			
			var data = {
				action: 'get_record',
				id: id
			};
				
			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			jQuery.post(SITE_URL + 'wp-admin/admin-ajax.php', data, function(response) {
				$data = JSON.parse(response);
				$data_id.val($data.id);
				if(info){
					<?php
						foreach($record_fields as $key) :
							if($key['Field'] == 'id')
								continue;
							
							echo 'jQuery(".'.$key['Field'].'").text($data.'.$key['Field'].');'."\n";
						endforeach;
					?>
				}else{
					<?php
						foreach($record_fields as $key) :
							if($key['Field'] == 'id')
								continue;
							
							echo '$'.$key['Field'].'.val($data.'.$key['Field'].');'."\n";
						endforeach;
					?>
				}
				
			});
			
			if(info){
				jQuery('#more_info').dialog('open');
			}else{
				jQuery('#edit_record').dialog('open');
			}
			
		}
		
		function use_this_spreadsheet(sskey) {
			var data = {
				action: 'update_user_spreadsheet',
				sskey: sskey,
				username: '<?php echo $user_detail['gmail_username']; ?>'
			};
			jQuery.post(SITE_URL + 'wp-admin/admin-ajax.php', data, function(response) {
				if(response == 'success'){
					alert('Spreadsheet Key Successfully added...');		
					window.location.href = SITE_URL + 'wp-admin/admin.php?page=gmap';
				} else {
					alert('Error: Cannot add Spreadsheet Key...Please Try Again');
				}
			});
		}

	</script>
	<style type="text/css">
		#gmap-tabs{
			width: 98%;
			font-size: 11px;
		}
		
		#gmap-tabs ul li a.ui-tabs-anchor{
			font-size: 11px !important;
		}
		
		.clear{
			clear:both;
		}
		
		.process_button{
			margin-bottom: 10px;
		}

		#edit_record{
			padding: 10px;
		}
		
		table{
		  max-width: none
		}
		input.field_name{
			margin-top:8px;
			font-size:18px;
			color:#000;
			-moz-border-radius: 2px;
			-webkit-border-radius: 2px;
			-border-radius: 2px;
			display:none;
			width:280px;
			
		}

		label.field_label{
			float:left;
			margin-top:8px;
			font-size:12px;
			color:#000;
			font-weight:bold;
			-moz-border-radius: 2px;
			-webkit-border-radius: 2px;
			-border-radius: 2px;
		}

		.edit_field
		{
			cursor: pointer;
			display: block;
			float: left;
			height: 24px;
			margin-left: 10px;
			margin-right: 10px;
			margin-top: 8px;
			width: 23px;
		}
	</style>
	<div id="gmap-tabs">
	  <ul>
		<li><a href="#account-tab">ACCOUNT</a></li>
		<li><a href="#process-tab">PROCESS</a></li>
		<li><a href="#data-tab">DATA</a></li>
		<li><a href="#config-tab">CONFIG</a></li>
		<li><a href="#how-to">HOW TO DISPLAY GMAP</a></li>
	  </ul>
	  <div id="account-tab">
		<form method="post" action="" name="account_form" id="account_form">
			<table cellpadding="0" cellspacing="0" border="0">
				<tbody>
					<tr>
						<td height="30">GMail Username</td>
						<td width="20" align="center">:</td>
						<td><input type="text" name="gmail_username" value="<?php echo $user_detail['gmail_username']; ?>" autocomplete="off" /></td>
					</tr>
					<tr>
						<td height="30">GMail Password</td>
						<td align="center">:</td>
						<td><input type="password" name="gmail_password" value="<?php echo $user_detail['gmail_password']; ?>" autocomplete="off" /></td>
					</tr>
					<?php if(isset($user_detail['spreadsheet_key']) && !empty($user_detail['spreadsheet_key'])) : ?>
					<tr>
						<td height="30">Spreadsheet Key</td>
						<td align="center">:</td>
						<!-- <td><strong><?php //echo $user_detail['spreadsheet_key']; ?></strong></td> -->
						<td><input type="text" name="spreadsheet_key" value="<?php echo $user_detail['spreadsheet_key']; ?>" autocomplete="off" /></td>
					</tr>
					<?php endif; ?>
					<tr>
						<td height="30">&nbsp;</td>
						<td>&nbsp;</td>
						<td><input type="submit" name="login_account" value="Get Spreadsheets" /></td>
					</tr>
				</tbody>
			</table>
		</form>
		<p>&nbsp;</p>
		<?php if(isset($_SESSION['list_spreadsheets']) && !empty($_SESSION['list_spreadsheets'])) : ?>
			<table cellpadding="0" cellspacing="0" border="0" class="display dataTable">
				<thead>
					<tr>
						<th>ID</th>
						<th>SPREADSHEET TITLE</th>
						<th>SPREADSHEET KEY</th>
						<th>OPTION</th>
					</tr>
				</thead>
				<tbody>
				<?php
					/* echo '<pre>';
					print_r($_SESSION['list_spreadsheets']);
					echo '</pre>'; */
					$cnt = 1;
					foreach($_SESSION['list_spreadsheets'] as $key => $value) :
				?>
						<tr>
							<td align="center"><?php echo $cnt;?></td>
							<td align="center"><?php echo $value['spreadsheet_title'];?></td>					
							<td align="center"><?php echo $value['spreadsheet_key'];?></td>					
							<td align="center"><input type="button" class="use_this" name="use_this" value="USE THIS" onclick="use_this_spreadsheet('<?php echo $value['spreadsheet_key'];?>');" /></td>					
						</tr>
				<?php
					$cnt++;
					endforeach;
				?>
				</tbody>
			</table>
		<?php endif; ?>
	  </div>
	  <div id="process-tab">
		<?php if(!empty($user_detail['gmail_username']) && !empty($user_detail['gmail_password']) && !empty($user_detail['spreadsheet_key'])) { ?>
		<form method="post" action="" name="process-form" id="process-form">
			<div class="process_button">
				<input type="submit" id="crawl-spreadsheet" name="crawl_spreadsheet" value="CRAWL SPREADSHEET" />
			</div>
			<div class="clear"></div>
			<div class="process_button">
				<input type="submit" id="update-geolocation" name="update_geolocation" value="GET GEOLOCATION" />
			</div>
			<div class="clear"></div>
			<div class="process_button">
				<input type="submit" id="refresh-data" name="refresh_data" value="REFRESH DATA" />
			</div>
		</form>
		<?php } ?>
	  </div>
	  <div id="data-tab">
		<table cellpadding="0" cellspacing="0" border="0" class="display dataTable" id="example">
			<thead>
				<?php
					$cnt = 0;
					$fields = $gmap_class->get_all_service_fields();
					$get_field_names = (array) $gmap_class->get_gmap_option('gmap_field_names');
					$field_keys = array_keys($get_field_names);
				?>
				<tr>
				<?php
					foreach($fields as $key) :	
					if($cnt < 10) :
				?>
					<th><?php echo in_array($key['Field'], $field_keys) ? $get_field_names[$key['Field']] : $key['Field']; ?></th>
				<?php
					endif;
					$cnt++;
					endforeach;
				?>
					
					<th></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php
				$data = $gmap_class->gmap_index(true);
				
				foreach($data as $key => $value) :
			?>
					<tr>
			<?php
					$j = 0;
					foreach($fields as $key) :	
						if($j < 10) :
			?>
						<td><?php echo $value[$key['Field']]; ?></td>
				<?php
						endif;
						$j++;
					endforeach;
				?>
						<td>
							<a href="#" onclick="edit_data(<?php echo $value['id'];?>,true);">More info...</a>
						</td>
						<td>
							<div>
								<a href="#" onclick="edit_data(<?php echo $value['id'];?>,false);"><img src="<?php echo IMG_PATH; ?>edit.png" alt="Edit" /></a>
							</div>
							<div>
								<a href="#" onclick="delete_data(<?php echo $value['id'];?>);"><img src="<?php echo IMG_PATH; ?>delete.png" alt="Delete" /></a>
							</div>
						</td>
					</tr>
			<?php
				endforeach;
			?>
			</tbody>
		</table>
	  </div>
	  <div id="config-tab">
		<form method="post" action="" name="gmap_display" id="gmap_display">
			<table>
				<tr>
					<td colspan="3"><h2>gMap Display Options</h2></td>
				</tr>
				<?php
					$get_display_options = $gmap_class->get_gmap_option('gmap_display');
					$get_title_desc = (array) $gmap_class->get_gmap_option('gmap_title_desc');
					
					$option = isset($get_display_options) && !empty($get_display_options) ? $get_display_options : '';
				
				?>
				<tr>
					<td><label for="gmap_title">gMap Title</label></td>
					<td align="center">:</td>
					<td><input type="text" name="gmap_title" id="gmap_title" value="<?php echo $get_title_desc['gmap_title']; ?>" /></td>
				</tr>
				<tr>
					<td><label for="gmap_description">gMap Description</label></td>
					<td align="center">:</td>
					<td><input type="text" name="gmap_description" id="gmap_description" value="<?php echo $get_title_desc['gmap_description']; ?>" /></td>
				</tr>
				<tr>
					<td><label for="gmap_width">gMap Width</label></td>
					<td align="center">:</td>
					<td><input type="text" name="gmap_width" id="gmap_width" value="<?php echo $option->width; ?>" /> px</td>
				</tr>
				<tr>
					<td><label for="gmap_gmap_heightwidth">gMap Height</label></td>
					<td align="center">:</td>
					<td><input type="text" name="gmap_height" id="gmap_height" value="<?php echo $option->height; ?>" /> px</td>
				</tr>
				<tr>
					<td><label for="gmap_legend">gMap Show Legend</label></td>
					<td align="center">:</td>
					<td><input type="checkbox" name="gmap_legend" id="gmap_legend" value="1" <?php echo isset($option->show_legend) ? 'checked' : ''; ?> /></td>
				</tr>
				<tr>
					<td><label for="gmap_search">gMap Show Search</label></td>
					<td align="center">:</td>
					<td><input type="checkbox" name="gmap_search" id="gmap_search" value="1" <?php echo isset($option->show_search) ? 'checked' : ''; ?> /></td>
				</tr>
				<tr>
					<td><label for="gmap_search">gMap Show Scroll/Zooming</label></td>
					<td align="center">:</td>
					<td><input type="checkbox" name="gmap_scrollzoom" id="gmap_scrollzoom" value="1" <?php echo isset($option->gmap_scrollzoom) ? 'checked' : ''; ?> /></td>
				</tr>
				<tr>
					<td><label for="gmap_search">gMap On-Hover Marker Show Details</label></td>
					<td align="center">:</td>
					<td><input type="checkbox" name="gmap_hoverdetails" id="gmap_hoverdetails" value="1" <?php echo isset($option->gmap_hoverdetails) ? 'checked' : ''; ?> /></td>
				</tr>
				<tr>
					<td colspan="3">&nbsp;</td>
				</tr>
				<?php
					$fields_name = $gmap_class->get_all_fields_name();
					
					$get_sorted_fields = $gmap_class->get_gmap_option('gmap_fields');
					$get_show_fields = $gmap_class->get_gmap_option('gmap_show_field');
					$get_search_fields = $gmap_class->get_gmap_option('gmap_search_field');
					
					$sort_value = isset($get_sorted_fields) && !empty($get_sorted_fields) ? $get_sorted_fields : '';
					$show_value = isset($get_show_fields) && !empty($get_show_fields) ? $get_show_fields : '';
					$search_value = isset($get_search_fields) && !empty($get_search_fields) ? $get_search_fields : '';
					
					if(!empty($sort_value)) { ?>
				<tr> 
					<td colspan="3"><h2>gMap Spreadsheet Fields Options</h2></td>
				</tr>
				<tr>
					<td valign="top"><strong>Field Name</strong></td>
					<td>&nbsp;</td>
					<td>
						<table cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td width="110" align="center" valign="top"><strong>Sort Number</strong></td>
								<td width="140" align="center" valign="top"><strong>Check to Show</strong><br/><input type="checkbox" id="selectall_show"><label for="selectall_show">Select all</label></td>
								<td align="center" valign="top"><strong>Add to Search Option</strong><br/><input type="checkbox" id="selectall_search" /><label for="selectall_search">Select all</label></td>
							</tr>
						</table>
					</td>
				</tr>
				<?php
					foreach($fields as $key) :	
						if($key['Field'] == 'id')
							continue;
				?>
							<tr class="fields">
								<td height="30" valign="middle">
									<label class="field_label"><strong><?php echo in_array($key['Field'], $field_keys) ? $get_field_names[$key['Field']] : $key['Field']; ?></strong></label><div class="edit_field">(Edit)</div>
									<input type="text" name="field_names[<?php echo $key['Field']; ?>]" class="field_name" value="<?php echo in_array($key['Field'], $field_keys) ? $get_field_names[$key['Field']] : $key['Field']; ?>" />
								</td>
								<td align="center">:</td>
								<td>
									<table cellpadding="0" cellspacing="0" border="0">
										<tr>
											<td align="center" width="110"><input type="text" name="fields[<?php echo $key['Field']; ?>]" value="<?php echo isset($sort_value->$key['Field']) ? $sort_value->$key['Field'] : '' ; ?>" size="2" /></td>
											<td align="center" width="140"><input type="checkbox" name="show_field[<?php echo $key['Field']; ?>]" id="show_field_<?php echo $key['Field']; ?>" class="show_field" value="1" <?php echo isset($show_value->$key['Field']) ? 'checked' : ''; ?> /></td>
											<td align="center" width="140"><input type="checkbox" name="search_field[<?php echo $key['Field']; ?>]" id="show_field_<?php echo $key['Field']; ?>" class="search_field" value="1" <?php echo isset($search_value->$key['Field']) ? 'checked' : ''; ?> /></td>
										</tr>
									</table>
								</td>
							</tr>
				<?php
					endforeach;
				} ?>
				<tr>
					<td>&nbsp;</td>
					<td align="center">&nbsp;</td>
					<td><input type="submit" name="gmap_display_submit" value="Submit" id="gmap_display_submit" /></td>
				</tr>
			</table>
		</form>
	  </div>
	  <div id="how-to">
		<p>Copy this code and paste it into your post, page or text widget content.</p>
		<p><strong>[gmap]</strong></p>
	  </div>
	</div>
	<div id="edit_record" title="Edit Data">
		<form method="post" name="edit_record_form" id="edit_record_form">
			<table cellspacing="0" cellpadding="0" border="0">
				<?php
					foreach($fields as $key) :
						if($key['Field'] == 'id')
							continue;

				?>
						<tr>
							<td><?php echo in_array($key['Field'], $field_keys) ? $get_field_names[$key['Field']] : $key['Field']; ?></td>
							<td align="center" width="20">:</td>
							<td><input type="text" name="<?php echo $key['Field']; ?>" value="" /></td>
						</tr>
				<?php

					endforeach;
				?>
			</table>
		</form>		
		<input type="hidden" value="" name="data_id" id="data_id" />
	</div>
	<div id="more_info" title="More Information">
		<form method="post" name="edit_record_form" id="edit_record_form">
			<table cellspacing="0" cellpadding="0" border="0">
				<?php
					foreach($fields as $key) :
						if($key['Field'] == 'id')
							continue;
				?>
						<tr>
							<td height="30" width="125"><?php echo in_array($key['Field'], $field_keys) ? $get_field_names[$key['Field']] : $key['Field']; ?></td>
							<td align="center" width="20">:</td>
							<td><span class="<?php echo $key['Field']; ?>"></span></td>
						</tr>
				<?php
					endforeach;
				?>
			</table>
		</form>		
		<input type="hidden" value="" name="data_id" id="data_id" />
	</div>
	<div id="delete_record" title="Delete Record">
		<p>
		<span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span>
		These record will be deleted. Are you sure?
		</p>
		<input type="hidden" value="" name="record_id" id="record_id" />
	</div>
	<div id="refresh_gmap_data" title="Refresh Data">
		<p>
		<span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span>
		All the datas from the database will be deleted. Are you sure?
		</p>
	</div>
<?php
}

function get_record() {
	global $wpdb, $gmap_model;
	
	$id = $_REQUEST['id'];
	$data['id'] = $id;
	$data['results'] = $gmap_model->get_record($id);	
	
	echo json_encode($data['results']);
	
	die(); // this is required to return a proper result
}

function delete_record() {
	global $wpdb, $gmap_model;
		
	$id = $_REQUEST['id'];
	$result = $gmap_model->delete_record($id);	
	
	if($result){
		echo 'success';
	}else{
		echo 'failed';
	}
	
	die(); // this is required to return a proper result
}

function update_record(){
	global $wpdb, $gmap_model;
	
	$datas = array();
		
	$id = $_REQUEST['id'];
	$data = $_REQUEST['data_info'];
	
	parse_str($data,$datas);
	
	$result = $gmap_model->update_data($datas, $id);	

	if($result){
		echo 'success';
	}else{
		echo 'failed';
	}
	
	die(); // this is required to return a proper result
}

function update_user_spreadsheet(){
	global $gmap_model;
	
	$sskey = $_REQUEST['sskey'];
	$username = $_REQUEST['username'];
	
	$result = $gmap_model->update_user_spreadsheet($sskey, $username);	
	
	if($result){
		echo 'success';
	}else{
		echo 'failed';
	}
	
	die(); // this is required to return a proper result
}

add_action('wp_ajax_get_record', 'get_record');
add_action('wp_ajax_update_record', 'update_record');
add_action('wp_ajax_delete_record', 'delete_record');
add_action('wp_ajax_update_user_spreadsheet', 'update_user_spreadsheet');