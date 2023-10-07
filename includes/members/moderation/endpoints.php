<?php
/**
 * AppP REST: APPP_Moderation_Endpoint class
 *
 * @package AppPresser
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Moderation endpoints.
 *
 * @since 1.0.0
 */
class APPP_Moderation_Endpoints extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/moderate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'moderate' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/blocked',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_blocked' ),
					'permission_callback' => array( $this, 'get_blocked_permissions_check' ),
				),
			)
		);

	}

	/**
	 * Moderate function
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request rest resquest object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function moderate( $request ) {

		$prams = $request->get_params();

		$retval = new WP_Error(
			'bp_rest_invalid_action',
			__( 'Sorry, you are not allowed to perform this action.', 'appp' ),
			array(
				'status' => 403,
			)
		);

		switch ( $prams['action'] ) {

			case 'block':
				$retval = appp_block_user( $prams['action'], $prams['user_id'] );
				break;
			case 'unblock':
				$retval = appp_unblock_user( $prams['action'], $prams['user_id'] );
				break;
			case 'report':
				$retval = appp_report_user( $prams['action'], $prams['user_id'] );
				break;
			case 'suspend':
				$retval = appp_suspend_user( $prams['action'], $prams['user_id'] );
				break;

		}

		return rest_ensure_response( $retval );

	}

	/**
	 * Check if a given request has access.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function permissions_check( $request ) {
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

		$user = bp_rest_get_user( $request['user_id'] );

		if ( true === $retval && ! $user instanceof WP_User ) {
			$retval = new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid user ID.', 'appp' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( ! isset( $request['action'] ) ) {
			$retval = new WP_Error(
				'bp_rest_invalid_action',
				__( 'Invalid moderation action.', 'appp' ),
				array(
					'status' => 404,
				)
			);
		}

		return apply_filters( 'bp_rest_moderate_permissions_check', $retval, $request );
	}


	/**
	 * Get array of users blocked list
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return array
	 */
	public function get_blocked( $request ) {

		$user_id = get_current_user_id();

		$list = get_blocked_users( $user_id );

		$blocked = [];

		foreach ( (array) $list as $num => $user_id ) {

			$avatar_full = bp_core_fetch_avatar(
				array(
					'item_id' => $user_id,
					'type'    => 'full',
					'html'    => false,
				)
			);

			$avatar_thumb = bp_core_fetch_avatar(
				array(
					'item_id' => $user_id,
					'type'    => 'thumb',
					'html'    => false,
				)
			);

			$blocked[] = [
				'id'        => $user_id,
				'username'  => bp_core_get_user_displayname( $user_id ),
				'avatar'    => $avatar_full,
				'thumbnail' => $avatar_thumb,
			];
		}

		return rest_ensure_response( $blocked );

	}


	/**
	 * Check if a given request has access.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return bool
	 */
	public function get_blocked_permissions_check( $request ) {

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

		return apply_filters( 'bp_rest_blocked_permissions_check', $retval, $request );

	}

}

add_action(
	'rest_api_init',
	function () {
		$bp_mod_endpoints = new APPP_Moderation_Endpoints();
		$bp_mod_endpoints->register_routes();
	}
);
