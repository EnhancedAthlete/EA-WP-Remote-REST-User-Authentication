<?php
/**
 * wp-admin settings page
 *
 * @see /wp-admin/options-general.php?page=ea-wp-remote-rest-user-authentication
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    EA_WP_Remote_REST_User_Authentication
 * @subpackage EA_WP_Remote_REST_User_Authentication/admin/partials
 */
?>

<div class="wrap">
	<h1>EA WP Remote REST User Authentication</h1>

	<?php
	// Let see if we have a caching notice to show
	$admin_notice = get_option( 'ea_wp_remote_rest_user_authentication_admin_notice' );
	if ( $admin_notice ) {
		// We have the notice from the DB, let's remove it.
		delete_option( 'ea_wp_remote_rest_user_authentication_admin_notice' );
		// Call the notice message
		$this->admin_notice( $admin_notice );
	}
	if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
		$this->admin_notice( 'Your settings have been updated!' );
	}
	?>
	<form method="POST" action="options.php">
		<?php
		settings_fields( 'ea-wp-remote-rest-user-authentication' );
		do_settings_sections( 'ea-wp-remote-rest-user-authentication' );
		submit_button();
		?>
	</form>

	<?php
	do_action( 'ea_wp_remote_rest_user_authentication_statistics' );
	?>

</div>
