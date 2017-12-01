# Client Hosting Manager #
**Contributors:**      inn_nerds
**Donate link:**       https://nerds.inn.org
**Tags:**
**Requires at least:** 4.4
**Tested up to:**      4.9
**Stable tag:**        1.0.0
**License:**           GPLv2
**License URI:**       http://www.gnu.org/licenses/gpl-2.0.html

Capability management for agency/client hosting relationships

## Description ##

This plugin restricts the permissions of users whose email address does not match the email address configured by site owners in defined constants. Agencies or other site operators can define a comma-separated list of domain names. Any account with an email address that does not match a domain name in that list will lose the following permissions:

- install_plugins
- activate_plugins
- delete_plugins
- edit_plugins
- install_themes
- switch_themes
- edit_themes
- update_core
- update_plugins
- update_themes

If an account matching that domain is promoted to administrator, that account gains those powers.

The plugin also prevents the site administrator's account from being deleted, in a similar fashion. The admin email address is set in a defined constant, and if a user attempts to delete a user whose email address matches that address, the deletion is prevented.

## Installation ##

### Manual Installation ###

1. Upload the entire `/client-hosting-manager` directory to the `/wp-content/plugins/` directory.
2. In your site's `wp-config.php`, add the following code and replace the bracketed text with information appropriate to your site:
		```
		define( 'client_hosting_manager_org_name', '[organization name]' );
		define( 'client_hosting_manager_support_email', '[email address that users should contact for support]' );
		define( 'client_hosting_manager_admin_email', '[email address of master account that should not be deleted]' );
		define( 'client_hosting_manager_domains', '[comma-separated list of domains]' );
		```
	For example:
		```
		define( 'client_hosting_manager_org_name', 'Example Organization' );
		define( 'client_hosting_manager_support_email', 'support@example.org' );
		define( 'client_hosting_manager_admin_email', 'admin@example.org' );
		define( 'client_hosting_manager_domains', 'people-in-these-domains-have-access-to-plugins.example.com,mail.example.com' );
		```
3. Activate Client Hosting Manager through the 'Plugins' menu in WordPress.

## Frequently Asked Questions ##


## Screenshots ##


## Changelog ##

### 1.0.0 ###
* Initial public release

## Upgrade Notice ##

### 1.0.0 ###
Initial public Release
