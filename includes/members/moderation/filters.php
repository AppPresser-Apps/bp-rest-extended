<?php
/**
 * Moderation filters
 *
 * @package AppPresser
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// add_action( 'bp_after_has_members_parse_args', array( $this, 'adjust_query' ) );
// add_filter( 'bp_get_total_member_count', array( $this, 'adjust_count' ) );
// add_filter( 'bp_get_member_latest_update', array( $this, 'redo_update' ) );
// add_action( 'bp_members_screen_display_profile', array( $this, 'display_block_screen' ) );
// add_filter( 'bp_activity_mentioned_users', array( $this, 'filter_mentions' ) );
// add_filter( 'bp_members_suggestions_query_args', array( $this, 'disable_suggestions_list' ) );
// add_filter( 'bp_groups_member_suggestions_query_args', array( $this, 'disable_suggestions_list' ) );
// add_filter( 'bp_activity_get', array( $this, 'filter_comments' ), 999 );


/**
 * Remove activities if blocked/blocking. Only relates to loops without scope, so this is not used in BuddyBoss.
 *
 * @param array $args Activities.
 * @since 1.0.0
 * @return array
 */
function appp_filter_activities( $args ) {
	// Create a new list.
	$list = array();
	// First, add everyone we are blocking to $list.
	$list = array_merge( $list, get_blocked_users( bp_loggedin_user_id() ) );
	// Next, add everyone blocking us to $list.
	$list = array_merge( $list, get_blocked_by_users( bp_loggedin_user_id() ) );
	// If neither side is blocking, enable all activities.
	if ( empty( $list ) ) {
		return $args;
	}
	$filter_query         = ( empty( $args['filter_query'] ) ? array() : $args['filter_query'] );
	$filter_query[]       = array(
		'column'  => 'user_id',
		'compare' => 'NOT IN',
		'value'   => $list,
	);
	$args['filter_query'] = $filter_query;

	return $args;
}
add_filter( 'bp_after_has_activities_parse_args', 'appp_filter_activities', 99 );
add_filter( 'bp_rest_activity_get_items_query_args', 'appp_filter_activities', 99 );

/**
 * Filter groups, remove blocked/blocking.
 *
 * @param [type] $paged_groups_sql
 * @param [type] $sql
 * @param [type] $r
 * @return void
 */
function appp_filter_groups_sql( $paged_groups_sql, $sql, $r ) {

	// Create a new list.
	$list = array();
	// First, add everyone we are blocking to $list.
	$list = array_merge( $list, get_blocked_users( bp_loggedin_user_id() ) );
	// Next, add everyone blocking us to $list.
	$list = array_merge( $list, get_blocked_by_users( bp_loggedin_user_id() ) );

	if ( empty( $list ) ) {
		return $paged_groups_sql;
	}

	$sql = $sql['select'] . ' FROM ' . $sql['from'] . ' WHERE ' . $sql['where'] . ' AND g.creator_id NOT IN ( ' . implode( ',', $list ) . ' ) ' . $sql['orderby'] . ' ' . $sql['pagination'];

	return $sql;
}
add_filter( 'bp_groups_get_paged_groups_sql', 'appp_filter_groups_sql', 99, 3 );


/**
 * Filter activities, taking into account BuddyBoss's scope precedence policy.
 *
 * @param $r array Scope arguments
 * @since 1.0.0
 * @return array
 */
