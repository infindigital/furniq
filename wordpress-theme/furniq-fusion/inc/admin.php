<?php
/**
 * Admin notices: WooCommerce requirement and how to import the original products.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="notice notice-warning"><p><strong>Furniq Fusion:</strong> ' .
				esc_html__( 'Install and activate the free WooCommerce plugin (Plugins → Add New) to add products, product categories and the shop.', 'furniq-fusion' ) .
				'</p></div>';
			return;
		}
		if ( get_option( 'furniq_import_notice_dismissed' ) ) {
			return;
		}
		$counts = wp_count_posts( 'product' );
		if ( $counts && (int) $counts->publish > 0 ) {
			return;
		}
		$import = admin_url( 'edit.php?post_type=product&page=product_importer' );
		$csv    = FURNIQ_URI . '/demo/furniq-products.csv';
		$dismiss = wp_nonce_url( add_query_arg( 'furniq_dismiss_import', 1 ), 'furniq_dismiss' );
		echo '<div class="notice notice-info"><p><strong>Furniq Fusion:</strong> ' .
			esc_html__( 'Start with the original 147 products: download the product file, then upload it in Products → Import (tick “Update existing products” off).', 'furniq-fusion' ) .
			' <a class="button" href="' . esc_url( $csv ) . '" download>' . esc_html__( 'Download products file', 'furniq-fusion' ) . '</a> ' .
			'<a class="button button-primary" href="' . esc_url( $import ) . '">' . esc_html__( 'Open the product importer', 'furniq-fusion' ) . '</a> ' .
			'<a href="' . esc_url( $dismiss ) . '">' . esc_html__( 'Dismiss', 'furniq-fusion' ) . '</a></p></div>';
	}
);

add_action(
	'admin_init',
	function () {
		if ( isset( $_GET['furniq_dismiss_import'] ) && check_admin_referer( 'furniq_dismiss' ) && current_user_can( 'manage_options' ) ) {
			update_option( 'furniq_import_notice_dismissed', 1 );
		}
	}
);
