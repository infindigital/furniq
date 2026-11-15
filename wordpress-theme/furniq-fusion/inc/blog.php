<?php
/**
 * Blog posts in the ElementsKit "image card" layout of the original Blog page.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

/**
 * Blog page posts per page (Customizer → Furniq: Blog page).
 */
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() && $q->is_main_query() && $q->is_home() ) {
			$q->set( 'posts_per_page', max( 1, (int) get_theme_mod( 'furniq_blog_posts_per_page', 9 ) ) );
		}
	}
);

/**
 * One post card.
 */
function furniq_post_card() {
	$link = get_permalink();
	?>
	<div class="ekit-col-4">
		<div class="elementskit-post-image-card">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="elementskit-entry-header">
					<a href="<?php echo esc_url( $link ); ?>" class="elementskit-entry-thumb">
						<?php the_post_thumbnail( 'medium_large', array( 'alt' => the_title_attribute( array( 'echo' => false ) ) ) ); ?>
					</a>
				</div>
			<?php endif; ?>
			<div class="elementskit-post-body ">
				<div class="post-meta-list">
					<span class="meta-date">
						<i aria-hidden="true" class="icon icon-calendar3"></i>
						<span class="meta-date-text"><?php echo esc_html( get_the_date() ); ?></span>
					</span>
				</div>
				<h2 class="entry-title"><a href="<?php echo esc_url( $link ); ?>"><?php the_title(); ?></a></h2>
				<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
				<div class="btn-wraper">
					<a href="<?php echo esc_url( $link ); ?>" class="elementskit-btn whitespace--normal"><?php esc_html_e( 'Read More', 'furniq-fusion' ); ?></a>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Posts loop used inside the Blog page design (main query on the posts page,
 * latest posts when the design is shown anywhere else).
 */
function furniq_blog_posts() {
	global $wp_query;
	$q = is_home() ? $wp_query : new WP_Query(
		array(
			'posts_per_page'      => max( 1, (int) get_theme_mod( 'furniq_blog_posts_per_page', 9 ) ),
			'ignore_sticky_posts' => true,
		)
	);
	if ( ! $q->have_posts() ) {
		echo '<div class="ekit-col-12"><p class="furniq-no-posts">' . esc_html__( 'No posts yet. Add one in Posts → Add New.', 'furniq-fusion' ) . '</p></div>';
		return;
	}
	while ( $q->have_posts() ) {
		$q->the_post();
		furniq_post_card();
	}
	if ( $q->max_num_pages > 1 ) {
		echo '<div class="ekit-col-12 furniq-pagination">';
		if ( is_home() ) {
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
				)
			);
		}
		echo '</div>';
	}
	if ( ! is_home() ) {
		wp_reset_postdata();
	}
}
