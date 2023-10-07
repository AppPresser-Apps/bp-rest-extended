<?php

/**
 * Flag BuddyPress activity. Adds activity meta.
 *
 * @param WP_REST_Request $request
 * @return void
 */
function appp_flag_content( $request ) {

	$params = $request->get_params();

	// Bail early if no params.
	if ( empty( $params ) ) {
		return rest_ensure_response(
			new \WP_Error(
				'no_params',
				esc_html__( 'No params supplied.', 'bpre' )
			)
		);
	}

	if ( ! isset( $params['action'] ) || ! isset( $params['item'] ) ) {
		return rest_ensure_response(
			new \WP_Error(
				'missing_params',
				esc_html__( 'Missing params.', 'bpre' )
			)
		);
	}

	switch ( $params['action'] ) {

		case 'activity_flag':
				$flag_count = (int) bp_activity_get_meta( $params['item'], 'flag_count', true );
				bp_activity_update_meta( $params['item'], 'flag_count', ( $flag_count + 1 ) );
			break;

		case 'user_flag':
				$flag_count = (int) get_user_meta( $params['item'], 'flag_count', true );
				update_user_meta( $params['item'], 'flag_count', ( $flag_count + 1 ) );
			break;

	}

	return rest_ensure_response(
		array(
			'status' => 200,
			'message' => esc_html__( 'Content flagged.', 'bpre' ),
		)
	);

}
