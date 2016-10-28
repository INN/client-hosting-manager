<?php
/*
Plugin Name: INN Hosting Manager
Plugin URI: 
Description: Basic WordPress Plugin Header Comment
Version:     1.0 
Author:      inn_nerds 
Author URI:  https://profiles.wordpress.org/inn_nerds/#content-plugins
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: inn_hosting_manager
Donate link: https://inn.org/donate
*/

$org = array(
	'name' => 'INN',
	'support_email' => 'support@largoproject.org',
	'admin_email' => 'largo@investigativenewsnetwork.org',
	'domain' => '@inn.org'
);


function inn_hosting_manager_delete_user( $user_id ) {
	global $wpdb, $org;
        $user_obj = get_userdata( $user_id );	
	if ( $user_obj->user_email ===  $org['admin_email'] ) {
		wp_die( 'You do not have permissions to delete ' . $org['name'] . ' users. Please contact <a href="mailto:' . $org['support_email'] . '">' . $org['support_email'] . '</a> for more information.' );
	}
}
add_action( 'delete_user', 'inn_hosting_manager_delete_user' );

function inn_hosting_manager_user_profile_update( $user_id, $old_user_data ) {
	var_dump( $old_user_data );
	exit;
}
add_action( 'profile_update', 'inn_hosting_manager_user_profile_update' );

/**
 * Remove capabilities for the admin role
 * Check for any admins that have org emails and grant their accounts the capabilities
 */
function inn_hosting_manager_remove_default_admin_caps() {
	global $org;

	$administrator = get_role( 'administrator' );
	inn_hosting_manager_update_caps( 'remove', $administrator );

	$admins = get_users( array( 'role' => 'administrator' ) );
	foreach ( $admins as $admin ) {
		if ( strpos( $admin->data->user_email, $org['domain'] ) != false ) {
			$user = new WP_User( $admin->data_user_email );
			inn_hosting_manager_update_caps( 'add', $user );
		}
	}	
}
register_activation_hook( __FILE__, 'inn_hosting_manager_remove_default_admin_caps' );


/**
 * Undo our work, leave no trace behind
 * grant capabilities for the admin role
 * Check for any admins that have org emails and grant their accounts the capabilities
 */
function inn_hosting_manager_leave_no_trace() {
	global $org;

	$administrator = get_role( 'administrator' );
	inn_hosting_manager_update_caps( 'add', $administrator );

	$admins = get_users( array( 'role' => 'administrator' ) );
	foreach ( $admins as $admin ) {
		if ( strpos( $admin->data->user_email, $org['domain'] ) != false ) {
			$user = new WP_User( $admin->data_user_email );
			inn_hosting_manager_update_caps( 'remove', $user );
		}
	}	
}
register_deactivation_hook( __FILE__, 'inn_hosting_manager_leave_no_trace' );


/**
 * If a new user administrator is created with an org email, grant their accounts the capabilities
 */
function inn_hosting_manager_user_registers( $user_id ) {
	global $org;

	$user = get_userdata( $user_id );
	if ( strpos( $user->user_email, $org['domain'] ) != false && in_array( 'administrator', $user->roles ) ) {
		inn_hosting_manager_update_caps( 'add', $user );
	}
}
add_action( 'user_register', 'inn_hosting_manager_user_registers', 10, 1 );


/*
 * If a user role is changed to administrator and the user has an org email, grant their account the capabilities
 * If a user role is changed from administrator and the user has an org email, remove the capabilities from their account
 */
function inn_hosting_manager_user_role_update( $user_id, $old_role, $new_role ) {
	global $org;

	if ( 'administrator' == $new_role && 'administrator' != $old_role ) {
		$user = get_userdata( $user_id );
		if ( strpos( $user->user_email, $org['domain'] ) != false ) {
			inn_hosting_manager_update_caps( 'add', $user );
		}
	}
	elseif ( 'administrator' != $new_role && 'administrator' == $old_role ) {
		$user = get_userdata( $user_id );
		if ( strpos( $user->user_email, $org['domain'] ) != false ) {
			inn_hosting_manager_update_caps( 'remove', $user );
		}
	}	
}
add_action( 'set_user_role', 'inn_hosting_manager_user_registers', 10, 3 );

function inn_hosting_manager_update_caps( $action, $object ) {
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
?>
