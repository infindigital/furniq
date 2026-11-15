<?php
/**
 * Shop, product category and product tag pages.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

get_header();
do_action( 'woocommerce_before_main_content' );
furniq_part( 'main-archive' );
do_action( 'woocommerce_after_main_content' );
get_footer();
