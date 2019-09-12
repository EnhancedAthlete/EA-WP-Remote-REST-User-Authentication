<?php


class AuthenticationTest extends \WP_Mock\Tools\TestCase {

	private $plugin_name;
	private $version;

	/** @var EA_WP_Remote_REST_User_Authentication_Authenticator */
	private $sut;

	public function setUp(): void {

		$this->plugin_name = 'ea-wp-remote-rest-user-authentication-tests';
		$this->version     = '1.0.0';

		$basedir = dirname( dirname( dirname( __FILE__ ) ) );

		require_once $basedir . '/vendor/wordpress/wordpress/src/wp-includes/class-wp-error.php';

		require_once $basedir . '/trunk/includes/class-ea-wp-remote-rest-user-authentication-options.php';
		require_once $basedir . '/trunk/login/class-ea-wp-remote-rest-user-authentication-authenticator.php';

		\WP_Mock::setUp();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'ea-wp-remote-rest-user-authentication-remote-sites', array() ),
				'times'  => 1,
				'return' => array( 'example.com' ),
			)
		);

		$config = new EA_WP_Remote_REST_User_Authentication_Options( $this->plugin_name, $this->version );

		$this->sut = new EA_WP_Remote_REST_User_Authentication_Authenticator( $this->plugin_name, $this->version, $config );

	}

	/**
	 * Login using username
	 * Successful remote authentication
	 * Remote REST response contains user email
	 * Local account matches email
	 *
	 * Updates local password
	 */
	public function test_remote_rest_authenticate_remote_user_email_exists_locally() {

		// Runs on WP authenticate filter
		// currently at priority 50
		// assumed/intended to be late, thus local authentication would be complete by now
		// Test parameters
		$user_object = null;
		$user_field  = 'username';
		$password    = 'password';

		// Debug log at beginning of function
		// \WP_Mock::expectAction( 'ea_log_debug', $this->plugin_name, $this->version, 'remote_rest_authenticate' );
		\WP_Mock::userFunction(
			'is_wp_error',
			array(
				'args'   => $user_object,
				'times'  => 1,
				'return' => false,
			)
		);

		// uses array() as second argument for default return value when there are no pending subscriptions
		\WP_Mock::userFunction(
			'is_email',
			array(
				'args'   => $user_field,
				'times'  => 1,
				'return' => false,
			)
		);

		$local_user             = new stdClass();
		$local_user->ID         = '456';
		$local_user->user_email = 'email@example.com';
		$local_user->user_login = $user_field;

		\WP_Mock::userFunction(
			'get_user_by',
			array(
				'args'   => array( 'login', $user_field ),
				'times'  => 1,
				'return' => $local_user,
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'args'  => array(
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'array' ),
					\WP_Mock\Functions::type( 'int' ),
				),
				'times' => 1,
			)
		);

		// ~"No local user found"
		// \WP_Mock::expectAction( 'ea_log_debug' );
		// ~"Authenticating against remote site"
		// \WP_Mock::expectAction( 'ea_log_debug' );
		$remote_user_id    = 123;
		$remote_user_email = 'email@example.com';

		$request_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode(
				array(
					'id'    => $remote_user_id,
					'email' => $remote_user_email,
				)
			),
		);

		\WP_Mock::userFunction(
			'wp_remote_get',
			array(
				'times'  => 1,
				'return' => $request_response,
			)
		);

		\WP_Mock::userFunction(
			'is_wp_error',
			array(
				'args'   => array( '*' ),
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'args'  => array(
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'array' ),
					\WP_Mock\Functions::type( 'int' ),
				),
				'times' => 1,
			)
		);

		// ~"success"
		// \WP_Mock::expectAction( 'ea_log_info', array()  );
		\WP_Mock::userFunction(
			'wp_set_password',
			array(
				'args'  => array( $password, $local_user->ID ),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'update_user_meta',
			array(
				'args'  => array( $local_user->ID, 'default_password_nag', '0' ),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'update_user_meta',
			array(
				'args'  => array( $local_user->ID, 'example.com', $remote_user_id ),
				'times' => 1,
			)
		);

		// remove_all_actions( 'authenticate' );
		\WP_Mock::userFunction(
			'remove_all_actions',
			array(
				'args'  => 'authenticate',
				'times' => 1,
			)
		);

		$result = $this->sut->remote_rest_authenticate( $user_object, $user_field, $password );

		self::assertEquals( $local_user, $result );

	}


	/**
	 * Login using username
	 * Successfully authenticate
	 * REST response contains email field
	 * No matching local user with that email address
	 *
	 * Creates local user
	 */
	public function test_remote_rest_authenticate_create_local_user_with_email_exposed_in_rest() {

		// Test parameters
		$user_object = null;
		$user_field  = 'username';
		$password    = 'password';

		// Debug log at beginning of function
		// \WP_Mock::expectAction( 'ea_log_debug', $this->plugin_name, $this->version, 'remote_rest_authenticate' );
		\WP_Mock::userFunction(
			'is_wp_error',
			array(
				'args'   => $user_object,
				'times'  => 1,
				'return' => false,
			)
		);

		// uses array() as second argument for default return value when there are no pending subscriptions
		\WP_Mock::userFunction(
			'is_email',
			array(
				'args'   => $user_field,
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_user_by',
			array(
				'args'   => array( 'login', $user_field ),
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'args'  => array(
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'array' ),
					\WP_Mock\Functions::type( 'int' ),
				),
				'times' => 1,
			)
		);

		$remote_user_id    = 123;
		$remote_user_email = 'email@example.com';

		$request_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode(
				array(
					'id'       => $remote_user_id,
					'email'    => $remote_user_email,
					'username' => $user_field,
				)
			),
		);

		\WP_Mock::userFunction(
			'wp_remote_get',
			array(
				'times'  => 1,
				'return' => $request_response,
			)
		);

		\WP_Mock::userFunction(
			'is_wp_error',
			array(
				'args'   => array( '*' ),
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'args'  => array(
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'array' ),
					\WP_Mock\Functions::type( 'int' ),
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'remove_action',
			array(
				'args'  => array( 'register_new_user', 'wp_send_new_user_notifications' ),
				'times' => 1,
			)
		);

		$local_new_user        = new stdClass();
		$local_new_user->ID    = '456';
		$local_new_user->email = $remote_user_email;

		\WP_Mock::userFunction(
			'get_user_by',
			array(
				'args'   => array( 'login', '*' ),
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'register_new_user',
			array(
				'args'   => array( $user_field, $remote_user_email ),
				'times'  => 1,
				'return' => $local_new_user->ID,
			)
		);

		\WP_Mock::userFunction(
			'is_wp_error',
			array(
				'args'   => $local_new_user->ID,
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'wp_set_password',
			array(
				'args'  => array( $password, $local_new_user->ID ),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'update_user_meta',
			array(
				'args'  => array( $local_new_user->ID, 'default_password_nag', '0' ),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'get_user_by',
			array(
				'args'   => array( 'id', $local_new_user->ID ),
				'times'  => 1,
				'return' => $local_new_user,
			)
		);

		// remove_all_actions( 'authenticate' );
		\WP_Mock::userFunction(
			'remove_all_actions',
			array(
				'args'  => 'authenticate',
				'times' => 1,
			)
		);

		$result = $this->sut->remote_rest_authenticate( $user_object, $user_field, $password );

		self::assertEquals( $local_new_user, $result );

	}

	/**
	 * Log in using username
	 * Successfully authenticate
	 * Email field not in REST response
	 *
	 * Not enough data to create local user
	 * Return WP Error suggesting to try email address
	 */
	public function test_remote_rest_authenticate_create_local_user_with_email_not_exposed_in_rest() {

		// Test parameters
		$user_object = null;
		$user_field  = 'username';
		$password    = 'password';

		// Debug log at beginning of function
		// \WP_Mock::expectAction( 'ea_log_debug', $this->plugin_name, $this->version, 'remote_rest_authenticate' );
		\WP_Mock::userFunction(
			'is_wp_error',
			array(
				'args'   => $user_object,
				'times'  => 1,
				'return' => false,
			)
		);

		// uses array() as second argument for default return value when there are no pending subscriptions
		\WP_Mock::userFunction(
			'is_email',
			array(
				'args'   => $user_field,
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_user_by',
			array(
				'args'   => array( 'login', $user_field ),
				'times'  => 1,
				'return' => false,
			)
		);

		$remote_user_id = 123;

		$request_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode(
				array(
					'id'       => $remote_user_id,
					'username' => $user_field,
				)
			),
		);

		\WP_Mock::userFunction(
			'wp_remote_get',
			array(
				'times'  => 1,
				'return' => $request_response,
			)
		);

		\WP_Mock::userFunction(
			'is_wp_error',
			array(
				'args'   => array( '*' ),
				'times'  => 1,
				'return' => false,
			)
		);

		// Returns a WP_Error object
		$result = $this->sut->remote_rest_authenticate( $user_object, $user_field, $password );

		self::assertTrue( $result instanceof WP_Error );

	}
}
