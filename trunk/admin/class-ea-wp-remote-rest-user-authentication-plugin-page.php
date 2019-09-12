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
class EA_WP_Remote_REST_User_Authentication_Plugin_Page {

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
	 * @see https://rudrastyh.com/wordpress/plugin_action_links-plugin_row_meta.html
	 *
	 * @param $links
	 *
	 * @return array
	 */
	function plugin_action_links( $links_array, $plugin_file_name ) {

		if ( $this->plugin_name . '/' . $this->plugin_name . '.php' == $plugin_file_name ) {

			$settings_url = admin_url( '/options-general.php?page=' . $this->plugin_name );

			array_unshift( $links_array, '<a href="' . $settings_url . '">Settings</a>' );
		}

		return $links_array;
	}

	/**
	 * Add a link to EnhancedAthlete.com on the plugins list
	 *
	 * @see https://rudrastyh.com/wordpress/plugin_action_links-plugin_row_meta.html
	 *
	 * @param $links_array
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links_array, $plugin_file_name, $plugin_data, $status ) {

		if ( $this->plugin_name . '/' . $this->plugin_name . '.php' == $plugin_file_name ) {

			foreach ( $links_array as $index => $link ) {
				$links_array[ $index ] = str_replace( 'Visit plugin site', 'View plugin on GitHub', $link );
			}

			$links_array[] = '<a target="_blank" href="https://enhancedathlete.com">Visit EnhancedAthlete.com</a>';
		}

		return $links_array;
	}
}
