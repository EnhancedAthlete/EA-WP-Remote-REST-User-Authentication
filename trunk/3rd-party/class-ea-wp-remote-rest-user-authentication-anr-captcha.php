<?php
/**
 * Functionality specifically for installs with Advanced NoCaptcha ReCaptcha
 * which would otherwise prevent registration completing
 *
 * @see https://wordpress.org/plugins/advanced-nocaptcha-recaptcha/
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    EA_WP_Remote_REST_User_Authentication
 * @subpackage EA_WP_Remote_REST_User_Authentication/3rd-party
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    EA_WP_Remote_REST_User_Authentication
 * @subpackage EA_WP_Remote_REST_User_Authentication/3rd-party
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class EA_WP_Remote_REST_User_Authentication_ANR_Captcha {

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

	public function remove_anr_captcha_class() {

		// Need to remove the captcha verification or new user creation fails
		// https://wordpress.org/plugins/advanced-nocaptcha-recaptcha/
		if ( class_exists( 'anr_captcha_class' ) ) {

			// The plugin
			// Advanced noCaptcha & invisible Captcha
			// https://wordpress.org/plugins/advanced-nocaptcha-recaptcha/
			do_action(
				'ea_log_debug',
				$this->plugin_name,
				$this->version,
				'Removing hooks for Advanced noCaptcha & Invisible Captcha plugin',
				array(
					'file'     => __FILE__,
					'class'    => __CLASS__,
					'function' => __FUNCTION__,
				)
			);

			$captcha_class = \anr_captcha_class::init();

			remove_action( 'register_form', array( $captcha_class, 'form_field' ), 99 );
			remove_action( 'woocommerce_register_form', array( $captcha_class, 'form_field' ), 99 );
			remove_filter( 'registration_errors', array( $captcha_class, 'registration_verify' ), 10 );
			remove_filter(
				'woocommerce_registration_errors',
				array(
					$captcha_class,
					'wc_registration_verify',
				),
				10
			);
		}
	}

}
