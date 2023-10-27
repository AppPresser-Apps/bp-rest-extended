<?php

function appp_upload_member_attachment( $files ) {

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$overrides = array( 'test_form' => false );

	// Register our path override.
	add_filter( 'upload_dir', 'appp_member_attachment_upload_dir' );

	// Do our thing. WordPress will move the file to 'uploads/attachments'.
	foreach ( $files['name'] as $key => $value ) {

		if ( $files['name'][ $key ] ) {

			$uploadedfile = array(
				'name'     => $files['name'][ $key ],
				'type'     => $files['type'][ $key ],
				'tmp_name' => $files['tmp_name'][ $key ],
				'error'    => $files['error'][ $key ],
				'size'     => $files['size'][ $key ],
			);

			$movefile = wp_handle_upload( $uploadedfile, $overrides );

			appp_shrink_image( $movefile['file'] );

		}
	}

	// Set everything back to normal.
	remove_filter( 'upload_dir', 'appp_member_attachment_upload_dir' );
}

/**
 * Override the default upload path.
 *
 * @param   array $dir
 * @return  array
 */
function appp_member_attachment_upload_dir( $dir ) {

	$user_id = get_current_user_id();

	return array(
		'path'   => $dir['basedir'] . '/attachments/member/' . $user_id,
		'url'    => $dir['baseurl'] . '/attachments/member/' . $user_id,
		'subdir' => '/attachments',
	) + $dir;
}

function appp_get_members_attachments( $request ) {

	$params = $request->get_params();

	if ( empty( $params ) ) {
		return rest_ensure_response(
			new \WP_Error(
				'no_params',
				esc_html__( 'No params supplied.', 'bpre' )
			)
		);
	}

	$upload_dir = wp_upload_dir();

	$dir = $upload_dir['basedir'] . '/attachments/member/' . $params['user_id'];
	$url = $upload_dir['baseurl'] . '/attachments/member/' . $params['user_id'];

	$files = array();

	if ( is_dir( $dir ) ) {

		if ( $dh = opendir( $dir ) ) {
			while ( ( $file = readdir( $dh ) ) !== false ) {
				if ( $file === '.' || $file === '..' ) {
					continue;
				}
				$files[] = $url . '/' . $file;
			}
			closedir( $dh );
		}
	}

	return $files;
}

function appp_delete_members_attachment( $request ) {

	$params = $request->get_params();

	if ( empty( $params ) ) {
		return rest_ensure_response(
			new \WP_Error(
				'no_params',
				esc_html__( 'No params supplied.', 'bpre' )
			)
		);
	}

	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return rest_ensure_response(
			new \WP_Error(
				'no_auth',
				esc_html__( 'No access to this resource.', 'bpre' )
			)
		);
	}

	$upload_dir = wp_upload_dir();

	$path = $upload_dir['basedir'] . '/attachments/member/' . $user_id . '/' . $params['file'];

	if ( file_exists( $path ) ) {
		wp_delete_file( $path );
	}

	return rest_ensure_response( 'deleted' );
}


/**
 *  Rest callback function. Upload image to members attahcment folder
 *
 * @since 1.0.0
 *
 * @param WP_REST_Request $request
 *
 * @return WP_REST_Response
 */
function appp_upload_member_attachments( $request ) {

	$files = $request->get_file_params();

	if ( isset( $files['files'] ) ) {
		appp_upload_member_attachment( $files['files'] );
		return 'uploaded';
	}

	return rest_ensure_response(
		new \WP_Error(
			'no_files',
			esc_html__( 'No files supplied.', 'bpre' )
		)
	);
}
