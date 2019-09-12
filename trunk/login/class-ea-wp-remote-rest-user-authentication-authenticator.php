<?php
/**
 * Checks for locally authenticated user
 * Looks for local user with matching username/email
 * Tries to authenticate remotely (using email is possible)
 * If unsuccessful, returns
 * If successful updates the local user's password
 * Or if successful creates the local user
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    EA_WP_Remote_REST_User_Authentication
 * @subpackage EA_WP_Remote_REST_User_Authentication/core
 */

/**
 * The user authentication-specific functionality of the plugin.
 *
 * @package    EA_WP_Remote_Rest_User_Authentication
 * @subpackage EA_WP_Remote_Rest_User_Authentication/core
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class EA_WP_Remote_REST_User_Authentication_Authenticator {

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
	 * Configuration class containing remote sites list and constants
	 *
	 * @var EA_WP_Remote_REST_User_Authentication_Options
	 */
	private $config;

	const TRANSIENT_PREFIX = 'remote-rest-user-requests-';

	/**
	 * The transient name for the current day.
	 *
	 * @example remote-rest-user-requests-2019-05-15
	 *
	 * @var string
	 */
	private $transient_name;

	/**
	 * Length of time in seconds the statistics should be stored in the transient.
	 *
	 * @var int
	 */
	private $transient_expiration;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string                                        $plugin_name The name of this plugin.
	 * @param      string                                        $version The version of this plugin.
	 * @param      EA_WP_Remote_REST_User_Authentication_Options $config The plugin config.
	 */
	public function __construct( $plugin_name, $version, $config ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->config = $config;

		$today_string         = date( 'Y-m-d' );
		$this->transient_name = self::TRANSIENT_PREFIX . $today_string;

		$this->transient_expiration = 60 * 60 * 24 * 7;
	}


	/**
	 * The crux of the plugin
	 *
	 * Runs on WP `authenticate` filter
	 *
	 * Ideally returns a WP_User $user_object
	 *
	 * @param WP_User|WP_Error|null $user_object   A user object if the already authenticated, error object from earlier authenticator, or null.
	 * @param string                $user_field   The username or email entered.
	 * @param string                $password     The password.
	 *
	 * @return bool|int|WP_Error|WP_User
	 */
	public function remote_rest_authenticate( $user_object, $user_field, $password ) {

		// Debug that we are here.
		// do_action( 'ea_log_debug',
		// $this->plugin_name,
		// $this->version,
		// __FUNCTION__
		// );
		// If the credentials entered match a local user, it should be logged in by now.
		if ( $user_object instanceof WP_User ) {

			// DEBUG: All good, we don't need to process.
			do_action(
				'ea_log_debug',
				$this->plugin_name,
				$this->version,
				'Valid user object passed to authenticate filter',
				array( 'function' => __FUNCTION__ )
			);

			return $user_object;
		}

		// If something other than WP_User or WP_Error is passed as $user_object.
		if ( ! is_null( $user_object ) && ! is_wp_error( $user_object ) ) {

			// What does this mean?
			// Maybe this could never happen.
			do_action(
				'ea_log_notice',
				$this->plugin_name,
				$this->version,
				'Unexpected object passed to authenticate filter',
				array(
					'error'    => $user_object,
					'function' => __FUNCTION__,
					'class'    => __CLASS__,
				)
			);
		}

		if ( empty( $user_field ) || empty( $password ) ) {

			// This happens when the login screen is loaded, before login is requested.
			// We don't have enough input to log in anyway so pass this along.
			return $user_object;
		}

		// User might exist locally but registered here with a different username to remote site.
		// It's also true that two people have the same username, one on each site, and this will
		// fail to work for one of them, but this seems the more prudent approach.
		// We will query the remote site by email address where possible.
		$login_type                       = is_email( $user_field ) ? 'email' : 'login';
		$local_user_from_login_form_input = get_user_by( $login_type, $user_field );

		if ( $local_user_from_login_form_input ) {

			do_action(
				'ea_log_debug',
				$this->plugin_name,
				$this->version,
				'User ' . $user_field . ' exists locally with email ' . $local_user_from_login_form_input->user_email,
				array(
					'user_id'  => $local_user_from_login_form_input->ID,
					'function' => __FUNCTION__,
					'class'    => __CLASS__,
				)
			);

			$user_field = $local_user_from_login_form_input->user_email;

		} else {

			do_action(
				'ea_log_debug',
				$this->plugin_name,
				$this->version,
				'User ' . $user_field . ' not found locally.',
				array(
					'user'     => $user_field,
					'function' => __FUNCTION__,
					'class'    => __CLASS__,
				)
			);
		}

		$remote_sites = $this->config->get_remote_sites();

		$num_remote_sites = count( $remote_sites );

		if ( 0 === $num_remote_sites ) {

			// TODO: log and use transient/option to show admin notice.
			return $user_object;
		}

		// Count uses for dashboard stats.
		// Prepare today's transient for dashboard statistics.
		$remote_rest_user_requests_run_today = get_transient( $this->transient_name );

		if ( false === $remote_rest_user_requests_run_today ) {
			$remote_rest_user_requests_run_today = array(
				'total' => 0,
			);
		}

		$remote_rest_user_requests_run_today['total'] = $remote_rest_user_requests_run_today['total'] + 1;

		set_transient( $this->transient_name, $remote_rest_user_requests_run_today, $this->transient_expiration );

		for ( $x = 0; $x < $num_remote_sites; $x++ ) {

			$remote_site = $remote_sites[ $x ];

			$url = 'https://' . $remote_site . EA_WP_Remote_REST_User_Authentication_Options::USER_API_ENDPOINT;

			$headers = array(
				'Authorization'    => 'Basic ' . base64_encode( $user_field . ':' . $password ),
				$this->plugin_name => $this->version,
			);

			// Timeout is set low so users aren't left waiting on the login screen.
			$request_args = array(
				'headers' => $headers,
				'timeout' => 3,
			);

			do_action(
				'ea_log_debug',
				$this->plugin_name,
				$this->version,
				'Authenticating against remote site ' . $remote_site,
				array(
					'url'      => $url,
					'file'     => __FILE__,
					'class'    => __CLASS__,
					'function' => __FUNCTION__,
				)
			);

			$request_response = wp_remote_get( $url, $request_args );

			if ( is_wp_error( $request_response ) ) {

				do_action(
					'ea_log_error',
					$this->plugin_name,
					$this->version,
					'wp_error request_response -- remote server down? ' . $remote_site,
					array(
						'remote_site' => $remote_site,
						'error'       => $request_response,
						'file'        => __FILE__,
						'class'       => __CLASS__,
						'function'    => __FUNCTION__,
					)
				);

				continue;
			}

			// Response where user did not authenticate.
			if ( 401 === $request_response['response']['code'] ) {

				do_action(
					'ea_log_debug',
					$this->plugin_name,
					$this->version,
					'Could not authenticate against remote site ' . $remote_site,
					array(
						'remote_site' => $remote_site,
						'file'        => __FILE__,
						'class'       => __CLASS__,
						'function'    => __FUNCTION__,
					)
				);

				continue;

				// Other unsuccessful remote authentication attempts.
			} elseif ( 200 !== $request_response['response']['code'] ) {

				do_action(
					'ea_log_notice',
					$this->plugin_name,
					$this->version,
					'Unexpected response authenticating against remote site ' . $remote_site,
					array(
						'remote_site'      => $remote_site,
						'request_response' => $request_response,
						'file'             => __FILE__,
						'class'            => __CLASS__,
						'function'         => __FUNCTION__,
					)
				);

				continue;
			}

			// Otherwise, we've status 200 and are logged in.
			do_action(
				'ea_log_info',
				$this->plugin_name,
				$this->version,
				'User ' . $user_field . ' successfully authenticated against ' . $remote_site,
				array(
					'remote_site' => $remote_site,
					'file'        => __FILE__,
					'class'       => __CLASS__,
					'function'    => __FUNCTION__,
				)
			);

			// Add to the statistics transient for dashboard reporting.
			if ( ! isset( $remote_rest_user_requests_run_today[ $remote_site ] ) ) {
				$remote_rest_user_requests_run_today[ $remote_site ] = 1;
			} else {
				$remote_rest_user_requests_run_today[ $remote_site ] = $remote_rest_user_requests_run_today[ $remote_site ] + 1;
			}
			set_transient( $this->transient_name, $remote_rest_user_requests_run_today, $this->transient_expiration );

			$body = json_decode( $request_response['body'] );

			// If there's a local user, we used that email to authenticate which is safe.
			// We don't care about the remote username, it's not a unique id.
			if ( $local_user_from_login_form_input ) {

				// Using info here because it should only happen once per user,
				// next time they'll just be immediately logged in.
				do_action(
					'ea_log_info',
					$this->plugin_name,
					$this->version,
					'Local user ' . $local_user_from_login_form_input->ID . ', ' . $local_user_from_login_form_input->user_login . ' authenticated against ' . $remote_site,
					array(
						'response_body' => $body,
						'file'          => __FILE__,
						'class'         => __CLASS__,
						'function'      => __FUNCTION__,
					)
				);

				// TODO: Does this send an email?
				wp_set_password( $password, $local_user_from_login_form_input->ID );

				update_user_meta( $local_user_from_login_form_input->ID, 'default_password_nag', '0' );
				update_user_meta( $local_user_from_login_form_input->ID, $remote_site, $body->id );

				// Other auth plugins don't work well with mixed username/user. If this works, let's just leave it at that!
				remove_all_actions( 'authenticate' );

				return $local_user_from_login_form_input;
			}

			// Scenarios from here... we've authenticated / we've no local user.
			$new_local_user_username = null;
			$new_local_user_email    = null;

			// Some remote sites will have `email` and `username` in the REST response.
			// If there is no local user, we can create one from the email address but we cannot without.
			if ( ! is_email( $user_field ) ) {

				$new_local_user_username = $user_field;

				if ( isset( $body->email ) ) {

					$new_local_user_email = $body->email;

				} else {

					do_action(
						'ea_log_notice',
						$this->plugin_name,
						$this->version,
						'Email not in REST response for ' . $remote_site,
						array(
							'response_body' => $body,
							'file'          => __FILE__,
							'class'         => __CLASS__,
							'function'      => __FUNCTION__,
						)
					);

					// It's possible the user has an account on both sites with the same email
					// but has logged into this one with the wrong username.
					$error = new WP_Error( 'potential_remote_user', sprintf( __( 'Please login using your email address.' ) ) );

					return $error;
				}
			} else { // $user_field (login_type) is an email address.

				$new_local_user_email = $user_field;

				if ( isset( $body->username ) ) {

					$new_local_user_username = $body->username;

				} else {

					// Create a username from the email prefix.
					$parts                = explode( '@', $new_local_user_email );
					$username             = $parts[0];
					$new_local_user_email = sanitize_user( $username );

				}
			}

			// create new user:
			// TODO: Move this to a static variable in an appropriate class.
			// This action is to allow other auth/new user plugins to be removed so they don't interfere.
			do_action( 'ea_wp_remote_rest_user_authentication_before_register_new_user' );

			// The user already has an account with us (remotely),
			// //this remote authentication is supposed to be transparent.
			remove_action( 'register_new_user', 'wp_send_new_user_notifications' );

			// If the username is already taken, append an incrementing digit.
			$new_local_user_username_base = $new_local_user_username;
			$append_digit                 = 2;

			while ( false !== get_user_by( 'login', $new_local_user_username ) ) {
				$new_local_user_username = $new_local_user_username_base . $append_digit;
				$append_digit++;
			}

			$new_user_id = register_new_user( $new_local_user_username, $new_local_user_email );

			if ( is_wp_error( $new_user_id ) ) {

				/**
				 * Unsure why exactly this would be an error.
				 *
				 * @var WP_Error $new_user_id
				 */

				do_action(
					'ea_log_error',
					$this->plugin_name,
					$this->version,
					'Error creating user for ' . $user_field,
					array(
						'error'      => $new_user_id,
						'user_field' => $user_field,
						'file'       => __FILE__,
						'class'      => __CLASS__,
						'function'   => __FUNCTION__,
					)
				);

				// Don't pass the error to users, since this plugin should be seamless,
				// Any error message would only confuse users.
				continue;
			}

			wp_set_password( $password, $new_user_id );
			update_user_meta( $new_user_id, 'default_password_nag', '0' );

			$local_user_from_login_form_input = get_user_by( 'id', $new_user_id );

			do_action(
				'ea_log_info',
				$this->plugin_name,
				$this->version,
				'User ' . $new_user_id . ' created for ' . $new_local_user_email,
				array(
					'user_id'  => $new_user_id,
					'email'    => $new_local_user_email,
					'file'     => __FILE__,
					'class'    => __CLASS__,
					'function' => __FUNCTION__,
				)
			);

			remove_all_actions( 'authenticate' );

			return $local_user_from_login_form_input;
		}

		return $user_object;
	}

	/**
	 * This runs on determine_current_user which is before authenticate but exits
	 * quickly if irrelevant.
	 *
	 * Priority is set to run after the JSON Basic Authentication plugin (@ 20),
	 * so if the user exists locally, they will already be set before this is hit.
	 *
	 * @see https://usersinsights.com/wordpress-user-login-hooks/
	 *
	 * @param int $user_id User id as determined by an earlier filter.
	 *
	 * @return int Local user id.
	 */
	public function remote_rest_forwarder( $user_id ) {

		// If the Basic Auth plugin isn't installed, don't use basic auth credentials to authenticate.
		if ( ! function_exists( 'json_basic_auth_handler' ) ) {
			return $user_id;
		}

		// Don't authenticate twice.
		if ( ! empty( $user_id ) ) {
			return $user_id;
		}

		// Nothing to do.
		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) || ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			return $user_id;
		}

		$plugin_http_header = str_replace( '-', '_', strtoupper( $this->plugin_name ) );

		// Don't forward if the request came from the same plugin remotely.
		// Prevent an infinite loop between sites using this plugin.
		// "HTTP_EA_WP_REMOTE_REST_USER_AUTHENTICATION": "1.0.0".
		if ( isset( $_SERVER[ 'HTTP_' . $plugin_http_header ] ) ) {

			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '-HTTP_USER_AGENT- not set';

			do_action(
				'ea_log_debug',
				$this->plugin_name,
				$this->version,
				'Basic auth request containing header: ' . $plugin_http_header,
				array(
					'remote_site' => $user_agent,
					'file'        => __FILE__,
					'class'       => __CLASS__,
					'function'    => __FUNCTION__,
				)
			);

			// TODO: If the versions don't match, maybe advise updating.
			return $user_id;
		}

		// TODO: Can usernames contain slashed?
		$username = $_SERVER['PHP_AUTH_USER'];

		// WordPress coding standards says to use unslash() here but / is a valid character in passwords.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$password = $_SERVER['PHP_AUTH_PW'];

		$user_object = $this->remote_rest_authenticate( null, $username, $password );

		if ( $user_object instanceof WP_User ) {

			return $user_object->ID;
		}

		return $user_id;
	}

}
