<?php
/**
 * Plugin Name: Client Hosting Manager
 * Plugin URI:  https://nerds.inn.org
 * Description: Capability management for agency/client hosting relationships
 * Version:     1.0.0
 * Author:      inn_nerds
 * Author URI:  https://nerds.inn.org
 * Donate link: https://nerds.inn.org
 * License:     GPLv2
 * Text Domain: client-hosting-manager
 * Domain Path: /languages
 *
 * @link https://nerds.inn.org
 *
 * @package Client Hosting Manager
 * @version 1.0.0
 */

/**
 * Copyright (c) 2017 inn_nerds (email : nerds@inn.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Include additional php files here.
// require 'includes/admin.php';

/**
 * Main initiation class
 *
 * @since  1.0.0
 */
final class Client_Hosting_Manager {

	/**
	 * Current version
	 *
	 * @var  string
	 * @since  1.0.0
	 */
	const VERSION = '1.0.0';

	/**
	 * URL of plugin directory
	 *
	 * @var string
	 * @since  1.0.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory
	 *
	 * @var string
	 * @since  1.0.0
	 */
	protected $path = '';

	/**
	 * Plugin basename
	 *
	 * @var string
	 * @since  1.0.0
	 */
	protected $basename = '';

	/**
	 * Detailed activation error messages
	 *
	 * @var array
	 * @since  1.0.0
	 */
	protected $activation_errors = array();

	/**
	 * Singleton instance of plugin
	 *
	 * @var Client_Hosting_Manager
	 * @since  1.0.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  1.0.0
	 * @return Client_Hosting_Manager A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin
	 *
	 * @since  1.0.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function plugin_classes() {
		// Attach other plugin classes to the base plugin class.
		// $this->plugin_class = new CHM_Plugin_Class( $this );
	} // END OF PLUGIN CLASSES FUNCTION

	/**
	 * Add hooks and filters
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function hooks() {
		// Priority needs to be:
		// < 10 for CPT_Core,
		// < 5 for Taxonomy_Core,
		// 0 Widgets because widgets_init runs at init priority 1.
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'delete_user', array( $this, 'delete_user' ), 10, 1 );
		add_action( 'user_register', array( $this, 'user_registration' ), 10, 1 );
		add_action( 'set_user_role', array( $this, 'user_role_update' ), 10, 3 );
	}

	/**
	 * Activate the plugin
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function _activate() {
		$org = $this->org_data();
		$administrator = get_role( 'administrator' );
		$this->client_hosting_manager_update_caps( 'remove', $administrator );

		$admins = get_users( array( 'role' => 'administrator' ) );
		foreach ( $admins as $admin ) {
			foreach ( $org['domains'] as $domain ) {
				if ( strpos( $admin->data->user_email, trim( $domain ) ) !== false ) {
					$user = get_user_by( 'email', $admin->data->user_email );
					$this->client_hosting_manager_update_caps( 'add', $user );
				}
			}
		}
	}

	/**
	 * Deactivate the plugin
	 * Uninstall routines should be in uninstall.php
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function _deactivate() {
		$org = $this->org_data();

		$administrator = get_role( 'administrator' );
		$this->client_hosting_manager_update_caps( 'add', $administrator );

		$admins = get_users( array( 'role' => 'administrator' ) );
		foreach ( $admins as $admin ) {
			foreach ( $org['domains'] as $domain ) {
				if ( strpos( $admin->data->user_email, $domain ) !== false ) {
					$user = get_user_by( 'email', $admin->data->user_email );
					$this->client_hosting_manager_update_caps( 'remove', $user );
				}
			}
		}
	}

	/**
	 * Fetch org constants
	 *
	 * @since  1.0.0
	 * @return array    $org    Array of org data from defined constants
	 */
	public function org_data() {
		$org['name'] = client_hosting_manager_org_name;
		$org['support_email'] = client_hosting_manager_support_email;
		$org['admin_email'] = client_hosting_manager_admin_email;
		$org['domains'] = array_map( 'trim', explode( ',', client_hosting_manager_domains ) ); // Explode comma-separated string into array & trim whitespace

		return $org;
	}

	/**
	 * Update user capabilities
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function client_hosting_manager_update_caps( $action, $object ) {
		$caps = array(
			'install_plugins',
			'activate_plugins',
			'delete_plugins',
			'edit_plugins',
			'install_themes',
			'switch_themes',
			'edit_themes',
			'update_core',
			'update_plugins',
			'update_themes',
		);

		foreach ( $caps as $cap ) {
			switch ( $action ) {
				case 'add':
					$object->add_cap( $cap );
					break;
				case 'remove':
					$object->remove_cap( $cap );
					break;
			}
		}
	}

	/**
	 * Prevent org's master user from being deleted.
	 *
	 * @since      1.0.0
	 * @param      int    $user_id    	The id of the user to be deleted.
	 */
	public function delete_user( $user_id ) {
		global $wpdb;
		$org = $this->org_data();
		$user_obj = get_userdata( $user_id );
		if ( $user_obj->user_email === $org['admin_email'] ) {
			wp_die( 'You do not have permissions to delete ' . esc_html( $org['name'] ) . ' users. Please contact <a href="mailto:' . esc_html( $org['support_email'] ) . '">' . esc_html( $org['support_email'] ) . '</a> for more information.' );
		}
	}

