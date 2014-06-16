<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("foxpro_data_miner_settings")) :

class foxpro_data_miner_settings {

	function __construct() {
		add_action('admin_init', array($this,'admin_init'), 20 );
	}

    function admin_init() {
        $default_values = array('database_name_text' => '<head></head>');
		register_setting( 'foxpro_data_miner_options', 'foxpro_data_miner_options', array($this, 'sanitize_options') );
		add_settings_section('foxpro_data_miner_main', 'Foxpro Data Miner Settings',
			array($this, 'main_section_text'), 'foxpro_data_miner_settings_page');

		add_settings_field('database_name_text', 'Database Name',
			array($this, 'render_text'), 'foxpro_data_miner_settings_page', 'foxpro_data_miner_main',
			array('id' => 'database_name_text', 'value' => 'database_name_text', 'default' => '' ));

		add_settings_field('database_path', 'Database Path',
			array($this, 'render_text'), 'foxpro_data_miner_settings_page', 'foxpro_data_miner_main',
            array('id' => 'database_path', 'value' => 'database_path', 'default' => '' ));

		add_settings_field('database_tables', 'Database Tables',
			array($this, 'render_textarea'), 'foxpro_data_miner_settings_page', 'foxpro_data_miner_main',
            array('id' => 'database_tables', 'value' => 'database_tables', 'default' => '' ));

		add_settings_field('doc_text', 'Documentation',
			array($this, 'render_doc_text'), 'foxpro_data_miner_settings_page', 'foxpro_data_miner_main');
    }

	function main_section_text() {
		echo '<p>Enter a Foxpro database name and path and the tables that should be extracted in the corresponding fields.</p>';
    }

    function render_textarea($args) {
        $option = get_option('foxpro_data_miner_options');
        $id = 'foxpro_data_miner_options['.$args['id'].']';
        $default = $args['default'];
        $value = $option[$args['id']];

        if($value == null) {
            $value = $args['default'];
        }

		?>
            <textarea id="<?php echo $id;?>" style="width:50%;"  type="text" name="<?php echo $id;?>"><?php echo $value; ?></textarea>
		<?php
    }

    function render_text($args) {
        $option = get_option('foxpro_data_miner_options');
        $id = 'foxpro_data_miner_options['.$args['id'].']';
        $default = $args['default'];
        $value = $option[$args['id']];

        if($value == null) {
            $value = $args['default'];
        }

		?>
			<input id="<?php echo $id;?>" style="width:50%;"  type="text" name="<?php echo $id;?>" value="<?php echo $value; ?>" />
		<?php
	}

    function render_doc_text($args) {
		?>
			<p>Dokumentation.</p>
		<?php
	}

    function sanitize_options($options) {
		return $options;
	}

} // end class
endif;
?>
