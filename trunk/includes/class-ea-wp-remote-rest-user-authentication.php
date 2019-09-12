<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    EA_WP_Remote_REST_User_Authentication
 * @subpackage EA_WP_Remote_REST_User_Authentication/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    EA_WP_Remote_REST_User_Authentication
 * @subpackage EA_WP_Remote_REST_User_Authentication/includes
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class EA_WP_Remote_REST_User_Authentication {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      EA_WP_Remote_REST_User_Authentication_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/** @var EA_WP_Remote_REST_User_Authentication_Options */
	private $config;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'EA_WP_REMOTE_REST_USER_AUTHENTICATION_VERSION' ) ) {
			$this->version = EA_WP_REMOTE_REST_USER_AUTHENTICATION_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'ea-wp-remote-rest-user-authentication';

		$this->load_dependencies();

		$this->define_admin_hooks();
		$this->define_rest_hooks();

		$this->define_3rd_party_hooks();

		$this->define_authenticator_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - EA_WP_Remote_REST_User_Authentication_Loader. Orchestrates the hooks of the plugin.
	 * - EA_WP_Remote_REST_User_Authentication_Admin. Defines all hooks for the admin area.
	 * - EA_WP_Remote_REST_User_Authentication_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ea-wp-remote-rest-user-authentication-loader.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ea-wp-remote-rest-user-authentication-options.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ea-wp-remote-rest-user-authentication-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ea-wp-remote-rest-user-authentication-plugin-page.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'login/class-ea-wp-remote-rest-user-authentication-authenticator.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'rest/class-ea-wp-remote-rest-user-authentication-rest-fields.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . '3rd-party/class-ea-wp-remote-rest-user-authentication-anr-captcha.php';

		$this->config = new EA_WP_Remote_REST_User_Authentication_Options( $this->plugin_name, $this->version );

		$this->loader = new EA_WP_Remote_REST_User_Authentication_Loader();

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new EA_WP_Remote_REST_User_Authentication_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_submenu_page_link' );

		// Used by the admin page itself
		$this->loader->add_action( 'ea_wp_remote_rest_user_authentication_statistics', $plugin_admin, 'display_statistics' );

		$plugin_plugin_page = new EA_WP_Remote_REST_User_Authentication_Plugin_Page( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_filter( 'plugin_action_links', $plugin_plugin_page, 'plugin_action_links', 20, 2 );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_plugin_page, 'plugin_row_meta', 20, 4 );

	}

	private function define_authenticator_hooks() {

		$plugin_authenticator = new EA_WP_Remote_REST_User_Authentication_Authenticator( $this->get_plugin_name(), $this->get_version(), $this->config );

		// set the priority late
		$this->loader->add_filter( 'authenticate', $plugin_authenticator, 'remote_rest_authenticate', 50, 3 );

		$this->loader->add_filter( 'determine_current_user', $plugin_authenticator, 'remote_rest_forwarder', 50, 1 );

	}

	private function define_rest_hooks() {

		$plugin_rest_fields = new EA_WP_Remote_REST_User_Authentication_Rest_Fields( $this->get_plugin_name(), $this->get_version(), $this->config );

		$this->loader->add_action( 'rest_api_init', $plugin_rest_fields, 'add_fields_to_wp_user_rest' );
	}

	private function define_3rd_party_hooks() {

		$plugin_anr_captcha = new EA_WP_Remote_REST_User_Authentication_ANR_Captcha( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'ea-wp-remote-rest-user-authentication-before-register-new-user', $plugin_anr_captcha, 'remove_anr_captcha_class' );

	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    EA_WP_Remote_REST_User_Authentication_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