function appp_override_scope( $r ) {
	// Create a new list.
	$list = array();
	// First, add everyone we are blocking to $list.
	$list = array_merge( $list, get_blocked_users( bp_loggedin_user_id() ) );
	// Next, add everyone blocking us to $list.
	$list = array_merge( $list, get_blocked_by_users( bp_loggedin_user_id() ) );
	// If neither side is blocking, enable all activities.
	if ( empty( $list ) ) {
		return $r;
	}
	$filter_query      = ( empty( $r['filter_query'] ) ? array() : $r['filter_query'] );
	$filter_query[]    = array(
		'column'  => 'user_id',
		'compare' => 'NOT IN',
		'value'   => $list,
	);
	$r['filter_query'] = $filter_query;
	return $r;
}
add_filter( 'bp_activity_set_public_scope_args', 'appp_override_scope', 99 );
add_filter( 'bp_activity_set_friends_scope_args', 'appp_override_scope', 99 );
add_filter( 'bp_activity_set_groups_scope_args', 'appp_override_scope', 99 );
add_filter( 'bp_activity_set_mentions_scope_args', 'appp_override_scope', 99 );
add_filter( 'bp_activity_set_following_scope_args', 'appp_override_scope', 99 );


/**
 * Adjusts the member count to reflect the function above.
 *
 * @since 1.0
 * @param integer $count
 * @return integer
 */
function appp_adjust_count( $count ) {
	if ( ! is_user_logged_in() ) {
		return $count;
	}
	$list = count( get_blocked_users( get_current_user_id() ) );
	if ( 0 === $list ) {
		return $count;
	}
	return $count - $list;
}
add_filter( 'bp_get_total_member_count', 'appp_adjust_count' );


/**
 * Adjusts the member query so blocked/blocking users aren't shown.
 *
 * @since 1.0
 * @param array $args
 * @return void
 */
function appp_adjust_query( $args ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return $args;
	}
	// Create a new list.
	$list = array();
	// First, add everyone we are blocking to $list.
	$list = array_merge( $list, get_blocked_users( bp_loggedin_user_id() ) );
	// Next, add everyone blocking us to $list.
	$list = array_merge( $list, get_blocked_by_users( bp_loggedin_user_id() ) );
	// If neither side is blocking, enable all activities.
	if ( empty( $list ) ) {
		return $args;
	}
	$excluded = ( isset( $args['exclude'] ) ? $args['exclude'] : array() );
	if ( ! is_array( $excluded ) ) {
		$excluded = explode( ',', $excluded );
	}
	$excluded        = array_merge( $excluded, $list );
	$args['exclude'] = $excluded;
	return $args;
}
add_filter( 'bp_after_has_members_parse_args', 'appp_adjust_query', 99 );
add_filter( 'bp_rest_members_get_items_query_args', 'appp_adjust_query', 99 );




/**
 * Filter any mentions if blocked/blocking.
 *
 * @since 1.0.0
 * @param array $names usernames.
 * @return array
 */
function appp_filter_mentions( $names ) {
	// Hide mentions from members you block.
	$blocked_users = get_blocked_users( bp_loggedin_user_id() );
	foreach ( $blocked_users as $blocked_user ) {
		unset( $names[ $blocked_user ] );
	}
	// Hide mentions from members blocking you.
	$blocking_users = get_blocked_by_users( bp_loggedin_user_id() );
	foreach ( $blocking_users as $blocking_user ) {
		unset( $names[ $blocking_user ] );
	}
	return $names;
}
add_filter( 'bp_activity_mentioned_users', 'appp_filter_mentions' );


/**
 * Prepare a list of blocked/blocking, and pass to our comment looping function.
 *
 * @since 1.0.0
 *
 * @param array $results [activities, total].
 * @return array
 */
function appp_filter_comments( $results ) {
	// Logged-in check.
	if ( ! is_user_logged_in() ) {
		return $results;
	}
	// Create a new list.
	$list = array();
	// Get parent activities.
	$activities = $results['activities'];
	// First, add everyone we are blocking to $my_list.
	$list = array_merge( $list, get_blocked_users( bp_loggedin_user_id() ) );
	// Next, add everyone blocking us to $my_list.
	$list = array_merge( $list, get_blocked_by_users( bp_loggedin_user_id() ) );
	// If neither side is blocking, enable all comments.
	if ( empty( $list ) ) {
		return $results;
	}
	// Loop through each parent activity.
	foreach ( $activities as $key => $activity ) {
		// If the activity doesn't have any comments, move on.
		if ( empty( $activity->children ) ) {
			continue;
		}
		// If it does, call our looping function.
		$activities[ $key ]->children = appp_filter_looped_comments( $activities[ $key ]->children, $list );
	}
	$results['activities'] = $activities;
	return $results;
}
add_filter( 'bp_activity_get', 'appp_filter_comments', 999 );


