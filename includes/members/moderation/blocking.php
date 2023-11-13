<?php
/**
 * Blocking functions
 *
 * @package AppPresser
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

function appp_toggle_blocking( $action, $blocker, $blocked ) {

	if ( (int) $blocker === (int) $blocked ) {
		return __( 'You can not block yourself.', 'bpre' );
	}

	$response = 'failed';

	switch ( $action ) {
		case 'unblock':
			$blocked_users = get_blocked_users( $blocker );
			$key = array_search( (int) $blocked, $blocked_users, true );

			if ( false !== $key ) {
				unset( $blocked_users[ $key ] );
				update_user_meta( $blocker, 'appp_blocked', $blocked_users );

				$blocked_by = get_user_meta( (int) $blocked, 'appp_blocked_by', true );

				if ( $blocked_by ) {
					$key = array_search( (int) $blocker, $blocked_by, true );

					if ( $key !== false ) {
						unset( $blocked_by[ $key ] );
						update_user_meta( (int) $blocked, 'appp_blocked_by', $blocked_by );
					}
				}

				do_action( 'bpre_user_unblocked', (int) $blocked, $blocker );

				$response = __( 'User successfully unblocked', 'bpre' );
			} else {
				$response = 'Not in block list?';
			}

			break;
		case 'block':
			$blocked_users = get_blocked_users( (int) $blocker );

			if ( user_can( (int) $blocked, 'manage_options' ) ) {
				$response = __( 'You cannot block administrators or moderators', 'bpre' );
			} else {
				$blocked_user    = (int) $blocked;
				$blocked_users[] = $blocked_user;
				update_user_meta( (int) $blocker, 'appp_blocked', $blocked_users );
				$blocked_by = get_user_meta( $blocked_user, 'appp_blocked_by', true );

				if ( $blocked_by ) {
					$key = array_search( (int) $blocker, $blocked_by, true );

					if ( false === $key ) {
						$blocked_by[] = (int) $blocker;
						update_user_meta( $blocked_user, 'appp_blocked_by', $blocked_by );
					}
				} else {
					$list   = array();
					$list[] = (int) $blocker;
					update_user_meta( $blocked_user, 'appp_blocked_by', $list );
				}

				do_action( 'bpre_user_blocked', (int) $blocked, (int) $blocker );

				$response = __( 'User successfully blocked.', 'bpre' );
			}

			break;
	}

	return $response;
}


/**
 * Get a list of users a given user_id has blocked
 *
 * @since 1.0
 */
function get_blocked_users( $user_id = 0 ) {
	if ( 0 === $user_id ) {
		return [];
	}
	$list = get_user_meta( $user_id, 'appp_blocked', true );
	if ( empty( $list ) ) {
		$list = [];
	}
	$_list = apply_filters( 'get_blocked_users', $list, $user_id );
	return array_filter( $_list );
}

/**
 * Get a list of users who have blocked a member
 *
 * @param int $user_id The User's ID
 *
 * @return array
 * @since 3.0
 */
function get_blocked_by_users( $user_id = 0 ) {
	if ( 0 === $user_id ) {
		return [];
	}
	$list = get_user_meta( $user_id, 'appp_blocked_by', true );
	return ( $list ? (array) $list : [] );
}
