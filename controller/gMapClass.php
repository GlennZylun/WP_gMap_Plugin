<?php
if( !class_exists('gMapClass') ) :

	class gMapClass {
	
		var $exist_latitude = false;
		
		var $exist_longitude = false;
		
		var $exist_zip_code = false;
		
		var $exist_address = false;
		
		var $exist_city = false;
		
		var $exist_state = false;
		
		public $spreadsheet_title = '';
	
		/**
		 * Get the Service Clients Lists
		 *
		 * @access public
		 * @return array
		 */
		public function gmap_index() {
			global $gmap_model;
			$data = array();
			$data = $gmap_model->get_lists();
			
			return $data;
		}
		
		/**
		 * Update the Service Clients Longitude and Latitude
		 *
		 * @access public
		 * @return boolean
		 */
		public function update_long_lang() {
			global $gmap_model;
			//get data
		
			try {
				$success = true;

				$site_services = $gmap_model->get_lists_nolatlng($this->exist_zip_code);
				
				foreach($site_services as $data):
					$zip_code = isset($data['zipcode']) ? $data['zipcode'] : '';
					$address = urlencode( $zip_code . $data['address'] . ', ' .  $data['city'] . ', ' . $data['state']);
					$url = str_replace('xx', $address, GMAP_CRAWL);
					$return = file_get_contents($url);
					$jsondata = json_decode($return,true);
					if($jsondata['status']=="OK"){
						$prepare_data = array('latitude' => $jsondata['results'][0]['geometry']['location']['lat'], 'longitude' => $jsondata['results'][0]['geometry']['location']['lng']);
						$update = $gmap_model->update_data($prepare_data, $data['id']);
						if(!$update){
							$success = false;
						}
					}
				endforeach;
				
				return $success;
			} catch (Exception $e) {
				die('ERROR: There\'s a problem getting Geo Location. Please refresh the browser.');
			}

		}
		
		/**
		 * Get the Lists of User Spreadsheets
		 *
		 * @access public
		 * @return array
		 */
		public function get_spreadsheets($cckey = '') {
			$user_detail = $this->get_user_detail();
			$list_spreadsheets = array();
			$list_spreadsheets2 = array();
		  try {  
			   // connect to API
			   $service = Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME;
			   $client = Zend_Gdata_ClientLogin::getHttpClient($user_detail['gmail_username'], $user_detail['gmail_password'], $service);
			   $service = new Zend_Gdata_Spreadsheets($client);
			   $feed = $service->getSpreadsheetFeed();
			   $cnt = 0;
			   
			   foreach($feed as $entry):
					$links = $entry->getLink();
					$titles = $entry->getTitle();
					$ssurl = $links[1]->getHref();
					$sstitle = $titles->getText();
					$get_key = $this->_get_string_after($ssurl,'key=');
					
					if(!empty($cckey) && $cckey == $get_key):
						$this->spreadsheet_title = $sstitle;
						
						return $entry->getId();
					else :
						$list_spreadsheets[$cnt]['user_id'] =  $user_detail['id'];
						$list_spreadsheets[$cnt]['spreadsheet_title'] =  $sstitle;
						$list_spreadsheets[$cnt]['spreadsheet_key'] =  $get_key;
						//$list_spreadsheets2[$cnt] = $sstitle;
					endif;
					$cnt++;
			   endforeach;
			   
			   
			   
			   return $list_spreadsheets;
		   
		  } catch (Exception $e) {
			die('ERROR: ' . $e->getMessage());
		  }
		}
		
		/**
		 * Get the Service Clients Spreadsheet Data and Auto Update the Longitude and Latitude.
		 *
		 * @access public
		 * @return boolean
		 */
		public function update_site_services() {
			global $gmap_model;
			
			$user_detail = $this->get_user_detail();
			
			try {  
				$success = false;
				$prepare_data = array();
				// connect to API
				$service = Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME;
				$client = Zend_Gdata_ClientLogin::getHttpClient($user_detail['gmail_username'], $user_detail['gmail_password'], $service);
				$service = new Zend_Gdata_Spreadsheets($client);

				// get spreadsheet entry
				$ssid =  $this->get_spreadsheets($user_detail['spreadsheet_key']);
				
				$key = $this->_get_string_after($ssid,'spreadsheets/');
				
				$ssEntry = $service->getSpreadsheetEntry('https://spreadsheets.google.com/feeds/spreadsheets/1EljNeY7IoAqGNv2nSkA2GEuV_tW8HFAYqFFw93QGQBE');
				//$ssEntry = $service->getSpreadsheetEntry($ssid);
			  
				// get worksheets in this spreadsheet
				$wsFeed = $ssEntry->getWorksheets();
				foreach($wsFeed as $wsEntry):
					$rows = $wsEntry->getContentsAsRows();
					if($rows):
						//delete all records
						$reset_service_tbl = $this->reset_service_tbl();
						
						if(!$reset_service_tbl){
							echo 'failed';die();
						}
						
						//alter gmap service data fields table
						reset($rows);
						$firstKey = key($rows);
						$arr = $rows[$firstKey];
						$keys = array_keys($arr);
						$this->alter_service_tbl($keys);
					endif;
					
					foreach ($rows as $row):
						if(empty($row)) :
							continue;
						endif;
						
						foreach($row as $key => $value) :
							$field_key = preg_replace("/[^a-zA-Z]/", "", $key);
							$prepare_data[$field_key] = $value;
						endforeach;	
						
						if($gmap_model->insert_data($prepare_data)) {
							$success = true;
						}	
					endforeach;
				endforeach; 
				
				if(!$this->exist_latitude && !$this->exist_latitude){
					if(!$this->exist_address || !$this->exist_city || !$this->exist_state) {
						echo '<h3>The following fields should be included in the spreadsheet</h3>';
						echo '<ul>';
							echo '<li>Address</li>';
							echo '<li>City</li>';
							echo '<li>State</li>';
						echo '</ul>';
					} else {
						$this->update_long_lang();
					}
					
				}
				
				
				
				if($success)
					return true;
					
			} catch (Exception $e) {
				die('ERROR: ' . $e->getMessage());
			}
		}
		
		/**
		 * Insert User Google E-Mail and Password to DB
		 *
		 * @access public
		 * @return boolean
		 */
		public function insert_gmap_user($data) {
			global $gmap_model;
			$spreadsheets = array();

			$result = $gmap_model->insert_user($data);
			
			if($result){
				//$spreadsheets = $this->get_spreadsheets();
				//$gmap_model->clear_tbl_spreadsheets();
				//$result_spreadsheets = $gmap_model->insert_spreadsheets($spreadsheets);
				//if($result_spreadsheets) {
				$_SESSION['list_spreadsheets'] = $this->get_spreadsheets();
				return true;
				//}
			}
			
			return false;
		}
		
		
		/**
		 * Delete User Google E-Mail and Password to DB
		 *
		 * @access public
		 * @return boolean
		 */
		public function delete_users() {
			global $gmap_model;
			
			$result = $gmap_model->delete_users();
			
			return $result;
		}
		
		
		public function get_data($id){
			global $gmap_model;
			$data = array();
			$data = $gmap_model->get_data($id);
			
			if(!empty($data)) {
				return $data;
			} else {
				return array();
			}
		}
		
		public function delete_record($id){
			global $gmap_model;

			$result = $gmap_model->delete_record($id);
			
			if($result) {
				return true;
			} else {
				return false;
			}
		}
		
		public function get_user_detail(){
			global $gmap_model;
			
			$data = array();
			$data = $gmap_model->get_user_detail();
			
			if(!empty($data)) {
				return $data;
			} else {
				return array();
			}
		}
		
		public function gmap_option($data){
			global $gmap_model;
			
			$result = $gmap_model->gmap_option($data);
			
			if($result){
				return true;
			}
			
			return false;
		}
		
		public function get_gmap_option($option = ''){
			global $gmap_model;
			
			$data = array();
			$data = $gmap_model->get_gmap_option($option);
			
			if(!empty($data)) {
				return json_decode($data['value']);
			} else {
				return array();
			}
		}
		
		private function _get_string_after($haystack,$needle,$case_insensitive=false){
			$strpos = ($case_insensitive) ? 'stripos' : 'strpos';
			$pos = $strpos($haystack,$needle);
			if(is_int($pos)){
				return substr($haystack, $pos + strlen($needle));
			}
			return $pos;
		}
		
		public function get_all_service_fields(){
			global $gmap_model;
			
			$data = array();
			$data = $gmap_model->get_all_service_fields();
			
			if(!empty($data)) {
				return $data;
			} else {
				return array();
			}
		}
		
		public function get_all_fields_name(){
			global $gmap_model;
			
			$data = array();
			$data = $gmap_model->get_all_fields_name();
			
			if(!empty($data)) {
				return $data;
			} else {
				return array();
			}
		}
		
		private function alter_service_tbl($fields){
			global $wpdb, $db_charset, $db_collate, $gmap_model;
			$field_names = array();
			$sort_value = array();
			$show_value = array();
			
			$cnt = 1;
			
			foreach($fields as $key => $value) {
				$field_value = preg_replace("/[^a-zA-Z]/", "", $value);
				$wpdb->query("ALTER TABLE `".WP_GMAP_SERVICE_CLIENTS."` ADD	`{$field_value}` VARCHAR(100) {$db_charset} {$db_collate}");
				$field_names[$field_value] = $field_value;
				$sort_value[$field_value] = $cnt;
				$show_value[$field_value] = 1;
				
				if($field_value == 'latitude') {
					$this->exist_latitude = true;
				}
				
				if($field_value == 'longitude') {
					$this->exist_latitude = true;
				}
				
				if($field_value == 'zipcode'){
					$this->exist_zip_code = true;
				}
				
				if($field_value == 'address'){
					$this->exist_address = true;
				}
				
				if($field_value == 'city'){
					$this->exist_city = true;
				}
				
				if($field_value == 'state'){
					$this->exist_state = true;
				}
				$cnt++;
			}
			
			if(!$this->exist_latitude){
				$wpdb->query("ALTER TABLE `".WP_GMAP_SERVICE_CLIENTS."` ADD	`latitude` VARCHAR(100) {$db_charset} {$db_collate} NOT NULL");
				$field_names['latitude'] = 'latitude';
				$sort_value['latitude'] = $cnt;
				$show_value['latitude'] = 1;
			}
			
			if(!$this->exist_latitude){
				$wpdb->query("ALTER TABLE `".WP_GMAP_SERVICE_CLIENTS."` ADD	`longitude` VARCHAR(100) {$db_charset} {$db_collate} NOT NULL");
				$field_names['longitude'] = 'longitude';
				$cnt++;
				$sort_value['longitude'] = $cnt;
				$show_value['longitude'] = 1;
			}
			
			$fields_params = array(
						'option' 	=> 'gmap_field_names',
						'value' 	=> json_encode($field_names)
					);
			
			$sort_params = array(
						'option' 	=> 'gmap_fields',
						'value' 	=> json_encode($sort_value)
					);
					
			$show_params = array(
						'option' 	=> 'gmap_show_field',
						'value' 	=> json_encode($show_value)
					);
					
			$gmap_title_desc = json_encode(array(
								'gmap_title' 		=> isset($this->spreadsheet_title) ? $this->spreadsheet_title : '',
								'gmap_description' 	=> ''
							));
			$params_title_desc = array(
								'option' => 'gmap_title_desc',
								'value' => $gmap_title_desc
							);			

			$gmap_model->clear_gmap_option_tbl();
			
			$this->gmap_option($fields_params);
			
			$this->gmap_option($sort_params);
			
			$this->gmap_option($show_params);
			
			$this->gmap_option($params_title_desc);	
		}
		
		private function reset_service_tbl(){
			global $wpdb, $db_charset, $db_collate;
			
			if($wpdb->get_var("SHOW TABLES LIKE '".WP_GMAP_SERVICE_CLIENTS."'") == WP_GMAP_SERVICE_CLIENTS) {
				$wpdb->query("DROP TABLE ".WP_GMAP_SERVICE_CLIENTS);
			}
			
			$sql = "CREATE TABLE " . WP_GMAP_SERVICE_CLIENTS . " (
						`id` int(25) NOT NULL AUTO_INCREMENT,
					PRIMARY KEY (id)
				) {$db_charset} {$db_collate};";
				
			$results = $wpdb->query( $sql );
			
			if($results){
				return true;
			}
		
			return false;
		}
		
		public function get_user_spreadsheets() {
			global $gmap_model;
			
			$data = array();
			$data = $gmap_model->get_spreadsheets();
			
			if(!empty($data)) {
				return $data;
			} else {
				return array();
			}
		}
	}

endif;