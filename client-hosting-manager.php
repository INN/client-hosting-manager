<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://profiles.wordpress.org/inn_nerds/
 * @since             1.0.0
 * @package           Client_Hosting_Manager
 *
 * @wordpress-plugin
 * Plugin Name:       Client Hosting Manager
 * Plugin URI:        https://github.com/INN/client-hosting-manager
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            inn_nerds
 * Author URI:        https://profiles.wordpress.org/inn_nerds/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       client-hosting-manager
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$org = array(
	'name' => 'INN',
	'support_email' => 'support@largoproject.org',
	'admin_email' => 'largo@investigativenewsnetwork.org',
	'domains' => array( 'inn.org', 'investigativenewsnetwork.org' );
);

/**
 * The code that runs during plugin activation.
 *
 * Remove capabilities for the admin role
 * Check for any admins that have org emails and grant their accounts the capabilities
 */
function activate_client_hosting_manager() {

	$administrator = get_role( 'administrator' );
	client_hosting_manager_update_caps( 'remove', $administrator );

	$admins = get_users( array( 'role' => 'administrator' ) );
	foreach ( $admins as $admin ) {
		foreach ( $org['domains'] as $domain ) {
			if ( strpos( $admin->data->user_email, $domain ) != false ) {
				$user = new WP_User( $admin->data_user_email );
				client_hosting_manager_update_caps( 'add', $user );
			}
		}
	}	
}
register_activation_hook( __FILE__, 'activate_client_hosting_manager' );

/**
 * The code that runs during plugin deactivation.
 *
 * Restore default capabilities for the admin role
 * Check for any admins that have org emails and remove overridden capabilities
 */
function deactivate_client_hosting_manager() {

	$administrator = get_role( 'administrator' );
	client_hosting_manager_update_caps( 'add', $administrator );

	$admins = get_users( array( 'role' => 'administrator' ) );
	foreach ( $admins as $admin ) {
		foreach ( $org['domains'] as $domain ) {
			if ( strpos( $admin->data->user_email, $domain ) != false ) {
				$user = new WP_User( $admin->data_user_email );
				client_hosting_manager_update_caps( 'remove', $user );
			}
		}
	}	
}
register_deactivation_hook( __FILE__, 'deactivate_client_hosting_manager' );

function client_hosting_manager_update_caps( $action, $object ) {
	$caps = array(
		'install_plugins',
		'activate_plugins',
		'edit_plugins',
		'install_themes',
		'switch_themes',
		'edit_themes'
	);

	foreach ( $caps as $cap ) {
		switch ( $action ) {
			case 'add':
				$administrator->add_cap( $cap );
				break;
			case 'remove':
				$administrator->remove_cap( $cap );
				break;
		}
	}
}

class Client_Hosting_Manager {

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

		$this->plugin_name = 'client-hosting-manager';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}


	/**
	 * Prevent org's master user from being deleted.
	 *
	 * @since      1.0.0
	 * @param      int    $user_id    	The new user's id.
	 */
	public function delete_user( $user_id ) {
		global $wpdb, $org;
		$user_obj = get_userdata( $user_id );	
		if ( $user_obj->user_email ===  $org['admin_email'] ) {
			wp_die( 'You do not have permissions to delete ' . $org['name'] . ' users. Please contact <a href="mailto:' . $org['support_email'] . '">' . $org['support_email'] . '</a> for more information.' );
		}
	}

	/**
	 * If a new user administrator is created with an org email, grant their accounts the capabilities.
	 *
	 * @since      1.0.0
	 * @param      int    $user_id    	The new user's id.
	 */
	public function user_registration( $user_id ) {
		global $org;

		$user = get_userdata( $user_id );
		foreach ( $org['domains'] as $domain ) {
			if ( strpos( $user->user_email, $domain ) != false && in_array( 'administrator', $user->roles ) ) {
				client_hosting_manager_update_caps( 'add', $user );
			}
		}
	}

	/**
	 * If a user role is changed to administrator and the user has an org email, grant their account the capabilities.
	 * If a user role is changed from administrator and the user has an org email, remove the capabilities from their account.
	 *
	 * @since      1.0.0
	 * @param      int	$user_id       The name of this plugin.
	 * @param      string   $old_role      The user's old role. 
	 * @param      string   $new_role      The user's new role.
	 */
	public function user_role_update( $user_id, $old_role, $new_role ) {
		global $org;

		if ( 'administrator' == $new_role && 'administrator' != $old_role ) {
			$user = get_userdata( $user_id );
			foreach ( $org['domains'] as $domain ) {
				if ( strpos( $user->user_email, $domain ) != false ) {
					client_hosting_manager_update_caps( 'add', $user );
				}
			}
		}
		elseif ( 'administrator' != $new_role && 'administrator' == $old_role ) {
			$user = get_userdata( $user_id );
			foreach ( $org['domains'] as $domain ) {
				if ( strpos( $user->user_email, $domain ) != false ) {
					client_hosting_manager_update_caps( 'remove', $user );
				}
			}
		}	
	}
}

$plugin = new Client_Hosting_Manager();
add_action( 'delete_user', $plugin->delete_user, 10, 1 );
add_action( 'user_register', $plugin->user_registration, 10, 1 );
add_action( 'set_user_role', $plugin->user_role_update, 10, 3 );
