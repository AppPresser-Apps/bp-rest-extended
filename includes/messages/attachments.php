<?php
/**
 * ApppP attachment class.
 *
 * @package AppPresser
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP Attachment class.
 *
 * @since 1.0.0
 */
class ApppAttachment {

	/** Upload properties *****************************************************/

	/**
	 * The file being uploaded.
	 *
	 * @var array
	 */
	public $attachment = array();


	/**
	 * The default args to be merged.
	 *
	 * @var array
	 */
	protected $default_args = array(
		'original_max_filesize'  => 0,
		'allowed_mime_types'     => array(),
		'base_dir'               => '',
		'action'                 => '',
		'component'              => '',
		'item_id'                => 0,
		'upload_error_strings'   => array(),
		'required_wp_files'      => array( 'file' ),
		'upload_dir_filter_args' => 0,
	);

	/**
	 * Construct Upload parameters.
	 *
	 * @since 1.0.0
	 * @since 1.0.0 Add the $upload_dir_filter_args argument to the $arguments array
	 *
	 * @param array|string $args {
	 *     @type int    $original_max_filesize  Maximum file size in kilobytes. Defaults to php.ini settings.
	 *     @type array  $allowed_mime_types     List of allowed file extensions (eg: array( 'jpg', 'gif', 'png' ) ).
	 *                                          Defaults to WordPress allowed mime types.
	 *     @type string $base_dir               Component's upload base directory. Defaults to WordPress 'uploads'.
	 *     @type string $action                 The upload action used when uploading a file, $_POST['action'] must be set
	 *                                          and its value must equal $action {@link wp_handle_upload()} (required).
	 *     @type array  $upload_error_strings   A list of specific error messages (optional).
	 *     @type array  $required_wp_files      The list of required WordPress core files. Default: array( 'file' ).
	 *     @type int    $upload_dir_filter_args 1 to receive the original Upload dir array in the Upload dir filter, 0 otherwise.
	 *                                          Defaults to 0 (optional).
	 * }
	 */
	public function __construct( $args = '' ) {

		if ( empty( $args['action'] ) || empty( $args['component'] ) ) {
			return false;
		}

		// Sanitize the action ID.
		$this->action = sanitize_key( $args['action'] );

		/**
		 * Max file size defaults to php ini settings or, in the case of
		 * a multisite config, the root site fileupload_maxk option.
		 */
		$this->default_args['original_max_filesize'] = (int) wp_max_upload_size();

		$params = wp_parse_args( $args, $this->default_args, $this->action . '_upload_params' );

		foreach ( $params as $key => $param ) {
			if ( 'upload_error_strings' === $key ) {
				$this->{$key} = $this->set_upload_error_strings( $param );

				// Sanitize the base dir.
			} elseif ( 'base_dir' === $key ) {
				$this->{$key} = sanitize_title( $param );

				// Sanitize the upload dir filter arg to pass.
			} elseif ( 'upload_dir_filter_args' === $key ) {
				$this->{$key} = (int) $param;

				// Action input is already set and sanitized.
			} elseif ( 'action' !== $key ) {
				$this->{$key} = $param;
			}
		}

		// Set the path/url and base dir for uploads.
		$this->set_upload_dir();
	}

