<?php
/**
 * Theme setup: supports, menus, body classes and first-time site setup.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'furniq-fusion', FURNIQ_DIR . '/languages' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 82,
				'width'       => 287,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
		add_theme_support(
			'woocommerce',
			array(
				'thumbnail_image_width' => 300,
				'single_image_width'    => 600,
				'product_grid'          => array(
					'default_columns' => 4,
					'default_rows'    => 4,
				),
			)
		);
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
		register_nav_menus(
			array(
				'primary' => __( 'Main menu (header)', 'furniq-fusion' ),
			)
		);
	}
);

/**
 * Body classes the original Elementor CSS relies on.
 */
add_filter(
	'body_class',
	function ( $classes ) {
		$ctx   = furniq_context();
		$extra = array( 'wp-custom-logo', 'theme-hello-elementor', 'hello-elementor-default', 'elementor-default', 'elementor-template-full-width', 'elementor-kit-11' );
		$page_ids = array(
			'home'    => 151,
			'about'   => 157,
			'blog'    => 164,
			'faq'     => 169,
			'contact' => 174,
			'generic' => 164,
		);
		if ( isset( $page_ids[ $ctx ] ) ) {
			$extra[] = 'elementor-page';
			$extra[] = 'elementor-page-' . $page_ids[ $ctx ];
			$extra[] = 'page-template-elementor_header_footer';
		}
		if ( 'archive' === $ctx ) {
			$extra[] = 'elementor-page-527';
		}
		if ( 'product' === $ctx ) {
			$extra[] = 'elementor-page-502';
		}
		return array_values( array_unique( array_merge( $classes, $extra ) ) );
	}
);

/**
 * Page templates offered in the page editor.
 */
add_filter(
	'theme_page_templates',
	function ( $templates ) {
		$templates['page-templates/about.php']   = __( 'Furniq – About design', 'furniq-fusion' );
		$templates['page-templates/faq.php']     = __( 'Furniq – FAQ design', 'furniq-fusion' );
		$templates['page-templates/contact.php'] = __( 'Furniq – Contact design', 'furniq-fusion' );
		return $templates;
	}
);

/**
 * First activation: create the pages, menu and reading settings of the original site.
 * Existing pages with the same slug are reused, nothing is deleted.
 */
function furniq_setup_site() {
	$pages = array(
		'home'    => array( 'Home', '' ),
		'about'   => array( 'About', 'page-templates/about.php' ),
		'blog'    => array( 'Blog', '' ),
		'faq'     => array( 'FAQ', 'page-templates/faq.php' ),
		'contact' => array( 'Contact', 'page-templates/contact.php' ),
	);
	$ids   = array();
	foreach ( $pages as $slug => $info ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$ids[ $slug ] = $page->ID;
		} else {
			$ids[ $slug ] = wp_insert_post(
				array(
					'post_title'  => $info[0],
					'post_name'   => $slug,
					'post_status' => 'publish',
					'post_type'   => 'page',
				)
			);
		}
		if ( $info[1] && $ids[ $slug ] && ! is_wp_error( $ids[ $slug ] ) && ! get_page_template_slug( $ids[ $slug ] ) ) {
			update_post_meta( $ids[ $slug ], '_wp_page_template', $info[1] );
		}
	}

	if ( 'page' !== get_option( 'show_on_front' ) || ! get_option( 'page_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
		update_option( 'page_for_posts', $ids['blog'] );
	}

	// Main menu: Home, About, Shop, Blog, FAQ, Contact.
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations['primary'] ) ) {
		$menu_id = wp_get_nav_menu_object( 'Main' ) ? wp_get_nav_menu_object( 'Main' )->term_id : wp_create_nav_menu( 'Main' );
		if ( ! is_wp_error( $menu_id ) && ! wp_get_nav_menu_items( $menu_id ) ) {
			$order = array( 'home', 'about', 'shop', 'blog', 'faq', 'contact' );
			$pos   = 1;
			foreach ( $order as $slug ) {
				$pid = 'shop' === $slug ? ( function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0 ) : $ids[ $slug ];
				if ( $pid && $pid > 0 ) {
					wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-object-id' => $pid,
							'menu-item-object'    => 'page',
							'menu-item-type'      => 'post_type',
							'menu-item-status'    => 'publish',
							'menu-item-position'  => $pos++,
						)
					);
				}
			}
		}
		if ( ! is_wp_error( $menu_id ) ) {
			$locations['primary'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}

	// WooCommerce: 16 products per page (4 x 4) like the original shop.
	if ( class_exists( 'WooCommerce' ) ) {
		update_option( 'woocommerce_catalog_columns', 4 );
		update_option( 'woocommerce_catalog_rows', 4 );
	}
	update_option( 'furniq_setup_done', FURNIQ_VERSION );
}
add_action( 'after_switch_theme', 'furniq_setup_site' );

// If WooCommerce is activated after the theme, add the Shop link to the menu once.
add_action(
	'admin_init',
	function () {
		if ( class_exists( 'WooCommerce' ) && ! get_option( 'furniq_shop_menu_done' ) && wc_get_page_id( 'shop' ) > 0 ) {
			$locations = get_theme_mod( 'nav_menu_locations', array() );
			if ( ! empty( $locations['primary'] ) ) {
				$items   = wp_get_nav_menu_items( $locations['primary'] );
				$shop_id = wc_get_page_id( 'shop' );
				$has     = false;
				foreach ( (array) $items as $item ) {
					if ( (int) $item->object_id === $shop_id ) {
						$has = true;
					}
				}
				if ( ! $has ) {
					wp_update_nav_menu_item(
						$locations['primary'],
						0,
						array(
							'menu-item-object-id' => $shop_id,
							'menu-item-object'    => 'page',
							'menu-item-type'      => 'post_type',
							'menu-item-status'    => 'publish',
							'menu-item-position'  => 3,
						)
					);
				}
			}
			update_option( 'woocommerce_catalog_columns', 4 );
			update_option( 'woocommerce_catalog_rows', 4 );
			update_option( 'furniq_shop_menu_done', 1 );
		}
	}
);
