<?php

/**
 * Filter to block the JWT authentication for the admin user.
 * For security reasons, admin jwt creation is disabled by default.
 *
 * @param array   $data User data.
 * @param WP_User $user WP_User object.
 * @return array
 */
function appp_dont_allow_admin_jwt_auth( $user ) {
	if ( user_can( $user, 'administrator' ) ) {
		return;
	}
}
add_filter( 'jwt_auth_token_after_authenticate', 'appp_dont_allow_admin_jwt_auth', 10 );
