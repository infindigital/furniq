<?php
/**
 * Single product page.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

get_header();
do_action( 'woocommerce_before_main_content' );
while ( have_posts() ) :
	the_post();
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		$product = wc_get_product( get_the_ID() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	}
	echo '<div class="woocommerce-notices-wrapper">';
	wc_print_notices();
	echo '</div>';
	furniq_part( 'main-product' );
endwhile;
do_action( 'woocommerce_after_main_content' );
get_footer();
