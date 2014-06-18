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

require('C:\inetpub\wwwroot\test\XBase\Class.php');

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

		add_action( 'your_action_here', array( &$this, 'action_callback_method_name' ) );
		add_filter( 'your_filter_here', array( &$this, 'filter_callback_method_name' ) );    
	}

	/** Add option page content  */
	function my_plugin_options() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		echo '<div class="wrap">';
		echo '<p>Here is where the form would go if I actually had options.</p>';
		echo '</div>';
	}

	function action_callback_method_name() {
		// TODO define your action method here
	}

	function filter_callback_method_name() {
		// TODO define your filter method here
	}

	function render_shortcode($atts) {
		// Extract the attributes
		extract(shortcode_atts(array(
			'database' => '',
			'column' => '',
			'id' => '',
			'delimiter' => '',
			'limit' => '500', //max is 500 records
			'offset' => '0',
			'sort' => 'first'
			), $atts));

		//figure out what database is used and grab the data
		$foxpro_data_miner_data = array();

		$table = new Table('C:\inetpub\wwwroot\test\Faktura.dbf', array('ordernr'));

		while ($record = $table->nextRecord()) {
		    $foxpro_data_miner_data[] .= $record->ordernr;
		}

		if($sort == 'first') {
			rsort($foxpro_data_miner_data);
		}

		$foxpro_data_miner_data = array_slice($foxpro_data_miner_data, $offset, $limit);

		?>
		<script type="text/javascript">
			function applyFDMData<?php echo $id; ?>() {
				var field = document.getElementById(<?php echo "'".$id."'"; ?>);
				console.log(field.tagName.toLowerCase());
				console.log('stuff');
				if (field.tagName.toLowerCase() == 'text') {
					field.value = <?php echo "'".$foxpro_data_miner_data[0]."'"; ?>;
				} else if(field.tagName.toLowerCase() == 'select-one' || field.tagName.toLowerCase() == 'select-multiple')  {
					<?php
					foreach ($foxpro_data_miner_data as $key => $value): ?>
						var option<?php echo $key; ?> = document.createElement("option");
						option<?php echo $key; ?>.text = <?php echo $val = ($value.length > 1 ? $value : '""'); ?>;
						field.add(option<?php echo $key; ?>);
				    <?php endforeach; ?>
				} else if(field.tagName.toLowerCase() == 'div' || field.tagName.toLowerCase() == 'p' || field.tagName.toLowerCase() == 'pre' || field.tagName.toLowerCase() == 'blockquote' || field.tagName.toLowerCase() == 'pre') {
					<?php
					$i = 0;
					$i++;
						foreach ($foxpro_data_miner_data as $key => $value): ?>
							field.innerHTML += <?php echo ($value.length > 1 ? $value : '""'); ?>;
							field.innerHTML += <?php if($delimiter) { echo "'<".$delimiter.">'"; } ?>;
						<?php endforeach; ?>
				}
			}

			jQuery( document ).ready(function($) {
				setTimeout(applyFDMData<?php echo $id; ?>,500);
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