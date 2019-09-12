<?php
/**
 * The authentication-specific functionality of the plugin.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    EA_WP_Remote_REST_User_Authentication
 * @subpackage EA_WP_Remote_REST_User_Authentication/admin
 */

/**
 * The user updater-specific functionality of the plugin.
 *
 * @package    EA_WP_Remote_REST_User_Authentication
 * @subpackage EA_WP_Remote_REST_User_Authentication/core
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class EA_WP_Remote_REST_User_Authentication_Options {

	const USER_API_ENDPOINT = '/wp-json/wp/v2/users/me';

	const SITES_WP_OPTION_KEY = 'ea-wp-remote-rest-user-authentication-remote-sites';

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

		$this->remote_sites = get_option( self::SITES_WP_OPTION_KEY, array() );
	}

	// without https:// and without /wp-json/...
	// Sites to check password against
	private $remote_sites = array();

	public function get_remote_sites() {
		return $this->remote_sites;
	}

	public $add_email_to_user_rest    = true;
	public $add_username_to_user_rest = true;


	public function add_remote_site( $new_site ) {

		$new_site = $this->strip_domain( $new_site );

		$this->remote_sites[] = $new_site;

		update_option( self::SITES_WP_OPTION_KEY, $this->remote_sites );

		do_action(
			'ea_log_info',
			$this->plugin_name,
			$this->version,
			'Remote site added: ' . $new_site,
			array(
				'file'     => __FILE__,
				'class'    => __CLASS__,
				'function' => __FUNCTION__,
			)
		);
	}

	public function remove_remote_site( $remote_site ) {

		$remote_site = $this->strip_domain( $remote_site );

		if ( ( $key = array_search( $remote_site, $this->remote_sites ) ) !== false ) {
			unset( $this->remote_sites[ $key ] );

			update_option( self::SITES_WP_OPTION_KEY, $this->remote_sites );

			do_action(
				'ea_log_info',
				$this->plugin_name,
				$this->version,
				'Remote site removed: ' . $remote_site,
				array(
					'file'     => __FILE__,
					'class'    => __CLASS__,
					'function' => __FUNCTION__,
				)
			);
		}
	}

	/**
	 * @see https://www.phpliveregex.com/p/rE6
	 *
	 * @param $url
	 *
	 * @return bool|mixed|string
	 */
	private function strip_domain( $url ) {
		// extract base url for remote site from string
		// 1. delete http:// or https://
		// 2. remove /wp-json onwards
		$output_array = array();

		preg_match( '/(?:http:\/\/)?(?:https:\/\/)?((?:(?!\/wp-json).)*)/', $url, $output_array );

		return untrailingslashit( $output_array[1] );
	}

}
