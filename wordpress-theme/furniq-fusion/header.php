<?php
/**
 * Site header.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'furniq-fusion' ); ?></a>

<?php
// Transparent header over the hero on content pages, solid header on shop & product pages.
furniq_part( in_array( furniq_context(), array( 'archive', 'product' ), true ) ? 'header-solid' : 'header-overlay' );
?>
<div id="content" class="furniq-content">
