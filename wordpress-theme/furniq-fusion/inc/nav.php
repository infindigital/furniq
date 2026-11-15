<?php
/**
 * Header menu in the ElementsKit markup of the original site.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

/**
 * Walker producing the ElementsKit nav-menu markup.
 */
class Furniq_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Submenu start.
	 *
	 * @param string   $output Output.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Args.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '<ul class="elementskit-dropdown elementskit-submenu-panel">';
	}

	/**
	 * Submenu end.
	 *
	 * @param string   $output Output.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Args.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
	}

	/**
	 * Item start.
	 *
	 * @param string   $output Output.
	 * @param WP_Post  $item   Item.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Args.
	 * @param int      $id     Id.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;
		$has_kids  = in_array( 'menu-item-has-children', $classes, true );
		$active    = in_array( 'current-menu-item', $classes, true ) || in_array( 'current_page_item', $classes, true );
		if ( 0 === $depth ) {
			$classes[] = 'nav-item';
		}
		if ( $has_kids ) {
			$classes[] = 'elementskit-dropdown-has relative_position elementskit-dropdown-menu-default_width';
		}
		$classes[] = 'elementskit-mobile-builder-content';
		if ( $active ) {
			$classes[] = 'active';
		}
		$class_str = implode( ' ', array_filter( array_map( 'trim', $classes ) ) );

		$link_class = 0 === $depth ? 'ekit-menu-nav-link' : 'dropdown-item';
		if ( $has_kids ) {
			$link_class .= ' ekit-menu-dropdown-toggle';
		}
		if ( $active ) {
			$link_class .= ' active';
		}

		$atts = '';
		if ( ! empty( $item->target ) ) {
			$atts .= ' target="' . esc_attr( $item->target ) . '"';
		}
		if ( ! empty( $item->xfn ) ) {
			$atts .= ' rel="' . esc_attr( $item->xfn ) . '"';
		}
		$title = apply_filters( 'the_title', $item->title, $item->ID );

		$output .= '<li id="menu-item-' . (int) $item->ID . '" class="' . esc_attr( $class_str ) . '" data-vertical-menu=750px>';
		$output .= '<a href="' . esc_url( $item->url ) . '" class="' . esc_attr( $link_class ) . '"' . $atts . '>' . esc_html( $title );
		if ( $has_kids ) {
			$output .= '<i aria-hidden="true" class="icon icon-down-arrow1 elementskit-submenu-indicator"></i>';
		}
		$output .= '</a>';
	}

	/**
	 * Item end.
	 *
	 * @param string   $output Output.
	 * @param WP_Post  $item   Item.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Args.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= "</li>\n";
	}
}

/**
 * Print the header menu (Appearance → Menus → "Main menu (header)").
 * Without a menu, the original six links are shown.
 */
function furniq_primary_menu() {
	$wrap = '<ul id="menu-main" class="elementskit-navbar-nav elementskit-menu-po-right submenu-click-on-icon">%3$s</ul>';
	$locations = get_nav_menu_locations();
	$menu_id   = isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;
	if ( $menu_id && wp_get_nav_menu_object( $menu_id ) && wp_get_nav_menu_items( $menu_id ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'items_wrap'     => $wrap,
				'walker'         => new Furniq_Nav_Walker(),
				'depth'          => 3,
				'fallback_cb'    => false,
			)
		);
		return;
	}
	$links = array(
		array( __( 'Home', 'furniq-fusion' ), home_url( '/' ), is_front_page() ),
		array( __( 'About', 'furniq-fusion' ), furniq_page_url( 'about' ), is_page( 'about' ) ),
		array( __( 'Shop', 'furniq-fusion' ), furniq_shop_url(), function_exists( 'is_shop' ) && is_shop() ),
		array( __( 'Blog', 'furniq-fusion' ), furniq_page_url( 'blog' ), is_home() ),
		array( __( 'FAQ', 'furniq-fusion' ), furniq_page_url( 'faq' ), is_page( 'faq' ) ),
		array( __( 'Contact', 'furniq-fusion' ), furniq_page_url( 'contact' ), is_page( 'contact' ) ),
	);
	echo '<ul id="menu-main" class="elementskit-navbar-nav elementskit-menu-po-right submenu-click-on-icon">';
	foreach ( $links as $i => $l ) {
		$active = $l[2] ? ' current-menu-item active' : '';
		echo '<li class="menu-item menu-item-type-post_type menu-item-object-page nav-item elementskit-mobile-builder-content' . esc_attr( $active ) . '" data-vertical-menu=750px>';
		echo '<a href="' . esc_url( $l[1] ) . '" class="ekit-menu-nav-link' . ( $l[2] ? ' active' : '' ) . '">' . esc_html( $l[0] ) . '</a></li>' . "\n";
	}
	echo '</ul>';
}
