<?php 
function gmap_load_results($results, $string, $search_by = '') { 
	global $gmap_model;

	$default_field = $gmap_model->get_default_search_field();

	$html = '';
	if(count($results)):
		$html .= '<ul>';
			foreach($results as $geo): 
				$client = empty($search_by) ? '<span>' . $geo[$default_field]. '</span>' : substr_replace($geo[$search_by], '<span>' . $string. '</span>', 0, strlen($string));
				$html .= '<li id="client_'.$geo['id'].'" class="map-result" onClick="showInfoWindow('.$geo['id'].');">'.$client.'</li>';
			endforeach; 
		$html .= '</ul>';
	else: 
		$html .= 'No Result Found.';
	endif; 
	
	return $html;
 } 