<?php
/**
 * Single blog post.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'parts/content-wrap-start' );
while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'furniq-entry furniq-single' ); ?>>
		<div class="furniq-entry-meta">
			<span class="furniq-date"><i aria-hidden="true" class="icon icon-calendar3"></i> <?php echo esc_html( get_the_date() ); ?></span>
			<?php
			$cats = get_the_category_list( ', ' );
			if ( $cats ) {
				echo '<span class="furniq-cats">' . wp_kses_post( $cats ) . '</span>';
			}
			?>
		</div>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="furniq-entry-thumb"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>
		<div class="furniq-entry-content"><?php the_content(); ?></div>
		<?php
		wp_link_pages();
		the_tags( '<div class="furniq-entry-tags">' . esc_html__( 'Tags:', 'furniq-fusion' ) . ' ', ', ', '</div>' );
		the_post_navigation(
			array(
				'prev_text' => '&larr; %title',
				'next_text' => '%title &rarr;',
			)
		);
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>
	</article>
	<?php
endwhile;
get_template_part( 'parts/content-wrap-end' );
get_footer();
