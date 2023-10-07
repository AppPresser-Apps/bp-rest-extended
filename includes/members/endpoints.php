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
}
add_action( 'rest_api_init', 'bpre_endpoints' );
