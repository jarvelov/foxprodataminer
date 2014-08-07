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

		add_settings_field('database_columns', 'Database Columns',
			array($this, 'render_textarea'), 'foxpro_data_miner_settings_page', 'foxpro_data_miner_main',
            array('id' => 'database_columns', 'value' => 'database_columns', 'default' => '' ));

		add_settings_field('doc_text', '',
			array($this, 'render_doc_text'), 'foxpro_data_miner_settings_page', 'foxpro_data_miner_main');
    }

	function main_section_text() {
		echo '<p>Enter a Foxpro database name and path and the columns that should be extracted in the corresponding fields.</p>';
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
			<style type="text/css">.fdm-documentation { margin-top: 1em; } #fdm-shortcode-list { list-style: circle; list-style-position: inside; } </style>
			<h1>FoxPro Data Miner Documentation</h1>
			<div class="fdm-documentation" id="introduction"><h3>Introduction</h3>FoxPro Data Miner is a Wordpress plugin that allows you to read and display data from a Foxpro (.dbf) database. This plugin uses the <a href="https://github.com/hisamu/php-xbase">XBase</a> framework by <a href="https://github.com/hisamu">hisamu</a>.<br>To get started configure a database above and then add a shortcode on the page you want the data displayed. For more information take a look at the documentation below and the shortcode examples.</div>
			<div class="fdm-documentation" id="settings-001"><h3>Configuring a database</h3><strong>Database Name</strong> can be any name but you will have to use it in the shortcode and wordpress filters some characters so make it simple and unique.<p><strong>Example: </strong>database01</div>
			<div class="fdm-documentation" id="settings-002"><strong>Database Path</strong> must be a location readable by the web server. Make sure the web server has the appropriate permissions (read) before you submit a bug. <p><strong>Example: </strong>C:\my_databases\database01.dbf <strong>or </strong> /usr/local/share/my_databases/database01.dbf</div>
			<div class="fdm-documentation" id="settings-003"><strong>Database Columns</strong> would be a comma-separated list of the columns you want to be able to access. Each column is cached when called upon (default 1 hour) to prevent needless reading of the Foxpro database as it is much slower. If you specificy a column in the shortcode that is not in the list the plugin will not read it.<p><strong>Example: </strong>id, orders, data, my_column_name</div>
			<div class="fdm-documentation" id="shortcode-001"><h3>Usage</h3>Foxpro Data Miner (FDM) relies on <a href="https://codex.wordpress.org/Shortcode">shortcodes</a> to function. For every column you want to read and display you'll need to use a shortcode to instruct FDM what, how and where you want your data.</div>
			<div class="fdm-documentation" id="shortcode-002"><h4>Shortcode Arguments</h4> Arguments marked with <strong>*</strong> are required
				<ul id="fdm-shortcode-list">
					<li>database <small>(default: <i>none</i>)*</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>Any, see <strong>Configuring a database</strong> for accepted names for databases</ol>
					</li>
					<li>column <small>(default: <i>none</i>)*</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>Any column listed in the <strong>Database Columns</strong> field for the respective database</ol>
					</li>
					<li>id <small>(default: <i>none</i>)*</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>Any - this will be used for the name of the javascript array variable and function and where the results of the query will be inserted. See <i>display</i> if you don't want output but still want the data accessible with javascript.</ol>
					</li>
					<li>column2 <small>(default: <i>none</i>)</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>Any column in Database Columns field for the respective database. This argument is used with <i>case</i> and <i>operator</i> and is used to filter results in <i>column</i>. See the examples for more info.</ol>
					</li>
					<li>delimiter <small>(default: <i>none</i>)</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>br</ol>
					</li>
					<li>case <small>(default: <i>none</i>)</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>Any string</ol>
					</li>
					<li>operator <small>(default: <i>eq</i>)</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>eq, (default) equal to</ol>
						<ol>ne, not equal to</ol>						
						<ol>gt, greater than</ol>
						<ol>gte, greater than or equal to</ol>
						<ol>lt, less than</ol>
						<ol>lte, less than or equal to</ol>
					</li>
					<li>limit <small>(default: <i>500</i>)</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>Any integer, limits the number of results returned by the query.</ol>
					</li>
					<li>cache_limit <small>(default: <i>3600</i>)</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>Any integer lower than cache limit setting for the database's cache expiration limit. Currently changing this setting can cause the caching of the same row multiple times.</ol>
					</li>
					<li>offset <small>(default: <i>0</i>)</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>Any integer, can be used to omit the first (or last depending on <i>sort</i>) x results. Useful if you have a set number of rows that you don't want to display.</ol>
					</li>
					<li>display <small>(default: <i>true</i>)</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>true (default), After page has loaded, insert the results in the html element specified with </i>id</i></ol>
						<ol>false, do not insert the results from query in the html object with corresponding id. <small>The data from the query will still be available in the javascript array with the name specified in id. If </i>id</i> is myid1234 then the array is called myid1234Arr.</small></ol>
					</li>
					<li>delay <small>(default: <i>200</i>)</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>Any integer, the value in milliseconds before the results from the query is applied to the html element. <i>display</i> must be set to true to have any effect. Can cause speedups if multiple queries are used on the same page and they have different delays, e.g. first 200, second 300 etc.</ol>
					</li>
					<li>sort <small>(default: <i>DESC</i>)</small>
						<ol><strong>Accepted Values</strong></ol>
						<ol>ASC, sort results by date ascending</ol>
						<ol>DESC (default), sort results by date descending</ol>
					</li>
				</ul>
			</div>
		<?php
	}

    function sanitize_options($options) {
		return $options;
	}

} // end class
endif;
?>
