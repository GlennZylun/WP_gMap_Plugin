<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	global $gmap_class, $field_description;
	
	$data = array();
	$fields_sort_not_empty = array();
	$fields_sort_empty = array();
	
	$data = $gmap_class->gmap_index();
	$fields = $gmap_class->get_all_service_fields();
	
	$option = $gmap_class->get_gmap_option('gmap_display');
	
	$get_title_desc = (array) $gmap_class->get_gmap_option('gmap_title_desc');
	
	$get_sort_fields = 	$gmap_class->get_gmap_option('gmap_fields');
	$get_show_fields = 	$gmap_class->get_gmap_option('gmap_show_field');
	$get_search_fields = 	$gmap_class->get_gmap_option('gmap_search_field');
	$get_field_names = (array) $gmap_class->get_gmap_option('gmap_field_names');
	$field_keys = array_keys($get_field_names);

	$field_sort = isset($get_sort_fields) && !empty($get_sort_fields) ? (array) $get_sort_fields : $get_field_names;
	$field_show = isset($get_show_fields) && !empty($get_show_fields) ? (array) $get_show_fields : $get_field_names;
	$field_search = isset($get_search_fields) && !empty($get_search_fields) ? (array) $get_search_fields : '';
	$show_fields = array_keys($field_show);
	
	if(isset($get_sort_fields) && !empty($get_sort_fields)){
		$fields_sort_not_empty = array_filter($field_sort);
		$fields_sort_empty = array_diff($field_sort, array_filter($field_sort));
		asort($fields_sort_not_empty);
	}
		
	$field_sort = array_merge($fields_sort_not_empty, $fields_sort_empty);

	$gmap_width = isset($option->width) && !empty($option->width) ? $option->width.'px;' : '0;';
	$gmap_height = isset($option->height) && !empty($option->height) ? $option->height.'px;' : '0;';
	
	$map_canvas_width = isset($option->gmap_hoverdetails) && !empty($option->gmap_hoverdetails) ? '70%;float:left;' : '100%';

?>
<style type="text/css">
	<?php
		if(!isset($option->show_legend)) :
	?>
		#legend{display:none;}
	<?php
		endif;
		
		if(!isset($option->show_search)) :
	?>
		#panel{display:none;}
		#field-input{display:none;}
		#legend{ top:10px !important;}
	<?php
		else :
	?>
		#legend{ top:50px !important;}
	<?php
		endif;
		
		if(isset($option->gmap_scrollzoom)) :
	?>
		#legend{ left:75px !important; }
		#panel { left:75px !important; }
	<?php
		endif;
	?>