/**
 * Loop through each comment in tree, and remove if blocked/blocking.
 *
 * @since 1.0.0
 *
 * @param array $comments comments.
 * @param array $my_list hidden user ids.
 * @return array
 */
function appp_filter_looped_comments( $comments, $list ) {
	// If empty, return.
	if ( empty( $comments ) ) {
		return $comments;
	}
	// If not empty, hide the comment if author is in our list, or see if it has a child.
	foreach ( $comments as $key => $comment ) {

		if ( in_array( $comment->user_id, $list, true ) ) {
			unset( $comments[ $key ] );
			continue;
		}

		// Next, if the comment has another comment below it, restart the magic.
		if ( ! empty( $comments[ $key ]->children ) ) {
			$comments[ $key ]->children = appp_filter_looped_comments( $comments[ $key ]->children, $list );
		}
	}
	return $comments;
}


/**
 * Remove blocked/blocking from the member suggestions list.
 *
 * @since 1.0.0
 *
 * @param array $user_query User query.
 * @return array
 */
function appp_disable_suggestions_list( $user_query ) {
	// Create a new list.
	$list = array();
	// First, add everyone we are blocking to $my_list.
	$list = array_merge( $list, get_blocked_users( bp_loggedin_user_id() ) );
	// Next, add everyone blocking us to $my_list.
	$list = array_merge( $list, get_blocked_by_users( bp_loggedin_user_id() ) );
	// If neither side is blocking, enable all suggestions.
	if ( empty( $list ) ) {
		return $user_query;
	}
	// Else, exclude any suggestions featuring those in our list.
	if ( $list ) {
		$user_query['exclude'] = array_unique( $list );
	}
	return $user_query;
}
add_filter( 'bp_members_suggestions_query_args', 'appp_disable_suggestions_list' );
add_filter( 'bp_groups_member_suggestions_query_args', 'appp_disable_suggestions_list' );


/**
 * Make sure that we don't see blocked member's updates
 *
 * @since 1.0.1
 * @param [type] $update_content
 * @return void
 */
function appp_redo_update( $update_content ) {
	if ( is_user_logged_in() ) {
		$list = get_blocked_users( bp_get_member_user_id() );
		if ( in_array( get_current_user_id(), $list, true ) ) {
			return '';
		}
	}

	return $update_content;
}
add_filter( 'bp_get_member_latest_update', 'appp_redo_update' );


/**
 * Check recipients before sending message.
 *
 * @since 1.0.0
 *
 * @param array $recipients
 * @return array
 */
function appp_check_recipients( $recipients ) {
	$cui   = bp_loggedin_user_id();
	$_list = get_blocked_users( $cui );
	// Loop though receipients and convert them into a list of user IDs.
	// Based on messages_new_message().
	$recipient_ids = array();
	foreach ( (array) $recipients as $recipient ) {
		$recipient = trim( $recipient );
		if ( empty( $recipient ) ) {
			continue;
		}
		$recipient_id = false;
		// input was numeric.

		if ( is_numeric( $recipient ) ) {
			// do a check against the user ID column first.

			if ( bp_core_get_core_userdata( (int) $recipient ) ) {
				$recipient_id = (int) $recipient;
			} elseif ( bp_is_username_compatibility_mode() ) {

					$recipient_id = bp_core_get_userid( (int) $recipient );
			} else {
				$recipient_id = bp_core_get_userid_from_nicename( (int) $recipient );
			}
		} elseif ( bp_is_username_compatibility_mode() ) {

				$recipient_id = bp_core_get_userid( $recipient );
		} else {
			$recipient_id = bp_core_get_userid_from_nicename( $recipient );
		}

		// Make sure we are not trying to send a message to someone we are blocking.
		if ( $recipient_id && ! in_array( $recipient_id, $_list, true ) ) {
			$recipient_ids[] = (int) $recipient_id;
		}
	}
	// Remove duplicates.
	$recipient_ids = array_unique( (array) $recipient_ids );
	// Loop though the user IDs and check for blocks.
	$filtered = array();
	foreach ( (array) $recipient_ids as $user_id ) {
		$list = get_blocked_users( $user_id );
		if ( ! in_array( $cui, (array) $list ) ) {
			$filtered[] = $user_id;
		}
	}
	return $filtered;
}

