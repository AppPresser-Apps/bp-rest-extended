<?php

/**
 * Filter a group response returned from the API.
 *
 * @since 1.0.0
 *
 * @param WP_REST_Response $response The response data.
 * @param WP_REST_Request  $request  Request used to generate the response.
 * @param BP_Groups_Group  $group    Group object.
 *
 * @return WP_REST_Response
 */
function appp_filter_groups_user_data( $response, $request, $group ) {

	$user    = wp_get_current_user();
	$user_id = $user->ID;

	error_log( print_r( $user, true ) );

	$avatar_full = bp_core_fetch_avatar(
		array(
			'item_id' => $group->id,
			'object'  => 'group',
			'type'    => 'full',
			'html'    => false,
		)
	);

	$avatar_thumb = bp_core_fetch_avatar(
		array(
			'item_id' => $group->id,
			'object'  => 'group',
			'type'    => 'thumb',
			'html'    => false,
		)
	);

	$response->data['avatar_url']['full']  = $avatar_full;
	$response->data['avatar_url']['thumb'] = $avatar_thumb;

	$url_parts               = wp_parse_url( $response->data['link'] );
	$response->data['route'] = $url_parts['path'];
	$response->data['api']   = $url_parts['scheme'] . '://' . $url_parts['host'];

	$response->data['group_meta']   = groups_get_groupmeta( $group->id );
	$response->data['member_count'] = (int) groups_get_total_member_count( $group->id );

	// Filter out privte data.
	$keys = array( 'id', 'display_name', 'fullname', 'avatar_url' );

	/**
	 * Member filtering
	 */
	$group_members = groups_get_group_members(
		array(
			'group_id' => $group->id,
		)
	)['members'];

	$filtered_members = array_map(
		function( $member ) use ( $keys ) {
			$member_array               = (array) $member;
			$member_array['avatar_url'] = bp_core_fetch_avatar(
				array(
					'item_id' => $member_array['id'],
					'object'  => 'user',
					'type'    => 'full',
					'html'    => false,
				)
			);
			$admin_array                = array_intersect_key( $member_array, array_flip( $keys ) );

			return apply_filters( 'appp_rest_group_member_prepare_value', $member_array );
		},
		$group_members
	);

	$response->data['group_members'] = $filtered_members;

	/**
	 * Mod filtering
	 */
	$group_mods = groups_get_group_members(
		array(
			'group_id'   => $group->id,
			'group_role' => 'mod',
		)
	)['members'];

	$filtered_mods = array_map(
		function( $mods ) use ( $keys ) {
			$mods_array               = (array) $mods;
			$mods_array['avatar_url'] = bp_core_fetch_avatar(
				array(
					'item_id' => $mods_array['id'],
					'object'  => 'user',
					'type'    => 'full',
					'html'    => false,
				)
			);
			$admin_array              = array_intersect_key( $mods_array, array_flip( $keys ) );

			return apply_filters( 'appp_rest_group_mod_prepare_value', $mods_array );
		},
		$group_mods
	);

	$response->data['group_mods'] = $filtered_mods;

	/**
	 * Admin filtering
	 */
	$group_admins = groups_get_group_members(
		array(
			'group_id'   => $group->id,
			'group_role' => 'admin',
		)
	)['members'];

	$filtered_admins = array_map(
		function( $admin ) use ( $keys ) {
			$admin_array               = (array) $admin;
			$admin_array['avatar_url'] = bp_core_fetch_avatar(
				array(
					'item_id' => $admin_array['id'],
					'object'  => 'user',
					'type'    => 'full',
					'html'    => false,
				)
			);

			$admin_array = array_intersect_key( $admin_array, array_flip( $keys ) );

			return apply_filters( 'appp_rest_group_admin_prepare_value', $admin_array );
		},
		$group_admins
	);

	$response->data['group_admins'] = $filtered_admins;

	$response->data['is_user_admin']  = groups_is_user_admin( $user_id, $group->id );
	$response->data['is_user_mod']    = groups_is_user_mod( $user_id, $group->id );
	$response->data['is_user_banned'] = groups_is_user_banned( $user_id, $group->id );

	$is_member                        = groups_is_user_member( $user_id, $group->id );
	$response->data['is_user_member'] = $is_member ? true : false;

	$is_pending                           = groups_check_for_membership_request( $user_id, $group->id );
	$response->data['is_request_pending'] = $is_pending ? true : false;

	$response->data['is_visible']      = bp_group_is_visible( $group );
	$response->data['user_can_invite'] = bp_groups_user_can_send_invites( $group->id, $user_id );

	return apply_filters( 'appp_rest_groups_prepare_value', $response, $group, $user );
}
add_filter( 'bp_rest_groups_prepare_value', 'appp_filter_groups_user_data', 10, 3 );

/**
 * Add extra request params to members endpoint.
 *
 * @since 1.0.0
 *
 * @param  array $params
 * @return array
 */
function appp_add_extra_param_to_group_endpoint( $params ) {
	$params['lattitude'] = array(
		'description'       => __( 'lattitude.', 'apppcore' ),
		'default'           => '',
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_key',
		'validate_callback' => 'rest_validate_request_arg',
	);

	$params['longitude'] = array(
		'description'       => __( 'longitude.', 'apppcore' ),
		'default'           => '',
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_key',
		'validate_callback' => 'rest_validate_request_arg',
	);

	return $params;
}
// add_filter( 'bp_rest_groups_collection_params', 'appp_add_extra_param_to_group_endpoint' );

/**
 * Save group Meta
 *
 * @param object $group
 * @param object $response
 * @param object $request
 * @return void
 */
function appp_save_group_meta( $group, $response, $request ) {

	$params = $request->get_params();

	if ( ! isset( $params['meta'] ) ) {
		return;
	}

	foreach ( $params['meta'] as $key => $value ) {
		$value = apply_filters( 'appp_rest_group_meta_value', $value, $key );

		groups_update_groupmeta( $group->id, $key, $value );
		$response->data[0]->$key = $value;
	}
}
add_action( 'bp_rest_groups_create_item', 'appp_save_group_meta', 5, 3 );
add_action( 'bp_rest_groups_update_item', 'appp_save_group_meta', 5, 3 );



/**
 * Filter with group Meta. Another dumb BuddyPress thing.
 * Docs say use meta as the key, but it throws WP error so have to set it after with meta_query.
 *
 * @param object $group
 * @param object $response
 * @param object $request
 * @return void
 */
function appp_get_groups( $args, $request ) {

	$params = $request->get_params();

	if ( isset( $params['meta_query'] ) ) {
		$args['meta_query'] = $params['meta_query']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}

	return $args;
}
add_filter( 'bp_rest_groups_get_items_query_args', 'appp_get_groups', 5, 2 );
