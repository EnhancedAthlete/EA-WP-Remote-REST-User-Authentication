<?php
/**
 * Adds `username` and `email` to /wp-json/users/me
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    EA_WP_Remote_REST_User_Authentication
 * @subpackage EA_WP_Remote_REST_User_Authentication/rest
 */

/**
 * The REST API-specific functionality of the plugin.
 *
 * @package    EA_WP_Remote_Rest_User_Authentication
 * @subpackage EA_WP_Remote_Rest_User_Authentication/rest
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class EA_WP_Remote_REST_User_Authentication_Rest_Fields {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Plugin config allows disabling exposing the fields via REST.
	 * TODO: consider only exposing them when it's this plugin remotely requesting
	 *
	 * @var EA_WP_Remote_REST_User_Authentication_Options
	 */
	private $config;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string                                        $plugin_name The name of this plugin.
	 * @param      string                                        $version The version of this plugin.
	 * @param EA_WP_Remote_REST_User_Authentication_Options $config Plugin settings object.
	 */
	public function __construct( $plugin_name, $version, $config ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->config = $config;
	}


	/**
	 * Add `email` and `username` fields to `/users/me` REST endpoint
	 */
	public function add_fields_to_wp_user_rest() {

		if ( $this->config->add_email_to_user_rest ) {

			register_rest_field(
				'user',
				'email',
				array(
					'get_callback' => function ( $user ) {

						/**
						 * We'll always have a user id at this point.
						 * Use it to get the user's email.
						 *
						 * @var WP_User $user
						 */
						$user = get_user_by( 'id', $user['id'] );

						$email = $user->user_email;

						return $email;
					},
					'schema'       => array(
						'description' => __( 'User email address.' ),
						'type'        => 'string',
					),
				)
			);

		}

		if ( $this->config->add_username_to_user_rest ) {
			register_rest_field(
				'user',
				'username',
				array(
					'get_callback' => function ( $user ) {

						/**
						 * We'll always have a user id at this point.
						 * Use it to get the username.
						 *
						 * @var WP_User $user
						 */
						$user = get_user_by( 'id', $user['id'] );

						$user_login = $user->user_login;

						return $user_login;
					},
					'schema'       => array(
						'description' => __( 'User username.' ),
						'type'        => 'string',
					),
				)
			);
		}
	}
}

