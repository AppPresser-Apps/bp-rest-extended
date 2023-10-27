<?php

/**
 * Register endpoints.
 */
function bpre_endpoints() {

	register_rest_route(
		'buddypress/v1',
		'/friends/follow',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'bpre_follow_user',
		)
	);

	register_rest_route(
		'buddypress/v1',
		'/friends/unfollow',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'bpre_unfollow_user',
		)
	);

	register_rest_route(
		'buddypress/v1',
		'/members/attachment',
		array(
			'methods'             => 'GET',
			'permission_callback' => 'rest_permissions_check',
			'callback'            => 'appp_get_members_attachments',
		)
	);

	register_rest_route(
		'buddypress/v1',
		'/members/attachment',
		array(
			'methods'             => 'POST',
			'permission_callback' => 'rest_permissions_check',
			'callback'            => 'appp_upload_member_attachments',
		)
	);

	register_rest_route(
		'buddypress/v1',
		'/members/attachment',
		array(
			'methods'             => 'DELETE',
			'permission_callback' => 'rest_permissions_check',
			'callback'            => 'appp_delete_members_attachment',
		)
	);
}
add_action( 'rest_api_init', 'bpre_endpoints' );


/**
 * Check if a given request has access.
 *
 * @since 1.0.0
 *
 * @param WP_REST_Request $request Full data about the request.
 * @return WP_Error|bool
 */
function rest_permissions_check( $request ) {
	$retval = true;

	if ( ! is_user_logged_in() ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'appp' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	return apply_filters( 'bp_rest_request_permissions_check', $retval, $request );
}