</style>
<script type="text/javascript">
	var map;
	var marker = new Array();
	var infowindow = new Array();
	var hover_infowindow = new Array();
	var currentInfoWindow = null;
	var accurCircle = new Array();
	
	var $j = jQuery.noConflict(); 

	var SITE_URL = "http://" + window.location.host + "/";

	function initialize() {	
		var myLatlng = new google.maps.LatLng(43.363882,-90.044922);		
		var mapOptions = {
			zoom: 4,
			center: myLatlng,
			<?php echo !isset($option->gmap_scrollzoom) ? 'panControl: false,zoomControl: false,streetViewControl: false, ' : ''; ?>
			scaleControl: false,
			mapTypeId: google.maps.MapTypeId.TERRAIN,
		}
		
		//var legend = document.createElement('div');
		//legend.id = 'site-data';
		var site_content = [];		
		map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);		
		<?php if(count($data)): ?>
		<?php $pin_count = array('f8971b' => 0, '3f5ba9' => 0, 'ffffff' => 0, 'ffdd5e' => 0); ?>
		<?php $pin_list = array('f8971b' => array(), '3f5ba9' => array(), 'ffffff' => array(), 'ffdd5e' => array());  ?>
		<?php $marker_color = array('rofr' => 'f8971b', 'none' => '3f5ba9', 'no value' => 'ffffff', 'exclusive' => 'ffdd5e'); ?>
		<?php $cnt = 1; ?>
		<?php $marker_icon = array('rofr' => IMG_PATH . 'rofr.png', 'none' => IMG_PATH . 'none.png', 'no value' => IMG_PATH . 'other.png', 'exclusive' => IMG_PATH . 'exclusive.png'); ?>
		<?php foreach($data as $geo): ?>
			
			<?php 
				foreach($geo as $key => $value): 
					if($key == "contactemail") {
						$geo[$key] = '<a href="mailto:' . $value . '">' . $value . '</a>';
					} elseif($key == "website") {
						$value = preg_replace('@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', '<a href="http://$1" target="blank">$1</a>', $value);
						$geo[$key] = $value;
					}else {
						if($key == "otherinfo") continue;
						$value = str_replace(array("\n","\r"), "", strip_tags($value));
						$value = str_replace("'", "`", $value);
						$geo[$key] = $value ? $value : '<span>No Value</span>';
						$store_geo[$key] = $value;
					}
				endforeach;

				//$exclusivity = isset($geo['exclusivity']) ? strtolower(strip_tags($geo['exclusivity'])) : 'none';
				$exclusivity = 'none';
				$color = $marker_color[$exclusivity];
				$pin_list[$color][] = array('id' => $geo['id'], 'lat' => $geo['latitude'], 'lng' => $geo['longitude']);
				$pin_count[$color] = isset($pin_count[$color]) ? ($pin_count[$color]) + 1 : 1;
			?>

			var pinColor = "<?php echo $marker_icon[$exclusivity]; ?>";
			var pinImage = new google.maps.MarkerImage(pinColor,	new google.maps.Size(36, 38),new google.maps.Point(0,0),new google.maps.Point(16, 38));
			var pinShadow = new google.maps.MarkerImage("http://chart.apis.google.com/chart?chst=d_map_pin_shadow",new google.maps.Size(40, 37),new google.maps.Point(0, 0),new google.maps.Point(12, 35));
			
			var location = new google.maps.LatLng(<?php echo $geo['latitude']; ?>, <?php echo $geo['longitude']; ?>);
			marker[<?php echo $geo['id']; ?>] = new google.maps.Marker({
				position: location,
				map: map,
				icon: pinImage,
                shadow: pinShadow
			});
			marker[<?php echo $geo['id']; ?>].setTitle('<?php echo isset($geo['client']) ? (string) $geo['client'] : ''; ?>');
			marker[<?php echo $geo['id']; ?>].set("type", "<?php echo ($exclusivity == "no value" ? 'other' : $exclusivity); ?>");
						
			var contentString = '<div id="infoContent">';
		
			contentString += '	<h1><?php echo isset($geo['client']) ? $geo['client'] : ''; ?></h1>';
			<?php
				foreach($field_sort as $key => $value) :
					if(in_array($key, $show_fields)) :
			?>
					contentString += '	<div id="map-infowindow-attr-area">';
					<?php
						if(isset($store_geo[$key]) && !empty($store_geo[$key])) :
					?>
						contentString += '		<div class="left label"><?php echo in_array($key, $field_keys) ? $get_field_names[$key] : $key; ?></div><div class="left value"><?php echo $geo[$key]; ?></div>';
					<?php
						endif;
					?>
					contentString += '	</div>';	
			<?php
					endif;
				endforeach;
			?>
			
			contentString += '</div>';

			infowindow[<?php echo $geo['id']; ?>] = new google.maps.InfoWindow({ 
					content: contentString,
					maxWidth:500,
					title: "<?php echo isset($geo['client']) ? $geo['client'] : ''; ?>"					
			});
			
			hover_infowindow[<?php echo $geo['id']; ?>] = contentString;
			
			google.maps.event.addListener(marker[<?php echo $geo['id']; ?>], 'click', function() {
				if (currentInfoWindow != null) { currentInfoWindow.close(); } 
				infowindow[<?php echo $geo['id']; ?>].close();
				infowindow[<?php echo $geo['id']; ?>].open(map,marker[<?php echo $geo['id']; ?>]);
				currentInfoWindow = infowindow[<?php echo $geo['id']; ?>]; 
			});
			
			google.maps.event.addListener(marker[<?php echo $geo['id']; ?>], 'mouseover', function(ev) {
				$j(".marker-info").html(hover_infowindow[<?php echo $geo['id']; ?>]);
				$j(".marker-info").css("border","1px solid #CCCCCC");
				$j(".marker-info").css("background","#FFFFFF");
				var colorPin = "<?php echo $marker_icon[$exclusivity]; ?>";
				colorPin = colorPin.replace('.png', '_selected.png');
				var newPin = new google.maps.MarkerImage(colorPin, new google.maps.Size(36, 38),new google.maps.Point(0,0),new google.maps.Point(16, 38));
				marker[<?php echo $geo['id']; ?>].setIcon(newPin);
			});
			
			google.maps.event.addListener(marker[<?php echo $geo['id']; ?>], 'mouseout', function(ev) { 
				$j(".marker-info").html('');
				$j(".marker-info").css("border","none");
				$j(".marker-info").css("background","transparent");
				var type = marker[<?php echo $geo['id']; ?>].get("type");
				if(!$j("#legend #" + type).hasClass("selected")) {
					var colorPin = "<?php echo $marker_icon[$exclusivity]; ?>";
					colorPin = colorPin.replace('._selected.png', '.png');
					var newPin = new google.maps.MarkerImage(colorPin, new google.maps.Size(36, 38),new google.maps.Point(0,0),new google.maps.Point(16, 38));
					marker[<?php echo $geo['id']; ?>].setIcon(newPin);
				}
			});
			
			google.maps.event.addListener(infowindow[<?php echo $geo['id']; ?>], 'domready', function() {
				jQuery("#infoContent").parent().parent().prev().addClass("my-custom-infowindow");
				jQuery("#infoContent").parent().parent().parent().css({width:"360px"});
				jQuery("#infoContent").parent().parent().css({width:"320px"});
			});
			
		<?php endforeach; ?>
		
		google.maps.event.addListener(map, 'click', function() {
			if(currentInfoWindow != null) { 
				currentInfoWindow.close(); 
			}
		});
		
	 	var legend = document.createElement('div');
		legend.id = 'legend';
		var content = [];		
		content.push('<h1><?php echo $get_title_desc['gmap_title']; ?></h1>');
		content.push('<p><?php echo $get_title_desc['gmap_description']; ?></p>');
		content.push('<div class="data-show"><a href="javascript:void(0);" onclick="showData();">Show Data</a></div>');
		legend.innerHTML = content.join('');
		legend.index = 1;
		map.controls[google.maps.ControlPosition.LEFT_TOP].push(legend); 
		
		$j('#searchTextField').keyup(function() {
			var string = $j(this).val();
			var panel_search_by = $j("#panel_search_by").val();
			
			var search_text = {
				action: 'ajax_search',
				string: string,
				search_by: panel_search_by
			};

			if(string) {
				$j.post(SITE_URL + 'wp-admin/admin-ajax.php', search_text, function(response) {
					if(response.result == 1) {
						console.log(response.output);
						$j('#gmap_search_results').html(response.output);
					} else {
						console.log('error');
					}
				}, 'json');
			} else {
				$j('#gmap_search_results').html('');
			}

		});		
		<?php endif; ?>
	}
	<?php if(count($data)): ?>
	function selected_out(_id_) {
		if(_id_) {
			$j("#legend li.selected").removeClass("selected");
			if(_id_ == "none") {
				showNone_selected($j("#legend li#none"), "out");
			} else if(_id_ == "exclusive") {
				showExclusive_selected($j("#legend li#exclusive"), "out");
			} else if(_id_ == "rofr") {
				showRofr_selected($j("#legend li#rofr"), "out");
			} else if(_id_ == "other") {
				showOther_selected($j("#legend li#other"), "out");
			} else if(_id_ == "all") {
				showAll_selected($j("#legend li#all"), "out");
			}
		}
		<?php foreach($data as $geo): ?>
		marker[<?php echo $geo['id']; ?>].setMap(null);
		<?php endforeach; ?>
	}
	
	function showAll_selected(obj, type) {
		console.log(type);
		if(!$j(obj).hasClass('selected')) {
			if(type == "hover" || type == "click") {
				if(type == "click") $j("#legend li.selected").removeClass("selected");
				showNone_selected($j("#legend li#none"), "hover");
				showExclusive_selected($j("#legend li#exclusive"), "hover");
				showRofr_selected($j("#legend li#rofr"), "hover");
				showOther_selected($j("#legend li#other"), "hover");
				if(type == "click") {
					
					$j(obj).addClass('selected');
				}
			} else {
				showNone_selected($j("#legend li#none"), "out");
				showExclusive_selected($j("#legend li#exclusive"), "out");
				showRofr_selected($j("#legend li#rofr"), "out");
				showOther_selected($j("#legend li#other"), "out");
			}
		} else {
			if(type == "click") {
				$j("#legend li.selected").removeClass("selected");
				showNone_selected($j("#legend li#none"), "out");
				showExclusive_selected($j("#legend li#exclusive"), "out");
				showRofr_selected($j("#legend li#rofr"), "out");
				showOther_selected($j("#legend li#other"), "out");
			}			
		}
	}
	
	function back_to_normal() {
		showNone_selected($j("#legend li#none"), "out");
		showExclusive_selected($j("#legend li#exclusive"), "out");
		showRofr_selected($j("#legend li#rofr"), "out");
		showOther_selected($j("#legend li#other"), "out");
	}
	  
	function showNone_selected(obj, type) {
		var colorPin = "<?php echo IMG_PATH . 'none.png'; ?>";
		var zIndex = google.maps.Marker.MAX_ZINDEX;
		var _id_ = $j("#legend li.selected").length ? $j("#legend li.selected").attr("id") : false;
		if(_id_ != false && type != "click") return;

		if(!$j(obj).hasClass('selected')) {
			if(type == "click" || type == "hover") {				
				if(type == "click") {					
					selected_out(_id_);
					$j(obj).addClass('selected');
				}
				colorPin = colorPin.replace('none', 'none_selected');
				 zIndex = google.maps.Marker.MAX_ZINDEX + 1;
			}
			var newPin = new google.maps.MarkerImage(colorPin, new google.maps.Size(36, 38),new google.maps.Point(0,0),new google.maps.Point(16, 38));
			<?php foreach($pin_list['3f5ba9'] as $data): ?>
			marker[<?php echo $data['id']; ?>].setIcon(newPin);
			marker[<?php echo $data['id']; ?>].setZIndex(zIndex);
			marker[<?php echo $data['id']; ?>].setMap(map);
			<?php endforeach; ?>
		} else {
			if(type == "click") {
				$j("#legend li.selected").removeClass("selected");
				back_to_normal();				
				showNone_selected($j("#legend li#none"), "out");
			}
		}

	}
	  
	  function showExclusive_selected(obj, type) {
		var colorPin = "<?php echo IMG_PATH . 'exclusive.png'; ?>";
		var zIndex = google.maps.Marker.MAX_ZINDEX;
		var _id_ = $j("#legend li.selected").length ? $j("#legend li.selected").attr("id") : false;
		if(_id_ != false && type != "click") return;
		if(!$j(obj).hasClass('selected')) {
			if(type == "click" || type == "hover") {
				if(type == "click") {
					selected_out(_id_);
					$j(obj).addClass('selected');
				}
				colorPin = colorPin.replace('exclusive', 'exclusive_selected');
				 zIndex = google.maps.Marker.MAX_ZINDEX + 1;
			}
			var newPin = new google.maps.MarkerImage(colorPin, new google.maps.Size(36, 38),new google.maps.Point(0,0),new google.maps.Point(16, 38));
			<?php foreach($pin_list['ffdd5e'] as $data): ?>
			marker[<?php echo $data['id']; ?>].setIcon(newPin);
			marker[<?php echo $data['id']; ?>].setZIndex(zIndex);
			marker[<?php echo $data['id']; ?>].setMap(map);
			<?php endforeach; ?>
		}else {
			if(type == "click") {
				$j("#legend li.selected").removeClass("selected");
				back_to_normal();
				showExclusive_selected($j("#legend li#exclusive"), "out");
			}
		}
	  }
	  
	  function showRofr_selected(obj, type) {
		var colorPin = "<?php echo IMG_PATH . 'rofr.png'; ?>";
		var zIndex = google.maps.Marker.MAX_ZINDEX;
		var _id_ = $j("#legend li.selected").length ? $j("#legend li.selected").attr("id") : false;
		if(_id_ != false && type != "click") return;
		if(!$j(obj).hasClass('selected')) {
			if(type == "click" || type == "hover") {
				if(type == "click") {
					selected_out(_id_);
					$j(obj).addClass('selected');
				}
				colorPin = colorPin.replace('rofr', 'rofr_selected');
				 zIndex = google.maps.Marker.MAX_ZINDEX + 1;
			}
			var newPin = new google.maps.MarkerImage(colorPin, new google.maps.Size(36, 38),new google.maps.Point(0,0),new google.maps.Point(16, 38));
			<?php foreach($pin_list['f8971b'] as $data): ?>
			marker[<?php echo $data['id']; ?>].setIcon(newPin);
			marker[<?php echo $data['id']; ?>].setZIndex(zIndex);
			marker[<?php echo $data['id']; ?>].setMap(map);
			<?php endforeach; ?>
		} else {
			if(type == "click") {
				$j("#legend li.selected").removeClass("selected");
				back_to_normal();
				showRofr_selected($j("#legend li#rofr"), "out");
			}
		}
	  }
	  
	  function showOther_selected(obj, type) {
		var colorPin = "<?php echo IMG_PATH . 'other.png'; ?>";
		var zIndex = google.maps.Marker.MAX_ZINDEX;
		var _id_ = $j("#legend li.selected").length ? $j("#legend li.selected").attr("id") : false;
		if(_id_ != false && type != "click") return;
		if(!$j(obj).hasClass('selected')) {
			if(type == "click" || type == "hover") {
				if(type == "click") {
					selected_out(_id_);
					$j(obj).addClass('selected');
				}
				colorPin = colorPin.replace('other', 'other_selected');
				 zIndex = google.maps.Marker.MAX_ZINDEX + 1;
			}
			var newPin = new google.maps.MarkerImage(colorPin, new google.maps.Size(36, 38),new google.maps.Point(0,0),new google.maps.Point(16, 38));
			<?php foreach($pin_list['ffffff'] as $data): ?>
			marker[<?php echo $data['id']; ?>].setIcon(newPin);
			marker[<?php echo $data['id']; ?>].setZIndex(zIndex);
			marker[<?php echo $data['id']; ?>].setMap(map);
			<?php endforeach; ?>
		} else {
			if(type == "click") {
				$j("#legend li.selected").removeClass("selected");
				back_to_normal();
				showOther_selected($j("#legend li#other"), "out");
			}
		}
	  }
	  
	  function showInfoWindow(geo_id) {
		if (currentInfoWindow != null) { currentInfoWindow.close(); } 
		infowindow[geo_id].close();
		infowindow[geo_id].open(map,marker[geo_id]);
		currentInfoWindow = infowindow[geo_id]; 
		$j('#searchTextField').val($j('#client_' + geo_id).text());
		$j('#gmap_search_results').html('');
	  }
	  
	  function showData() {
		$j('#site-data').css("display","block");
	  }
	  <?php endif; ?>
	$j(function() {
	google.maps.event.addDomListener(window, 'load', initialize);
	});
	<?php if(!count($data)): ?>
	alert('System is still processing data. Please try again later');
	<?php endif; ?>
	
	$j(function() {
		$j('#site-data span.close').click(function() {
			$j('#site-data').css('display', 'none');
		});
		
		var data1 = {
			action: 'display_sites',
			string: ''
		};
				
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		$j.post(SITE_URL + 'wp-admin/admin-ajax.php', data1, function(response) {
			$j('#site-data table tbody').html(response.output);
			$j('#site-data table tbody tr td').click(function() {
				$j('#site-data table tbody tr.selected').removeClass('selected');
				$j(this).parent().addClass('selected');
				var geo_id = $j(this).parent().attr("id").replace("siteid_", "");
				if (currentInfoWindow != null) { currentInfoWindow.close(); } 
				infowindow[geo_id].close();
				infowindow[geo_id].open(map,marker[geo_id]);
				currentInfoWindow = infowindow[geo_id]; 
			});
		}, 'json');
		

		$j('#findme').keyup(function() {
			var string = $j.trim($j(this).val());
			var data_search_by = $j("#data_search_by").val();
			
			var data2 = {
				action: 'display_sites',
				string: string,
				search_by: data_search_by
			};

			$j.post(SITE_URL + 'wp-admin/admin-ajax.php', data2, function(response) {
				$j('#site-data table tbody').html(response.output);
				$j('#site-data table tbody tr td').click(function() {
					$j('#site-data table tbody tr.selected').removeClass('selected');
					$j(this).parent().addClass('selected');
					var geo_id = $j(this).parent().attr("id").replace("siteid_", "");
					if (currentInfoWindow != null) { currentInfoWindow.close(); } 
					infowindow[geo_id].close();
					infowindow[geo_id].open(map,marker[geo_id]);
					currentInfoWindow = infowindow[geo_id]; 
				});
			}, 'json');

		});
	});
