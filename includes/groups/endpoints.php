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
class APPP_Group_Endpoints extends WP_REST_Controller {

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
			'/membership',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/membership-request',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'accept' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/membership-request',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'reject' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Remove User from group.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request rest resquest object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove( $request ) {

		$prams = $request->get_params();

		$retval = new WP_Error(
			'bp_rest_invalid_action',
			__( 'Sorry, you are not allowed to perform this action.', 'appp' ),
			array(
				'status' => 403,
			)
		);

		if ( isset( $prams['group_id'] ) && $user_id ) {
			$rsp = groups_delete_membership_request( null, $prams['user_id'], $prams['group_id'] );
			if ( 1 === $rsp ) {
				$retval = new WP_REST_Response(
					array(
						'message' => __( 'Membership canceled.', 'appp' ),
					),
				);
			} else {
				$retval = new WP_Error(
					'bp_rest_invalid_action',
					__( 'Sorry, you are not allowed to perform this action.', 'appp' ),
					array(
						'status' => 403,
					)
				);
			}
		}

		return rest_ensure_response( $retval );
	}

	/**
	 * Accept Group Membership.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request rest resquest object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function accept( $request ) {

		$prams = $request->get_params();

		$retval = new WP_Error(
			'bp_rest_invalid_action',
			__( 'Sorry, you are not allowed to perform this action.', 'appp' ),
			array(
				'status' => 403,
			)
		);

		if ( isset( $prams['group_id'] ) && $user_id ) {
			$rsp = groups_accept_membership_request( null, $prams['user_id'], $prams['group_id'] );
			if ( 1 === $rsp ) {
				$retval = new WP_REST_Response(
					array(
						'message' => __( 'Membership Accepted.', 'appp' ),
					),
				);
			} else {
				$retval = new WP_Error(
					'bp_rest_invalid_action',
					__( 'Sorry, you are not allowed to perform this action.', 'appp' ),
					array(
						'status' => 403,
					)
				);
			}
		}

		return rest_ensure_response( $retval );
	}

	/**
	 * Reject Group Membership.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request rest resquest object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function reject( $request ) {

		$prams = $request->get_params();

		$user_id = get_current_user_id();

		$retval = new WP_Error(
			'bp_rest_invalid_action',
			__( 'Sorry, you are not allowed to perform this action.', 'appp' ),
			array(
				'status' => 403,
			)
		);

		if ( isset( $prams['group_id'] ) && $user_id ) {
			$rsp = groups_reject_membership_request( null, $user_id, $prams['group_id'] );
			if ( 1 === $rsp ) {
				$retval = new WP_REST_Response(
					array(
						'message' => __( 'Membership Rejected.', 'appp' ),
					),
				);
			} else {
				$retval = new WP_Error(
					'bp_rest_invalid_action',
					__( 'Sorry, you are not allowed to perform this action.', 'appp' ),
					array(
						'status' => 403,
					)
				);
			}
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

		return apply_filters( 'bp_rest_request_permissions_check', $retval, $request );
	}

}

add_action(
	'rest_api_init',
	function () {
		$bp_endpoints = new APPP_Group_Endpoints();
		$bp_endpoints->register_routes();
	}
);
