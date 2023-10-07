<?php 

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'appp/v1',
			'/flag',
			array(
				'methods'  => 'POST',
				'callback' => 'appp_flag_content',
			)
		);
	}
);