</script>
<div style="position:relative;<?php echo 'width:'. $gmap_width.';height:'.$gmap_height;?>">
	<div id="panel">
		<?php
			if(!empty($field_search)) {
		?>
			<label for="search_by">Search By : </label>
			<select id="panel_search_by" name="panel_search_by" style="padding: 6px;">
		<?php
			foreach($field_search as $key => $value) {
				if(in_array($key, $show_fields)) :
		?>
				<option value="<?php echo $key; ?>"><?php echo in_array($key, $field_keys) ? $get_field_names[$key] : $key; ?></option>
		<?php
				endif;
			}
		?>	
			</select>
		<?php
			} else {
		?>
			<label for="searchTextField">Search : </label>
		<?php
			}
		?>
		<input type="text" name="search" id="searchTextField" style="<?php echo !empty($field_search) ? 'width:190px;' : ''; ?>" />
		<div id="gmap_search_results"></div>
	</div>

	<div id="site-data">
		<h3><?php echo $get_title_desc['gmap_title']; ?></h3>
		<span class="close">close</span>
		<div id="field-input">
			<?php
			if(!empty($field_search)) {
			?>
				<label for="search_by">Search By : </label>
				<select id="data_search_by" name="data_search_by" style="padding: 6px;">
			<?php
				foreach($field_search as $key => $value) {
					if(in_array($key, $show_fields)) :
			?>
					<option value="<?php echo $key; ?>"><?php echo in_array($key, $field_keys) ? $get_field_names[$key] : $key; ?></option>
			<?php
					endif;
				}
			?>	
				</select>
			<?php
				}
			?>
			<input type="text" placeholder="Search..." value="" name="findme" id="findme"/>
		</div>
		<div class="table-data">
			<table class="ctable" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th>&nbsp;</th>
						<?php
							foreach($fields as $key) :	
								if($key['Field'] == 'id')
									continue;
									
								if(in_array($key['Field'], $show_fields)) :
						?>
						<th><?php echo in_array($key['Field'], $field_keys) ? $get_field_names[$key['Field']] : $key['Field']; ?></th>	
						<?php
								endif;
							endforeach;
						?>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
	</div>

	<div class="map_container" style="<?php echo 'width:'. $gmap_width.'height:'.$gmap_height;?>">
		<div id="map-canvas" class="marginA" style="width: <?php echo $map_canvas_width; ?>"></div>
		<?php if(isset($option->gmap_hoverdetails) && !empty($option->gmap_hoverdetails)) { ?>
			<div class="marker-info" style="height: <?php echo $gmap_height; ?>"></div>
		<?php } ?>
	</div>
</div>