/**
 * Check existing messages and suspend replies if recipient on block list.
 *
 * @since 1.0.0
 * @param object $message
 * @return object
 */
function appp_check_conversations( $message ) {
	if ( empty( $message->recipients ) ) {
		return;
	}
	$cui        = bp_loggedin_user_id();
	$recipients = $message->recipients;
	// First make sure we are not sending a new message to someone we selected to block.
	$_list = get_blocked_users( $cui );
	foreach ( $_list as $_user_id ) {
		if ( array_key_exists( $_user_id, $recipients ) ) {
			unset( $recipients[ $_user_id ] );
		}
	}
	// Second make sure that the message receipients are not blocking us.
	$filtered = array();
	foreach ( $recipients as $user_id => $receipient ) {
		$list = get_blocked_users( $user_id );
		if ( ! in_array( $cui, (array) $list, true ) ) {
			$filtered[ $user_id ] = $receipient;
		}
	}
	$message->recipients = $filtered;
}

if ( bp_is_active( 'messages' ) ) {
	add_filter( 'bp_messages_recipients', 'appp_check_recipients' );
	add_action( 'messages_message_before_save', 'appp_check_conversations' );
}


/**
 * Remove the Add Friend button if the user is blocking.
 *
 * @since 1.0.0
 * @return boolean
 */
function friend_check( $status, $user_id ) {
	$list = get_blocked_users( $user_id );
	if ( in_array( bp_loggedin_user_id(), (array) $list, true ) ) {
		return false;
	}
	return $status;
}


/**
 * Handle pending or current connections when a block takes place.
 *
 * @since 1.0.0
 * @param $blocker_id
 * @param $blocked_id
 */
function remove_friendship( $blocker_id, $blocked_id ) {
	// Check friendship status, and take action based on result.
	switch ( friends_check_friendship_status( $blocker_id, $blocked_id ) ) {
		case 'is_friend':
			$result = friends_remove_friend( $blocker_id, $blocked_id );
			break;
		case 'pending':
			$result = friends_withdraw_friendship( $blocker_id, $blocked_id );
			break;
		case 'awaiting_response':
			$result = friends_withdraw_friendship( $blocked_id, $blocker_id );
			break;
		case 'not_friends':
			break;
	}
}

if ( bp_is_active( 'friends' ) ) {
	add_filter(
		'bp_is_friend',
		array( $this, 'friend_check' ),
		10,
		2
	);
	add_action(
		'appp_user_blocked',
		array( $this, 'remove_friendship' ),
		10,
		2
	);
}

/**
 * Check user has the required level to block others.
 *
 * @param string $user_id The id of the user.
 *
 * @return boolean   The result of the check.
 * @since 2.0
 */
function has_required_level( $user_id ) {
	$result = true;
	return $result;
}


/**
 * Removes a user from a block list.
 *
 * @since 1.0.0
 * @param [type] $list_id
 * @param [type] $id_to_remove
 * @return void
 */
function appp_remove_user( $list_id = null, $id_to_remove = null ) {
	$blocked_users = get_blocked_users( $list_id );
	$new           = array();
	foreach ( (array) $blocked_users as $user_id ) {
		if ( $user_id !== $id_to_remove ) {
			$new[] = $user_id;
		}
	}
	update_user_meta(
		$list_id,
		'appp_blocked',
		apply_filters(
			'remove_user',
			$new,
			$list_id,
			$id_to_remove
		)
	);
}
