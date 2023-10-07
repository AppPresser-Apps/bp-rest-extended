<?php
/**
 * Moderation functions
 *
 * @package AppPresser
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block user function
 *
 * @since 1.0.0
 *
 * @param string $action
 * @param integer $user_id
 * @return string|boolean
 */
function appp_block_user( $action = '', $user_id = 0 ) {

	$loggedin_user_id = get_current_user_id();

	$response = appp_toggle_blocking( $action, $loggedin_user_id, $user_id );

	return $response;

}

/**
 * Unblock user function
 *
 * @since 1.0.0
 *
 * @param string $action
 * @param integer $user_id
 * @return string|boolean
 */
function appp_unblock_user( $action = '', $user_id = 0 ) {

	$loggedin_user_id = get_current_user_id();

	$response = appp_toggle_blocking( $action, $loggedin_user_id, $user_id );

	return $response;

}

/**
 * Report user function
 *
 * @since 1.0.0
 *
 * @param string $action
 * @param integer $user_id
 * @return void
 */
function appp_report_user( $action = '', $user_id = 0 ) {

	$loggedin_user_id = get_current_user_id();

	return $loggedin_user_id;

}

/**
 * Suspend user function
 *
 * @since 1.0.0
 *
 * @param string $action
 * @param integer $user_id
 * @return void
 */
function appp_suspend_user( $action = '', $user_id = 0 ) {

	$loggedin_user_id = get_current_user_id();

	return $loggedin_user_id;

}

/**
 * Returns whether member is blocked.
 *
 * @since 1.0.0
 *
 * @param int $blocker The current user
 * @param int $blocked The user to test to see if blocked
 * return boolean
 */
function appp_is_blocked( $blocker, $blocked ) {
	$list = get_blocked_users( $blocker );

	if ( in_array( $blocked, $list, true ) ) {
		return true;
	} else {
		return false;
	}

}
