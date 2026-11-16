<?php
/**
 * Small helpers used by every template.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generated data: editable fields, Customizer sections, assets per page.
 *
 * @param string $key fields|sections|assets|body_classes.
 * @return array
 */
function furniq_data( $key ) {
	static $data = null;
	if ( null === $data ) {
		$data = require FURNIQ_DIR . '/inc/fields.php';
	}
	return isset( $data[ $key ] ) ? $data[ $key ] : array();
}

/**
 * Field definition.
 *
 * @param string $id Field id.
 * @return array|null
 */
function furniq_field( $id ) {
	$fields = furniq_data( 'fields' );
	return isset( $fields[ $id ] ) ? $fields[ $id ] : null;
}

/**
 * The default value of a field, resolved (image paths become URLs, link tokens become permalinks).
 *
 * @param string $id Field id.
 * @return string
 */
function furniq_default( $id ) {
	$f = furniq_field( $id );
	if ( ! $f ) {
		return '';
	}
	$d = $f['default'];
	if ( 'image' === $f['type'] && '' !== $d && ! preg_match( '#^https?://#', $d ) ) {
		return FURNIQ_URI . '/assets/' . str_replace( '%2F', '/', rawurlencode( $d ) );
	}
	if ( 'url' === $f['type'] ) {
		return furniq_resolve_link( $d );
	}
	return $d;
}

/**
 * Current value of a field: the Customizer value, or the original content.
 *
 * @param string $id Field id.
 * @return string
 */
function furniq_mod( $id ) {
	$value = get_theme_mod( 'furniq_' . $id, null );
	if ( null === $value || '' === $value ) {
		return furniq_default( $id );
	}
	return $value;
}

/**
 * Echo a field, escaped for its type.
 *
 * @param string $id Field id.
 */
function furniq_e( $id ) {
	$f     = furniq_field( $id );
	$type  = $f ? $f['type'] : 'text';
	$value = furniq_mod( $id );
	switch ( $type ) {
		case 'html':
			echo wp_kses_post( $value );
			break;
		case 'url':
		case 'image':
			echo esc_url( $value );
			break;
		case 'number':
			echo esc_attr( is_numeric( $value ) ? $value : '0' );
			break;
		default:
			echo esc_html( $value );
	}
}

/**
 * Turn a stored link token into a URL.
 * Tokens: home, shop, page:<slug>, term:<taxonomy>:<slug>, product:<slug>, or any URL.
 *
 * @param string $token Link token.
 * @return string
 */
function furniq_resolve_link( $token ) {
	if ( 'home' === $token ) {
		return home_url( '/' );
	}
	if ( 'shop' === $token ) {
		return furniq_shop_url();
	}
	if ( 0 === strpos( $token, 'page:' ) ) {
		return furniq_page_url( substr( $token, 5 ) );
	}
	if ( 0 === strpos( $token, 'term:' ) ) {
		$parts = explode( ':', $token );
		return furniq_term_url( $parts[1], $parts[2] );
	}
	if ( 0 === strpos( $token, 'product:' ) ) {
		return furniq_product_url( substr( $token, 8 ) );
	}
	if ( '#' === $token ) {
		return '#';
	}
	return $token;
}

/**
 * URL of one of the site pages (about, blog, faq, contact) – looked up by slug.
 *
 * @param string $slug Page slug.
 * @return string
 */
function furniq_page_url( $slug ) {
	if ( 'blog' === $slug && get_option( 'page_for_posts' ) ) {
		return get_permalink( (int) get_option( 'page_for_posts' ) );
	}
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return get_permalink( $page );
	}
	return home_url( '/' . $slug . '/' );
}

/**
 * Shop page URL.
 *
 * @return string
 */
function furniq_shop_url() {
	if ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 ) {
		return get_permalink( wc_get_page_id( 'shop' ) );
	}
	if ( post_type_exists( 'product' ) ) {
		return get_post_type_archive_link( 'product' );
	}
	return home_url( '/shop/' );
}

/**
 * Cart / checkout URL.
 *
 * @param string $page cart|checkout.
 * @return string
 */
function furniq_wc_url( $page ) {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( $page );
	}
	return home_url( '/' . $page . '/' );
}

/**
 * Link to a taxonomy term by slug (falls back to the shop).
 *
 * @param string $taxonomy Taxonomy.
 * @param string $slug     Term slug.
 * @return string
 */
function furniq_term_url( $taxonomy, $slug ) {
	if ( taxonomy_exists( $taxonomy ) ) {
		$link = get_term_link( $slug, $taxonomy );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}
	return 'product_cat' === $taxonomy || 'product_tag' === $taxonomy ? furniq_shop_url() : home_url( '/' );
}

/**
 * Link to a product by slug.
 *
 * @param string $slug Product slug.
 * @return string
 */
function furniq_product_url( $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'product' );
	return $p ? get_permalink( $p ) : furniq_shop_url();
}

