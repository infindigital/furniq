<?php
/**
 * Page not found.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'parts/content-wrap-start' );
?>
<div class="furniq-entry furniq-404">
	<p><?php esc_html_e( 'The page you are looking for does not exist. Try the shop or the search below.', 'furniq-fusion' ); ?></p>
	<?php get_search_form(); ?>
	<p><a class="elementor-button elementor-size-sm" href="<?php echo esc_url( furniq_shop_url() ); ?>"><span class="elementor-button-text"><?php esc_html_e( 'Go to the shop', 'furniq-fusion' ); ?></span></a></p>
</div>
<?php
get_template_part( 'parts/content-wrap-end' );
get_footer();
