<?php

function appp_attachments( $request ) {

	$params = $request->get_params();

	return appp_get_attachments( $params['component'], $params['item_id'] );
}


function appp_upload_attachments( $component, $item_id, $files ) {

	// error_log(print_r($component,true));
	// error_log(print_r($item_id,true));
	// error_log(print_r($files,true));

	if ( ! empty( $files ) ) {
		$attachment = new ApppAttachment(
			array(
				'action'    => 'appp_attachment_upload',
				'component' => $component,
				'item_id'   => $item_id,
				'base_dir'  => 'attachments',
			)
		);

		foreach ( $files['files']['name'] as $key => $value ) {

			if ( $files['files']['name'][ $key ] ) {

				$uploadedfile = array(
					'name'     => $files['files']['name'][ $key ],
					'type'     => $files['files']['type'][ $key ],
					'tmp_name' => $files['files']['tmp_name'][ $key ],
					'error'    => $files['files']['error'][ $key ],
					'size'     => $files['files']['size'][ $key ],
				);

				$upload = $attachment->upload( $uploadedfile );

				error_log( print_r( $upload, true ) );

			}
		}
	}
}
