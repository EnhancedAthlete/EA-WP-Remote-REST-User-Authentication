<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    EA_WP_Remote_REST_User_Authentication
 * @subpackage EA_WP_Remote_REST_User_Authentication/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    EA_WP_Remote_REST_User_Authentication
 * @subpackage EA_WP_Remote_REST_User_Authentication/admin
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class EA_WP_Remote_REST_User_Authentication_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in EA_WP_Remote_REST_User_Authentication_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The EA_WP_Remote_REST_User_Authentication_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ea-wp-remote-rest-user-authentication-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in EA_WP_Remote_REST_User_Authentication_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The EA_WP_Remote_REST_User_Authentication_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ea-wp-remote-rest-user-authentication-admin.js', array( 'jquery' ), $this->version, false );

	}



	public function add_submenu_page_link() {

		// do_action( 'ea_log_debug', $this->plugin_name, $this->version, 'Adding menu', array( 'file' => __FILE__, 'class' => __CLASS__, 'function' => __FUNCTION__ ) );
		add_submenu_page(
			'options-general.php',
			'EA Remote REST User Authentication',
			'EA Remote REST User Authentication',
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	function setup_sections() {
		add_settings_section(
			'default',
			'Settings',
			function() {
				echo '<p>Remote sites must have https://github.com/WP-API/Basic-Auth installed.</p>'; },
			$this->plugin_name
		);
	}


	/**
	 * Field Configuration, each item in this array is one field/setting we want to capture
	 *
	 * @see https://github.com/reside-eng/wordpress-custom-plugin/blob/master/admin/class-wordpress-custom-plugin-admin.php
	 *
	 * @since    1.0.0
	 */
	public function setup_fields() {

		// do_action( 'ea_log_debug', $this->plugin_name, $this->version, 'Setup fields', array( 'file' => __FILE__, 'class' => __CLASS__, 'function' => __FUNCTION__ ) );
		$fields = array(
			array(
				'uid'          => 'remote_auth_site',
				'label'        => 'Remote site',
				'type'         => 'text',
				'helper'       => 'https remote site',
				'supplemental' => 'e.g. my-other-wordpress-site.com',
			),

		);

		foreach ( $fields as $field ) {
			add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), $this->plugin_name, 'default', $field );
			register_setting( $this->plugin_name, $field['uid'] );
		}

	}


	public function field_callback( $arguments ) {

		$value = get_option( $arguments['uid'] );

		if ( ! $value ) {
			$value = $arguments['default'];
		}

		switch ( $arguments['type'] ) {
			case 'text':
				printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );

				break;
			default:
				break;
		}

		// If there is helper text, lets show it.
		if ( array_key_exists( 'helper', $arguments ) && $helper = $arguments['helper'] ) {
			printf( '<span class="helper"> %s</span>', $helper );
		}
		// If there is supplemental text lets show it.
		if ( array_key_exists( 'supplemental', $arguments ) && $supplemental = $arguments['supplemental'] ) {
			printf( '<p class="description">%s</p>', $supplemental );
		}

	}

	/**
	 * Admin Notice
	 *
	 * This displays the notice in the admin page for the user
	 *
	 * @since    1.0.0
	 */
	public function admin_notice( $message ) { ?>
		<div class="notice notice-success is-dismissible">
		<p><?php echo( $message ); ?></p>
		</div>
		<?php
	}


	function display_plugin_admin_page() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/ea-wp-remote-rest-user-authentication-admin-display.php';

	}



	// do_action('ea-wp-remote-rest-user-authentication-statistics');
	function display_statistics() {

		$statistics = array();

		// TODO: move to common class as const
		$transient_prefix = 'remote-rest-user-requests-';

		$days           = 0;
		$min_days_count = 7;
		// Loop through the transients for today, yesterday... until there is none for that day
		do {

			// e.g. remote-rest-user-requests-2019-05-15
			$next_day_string = date( 'Y-m-d', time() - ( 60 * 60 * 24 * $days ) );

			$transient_name = $transient_prefix . $next_day_string;

			$new_value = get_transient( $transient_name );

			if ( false != $new_value ) {
				$statistics[ $next_day_string ] = $new_value;
			}

			$days ++;
			$min_days_count--;

		} while ( false != $new_value || $min_days_count > 0 );

		echo '<pre>' . json_encode( $statistics, JSON_PRETTY_PRINT ) . '</pre>';

	}

}
