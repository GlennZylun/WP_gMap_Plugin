<?php
global $wpdb, $gmap_model, $gmap_class, $db_charset, $db_collate;

$gmap_model = new gMapModel();
$gmap_class = new gMapClass();

if(!defined('DB_CHARSET') || !($db_charset = DB_CHARSET))
	$db_charset = 'utf8';
$db_charset = "CHARACTER SET ".$db_charset;
if(defined('DB_COLLATE') && $db_collate = DB_COLLATE) 
	$db_collate = "COLLATE ".$db_collate;

$siteurl = get_bloginfo('url');

$get_user_detail = $gmap_class->get_user_detail();

$user_detail = isset($get_user_detail) && !empty($get_user_detail) ? $get_user_detail : array();

define('SITE_URL', $siteurl);
define('GMAP_FOLDER', dirname(plugin_basename(__FILE__))); // gMap folder/directory name
define('GMAP_URL', SITE_URL .'/wp-content/plugins/' . GMAP_FOLDER); // gMap base name
define('GMAP_FILE_PATH', dirname(__FILE__)); // gMap file path directory

define('TABLE_GMAP_SERVICE_CLIENTS', 'gmap_service_clients'); // Database Table Service Clients
define('TABLE_GMAP_USER', 'gmap_user'); // Database Table User
define('TABLE_GMAP_OPTIONS', 'gmap_options'); // Database Table User
define('TABLE_GMAP_USER_SPREADSHEETS', 'gmap_user_spreadsheets'); // Database Table User SpreadSheets

define('WP_GMAP_SERVICE_CLIENTS', $wpdb->prefix . TABLE_GMAP_SERVICE_CLIENTS); // WP Database Table Service Clients
define('WP_GMAP_USER', $wpdb->prefix . TABLE_GMAP_USER); // WP Database Table Service User
define('WP_GMAP_OPTIONS', $wpdb->prefix . TABLE_GMAP_OPTIONS); // WP Database Table Service User
define('WP_GMAP_USER_SPREADSHEETS', $wpdb->prefix . TABLE_GMAP_USER_SPREADSHEETS); // WP Database Table User SpreadSheets

define('GETSTAT_API_KEY', '3a978800b9f6d57f31788af85c752907b5bfc519');
define('GETSTAT_API_URL', 'http://app.getstat.com/api/v2/' . GETSTAT_API_KEY);
define('GMAP_API', 'AIzaSyBoIANdNyIbILkzd-ethlKMpTiCtxY2Dic');

/*define('GMAIL_UNAME', 'rebesa@consultwebs-email.com');
define('GMAIL_PASSWORD', '1218password');
define('SSID', 'tJVQNr1hCKx3kAKe-mn-GWw');*/

define('GMAP_CRAWL', 'http://maps.googleapis.com/maps/api/geocode/json?address=xx&sensor=false');
define('IMG_PATH', GMAP_URL.'/view/images/');
define('IMG_DIR', GMAP_URL.'/view/images/');

define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');