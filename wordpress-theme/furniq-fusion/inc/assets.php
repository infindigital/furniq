<?php
/**
 * Prints the original site's styles and scripts for the current page.
 *
 * The captured asset lists live in parts/generated/assets-<page>-head.php and
 * assets-<page>-footer.php. WordPress core and WooCommerce scripts are not in
 * those lists: they are enqueued the normal way so plugins keep working.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		$set    = furniq_asset_set();
		$assets = furniq_data( 'assets' );
		wp_enqueue_script( 'jquery' );
		if ( isset( $assets[ $set ] ) ) {
			foreach ( $assets[ $set ]['scripts'] as $handle ) {
				wp_enqueue_script( $handle );
			}
			foreach ( $assets[ $set ]['styles'] as $handle ) {
				wp_enqueue_style( $handle );
			}
		}
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
		wp_enqueue_style( 'furniq', FURNIQ_URI . '/assets/css/furniq.css', array(), FURNIQ_VERSION );

		// WooCommerce's default single product scripts are used as-is; its blocks
		// stylesheet and the original page styles cover the rest.
	},
	20
);

// Original stylesheets, right after WordPress prints its own (priority 8).
add_action(
	'wp_head',
	function () {
		furniq_part( 'assets-' . furniq_asset_set() . '-head' );
	},
	8
);

// Enquiry popup + Elementor runtime scripts, after WordPress footer scripts (priority 20).
add_action(
	'wp_footer',
	function () {
		furniq_part( 'popup' );
	},
	5
);
add_action(
	'wp_footer',
	function () {
		furniq_part( 'assets-' . furniq_asset_set() . '-footer' );
	},
	25
);

// Mirror the original: the product gallery is visible straight away.
add_action(
	'wp_head',
	function () {
		echo "<style>.woocommerce-product-gallery{ opacity: 1 !important; }</style>\n";
	},
	99
);
