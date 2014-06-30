<?php
/*
Plugin Name: Foxpro Data Miner
Plugin URI: http://jarvelov.se/projects/foxprodataminer
Description: Mine data from a foxpro database.
Version: 1.0
Author: Tobias Järvelöv
Author Email: tobias@jarvelov.se
License:

  Copyright 2014 Tobias Järvelöv (tobias@jarvelov.se)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/
require('XBase\Class.php');

Use XBase\Table;

define( 'FPDDIR', WP_PLUGIN_DIR . '/foxprodataminer'  );

class FoxproDataMiner {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'Foxpro Data Miner';
	const slug = 'foxpro_data_miner';
	const plain = 'foxprodataminer';

	var $settings, $options_page;
	
	/**
	 * Constructor
	 */
	function __construct() {
		//register an activation hook for the plugin
		register_activation_hook( __FILE__, array( &$this, 'install_foxpro_data_miner' ) );

		//Hook up to the init action
		add_action( 'init', array( &$this, 'init_foxpro_data_miner' ) );
	}
  
	/**
	 * Runs when the plugin is activated
	 */  
	function install_foxpro_data_miner() {
		// do not generate any output here
    	global $wpdb;
		$table_name = $wpdb->prefix . "fdm_dbs";
	    $sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
		    fdm_database VARCHAR(255) NOT NULL,
		    fdm_column VARCHAR(255) DEFAULT '' NOT NULL,
		    fdm_id mediumint(9) NOT NULL,
		    fdm_data text NOT NULL,
		    fdm_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		    UNIQUE KEY id (id)
		);";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); 
		dbDelta($sql);
	}
  
	/**
	 * Runs when the plugin is initialized
	 */
	function init_foxpro_data_miner() {

		// Setup localization
		load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		// Load JavaScript and stylesheets
		$this->register_scripts_and_styles();

		// Register the shortcode [foxprodataminer]
		add_shortcode( 'foxprodataminer', array( &$this, 'render_shortcode' ) );
	
		if ( is_admin() ) {
			//this will run when in the WordPress admin
			if (!class_exists(self::plain . '_settings'))
				require(FPDDIR . '/' . self::plain .'-settings.php');
				$this->settings = new foxpro_data_miner_settings();

			if (!class_exists(self::plain . '_options'))
				require(FPDDIR . '/'. self::plain .'-options.php');
		        $this->options_page = new foxpro_data_miner_options();    		        
		} else {
			//this will run when on the frontend
		}

		add_action( 'fdm_update_dbs', array( &$this, 'action_callback_fdm_update_dbs' ), 10, array($args = NULL) );
		add_action( 'fdm_get_db_records', array( &$this, 'action_callback_fdm_get_db_records' ), 10, array($args = NULL) );
	}

	function action_callback_fdm_get_db_records($args) {
		global $wpdb;
		$option = get_option('foxpro_data_miner_options');
		$table_name = $wpdb->prefix . "fdm_dbs";

		if($option['database_name_text'] == $args['database']) {
			$fdm_database = $args['database'];
			$columns = explode(",",$option['database_columns']);
			$columns = array_map('trim', $columns); //trim whitespace of any values in array

			if(in_array($args['column'], $columns)) {
				$fdm_column = $args['column'];
				$fdm_path = $option['database_path'];
				$fdm_limit = isset($args['limit']) ? $args['limit'] : 500; //TODO
				$fdm_cache_limit = isset($args['cache_limit']) ? $args['cache_limit'] : 3600;
			}
		}

		$last_record_timestamp = 0;

		$last_db_record = $wpdb->get_results("SELECT fdm_timestamp FROM $table_name WHERE fdm_db = '".$fdm_database."' AND fdm_column = '".$fdm_column."' ORDER BY fdm_timestamp DESC LIMIT 1", ARRAY_A);

		$last_record_timestamp = strtotime(($last_db_record[0]['fdm_timestamp']));
		$current_timestamp = time() - $fdm_cache_limit;

		if($last_record_timestamp <= $current_timestamp || sizeof($last_record_timestamp) <= 0) {
			do_action('fdm_update_dbs', array('database' => $fdm_database, 'database_path' => $fdm_path, 'column' => $fdm_column, 'limit' => $fdm_limit));
		}
	}

	function action_callback_fdm_update_dbs($args) {
		global $wpdb;
		$option = get_option('foxpro_data_miner_options');
		$table_name = $wpdb->prefix . "fdm_dbs";

		$fdm_database = $args['database'];
		$fdm_column = $args['column'];
		$fdm_path = $args['database_path'];
		$fdm_limit = $args['limit'];

		if($fdm_database AND $fdm_column) {
			$result = $wpdb->get_results("SELECT id FROM $table_name WHERE fdm_db = $fdm_database AND fdm_column = $fdm_column LIMIT $fdm_limit");
			if(!$result) {
				$fdm_db_records = array();

				$table = new Table($fdm_path, array($fdm_column));

				while ($record = $table->nextRecord()) {
					$tmpRecord = $record->getChar($fdm_column);
					$encRecord = (iconv("ISO-8859-1", "UTF-8", $tmpRecord)); //encode to utf-8 before adding to array
				    $fdm_db_records[] = $encRecord;
				}

				foreach ($fdm_db_records as $fdm_id => $fdm_db_record) {
				 	$wpdb->insert($table_name, array(
				 		'fdm_db' => $fdm_database,
				 		'fdm_column' => $fdm_column,
				 		'fdm_id' => (int)$fdm_id,
				 		'fdm_data' => $fdm_db_record
				 		)
				 	);
				 }
			}
		}
	}

	function render_shortcode($atts) {
		// Extract the attributes
		extract(shortcode_atts(array(
			'database' => '',
			'column' => '',
			'column2' => '',
			'id' => '', //element to print the results to
			'delimiter' => '',
			'case' => '',
			'operator' => 'eq',
			'limit' => '500', //max is 500 records
			'cache_limit' => 3600, //do not change, may yield duplicate results
			'offset' => '0', //split the array result from nth position
			'display' => 'true', //whether or not to create the function that prints out the variables, js array still created
			'delay' => 200,
			'sort' => strtoupper('desc')
			), $atts));

		if(!$id) {
			$display = 'false';
		}

		$operator_values = array('gt' => '>', 'lt' => '<', 'gte' => '>=', 'lte' => '<=', 'eq' => '=', 'ne' => '!=');
		if(array_key_exists($operator, $operator_values)) {
			$operator = $operator_values[$operator];
		} else {
			$operator = '=';
		}

		//figure out what database is used and grab the data
		global $wpdb;
		$table_name = $wpdb->prefix . "fdm_dbs";

		if(!empty($column2) AND !empty($case)) {
			do_action('fdm_get_db_records', array('database' => $database, 'column' => $column));
			do_action('fdm_get_db_records', array('database' => $database, 'column' => $column2));
			$foxpro_data_miner_data = $wpdb->get_results("SELECT a.fdm_data as fdm_data FROM $table_name a INNER JOIN $table_name b on a.fdm_id = b.fdm_id WHERE a.fdm_db = '".$database."' AND a.fdm_column = '".$column."' AND b.fdm_column = '".$column2."' AND b.fdm_data ".$operator." '".$case."' AND a.fdm_timestamp >= DATE_SUB( NOW(), INTERVAL ".$cache_limit." MINUTE) ORDER BY a.fdm_id ".$sort." LIMIT $limit", ARRAY_A);
		} else {
			do_action('fdm_get_db_records', array('database' => $database, 'column' => $column));
			$foxpro_data_miner_data = $wpdb->get_results("SELECT fdm_data FROM $table_name WHERE fdm_db = '".$database."' AND fdm_column = '".$column."' ORDER BY fdm_id ".$sort." AND fdm_timestamp >= DATE_SUB( NOW(), INTERVAL ".$cache_limit." MINUTE) LIMIT $limit", ARRAY_A);
		}
		$foxpro_data_miner_data = array_slice($foxpro_data_miner_data, $offset, $limit);

		?>
		<script type="text/javascript">
			function applyFDMData_<?php echo $id; ?>Arr(field,data) {
				var field = document.getElementById(<?php echo "'".$id."'"; ?>);
				if (field.tagName.toLowerCase() == 'text' || field.tagName.toLowerCase() == 'input') {
					field.value = data[0];
				} else if(field.tagName.toLowerCase() == 'select' || field.tagName.toLowerCase() == 'select-one' || field.tagName.toLowerCase() == 'select-multiple')  {
					for(var i = 0; i < data.length; i++) {
						var option = document.createElement("option");
						option.text = data[i];
						field.add(option);
					}
				} else if(field.tagName.toLowerCase() == 'div' || field.tagName.toLowerCase() == 'p' || field.tagName.toLowerCase() == 'pre' || field.tagName.toLowerCase() == 'blockquote' || field.tagName.toLowerCase() == 'pre' || field.tagName.toLowerCase() == 'textarea') {
						for(var i = 0; i < data.length; i++) {
							field.innerHTML += data[i];
							field.innerHTML += <?php if($delimiter) { echo "'<".$delimiter.">'"; } else { echo '""'; } ?>;
						}
				}
			}

			jQuery( document ).ready(function($) {
				window.<?php echo $id; ?>Arr = [];
				<?php foreach ($foxpro_data_miner_data as $key => $value): ?>
					<?php echo $id; ?>Arr.push(<?php if((strlen($value['fdm_data'])) > 1) { echo "'".$value['fdm_data']."'"; } else { echo '""'; } ?>);
				<?php endforeach; ?>

				<?php if($display == 'true') { echo 'setTimeout(applyFDMData_'.$id.'Arr('.$id.','.$id.'Arr),'.$delay.');'; } ?>
			});
		</script>
		<?php
	}
  
	/**
	 * Registers and enqueues stylesheets for the administration panel and the
	 * public facing site.
	 */
	private function register_scripts_and_styles() {
		if ( is_admin() ) {
			$this->load_file( self::slug . '-admin-script', '/js/admin.js', true );
			$this->load_file( self::slug . '-admin-style', '/css/admin.css' );
		} else {
			$this->load_file( self::slug . '-script', '/js/widget.js', true );
			$this->load_file( self::slug . '-style', '/css/widget.css' );
		} // end if/else
	} // end register_scripts_and_styles
	
	/**
	 * Helper function for registering and enqueueing scripts and styles.
	 *
	 * @name	The 	ID to register with WordPress
	 * @file_path		The path to the actual file
	 * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
	 */
	private function load_file( $name, $file_path, $is_script = false ) {

		$url = plugins_url($file_path, __FILE__);
		$file = plugin_dir_path(__FILE__) . $file_path;

		if( file_exists( $file ) ) {
			if( $is_script ) {
				wp_register_script( $name, $url, array('jquery') ); //depends on jquery
				wp_enqueue_script( $name );
			} else {
				wp_register_style( $name, $url );
				wp_enqueue_style( $name );
			} // end if
		} // end if

	} // end load_file
  
} // end class
new FoxproDataMiner();

?>