/**
 * URL with slashes escaped, for use inside JSON in an HTML attribute.
 *
 * @param string $url URL.
 * @return string
 */
function furniq_json_url( $url ) {
	return str_replace( '/', '\/', esc_url( $url ) );
}

/**
 * Header logo: the WordPress Site Logo, or the original Furniq Fusion logo.
 *
 * @return string
 */
function furniq_logo_url() {
	$id = get_theme_mod( 'custom_logo' );
	if ( $id ) {
		$src = wp_get_attachment_image_url( $id, 'full' );
		if ( $src ) {
			return $src;
		}
	}
	return FURNIQ_URI . '/assets/wp-content/uploads/2026/05/cropped-Untitled-design-2026-02-02T101005.227-e1776752494293.png';
}

/**
 * WhatsApp chat link built from the Customizer number and message.
 *
 * @return string
 */
function furniq_whatsapp_url() {
	$number = preg_replace( '/\D+/', '', furniq_mod( 'whatsapp_number' ) );
	return 'https://api.whatsapp.com/send?phone=' . $number . '&text=' . rawurlencode( furniq_mod( 'whatsapp_message' ) );
}

/**
 * "Whatsapp Us" button on product pages: custom link, or a chat that names the product.
 *
 * @return string
 */
function furniq_product_whatsapp_url() {
	$custom = get_theme_mod( 'furniq_product_whatsapp_link', null );
	if ( null === $custom ) {
		$custom = furniq_default( 'product_whatsapp_link' );
	}
	if ( '' !== $custom ) {
		return $custom;
	}
	$number  = preg_replace( '/\D+/', '', furniq_mod( 'whatsapp_number' ) );
	$message = furniq_mod( 'whatsapp_message' );
	if ( is_singular( 'product' ) ) {
		$message .= get_the_title() . ' – ' . get_permalink();
	}
	return 'https://api.whatsapp.com/send?phone=' . $number . '&text=' . rawurlencode( $message );
}

/**
 * Which design the current request uses.
 *
 * @return string home|about|blog|faq|contact|archive|product|generic
 */
function furniq_context() {
	static $ctx = null;
	if ( null !== $ctx ) {
		return $ctx;
	}
	$ctx = 'generic';
	if ( function_exists( 'is_woocommerce' ) && ( is_shop() || is_product_taxonomy() ) ) {
		$ctx = 'archive';
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$ctx = 'product';
	} elseif ( is_front_page() && ! is_home() ) {
		$ctx = 'home';
	} elseif ( is_home() ) {
		$ctx = 'blog';
	} elseif ( is_page() ) {
		$tpl  = get_page_template_slug();
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		foreach ( array( 'about', 'faq', 'contact' ) as $design ) {
			if ( 'page-templates/' . $design . '.php' === $tpl ) {
				$ctx = $design;
			}
			// Pages kept from the original Elementor site (Elementor template, Elementor not needed).
			if ( $slug === $design && ( '' === $tpl || 0 === strpos( $tpl, 'elementor_' ) ) ) {
				$ctx = $design;
			}
		}
		if ( 'blog' === $slug && ( '' === $tpl || 0 === strpos( $tpl, 'elementor_' ) ) ) {
			$ctx = 'blog';
		}
	}
	return $ctx;
}

/**
 * Asset set used by a context (generic pages reuse the blog design).
 *
 * @return string
 */
function furniq_asset_set() {
	$ctx = furniq_context();
	return 'generic' === $ctx ? 'blog' : $ctx;
}

/**
 * Elementor frontend "post" config for the current page.
 *
 * @return string JSON.
 */
function furniq_elementor_post_json() {
	$id = get_queried_object_id();
	return wp_json_encode(
		array(
			'id'            => (int) $id,
			'title'         => rawurlencode( wp_get_document_title() ),
			'excerpt'       => '',
			'featuredImage' => $id && has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'large' ) : false,
		)
	);
}

/**
 * Include a generated template part.
 *
 * @param string $name File name without .php inside parts/generated.
 */
function furniq_part( $name ) {
	$file = FURNIQ_DIR . '/parts/generated/' . $name . '.php';
	if ( file_exists( $file ) ) {
		include $file;
	}
}

/**
 * Title shown in the banner of plain pages, posts, archives and search.
 *
 * @return string
 */
function furniq_banner_title() {
	if ( is_singular() ) {
		return single_post_title( '', false );
	}
	if ( is_search() ) {
		/* translators: %s: search terms */
		return sprintf( __( 'Search results for “%s”', 'furniq-fusion' ), get_search_query() );
	}
	if ( is_404() ) {
		return __( 'Page not found', 'furniq-fusion' );
	}
	if ( is_archive() ) {
		return wp_strip_all_tags( get_the_archive_title() );
	}
	return get_bloginfo( 'name' );
}
