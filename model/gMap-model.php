<?php
if( !class_exists('gMapModel') ) :
	
	class gMapModel {
		
		public function insert_data($data) {
			global $wpdb;

			// Return immediately if the $data is empty.
			if (empty($data)) {
				return false;
			}
			// Proceed to the db query
			$result = $wpdb->insert(WP_GMAP_SERVICE_CLIENTS, $data);
			
			if($result)
				return true;
			
			return false;
		}
		
		public function get_record($id){
			global $wpdb;

			$sql = "SELECT * FROM " .WP_GMAP_SERVICE_CLIENTS. " 
					WHERE id = '{$id}'";
			if($results = $wpdb->get_row($sql, ARRAY_A))
				return $results;	
			else
				return array();
		}
		
		public function delete_record($id){
			global $wpdb;

			$result = $wpdb->delete( WP_GMAP_SERVICE_CLIENTS , array( 'id' => $id ) );
			
			if($result){
				return true;
			}else{
				return false;
			}
		}
		
		public function get_lists_nolatlng($exist_zipcode = false) {
			global $wpdb;
			
			$sql = "SELECT * FROM " .WP_GMAP_SERVICE_CLIENTS;
			
			if($exist_zipcode){
				$sql .= " WHERE latitude = '' AND longitude = '' AND address <> '' AND city <> '' AND state <> '' AND zipcode <> ''";
			} else {
				$sql .= " WHERE latitude = '' AND longitude = '' AND address <> '' AND city <> '' AND state <> '' AND address NOT LIKE '%:%' AND city NOT LIKE '%:%' AND state NOT LIKE '%:%'";
			}
					
			if($results = $wpdb->get_results($sql, ARRAY_A))
				return $results;	
			else
				return array();
		}
		
		public function fields_require() {
			global $wpdb;
			
			$sql = "SELECT * FROM " .WP_GMAP_SERVICE_CLIENTS. " 
					WHERE latitude = '' AND longitude = '' AND address <> '' AND city <> '' AND state <> '' AND zipcode <> ''";
			if($results = $wpdb->get_row($sql, ARRAY_A))
				return $results;	
			else
				return array();
		}
		
		public function update_data($data_info, $id) {
			global $wpdb;
						
			$result = $wpdb->update(WP_GMAP_SERVICE_CLIENTS, $data_info, array('id' => $id));
			
			if($result){
				return true;
			}
			
			return false;
		}
		
		public function delete_data() {
			global $wpdb;

			if($wpdb->query("TRUNCATE TABLE ".WP_GMAP_SERVICE_CLIENTS))
				return true;
			else
				return false;
		}
		
		public function delete_users() {
			global $wpdb;

			if($wpdb->query("TRUNCATE TABLE ".WP_GMAP_USER))
				return true;
			else
				return false;
		}
		
		public function clear_gmap_option_tbl(){
			global $wpdb;

			if($wpdb->query("TRUNCATE TABLE ".WP_GMAP_OPTIONS))
				return true;
			else
				return false;
		}
		
		public function get_lists() {
			global $wpdb;

			$sql = "SELECT * FROM " .WP_GMAP_SERVICE_CLIENTS. " 
					WHERE longitude <> '' AND latitude <> ''
					ORDER BY id ASC";
			
			if($results = $wpdb->get_results($sql, ARRAY_A))
				return $results;
			else
				return array();
		}

		
		public function search_map($keyword = '', $search_by = '') {
			global $wpdb;

			$sql = "SELECT * FROM " . WP_GMAP_SERVICE_CLIENTS." WHERE ";
			
			if(!empty($keyword)) {
				if(!empty($search_by)){
					$sql .= " LOWER({$search_by}) LIKE '{$keyword}%'";
				} else {
					$sql_search_fields = array();
					$record_fields = $this->get_all_service_fields();
					
					foreach($record_fields as $key) :
						if($key['Field'] == 'id')
							continue;
							
						$sql_search_fields[] = $key['Field']." LIKE '{$keyword}%'";
					endforeach;
					
					$sql .= implode(" OR ", $sql_search_fields);
				}
				
				$sql .= " AND longitude <> '' AND latitude <> '' ORDER BY id ASC";
			} else {
				$sql .= " longitude <> '' AND latitude <> '' ORDER BY id ASC";
			}

			if($results = $wpdb->get_results($sql, ARRAY_A))
				return $results;	
			else
				return array();				
		}
		
		public function load_sites($keyword = '', $search_by = '') {
			global $wpdb;

			$query = "SELECT * FROM ".WP_GMAP_SERVICE_CLIENTS." WHERE ";
			
			if(!empty($keyword)) {
				if(!empty($search_by)){
					$query .= " LOWER({$search_by}) LIKE '{$keyword}%'";
				} else {
					$sql_search_fields = array();
					$record_fields = $this->get_all_service_fields();
					
					foreach($record_fields as $key) :
						if($key['Field'] == 'id')
							continue;
							
						$sql_search_fields[] = $key['Field']." LIKE '{$keyword}%'";
					endforeach;
					
					$query .= implode(" OR ", $sql_search_fields);
				}
				
				$query .= " AND longitude <> '' AND latitude <> '' ORDER BY id ASC";
			} else {
				$query .= " longitude <> '' AND latitude <> '' ORDER BY id ASC";
			}
			
			if($results = $wpdb->get_results($query, ARRAY_A))
				return $results;	
			else
				return array();					
		}
		
		public function insert_user($data) {
			global $wpdb;
			
			// Return immediately if the $data is empty.
			if (empty($data)) {
				return false;
			}
			
			$clear_tbl_user = $this->clear_tbl_user();
			
			if(!$clear_tbl_user){
				return false;
			}
			
			$results = $wpdb->insert(WP_GMAP_USER, $data);

			if($results)
				return true;

			return false;
		}
		
		public function update_user_spreadsheet($sskey, $username) {
			global $wpdb;
			
			// Return immediately if the $sskey is empty.
			if (empty($sskey)) {
				return false;
			}
			
			$param = array(
						'spreadsheet_key' => $sskey
						);
			
			$result = $wpdb->update(WP_GMAP_USER, $param, array('gmail_username' => $username));
			
			if($result){
				unset($_SESSION['list_spreadsheets']);
				return true;
			}
			
			return false;
		}
		
		public function get_user($gmail_username){
			global $wpdb;

			$sql = "SELECT * FROM " .WP_GMAP_USER. " 
					WHERE gmail_username = '{$gmail_username}'";
			if($results = $wpdb->get_row($sql, ARRAY_A))
				return $results;	
			else
				return array();
		}
		
		public function get_user_detail(){
			global $wpdb;
			
			$sql = "SELECT * FROM " .WP_GMAP_USER;
			
			if($results = $wpdb->get_row($sql, ARRAY_A))
				return $results;	
			else
				return array();
		}
		
		public function gmap_option($data){
			global $wpdb;
			
			$record = $wpdb->get_row("SELECT * FROM ".WP_GMAP_OPTIONS." WHERE `option` = '{$data['option']}'");
			
			// Return immediately if the $data is empty.
			if (empty($data)) {
				return false;
			}
			
			if(count($record) > 0) {
				$results = $wpdb->update(WP_GMAP_OPTIONS, $data, array('option' => $data['option']));
			} else {
				$results = $wpdb->insert(WP_GMAP_OPTIONS, $data);
			}

			if($results)
				return true;

			return false;
		}
		
		public function get_gmap_option($option = ''){
			global $wpdb;

			$sql = "SELECT `value` FROM " .WP_GMAP_OPTIONS . " WHERE `option` = '{$option}'";
			
			if($results = $wpdb->get_row($sql, ARRAY_A))
				return $results;	
			else
				return array();
		}
		
		public function get_all_service_fields(){
			global $wpdb;

			$query = "SHOW COLUMNS FROM ".WP_GMAP_SERVICE_CLIENTS;

			if($results = $wpdb->get_results($query, ARRAY_A))
				return $results;	
			else
				return array();	
		}
		
		public function get_default_search_field() {
			$result = $this->get_all_service_fields();
			
			if($result){
				return $result[1]['Field'];
			} else {
				return 'id';
			}
		}
		
		public function get_all_fields_name(){
			global $wpdb;
			$result = array();

			$sql = "SELECT `value` FROM " .WP_GMAP_OPTIONS . " WHERE `option` = 'gmap_field_names'";
			
			if($results = $wpdb->get_row($sql, ARRAY_A)){
				$result = (array) json_decode($results['value']);	
				return $result;
			}else{
				return array();
			}
		}
		
		public function insert_spreadsheets($spreadsheets = array()) {
			global $wpdb;
			
			$data = array();
			
			$succes = false;

			// Return immediately if the $data is empty.
			if (empty($spreadsheets)) {
				return false;
			}

			foreach($spreadsheets as $row => $record) {
				foreach($record as $key => $value){
					$data[$key] = $value;
				}
				// Proceed to the db query
				$result = $wpdb->insert(WP_GMAP_USER_SPREADSHEETS, $data);
				
				if($result)
					$success = true;
			}
			
			
			if($success)
				return true;
			
			return false;
		}
		
		public function clear_tbl_spreadsheets(){
			global $wpdb;

			if($wpdb->query("TRUNCATE TABLE ".WP_GMAP_USER_SPREADSHEETS))
				return true;
			else
				return false;
		}
		
		public function get_spreadsheets() {
			global $wpdb;
			$result = array();

			$sql = "SELECT * FROM ".WP_GMAP_USER_SPREADSHEETS;
			
			if($result = $wpdb->get_results($sql, ARRAY_A)){
				return $result;
			}else{
				return array();
			}
		}
		
		public function clear_tbl_user() {
			global $wpdb;
			
			if($wpdb->query("TRUNCATE TABLE ".WP_GMAP_USER))
				return true;
			else
				return false;
		}

	}
	
endif;