<?php
/*
	Plugin Name: gMap
	Description: Google Map Integration
	Version: 1.0
	Author: Glenn G. Rible
	License: GPL2
*/

set_include_path(implode(PATH_SEPARATOR, array(
					get_include_path(),
					dirname(__FILE__),
					dirname(__FILE__) . '/libraries',
				)));

require_once 'libraries/Zend/Loader.php';
require_once 'model/gMap-model.php';
require_once 'controller/gMapClass.php';
require_once 'gMap-constants.php';
require_once 'view/gMap-admin.php';
require_once 'view/gMap-sites.php';
require_once 'view/gMap-result.php';
 
Zend_Loader::loadClass('Zend_Gdata_Spreadsheets');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');	
			
$gMap_version = '1.0';

function gmap_install()
{
	global $db_charset, $db_collate;
	
	if(!create_tbl_service_clients($db_charset, $db_collate)){
		echo 'Failed to create database table.';
	}
	
	if(!create_tbl_gmap_user($db_charset, $db_collate)){
		echo 'Failed to create database table.';
	}
	
	if(!create_tbl_gmap_options($db_charset, $db_collate)){
		echo 'Failed to create database table.';
	}
		
	global $gmap_db_version;
	$options = get_option('gmap');
	$options['db_version'] = $gmap_db_version;
	update_option('gmap', $options);
}

function create_tbl_service_clients($db_charset, $db_collate) {
	global $wpdb;

	// if table name already exists
	if($wpdb->get_var("SHOW TABLES LIKE '".WP_GMAP_SERVICE_CLIENTS."'") != WP_GMAP_SERVICE_CLIENTS) {
		//$wpdb->query("DROP TABLE ".WP_GMAP_SERVICE_CLIENTS);
		//Creating the table wp_gmap_service_clients... fresh!
		$sql = "CREATE TABLE " . WP_GMAP_SERVICE_CLIENTS . " (
				`id` int(25) NOT NULL AUTO_INCREMENT,                    
			PRIMARY KEY  (id)
		) {$db_charset} {$db_collate};";
		$results = $wpdb->query( $sql );
		if($results){
			return true;
		}
	} else {
		return true;
	}
	
}

function create_tbl_gmap_user($db_charset, $db_collate){
	global $wpdb;
	$success = false;
	
	if($wpdb->get_var("SHOW TABLES LIKE '".WP_GMAP_USER."'") != WP_GMAP_USER) {
		//$wpdb->query("DROP TABLE ".WP_GMAP_USER);
		//Creating the table wp_gmap_user... fresh!
		$sql = "CREATE TABLE " . WP_GMAP_USER . " (
				`id` int(11) NOT NULL AUTO_INCREMENT,                                       
				`gmail_username` varchar(100) NOT NULL,
				`gmail_password` varchar(200) NOT NULL,
				`spreadsheet_key` varchar(200) NOT NULL,
			PRIMARY KEY (id)
		) {$db_charset} {$db_collate};";
		
		$results = $wpdb->query( $sql );
		if($results){
			return true;
		}
	} else {
		return true;
	}
}

function create_tbl_gmap_options($db_charset, $db_collate){
	global $wpdb;
	$success = false;
	
	if($wpdb->get_var("SHOW TABLES LIKE '".WP_GMAP_OPTIONS."'") != WP_GMAP_OPTIONS) {
		//$wpdb->query("DROP TABLE ".WP_GMAP_OPTIONS);
		//Creating the table wp_gmap_user... fresh!
		$sql = "CREATE TABLE " . WP_GMAP_OPTIONS . " (
				`option` varchar(100) NOT NULL,
				`value` text NOT NULL
		) {$db_charset} {$db_collate};";
		
		$results = $wpdb->query( $sql );
		
		if($results){
			return true;
		}
	} else {
		return true;
	}

}

function gmap_shortcode(){
	require_once 'view/gMap-view.php';	
}

add_shortcode('gmap', 'gmap_shortcode');

function gmap_view_ui(){
	wp_enqueue_script('gmap-maps-api', 'http://maps.googleapis.com/maps/api/js?libraries=places&sensor=false');
	wp_enqueue_script('gmap-infobox', 'http://google-maps-utility-library-v3.googlecode.com/svn/trunk/infobox/src/infobox.js');
	wp_enqueue_style('gmap-ui', GMAP_URL.'/view/css/gmap.css');
}

function initialized() {
	wp_enqueue_script('jquery');   
  
	if(!session_id()){
		session_start();
	}
}

add_action('init', 'initialized', 1);
add_action( 'wp_enqueue_scripts', 'gmap_view_ui' );

add_action('wp_ajax_display_sites', 'display_sites');
add_action('wp_ajax_ajax_search', 'ajax_search');


function display_sites() {
	global $wpdb, $gmap_model;

	$string = $_REQUEST['string'];
	$search_by = isset($_REQUEST['search_by']) && !empty($_REQUEST['search_by']) ? $_REQUEST['search_by'] : '';
	
	$data['string'] = $string;
	$data['results'] = $gmap_model->load_sites(strtolower($string), $search_by);
	$html = gmap_load_sites($data['results']);	
	$return = array('result' => 1, 'output' => $html);
	//Out to JSON
	echo json_encode($return);		
	
	die(); // this is required to return a proper result
}

function ajax_search() {
	global $wpdb, $gmap_model;
	
	$string = $_REQUEST['string'];
	$search_by = isset($_REQUEST['search_by']) && !empty($_REQUEST['search_by']) ? $_REQUEST['search_by'] : '';
	$data['string'] = $string;
	$data['results'] = $gmap_model->search_map(strtolower($string), $search_by);
	$html = gmap_load_results($data['results'], $string, $search_by);
	$return = array('result' => 1, 'output' => $html);
	//Out to JSON
	echo json_encode($return);		
	
	die(); // this is required to return a proper result
}


register_activation_hook( __FILE__, 'gmap_install' );