<?php

/**
 * Follow user
 * This is piggy backing on the friend request functionality and basically forcing a friend connection.
 *
 * @param WP_Rest_Request $request
 * @return void
 */
function bpre_follow_user( $request ) {

	$friend_id    = $request->get_param( 'user_id' );
	$initiator_id = bp_loggedin_user_id();

	remove_action( 'friends_friendship_accepted', 'friends_notification_accepted_request' );

	$status = friends_check_friendship_status( $initiator_id, $friend_id );

	if ( ! friends_add_friend( $initiator_id, $friend_id, true ) ) {
		return new WP_Error(
			'bp_rest_follow_create_item_failed',
			__( 'There was an error trying to follow user.', 'bpre' ),
			array(
				'status' => 500,
			)
		);
	}

	return rest_ensure_response( array( 'is_confirmed' => true ) );
}

/**
 * Follow user
 * This is piggy backing on the friend request functionality and basically forcing a friend connection.
 *
 * @param WP_Rest_Request $request
 * @return void
 */
function bpre_unfollow_user( $request ) {

	$friend_id    = $request->get_param( 'user_id' );
	$initiator_id = bp_loggedin_user_id();

	// remove_action( 'friends_friendship_accepted', 'friends_notification_accepted_request' );

	$status = friends_check_friendship_status( $initiator_id, $friend_id );

	if ( ! friends_remove_friend( $initiator_id, $friend_id, true ) ) {
		return new WP_Error(
			'bp_rest_unfollow_create_item_failed',
			__( 'There was an error trying to unfollow user.', 'bpre' ),
			array(
				'status' => 500,
			)
		);
	}

	return rest_ensure_response( array( 'is_confirmed' => true ) );
}

/**
 * Send Follow email after follow success.
 *
 * @param int    $friendship_id ID of the pending friendship connection.
 * @param int    $initiator_id  ID of the friendship initiator.
 * @param int    $friend_id     ID of the friend user.
 * @param object $friendship    BuddyPress Friendship Object.
 * @return void
 */
function bpre_send_follow_email( $friendship_id, $initiator_id, $friend_id, $friendship ) {

	if ( 'no' === bp_get_user_meta( (int) $initiator_id, 'notification_friends_friendship_accepted', true ) ) {
		return;
	}

	$unsubscribe_args = array(
		'user_id'           => $initiator_id,
		'notification_type' => 'friends-follow',
	);

	$args = array(
		'tokens' => array(
			'friend.id'      => $friend_id,
			'friendship.url' => esc_url( bp_core_get_user_domain( $friend_id ) ),
			'friend.name'    => bp_core_get_user_displayname( $friend_id ),
			'friendship.id'  => $friendship_id,
			'initiator.id'   => $initiator_id,
			'unsubscribe'    => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
		),
	);
	bp_send_email( 'user_followed', $initiator_id, $args );
}
add_action( 'friends_friendship_accepted', 'bpre_send_follow_email', 10, 4 );

/**
 * Adds post to bp emails with follow message
 *
 * @return void
 */
function bpre_follow_email_message() {

	// Do not create if it already exists and is not in the trash.
	$post_exists = post_exists( '[{{{site.name}}}] New Follower' );

	if ( $post_exists !== 0 && get_post_status( $post_exists ) === 'publish' ) {
		return;
	}

	// Create post object.
	$my_post = array(
		'post_title'   => __( '[{{{site.name}}}] New Follower.', 'buddypress' ),
		'post_content' => __( '[{{{site.name}}}] {{friend.name}} started following you. To learn more about them, visit their profile: {{{friendship.url}}}', 'buddypress' ),  // HTML email content.
		'post_excerpt' => __( '[{{{site.name}}}] {{friend.name}} started following you. To learn more about them, visit their profile: {{{friendship.url}}}', 'buddypress' ),  // Plain text email content.
		'post_status'  => 'publish',
		'post_type'    => bp_get_email_post_type(), // this is the post type for emails.
	);

	// Insert the email post into the database.
	$post_id = wp_insert_post( $my_post );

	if ( $post_id ) {
		// add our email to the taxonomy term 'post_received_comment'.
		// Email is a custom post type, therefore use wp_set_object_terms.

		$tt_ids = wp_set_object_terms( $post_id, 'user_followed', bp_get_email_tax_type() );
		foreach ( $tt_ids as $tt_id ) {
			$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
			wp_update_term(
				(int) $term->term_id,
				bp_get_email_tax_type(),
				array(
					'description' => 'A member receives a follow.',
				)
			);
		}
	}
}
add_action( 'bp_core_install_emails', 'bpre_follow_email_message' );
