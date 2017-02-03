<?php

function gmap_load_sites($results) {
	global $gmap_class;
	$get_show_fields = 	$gmap_class->get_gmap_option('gmap_show_field');
	$get_field_names = (array) $gmap_class->get_gmap_option('gmap_field_names');
	$field_show = isset($get_show_fields) && !empty($get_show_fields) ? (array) $get_show_fields : $get_field_names;
	$show_fields = array_keys($field_show);
	
	$html = '';
	
	if(count($results)): 
		$counter = 1; 
		
		foreach($results as $geo): 

			foreach($geo as $key => $value): 
				if($key == "contactemail") {
					$geo[$key] = $value ? '<a href="mailto:' . $value . '">' . $value . '</a>' : '<span>No Value</span>';
				} elseif($key == "website") {
					$value = preg_replace('@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', '<a href="http://$1" target="blank">$1</a>', $value);
					$geo[$key] = $value;
				}
			endforeach;
			
			$html .= @extract($geo);
		
			if($client == '<span>No Value</span>') continue;	
			
			$fields = $gmap_class->get_all_service_fields();
	
			$html .= '<tr id="siteid_'.$id.'">';
			$html .= '<td class="frow" align="center">'.$counter.'</td>';
			
			foreach($fields as $key) :
				if($key['Field'] == 'id')
					continue;
				
				if(in_array($key['Field'], $show_fields)) :
					$html .= '<td>'.$geo[$key['Field']].'</td>';
				endif;
				
			endforeach;

			$html .= '</tr>';
			
			$counter++; 
		 endforeach;
	else:
		$html .= '<tr><td colspan="35">';
		$html .= 'No Result Found.';
		$html .= '</td></tr>';
	endif; 
 
 return $html;
} 