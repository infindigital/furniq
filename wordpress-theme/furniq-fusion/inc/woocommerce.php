<?php
/**
 * WooCommerce: product grids, shop loop and single product details in the
 * original markup.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

// The theme draws its own wrappers, breadcrumbs and sidebar.
add_action(
	'init',
	function () {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	}
);

// 16 products per page, 4 columns, as on the original shop.
add_filter(
	'loop_shop_per_page',
	function () {
		return (int) apply_filters( 'furniq_products_per_page', 16 );
	},
	20
);
add_filter(
	'loop_shop_columns',
	function () {
		return 4;
	}
);

/**
 * Featured products grid on the home page.
 */
function furniq_home_products() {
	if ( ! function_exists( 'wc_get_template_part' ) ) {
		echo '<p class="furniq-notice">' . esc_html__( 'Install and activate WooCommerce to show products here.', 'furniq-fusion' ) . '</p>';
		return;
	}
	$source = get_theme_mod( 'furniq_home_products_source', 'featured' );
	$count  = max( 1, (int) get_theme_mod( 'furniq_home_products_count', 8 ) );
	$args   = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'orderby'             => array(
			'date' => 'DESC',
			'ID'   => 'DESC',
		),
		'tax_query'           => WC()->query->get_tax_query(), // phpcs:ignore WordPress.DB.SlowDBQuery
	);
	if ( 'random' === $source ) {
		$args['orderby'] = 'rand';
	}
	if ( 'featured' === $source ) {
		$ids = wc_get_featured_product_ids();
		if ( $ids ) {
			$args['post__in'] = $ids;
		}
	}
	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) {
		echo '<p class="furniq-notice">' . esc_html__( 'No products yet. Add them in Products → Add New.', 'furniq-fusion' ) . '</p>';
		return;
	}
	wc_set_loop_prop( 'columns', 4 );
	wc_set_loop_prop( 'name', 'furniq_home' );
	echo '<ul class="products elementor-grid columns-4">' . "\n";
	while ( $q->have_posts() ) {
		$q->the_post();
		wc_get_template_part( 'content', 'product' );
	}
	echo '</ul>';
	wp_reset_postdata();
	wc_reset_loop();
}

/**
 * Shop / category / tag product loop (same markup as the original archive widget).
 */
function furniq_archive_products() {
	if ( ! function_exists( 'woocommerce_product_loop' ) ) {
		return;
	}
	echo '<div class="woocommerce columns-4 ">';
	woocommerce_output_all_notices();
	if ( woocommerce_product_loop() ) {
		woocommerce_result_count();
		woocommerce_catalog_ordering();
		echo '<ul class="products elementor-grid columns-4">' . "\n";
		if ( wc_get_loop_prop( 'total' ) ) {
			while ( have_posts() ) {
				the_post();
				do_action( 'woocommerce_shop_loop' );
				wc_get_template_part( 'content', 'product' );
			}
		}
		echo '</ul>' . "\n";
		woocommerce_pagination();
	} else {
		do_action( 'woocommerce_no_products_found' );
	}
	echo '</div>';
}

/**
 * Heading of the shop banner: "Shop", or the category / tag name.
 *
 * @return string
 */
function furniq_archive_title() {
	if ( is_product_taxonomy() ) {
		return single_term_title( '', false );
	}
	if ( is_search() ) {
		/* translators: %s: search terms */
		return sprintf( __( 'Search results: “%s”', 'furniq-fusion' ), get_search_query() );
	}
	$title = furniq_mod( 'archive_title' );
	return $title ? $title : __( 'Shop', 'furniq-fusion' );
}

/**
 * Classes of the single product wrapper (post classes of the product).
 *
 * @return string
 */
function furniq_product_wrapper_class() {
	return 'elementor elementor-502 elementor-location-single ' . implode( ' ', wc_get_product_class( '', get_the_ID() ) ) . ' product';
}

/**
 * Short description (falls back to the full description when empty).
 */
function furniq_product_short_description() {
	global $post;
	$short = apply_filters( 'woocommerce_short_description', $post->post_excerpt );
	if ( ! $short ) {
		$short = apply_filters( 'the_content', $post->post_content );
	}
	echo '<div class="woocommerce-product-details__short-description">' . $short . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered post content.
}

/**
 * Categories and tags in the original "detail-label / detail-content" layout.
 */
function furniq_product_meta() {
	global $product;
	if ( ! $product ) {
		return;
	}
	echo '<div class="product_meta">';
	do_action( 'woocommerce_product_meta_start' );
	if ( wc_product_sku_enabled() && $product->get_sku() ) {
		echo '<span class="sku_wrapper detail-container"><span class="detail-label">' . esc_html__( 'SKU', 'furniq-fusion' ) . '</span> <span class="sku">' . esc_html( $product->get_sku() ) . '</span></span>';
	}
	$cats = get_the_term_list( $product->get_id(), 'product_cat', '', ', ' );
	if ( $cats && ! is_wp_error( $cats ) ) {
		echo '<span class="posted_in detail-container"><span class="detail-label">' . esc_html( _n( 'Category', 'Categories', count( $product->get_category_ids() ), 'furniq-fusion' ) ) . '</span> <span class="detail-content">' . wp_kses_post( $cats ) . '</span></span>';
	}
	$tags = get_the_term_list( $product->get_id(), 'product_tag', '', ', ' );
	if ( $tags && ! is_wp_error( $tags ) ) {
		echo '<span class="tagged_as detail-container"><span class="detail-label">' . esc_html( _n( 'Tag', 'Tags', count( $product->get_tag_ids() ), 'furniq-fusion' ) ) . '</span> <span class="detail-content">' . wp_kses_post( $tags ) . '</span></span>';
	}
	do_action( 'woocommerce_product_meta_end' );
	echo '</div>';
}

/**
 * Put the product name into the enquiry popup's "Product Name" field.
 */
add_action(
	'wp_footer',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		?>
		<script>
		document.addEventListener('click', function (e) {
			var a = e.target.closest ? e.target.closest('a[href*="elementor-action"]') : null;
			if (!a) { return; }
			setTimeout(function () {
				var f = document.querySelector('[name="form_fields[field_a0eac2e]"]');
				if (f && !f.value) { f.value = <?php echo wp_json_encode( html_entity_decode( get_the_title(), ENT_QUOTES ) ); ?>; }
			}, 400);
		});
		</script>
		<?php
	},
	30
);
