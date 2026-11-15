<?php
/**
 * Receives the enquiry / contact forms (the original Elementor form markup and
 * script are kept; this answers their AJAX request) and emails them.
 * Every submission is also saved under Tools → Furniq enquiries.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handle a form submission.
 */
function furniq_handle_form() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- public form, like the original; spam is limited below.
	$raw = isset( $_POST['form_fields'] ) && is_array( $_POST['form_fields'] ) ? wp_unslash( $_POST['form_fields'] ) : array();
	$labels = array(
		'name'            => __( 'Name', 'furniq-fusion' ),
		'email'           => __( 'Email', 'furniq-fusion' ),
		'message'         => __( 'Message', 'furniq-fusion' ),
		'field_4f58c1e'   => __( 'Phone', 'furniq-fusion' ),
		'field_a0eac2e'   => __( 'Product Name', 'furniq-fusion' ),
	);
	$fields = array();
	foreach ( $raw as $key => $value ) {
		$key = sanitize_key( $key );
		$fields[ $key ] = 'message' === $key ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
	}
	$email = isset( $fields['email'] ) ? sanitize_email( $fields['email'] ) : '';
	if ( ! $email || ! is_email( $email ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Please enter a valid email address.', 'furniq-fusion' ),
				'errors'  => array( 'email' => __( 'Please enter a valid email address.', 'furniq-fusion' ) ),
				'data'    => array(),
			)
		);
	}

	// Simple rate limit: 5 submissions per 10 minutes per IP.
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key  = 'furniq_form_' . md5( $ip );
	$hits = (int) get_transient( $key );
	if ( $hits >= 5 ) {
		wp_send_json_error(
			array(
				'message' => __( 'Too many messages, please try again in a few minutes.', 'furniq-fusion' ),
				'errors'  => array(),
				'data'    => array(),
			)
		);
	}
	set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

	$referrer = isset( $_POST['referrer'] ) ? esc_url_raw( wp_unslash( $_POST['referrer'] ) ) : '';
	// phpcs:enable

	$lines = array();
	foreach ( $fields as $k => $v ) {
		$label   = isset( $labels[ $k ] ) ? $labels[ $k ] : ucwords( str_replace( '_', ' ', $k ) );
		$lines[] = $label . ': ' . $v;
	}
	$lines[] = '';
	$lines[] = __( 'Sent from:', 'furniq-fusion' ) . ' ' . $referrer;
	$body    = implode( "\n", $lines );

	$to = furniq_mod( 'form_recipient' );
	if ( ! $to || ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}
	$subject = isset( $fields['field_a0eac2e'] ) && $fields['field_a0eac2e']
		/* translators: %s: product name */
		? sprintf( __( 'Product enquiry: %s', 'furniq-fusion' ), $fields['field_a0eac2e'] )
		/* translators: %s: site name */
		: sprintf( __( 'New message from %s', 'furniq-fusion' ), get_bloginfo( 'name' ) );
	$name    = isset( $fields['name'] ) ? $fields['name'] : '';
	$headers = array( 'Reply-To: ' . ( $name ? $name . ' <' . $email . '>' : $email ) );

	wp_insert_post(
		array(
			'post_type'    => 'furniq_enquiry',
			'post_status'  => 'private',
			'post_title'   => $subject . ' – ' . $email,
			'post_content' => $body,
		)
	);
	wp_mail( $to, $subject, $body, $headers );

	wp_send_json_success(
		array(
			'message' => __( 'Your submission was successful.', 'furniq-fusion' ),
			'data'    => array(),
		)
	);
}
add_action( 'wp_ajax_elementor_pro_forms_send_form', 'furniq_handle_form' );
add_action( 'wp_ajax_nopriv_elementor_pro_forms_send_form', 'furniq_handle_form' );

/**
 * Saved enquiries (Tools → Furniq enquiries), in case email delivery fails.
 */
add_action(
	'init',
	function () {
		register_post_type(
			'furniq_enquiry',
			array(
				'labels'          => array(
					'name'          => __( 'Furniq enquiries', 'furniq-fusion' ),
					'singular_name' => __( 'Enquiry', 'furniq-fusion' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'tools.php',
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}
);
