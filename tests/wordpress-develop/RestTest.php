<?php
/**
 * Class RestTest
 *
 * @package EA_WP_Remote_REST_User_Authentication
 */

/**
 * Ensures the required fields have been added to the REST response for /users/ms
 */
class RestTest extends WP_UnitTestCase {

	private $data;

	public function setUp() {
		parent::setUp();

		$user_id = wp_insert_user(
			array(
				'user_pass'     => 'hunter2',
				'user_login'    => 'brianhenryie',
				'user_nicename' => 'brian',
				'user_email'    => 'brianhenryie@gmail.com',
				'display_name'  => 'brian',
			)
		);

		wp_set_current_user( $user_id );

		$rest_server = rest_get_server();

		$request = new WP_REST_Request( 'GET', '/wp/v2/users/me' );

		$response = rest_do_request( $request );

		$this->data = $rest_server->response_to_data( $response, false );
	}

	/**
	 * The email field is not exposed by the WP REST API but is required to create
	 * new users when only the username has been inputted.
	 */
	function test_rest_has_email_field() {

		$this->assertArrayHasKey( 'email', $this->data );

	}

	/**
	 * Conversely, the username field is useful when an email address has been used to log in,
	 * otherwise the local username would have to be auto-generated.
	 */
	function test_rest_has_username_field() {

		$this->assertArrayHasKey( 'username', $this->data );

	}
}
