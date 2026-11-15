<?php
/**
 * Fallback template.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'parts/content-wrap-start' );
?>
<div class="elementor-element elementor-element-3c6c2926 elementor-widget elementor-widget-elementskit-blog-posts">
	<div class="ekit-wid-con"><div class="ekit-row post-items">
		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				furniq_post_card();
			}
		} else {
			echo '<div class="ekit-col-12"><p>' . esc_html__( 'Nothing found.', 'furniq-fusion' ) . '</p></div>';
		}
		?>
	</div></div>
</div>
<?php
the_posts_pagination(
	array(
		'mid_size'  => 1,
		'prev_text' => '&larr;',
		'next_text' => '&rarr;',
	)
);
get_template_part( 'parts/content-wrap-end' );
get_footer();