	/**
	 * If a new user administrator is created with an org email, grant their accounts the capabilities.
	 *
	 * @since      1.0.0
	 * @param      int    $user_id    	The new user's id.
	 */
	public function user_registration( $user_id ) {
		$org = $this->org_data();
		$user = get_userdata( $user_id );
		foreach ( $org['domains'] as $domain ) {
			if ( strpos( $user->user_email, $domain ) !== false && in_array( 'administrator', $user->roles, true ) ) {
				$this->client_hosting_manager_update_caps( 'add', $user );
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
		$org = $this->org_data();

		if ( 'administrator' === $new_role && 'administrator' !== $old_role ) {
			$user = get_userdata( $user_id );
			foreach ( $org['domains'] as $domain ) {
				if ( strpos( $user->user_email, $domain ) !== false ) {
					$this->client_hosting_manager_update_caps( 'add', $user );
				}
			}
		} elseif ( 'administrator' !== $new_role && 'administrator' === $old_role ) {
			$user = get_userdata( $user_id );
			foreach ( $org['domains'] as $domain ) {
				if ( strpos( $user->user_email, $domain ) !== false ) {
					$this->client_hosting_manager_update_caps( 'remove', $user );
				}
			}
		}
	}

	/**
	 * Init hooks
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function init() {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Load translated strings for plugin.
		load_plugin_textdomain( 'client-hosting-manager', false, dirname( $this->basename ) . '/languages/' );

		// Initialize plugin classes.
		$this->plugin_classes();
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  1.0.0
	 * @return boolean result of meets_requirements
	 */
	public function check_requirements() {
		// Bail early if plugin meets requirements.
		if ( $this->meets_requirements() ) {
			return true;
		}

		// Add a dashboard notice.
		add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

		// Deactivate our plugin.
		add_action( 'admin_init', array( $this, 'deactivate_me' ) );

		return false;
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function deactivate_me() {
		// We do a check for deactivate_plugins before calling it, to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Check that all plugin requirements are met
	 *
	 * @since  1.0.0
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements() {
		if (
			! defined( 'client_hosting_manager_org_name' ) ||
			! defined( 'client_hosting_manager_support_email' ) ||
			! defined( 'client_hosting_manager_admin_email' ) ||
			! defined( 'client_hosting_manager_domains' ) ) {
				$this->activation_errors[] = "<p>Please add the following code to your wp-config.php file before proceeding:</p>
					<p><code>define( 'client_hosting_manager_org_name', '[organization name]' );</code></p>
					<p><code>define( 'client_hosting_manager_support_email', '[email address that users should contact for support]' );</code></p>
					<p><code>define( 'client_hosting_manager_admin_email', '[email address of master account that should not be deleted]' );</code></p>
					<p><code>define( 'client_hosting_manager_domains', '[comma-separated list of domains]' );</code></p>";
			return false;
		}
		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function requirements_not_met_notice() {
		// Compile default message.
		$default_message = sprintf(
			// translators: this message is to alert adminitstrators that the plugin is missing requirements and cannot be activated.
			__( 'Client Hosting Manager is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'client-hosting-manager' ),
			admin_url( 'plugins.php' )
		);

		// Default details to null.
		$details = null;

		// Add details if any exist.
		if ( ! empty( $this->activation_errors ) && is_array( $this->activation_errors ) ) {
			$details = '<small>' . implode( '</small><br /><small>', $this->activation_errors ) . '</small>';
		}

		// Output errors.
		?>
		<div id="message" class="error">
			<p><?php echo esc_html( $default_message ); ?></p>
			<?php echo esc_html( $details ); ?>
		</div>
		<?php
	}
}

/**
 * Grab the Client_Hosting_Manager object and return it.
 * Wrapper for Client_Hosting_Manager::get_instance()
 *
 * @since  1.0.0
 * @return Client_Hosting_Manager  Singleton instance of plugin class.
 */
function client_hosting_manager() {
	return Client_Hosting_Manager::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( client_hosting_manager(), 'hooks' ) );

register_activation_hook( __FILE__, array( client_hosting_manager(), '_activate' ) );
register_deactivation_hook( __FILE__, array( client_hosting_manager(), '_deactivate' ) );
