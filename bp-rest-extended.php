<?php
/**
 * BP Rest Extended
 *
 * @package   BP Rest Extended
 * @copyright Copyright(c) 2019, AppPresser
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * Plugin Name: BP Rest Extended
 * Description: Extended functionality for BuddyPress JSON rest api.
 * Version: 1.2.3
 * Author: modemlooper, AppPresser
 * Plugin URI: https://github.com/AppPresser-Apps/bp-rest-extended
 * Author URI: https://github.com/apppresser
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bpre
 * Domain Path: languages
 */

define( 'BPRE_VERSION', '1.2.3' );
define( 'BPRE_API_VERSION', '1' );
define( 'BPRE_PLUGIN_NAME', 'BPRE' );
define( 'BPRE_DIR', plugin_dir_path( __FILE__ ) );
define( 'BPRE_URL', plugins_url( '/', __FILE__ ) );
define( 'BPRE_SLUG', plugin_basename( __FILE__ ) );
define( 'BPRE_FILE', __FILE__ );

/**
 * The main function.
 *
 * @return BP_Rest_Extended|null The one true BP_Rest_Extended Instance.
 */
function bpre_init() {

	if ( ! class_exists( 'BuddyPress' ) ) {
		add_action( 'admin_notices', 'bpre_requirements_notice' );
		add_action( 'network_admin_notices', 'bpre_requirements_notice' );
		return;
	}

	require __DIR__ . '/class-bp-rest-extended.php';
	BP_Rest_Extended::instance();
}
add_action( 'bp_loaded', 'bpre_init' );

require_once BPRE_DIR . 'jwt-auth/includes/class-jwt-auth.php';
$jwt = new Jwt_Auth();
$jwt->run();

/**
 * Adds an admin notice to installations that don't have BuddyPress installed.
 *
 * @since 1.0.0
 */
function bpre_requirements_notice() {

	?>

	<div id="message" class="error notice">
		<p><strong><?php esc_attr_e( 'Your site does not have BuddyPress installed. <a target="_blank" href="https://wordpress.org/plugins/buddypress/">Click here to download and install</a>.', 'bpre' ); ?></strong></p>
	</div>

	<?php
}

/**
 * Activation hook
 *
 * @since 1.0.0
 * @return void
 */
function bpre_activate() {
	bpre_init();
	bpre_follow_email_message();
}
register_activation_hook( __FILE__, 'bpre_activate' );

/**
 * Plugin updater. Gets new version from Github.
 */
if ( is_admin() ) {

	function bprest_updater() {

		require 'plugin-update/plugin-update-checker.php';
		$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
			'https://github.com/AppPresser-Apps/bp-rest-extended',
			__FILE__,
			'bprest-extended'
		);

		// Set the branch that contains the stable release.
		$myUpdateChecker->setBranch( 'main' );
		$myUpdateChecker->getVcsApi()->enableReleaseAssets();
	}
	bprest_updater();
}
