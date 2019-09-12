# EA WP – Remote REST User Authentication

Authenticates users against other WordPress instances using HTTP Basic Authentication against the WordPress REST API.

## About

When a user tries unsuccessfully to login, a successful GET request of `<https://another-website.com>/wp-json/wp/v2/users/me` suggests the correct username and password for that user and creates an account locally with the same username and password.

If a local account exists for the username but the password is incorrect, the plugin uses the local email address and if it successfully authenticates against the remote site, the user is logged in and the local password is updated.

It is assumed both the sites will point at each other for authenticaiton, e.g. a blog and a forum, where a user might sign up on one and when visiting the other later, we want them to log in seamlessly without realising any distinction between the sites.

The remote authentication currently only runs when local login has failed (i.e. late action/filter priority).

Forwards failed basic auth requests.

## How to Use

### Requirements:

Requires the [WordPress Basic Auth](https://github.com/WP-API/Basic-Auth) plugin on remote site.

Works better with `user_email` exposed as `email` in REST `/users/me`, which this plugin will do when installed.

### Scenarios

##### User has local account and logs in normally

This plugin exits quickly.

##### User has local account and logs in by email with incorrect local password

This plugin authenticates against the remote site, and if successful, updates the password for the local account with the same email address.

##### User has local account and logs in by username with incorrect local password

This plugin authenticates against the remote site using the local account's email, continues as above.

##### User has no local account and logs in by email with valid remote credentials

A new user account is set up with the same username, email and password as the remote account. If the username is taken, an incrementing number is appended.

##### User has no local account and logs in by username with valid remote credentials

If `email` field is in the REST response, a local account is searched for with that email address, and logged in.

Or a new user account is created locally with the same username, email and password as the remote account.

If `email` field is not in the REST response the user is prompted to login by email.

## Notes

### Basic Authentication Criticism

A question on security.stackexchange.com asks: [Is BASIC-Auth secure if done over HTTPS?](https://security.stackexchange.com/questions/988/is-basic-auth-secure-if-done-over-https)

The criticims aren't so relevant to this use case:

1. "The password is sent over the wire in ... plaintext" : HTTPS addresses this. This plugin does not function with HTTP.
2. "The password is sent repeatedly, for each request." : The password is sent only once.
3. "The password is cached by the webbrowser" : All functionality is performed by the server, so moot.
4. "The password may be stored permanently in the browser" : As above.

I don't work in IT security, so am open to correction.

### Logging

To handle logs, write functions for `ea_log_notice`, `ea_log_info`, `ea_log_debug`, `ea_log_error`:

```
add_action( 'ea_log_info', 'my_info_log_handler', 10, 4 );

function my_info_log_handler( $plugin_name, $plugin_version, $message, $context = array() ) {
	error_log( $message );
}
```

## Develop

Run `composer install` to install [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer), the  [WordPress Coding Standards](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards), [WP_Mock](https://github.com/10up/wp_mock) (and its [PHP Unit 7](https://github.com/sebastianbergmann/phpunit) dependency) and wordpress-develop testing environment.

Run `vendor/bin/phpcs` to see WordPress Coding Standards errors and run `vendor/bin/phpcbf` to automatically correct them where possible.

WP_Mock tests can be run with:

```
phpunit ./tests/wp-mock --bootstrap ./tests/wp-mock/bootstrap.php
```

The wordpress-develop tests require a local database (which gets wiped each time) and this plugin is set to require a database called `wordpress_tests` and a user named `wordpress-develop` with the password `wordpress-develop`. These tests also require PHP Unit 6, which can be downloaded and make executable with:

```
wget https://phar.phpunit.de/phpunit-6.5.9.phar
chmod +x phpunit-6.5.9.phar
```

(create the database)

The database user can be set up in the MySQL CLI using:

```
CREATE USER 'wordpress-develop'@'%' IDENTIFIED WITH mysql_native_password BY 'wordpress-develop'
GRANT Alter ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Create ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Create view ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Delete ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Drop ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Grant option ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Index ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Insert ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT References ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Select ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Show view ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Trigger ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Update ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Alter routine ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Create routine ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Create temporary tables ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Execute ON wordpress_test.* TO 'wordpress-develop'@'%';
GRANT Lock tables ON wordpress_test.* TO 'wordpress-develop'@'%';
FLUSH PRIVILEGES;
```

The wordpress-develop tests can then be run with:

```
./phpunit-6.5.9.phar tests --bootstrap ./tests/wordpress-develop/bootstrap.php
```

## TODO: 

* Doesn't appear to be running on REST requests (i.e. from app). Could potentially cause infinite loop once fixed.
* Add settings page UI for specifying sites: currently `update_option( 'ea-wp-remote-rest-user-authentication-remote-sites', array( '<remote-domain>.com' ) );` (without `https://` or `/wp-json/...`)
* Perform a HTTP OPTION against the remote REST when adding in admin UI
* Forgot password: don't say "no account" if there is a remote account
* When someone requests a new password and the account exists locally but not remotely, just create the account from the remote personal information and send a new password
* Sites send a "click here to set your password" when account is created from a remote site: Disable email.
* Could remote auth plugin type be flexible?
* Add filter on authentication header generation to allow other auth mechanisms
* GDPR: user might assume deleting account on one site will delete it everywhere, and that's the law
* Can logging in one site actually log in on all? Maybe some iframe trickery with autologinlinks.
* Updates could be reflected (username, display name, email, user meta)
		
## Acknowledgements

Built by [Brian Henry](https://BrianHenry.ie) using [WordPress Plugin Boilerplate](https://wppb.me/), the [WordPress Coding Standards](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards) and [WP Mock](https://github.com/10up/wp_mock) for:

[![Enhanced Athlete](./assets/enhanced_athlete.png "Enhanced Athlete")](https://EnhancedAthlete.com)