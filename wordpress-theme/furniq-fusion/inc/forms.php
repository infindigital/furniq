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

	$name  = isset( $fields['name'] ) ? $fields['name'] : '';
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
	$headers = array( 'Reply-To: ' . ( $name ? $name . ' <' . $email . '>' : $email ) );

	$enquiry_id = wp_insert_post(
		array(
			'post_type'    => 'furniq_enquiry',
			'post_status'  => 'private',
			'post_title'   => $subject . ' – ' . $email,
			'post_content' => $body,
		)
	);
	if ( $enquiry_id && ! is_wp_error( $enquiry_id ) ) {
		$meta = array(
			'_furniq_name'    => $name,
			'_furniq_email'   => $email,
			'_furniq_phone'   => isset( $fields['field_4f58c1e'] ) ? $fields['field_4f58c1e'] : '',
			'_furniq_product' => isset( $fields['field_a0eac2e'] ) ? $fields['field_a0eac2e'] : '',
			'_furniq_message' => isset( $fields['message'] ) ? $fields['message'] : '',
			'_furniq_page'    => $referrer,
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $enquiry_id, $k, $v );
		}
	}

	/**
	 * Fires after a form was sent – e.g. to pass the email to a newsletter service.
	 *
	 * @param array $fields Sanitised form fields (name, email, message, field_… ).
	 * @param int   $enquiry_id Saved enquiry id.
	 */
	do_action( 'furniq_form_submitted', $fields, $enquiry_id );
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

/**
 * Columns in Tools → Furniq enquiries.
 */
add_filter(
	'manage_furniq_enquiry_posts_columns',
	function () {
		return array(
			'cb'      => '<input type="checkbox" />',
			'title'   => __( 'Enquiry', 'furniq-fusion' ),
			'fname'   => __( 'Name', 'furniq-fusion' ),
			'femail'  => __( 'Email', 'furniq-fusion' ),
			'fphone'  => __( 'Phone', 'furniq-fusion' ),
			'fprod'   => __( 'Product', 'furniq-fusion' ),
			'date'    => __( 'Date', 'furniq-fusion' ),
		);
	}
);
add_action(
	'manage_furniq_enquiry_posts_custom_column',
	function ( $column, $post_id ) {
		$map = array(
			'fname'  => '_furniq_name',
			'femail' => '_furniq_email',
			'fphone' => '_furniq_phone',
			'fprod'  => '_furniq_product',
		);
		if ( isset( $map[ $column ] ) ) {
			$value = get_post_meta( $post_id, $map[ $column ], true );
			if ( 'femail' === $column && $value ) {
				echo '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';
			} else {
				echo esc_html( $value );
			}
		}
	},
	10,
	2
);

/**
 * "Download CSV" button above the enquiries list.
 */
add_action(
	'manage_posts_extra_tablenav',
	function ( $which ) {
		$screen = get_current_screen();
		if ( 'top' !== $which || ! $screen || 'furniq_enquiry' !== $screen->post_type || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=furniq_export_enquiries' ), 'furniq_export' );
		echo '<div class="alignleft actions"><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Download CSV (all enquiries)', 'furniq-fusion' ) . '</a></div>';
	}
);

add_action(
	'admin_post_furniq_export_enquiries',
	function () {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'furniq-fusion' ) );
		}
		check_admin_referer( 'furniq_export' );
		$posts = get_posts(
			array(
				'post_type'   => 'furniq_enquiry',
				'post_status' => 'any',
				'numberposts' => -1,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=furniq-enquiries-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM so Excel shows accents correctly.
		fputcsv( $out, array( 'Date', 'Name', 'Email', 'Phone', 'Product', 'Message', 'Page' ) );
		foreach ( $posts as $p ) {
			$row = array( get_the_date( 'Y-m-d H:i', $p ) );
			foreach ( array( '_furniq_name', '_furniq_email', '_furniq_phone', '_furniq_product', '_furniq_message', '_furniq_page' ) as $k ) {
				$v = (string) get_post_meta( $p->ID, $k, true );
				// Stop spreadsheet formula injection.
				if ( '' !== $v && ( in_array( $v[0], array( '=', '@' ), true ) || ( in_array( $v[0], array( '+', '-' ), true ) && ! preg_match( '/^[+\-][\d\s()\-]+$/', $v ) ) ) ) {
					$v = "'" . $v;
				}
				$row[] = $v;
			}
			fputcsv( $out, $row );
		}
		fclose( $out );
		exit;
	}
);
