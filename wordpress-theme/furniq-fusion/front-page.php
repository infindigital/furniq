<?php
/**
 * Home page (Settings → Reading → "A static page" → Homepage).
 * Every text and image is editable in Appearance → Customize → Furniq: Home page.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

if ( is_home() ) {
	// "Your latest posts" is selected as the homepage: show the blog design.
	get_template_part( 'home' );
	return;
}

get_header();
furniq_part( 'main-home' );
get_footer();