	/**
	 * Upload the attachment.
	 *
	 * @since 1.0.0
	 *
	 * @param array       $file              The appropriate entry the from $_FILES superglobal.
	 * @param string      $upload_dir_filter A specific filter to be applied to 'upload_dir' (optional).
	 * @param string|null $time              Optional. Time formatted in 'yyyy/mm'. Default null.
	 * @return array On success, returns an associative array of file attributes.
	 *               On failure, returns an array containing the error message
	 *               (eg: array( 'error' => $message ) )
	 */
	public function upload( $file, $time = null ) {

		/**
		 * Upload action and the file input name are required parameters.
		 *
		 * @see BP_Attachment:__construct()
		 */
		if ( empty( $this->action ) ) {
			return false;
		}

		/**
		 * Add custom rules before enabling the file upload
		 */
		// add_filter( "{$this->action}_prefilter", array( $this, 'validate_upload' ), 10, 1 );

		// Set Default overrides.
		$overrides = array(
			'action'               => $this->action,
			'test_form'            => false,
			'upload_error_strings' => $this->upload_error_strings,
		);

		/**
		 * Add a mime override if needed
		 * Used to restrict uploads by extensions
		 */
		if ( ! empty( $this->allowed_mime_types ) ) {
			$mime_types = $this->validate_mime_types();

			if ( ! empty( $mime_types ) ) {
				$overrides['mimes'] = $mime_types;
			}
		}

		/**
		 * If you need to add some overrides we haven't thought of.
		 *
		 * @param array $overrides The wp_handle_upload overrides
		 */
		$overrides = apply_filters( 'appp_attachment_upload_overrides', $overrides );

		$this->includes();

		// Make sure the file will be uploaded in the attachment directory.
		add_filter( 'upload_dir', array( $this, 'upload_dir_filter' ), 10 );

		// Helper for utf-8 filenames.
		add_filter( 'sanitize_file_name', array( $this, 'sanitize_utf8_filename' ) );

		// Upload the attachment.
		$this->attachment = wp_handle_upload( $file, $overrides );

		remove_filter( 'sanitize_file_name', array( $this, 'sanitize_utf8_filename' ) );

		// Restore WordPress Uploads data.
		if ( ! empty( $upload_dir_filter ) ) {
			remove_filter( 'upload_dir', array( $this, 'upload_dir_filter' ), 10 );
		}

		if ( $this->attachment && ! isset( $this->attachment['error'] ) ) {
			return $this->shrink_image( $this->attachment['file'] );
		} else {
			return $this->attachment;
		}
	}

	/**
	 * Include the WordPress core needed files.
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		foreach ( array_unique( $this->required_wp_files ) as $wp_file ) {

			if ( ! file_exists( ABSPATH . "wp-admin/includes/{$wp_file}.php" ) ) {
				continue;
			}

			require_once ABSPATH . "wp-admin/includes/{$wp_file}.php";
		}
	}

	/**
	 * Set upload path and url.
	 *
	 * @since 1.0.0
	 */
	public function set_upload_dir() {

		// Set the directory, path, & url variables.
		$this->upload_dir = wp_upload_dir();

		if ( empty( $this->upload_dir ) ) {
			return false;
		}

		$this->upload_path = $this->upload_dir['basedir'];
		$this->upload_url  = $this->upload_dir['baseurl'];

		// Ensure URL is https if SSL is set/forced.
		if ( is_ssl() ) {
			$this->upload_url = str_replace( 'http://', 'https://', $this->upload_url );
		}

		/**
		 * Custom base dir.
		 *
		 * If the component set this property, set the specific path, url and create the dir
		 */
		if ( ! empty( $this->base_dir ) ) {
			$this->upload_path = trailingslashit( $this->upload_path ) . trailingslashit( $this->base_dir ) . trailingslashit( $this->component ) . $this->item_id;
			$this->upload_url  = trailingslashit( $this->upload_url ) . trailingslashit( $this->base_dir ) . trailingslashit( $this->component ) . $this->item_id;

			// Finally create the base dir.
			$this->create_dir();
		}
	}


