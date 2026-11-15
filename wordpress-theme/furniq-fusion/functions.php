<?php
/**
 * Furniq Fusion theme bootstrap.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

define( 'FURNIQ_VERSION', '1.0.0' );
define( 'FURNIQ_DIR', get_template_directory() );
define( 'FURNIQ_URI', get_template_directory_uri() );
define( 'FURNIQ_A', esc_url( FURNIQ_URI . '/assets' ) );
define( 'FURNIQ_AJ', str_replace( '/', '\/', FURNIQ_A ) );

require FURNIQ_DIR . '/inc/helpers.php';
require FURNIQ_DIR . '/inc/setup.php';
require FURNIQ_DIR . '/inc/assets.php';
require FURNIQ_DIR . '/inc/nav.php';
require FURNIQ_DIR . '/inc/customizer.php';
require FURNIQ_DIR . '/inc/blog.php';
require FURNIQ_DIR . '/inc/woocommerce.php';
require FURNIQ_DIR . '/inc/forms.php';
require FURNIQ_DIR . '/inc/admin.php';
