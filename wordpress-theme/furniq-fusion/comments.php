<?php
/**
 * Comments.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="furniq-comments">
	<?php if ( have_comments() ) : ?>
		<h3 class="furniq-comments-title"><?php echo esc_html( get_comments_number_text() ); ?></h3>
		<ol class="comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true ) ); ?></ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</div>
