<?php
/**
 * Plain pages (default template). Pages called About, FAQ or Contact can use the
 * matching "Furniq – … design" template from the page editor.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

// About / FAQ / Contact / Blog pages carried over from the original Elementor site.
if ( in_array( furniq_context(), array( 'about', 'faq', 'contact', 'blog' ), true ) ) {
	get_header();
	furniq_part( 'main-' . furniq_context() );
	get_footer();
	return;
}

get_header();
get_template_part( 'parts/content-wrap-start' );
while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'furniq-entry' ); ?>>
		<div class="furniq-entry-content"><?php the_content(); ?></div>
		<?php
		wp_link_pages();
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>
	</article>
	<?php
endwhile;
get_template_part( 'parts/content-wrap-end' );
get_footer();