	/**
	 * Set Upload error messages.
	 *
	 * Used into the $overrides argument of ApppAttachment->upload()
	 *
	 * @since 1.0.0
	 *
	 * @param array $param A list of error messages.
	 * @return array $upload_errors The list of upload errors.
	 */
	public function set_upload_error_strings( $param = array() ) {
		/**
		 * Index of the array is the error code
		 * Custom errors will start at 9 code
		 */
		$upload_errors = array(
			0 => __( 'The file was uploaded successfully', 'appppresser' ),
			1 => __( 'The uploaded file exceeds the maximum allowed file size for this site', 'appppresser' ),

			/* translators: %s: Max file size for the file */
			2 => sprintf( __( 'The uploaded file exceeds the maximum allowed file size of: %s', 'appppresser' ), size_format( $this->original_max_filesize ) ),
			3 => __( 'The uploaded file was only partially uploaded.', 'appppresser' ),
			4 => __( 'No file was uploaded.', 'appppresser' ),
			5 => '',
			6 => __( 'Missing a temporary folder.', 'appppresser' ),
			7 => __( 'Failed to write file to disk.', 'appppresser' ),
			8 => __( 'File upload stopped by extension.', 'appppresser' ),
		);

		if ( ! array_intersect_key( $upload_errors, (array) $param ) ) {
			foreach ( $param as $key_error => $error_message ) {
				$upload_errors[ $key_error ] = $error_message;
			}
		}

		return $upload_errors;
	}


	/**
	 * Create the custom base directory for the component uploads.
	 *
	 * @since 1.0.0
	 */
	public function create_dir() {
		// Bail if no specific base dir is set.
		if ( empty( $this->base_dir ) ) {
			return false;
		}

		// Check if upload path already exists.
		if ( ! is_dir( $this->upload_path ) ) {

			// If path does not exist, attempt to create it.
			if ( ! wp_mkdir_p( $this->upload_path ) ) {
				return false;
			}
		}

		// Directory exists.
		return true;
	}

	/**
	 * Validate the allowed mime types using WordPress allowed mime types.
	 *
	 * In case of a multisite, the mime types are already restricted by
	 * the 'upload_filetypes' setting. BuddyPress will respect this setting.
	 *
	 * @see check_upload_mimes()
	 *
	 * @since 1.0.0
	 */
	protected function validate_mime_types() {
		$wp_mimes    = get_allowed_mime_types();
		$valid_mimes = array();

		// Set the allowed mimes for the upload.
		foreach ( (array) $this->allowed_mime_types as $ext ) {
			foreach ( $wp_mimes as $ext_pattern => $mime ) {
				if ( $ext !== '' && strpos( $ext_pattern, $ext ) !== false ) {
					$valid_mimes[ $ext_pattern ] = $mime;
				}
			}
		}
		return $valid_mimes;
	}

	/**
	 * Specific upload rules.
	 *
	 * Override this function from your child class to build your specific rules
	 * By default, if an original_max_filesize is provided, a check will be done
	 * on the file size.
	 *
	 * @since 1.0.0
	 *
	 * @param array $file The temporary file attributes (before it has been moved).
	 * @return array The file.
	 */
	public function validate_upload( $file = array() ) {
		// Bail if already an error.
		if ( ! empty( $file['error'] ) ) {
			return $file;
		}

		if ( ! empty( $this->original_max_filesize ) && $file['size'] > $this->original_max_filesize ) {
			$file['error'] = 2;
		}

		// Return the file.
		return $file;
	}

	/**
	 * Default filter to save the attachments.
	 *
	 * @since 1.0.0
	 * @since 1.0.0 Add the $upload_dir parameter to the method
	 *
	 *       regarding to context
	 *
	 * @param array $upload_dir The original Uploads dir.
	 * @return array The upload directory data.
	 */
	public function upload_dir_filter( $upload_dir = array() ) {

		/**
		 * Filters the component's upload directory.
		 *
		 * @since 1.0.0
		 * @since 1.0.0 Include the original Upload directory as the second parameter of the filter.
		 *
		 * @param array $value          Array containing the path, URL, and other helpful settings.
		 * @param array $upload_dir     The original Uploads dir.
		 */
		return array(
			'path'    => $this->upload_path,
			'url'     => $this->upload_url,
			'subdir'  => false,
			'basedir' => $this->upload_path,
			'baseurl' => $this->upload_url,
			'error'   => false,
		);
	}

