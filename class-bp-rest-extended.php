<?php
/**
 * BP Rest Extended
 *
 * @package BP_Rest_Extended
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main BP_Rest_Extended Class.
 *
 * Tap tap tap... Is this thing on?
 *
 * @since 1.0.0
 */
class BP_Rest_Extended {

	/**
	 * Main BP_Rest_Extended Instance.
	 *
	 * @since 1.0.0
	 *
	 * @static object $instance
	 * @see BP_Rest_Extended()
	 *
	 * @return BP_Rest_Extended|null The one true BP_Rest_Extended.
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication.
		static $instance = null;

		// Only run these methods if they haven't been run previously.
		if ( null === $instance ) {
			$instance = new BP_Rest_Extended();
			$instance->includes();
		}

		// Always return the instance.
		return $instance;

		// Long live and prosper.
	}

	/** Magic Methods *********************************************************/

	/**
	 * A dummy constructor to prevent BP_Rest_Extended from being loaded more than once.
	 *
	 * @since 1.0.0
	 * @see BP_Rest_Extended::instance()
	 * @see BP_Rest_Extended()
	 */
	private function __construct() {
		/* Do nothing here */
	}

	/**
	 * Include files
	 *
	 * @return void
	 */
	public function includes() {

		require_once BPRE_DIR . 'includes/attachments/attachments.php';

		require_once BPRE_DIR . 'includes/functions.php';
		require_once BPRE_DIR . 'includes/actions.php';

		require_once BPRE_DIR . 'includes/members/filters.php';
		require_once BPRE_DIR . 'includes/members/actions.php';
		require_once BPRE_DIR . 'includes/members/endpoints.php';
		require_once BPRE_DIR . 'includes/members/functions.php';

		require_once BPRE_DIR . 'includes/groups/endpoints.php';
		require_once BPRE_DIR . 'includes/groups/filters.php';

		require_once BPRE_DIR . 'includes/activity/endpoints.php';
		require_once BPRE_DIR . 'includes/activity/functions.php';
	}

}
