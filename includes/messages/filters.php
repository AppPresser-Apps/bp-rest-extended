<?php

/**
 * Filter a message value returned from the API.
 *
 * @since 1.0.0
 *
 * @param array               $response    The message value for the REST response.
 * @param BP_Messages_Message $message The Message object.
 * @param WP_REST_Request     $request Request used to generate the response.
 */
function appp_filter_user_message_data( $response, $message, $request ) {

	$response['display_name'] = bp_core_get_user_displayname( $response['sender_id'] );
	$response['is_sender']    = get_current_user_id() === $response['sender_id'];
	$response['time_since']   = bp_core_time_since( str_replace( 'T', ' ', $response['date_sent'] ) );
	$response['meta']         = bp_messages_get_meta( $response['id'] );

	/**
	 * Calculate a width for the chat message. Adds randomness to the UI.
	 */
	$number            = (int) substr( strtotime( $response['date_sent'] ), -2 );
	$percent           = 40;
	$percent_decimal   = $percent / 100;
	$percent           = $percent_decimal * $number;
	$width             = 55 - (int) $percent;
	$response['width'] = $width <= 35 ? $width + 12 : $width;

	$response['attachments'] = appp_get_attachments( 'messages', $response['id'] );

	return $response;
}
add_filter( 'bp_rest_message_prepare_value', 'appp_filter_user_message_data', 10, 3 );


/**
 * Add meta to messages.
 *
 * @since 1.0.0
 *
 * @param BP_Messages_Thread $thread  Thread object.
 * @param WP_REST_Response   $response  The response data.
 * @param WP_REST_Request    $request The request sent to the API.
 *
 * @return BP_Messages_Thread
 */
function appp_filter_message_create_data( $thread, $response, $request ) {

	$params = $request->get_params();
	$files  = $request->get_file_params();

	if ( isset( $params['meta'] ) ) {

		$meta = is_object( json_decode( $params['meta'] ) ) ? (array) json_decode( $params['meta'] ) : $params['meta'];

		foreach ( $meta as $key => $value ) {
			bp_messages_update_meta( $response->data[0]['message_id'], $key, $value );
		}
	}

	// appp_upload_attachments( 'messages', $response->data[0]['message_id'], $files );

	return $response;
}
add_filter( 'bp_rest_messages_create_item', 'appp_filter_message_create_data', 10, 3 );

function appp_filter_messages_query_args( $args, $method ) {

	$args['recipients']['required'] = false;

	return $args;
}
//add_filter( 'bp_rest_messages_create_item_query_arguments', 'appp_filter_messages_query_args', 10, 2 )