	/**
	 * Helper to convert utf-8 characters in filenames to their ASCII equivalent.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $retval Filename.
	 * @return string
	 */
	public function sanitize_utf8_filename( $retval ) {
		// PHP 5.4+ or with PECL intl 2.0+
		if ( function_exists( 'transliterator_transliterate' ) && seems_utf8( $retval ) ) {
			$retval = transliterator_transliterate( 'Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove', $retval );

			// Older.
		} else {
			// Use WP's built-in function to convert accents to their ASCII equivalent.
			$retval = remove_accents( $retval );

			// Still here? use iconv().
			if ( function_exists( 'iconv' ) && seems_utf8( $retval ) ) {
				$retval = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $retval );
			}
		}

		return $retval;
	}

	/**
	 * Shrink upload image attachments
	 *
	 * @return string
	 */
	public function shrink_image( $file ) {

		// Get the image editor.
		$editor = wp_get_image_editor( $file );

		if ( is_wp_error( $editor ) ) {
			// error_log(print_r($editor,true));
			return $editor;
		}

		// Image and target size.
		$target   = 1200;
		$sizeORIG = $editor->get_size();

		// chekc if the image is larger than the target crop dimensions.
		if ( ( isset( $sizeORIG['width'] ) && $sizeORIG['width'] > $target ) || ( isset( $sizeORIG['height'] ) && $sizeORIG['height'] > $target ) ) {

			$width  = $sizeORIG['width'];
			$height = $sizeORIG['height'];

			if ( $width > $height ) {
				$percentage = ( $target / $width );
			} else {
				$percentage = ( $target / $height );
			}

			// gets the new value and applies the percentage, then rounds the value.
			$width  = round( $width * $percentage );
			$height = round( $height * $percentage );

			$resized = $editor->resize( $width, $height, false );

			// Stop in case of error.
			if ( is_wp_error( $resized ) ) {
				error_log( print_r( $resized, true ) );
				return $resized;
			}
		}

		$editor->set_quality( 70 );

		// Use the editor save method to get a path to the edited image.
		return $editor->save( $file );
	}


	public function appp_get_image_data( $file ) {
		// Try to get image basic data.
		list( $width, $height, $sourceImageType ) = @getimagesize( $file );

		// No need to carry on if we couldn't get image's basic data.
		if ( is_null( $width ) || is_null( $height ) || is_null( $sourceImageType ) ) {
			return false;
		}

		// Initialize the image data.
		$image_data = array(
			'width'  => $width,
			'height' => $height,
		);

		/**
		 * Make sure the wp_read_image_metadata function is reachable for the old Avatar UI
		 * or if WordPress < 3.9 (New Avatar UI is not available in this case)
		 */
		if ( ! function_exists( 'wp_read_image_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		// Now try to get image's meta data.
		$meta = wp_read_image_metadata( $file );
		if ( ! empty( $meta ) ) {
			$image_data['meta'] = $meta;
		}

		return $image_data;
	}

}


function appp_get_attachments_path() {

	$upload_dir = wp_upload_dir();
	return $upload_dir['basedir'] . '/attachments';
}

function appp_get_attachments_url() {

	$upload_dir = wp_upload_dir();
	return $upload_dir['baseurl'] . '/attachments';
}

function appp_get_attachments( $sub_dir, $item_id ) {

	$dir = appp_get_attachments_path() . '/' . $sub_dir . '/' . $item_id;
	$url = appp_get_attachments_url() . '/' . $sub_dir . '/' . $item_id;

	$files = array();

	if ( is_dir( $dir ) ) {
		if ( $dh = opendir( $dir ) ) {
			while ( ( $file = readdir( $dh ) ) !== false ) {
				if ( $file === '.' || $file === '..' || $file === '.DS_Store' ) {
					continue;
				}
				$files[] = $url . '/' . $file;
			}
			closedir( $dh );
		}
	}

	return $files;
}

