<?php
/**
 * Plain pages (default template). Pages called About, FAQ or Contact can use the
 * matching "Furniq – … design" template from the page editor.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

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
