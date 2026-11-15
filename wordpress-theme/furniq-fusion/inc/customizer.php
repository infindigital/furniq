<?php
/**
 * Appearance → Customize: one panel per page, one section per page section,
 * one control per text / image / link. Defaults are the original content.
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$panels   = array();
		$priority = 30;
		foreach ( furniq_data( 'sections' ) as $sid => $section ) {
			$panel_id = 'furniq_' . sanitize_key( $section['panel'] );
			if ( ! isset( $panels[ $panel_id ] ) ) {
				$wp_customize->add_panel(
					$panel_id,
					array(
						/* translators: %s: page name */
						'title'    => sprintf( __( 'Furniq: %s', 'furniq-fusion' ), $section['panel'] ),
						'priority' => $priority++,
					)
				);
				$panels[ $panel_id ] = true;
			}
			$wp_customize->add_section(
				'furniq_sec_' . $sid,
				array(
					'title'    => $section['title'],
					'panel'    => $panel_id,
					'priority' => (int) $section['priority'],
				)
			);
		}

		foreach ( furniq_data( 'fields' ) as $fid => $field ) {
			$setting = 'furniq_' . $fid;
			$default = furniq_default( $fid );
			switch ( $field['type'] ) {
				case 'html':
					$sanitize = 'wp_kses_post';
					break;
				case 'url':
					$sanitize = 'furniq_sanitize_link';
					break;
				case 'image':
					$sanitize = 'esc_url_raw';
					break;
				case 'number':
					$sanitize = 'furniq_sanitize_number';
					break;
				default:
					$sanitize = 'sanitize_text_field';
			}
			$wp_customize->add_setting(
				$setting,
				array(
					'default'           => $default,
					'sanitize_callback' => $sanitize,
					'transport'         => 'refresh',
				)
			);
			$args = array(
				'label'    => $field['label'],
				'section'  => 'furniq_sec_' . $field['section'],
				'priority' => (int) $field['priority'],
			);
			if ( 'image' === $field['type'] ) {
				$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $setting, $args ) );
				continue;
			}
			$args['type'] = 'html' === $field['type'] ? 'textarea' : ( 'number' === $field['type'] ? 'number' : ( 'url' === $field['type'] ? 'url' : 'text' ) );
			if ( 'html' === $field['type'] ) {
				$args['description'] = __( 'Basic HTML allowed (p, strong, br, a…).', 'furniq-fusion' );
			}
			$wp_customize->add_control( $setting, $args );
		}

		// Featured products section on the home page.
		$wp_customize->add_setting(
			'furniq_home_products_source',
			array(
				'default'           => 'featured',
				'sanitize_callback' => 'sanitize_key',
			)
		);
		$wp_customize->add_control(
			'furniq_home_products_source',
			array(
				'label'       => __( 'Products shown', 'furniq-fusion' ),
				'description' => __( 'Mark products as featured with the ★ in Products → All Products. When none are featured the newest are shown.', 'furniq-fusion' ),
				'section'     => 'furniq_sec_home_s4',
				'type'        => 'select',
				'choices'     => array(
					'featured' => __( 'Featured products', 'furniq-fusion' ),
					'latest'   => __( 'Newest products', 'furniq-fusion' ),
					'random'   => __( 'Random products', 'furniq-fusion' ),
				),
				'priority'    => 1,
			)
		);
		$wp_customize->add_setting(
			'furniq_home_products_count',
			array(
				'default'           => 8,
				'sanitize_callback' => 'absint',
			)
		);
		$wp_customize->add_control(
			'furniq_home_products_count',
			array(
				'label'    => __( 'Number of products', 'furniq-fusion' ),
				'section'  => 'furniq_sec_home_s4',
				'type'     => 'number',
				'priority' => 2,
			)
		);

		$wp_customize->add_setting(
			'furniq_blog_posts_per_page',
			array(
				'default'           => 9,
				'sanitize_callback' => 'absint',
			)
		);
		$wp_customize->add_control(
			'furniq_blog_posts_per_page',
			array(
				'label'    => __( 'Blog posts per page', 'furniq-fusion' ),
				'section'  => 'furniq_sec_blog_s2',
				'type'     => 'number',
				'priority' => 1,
			)
		);
	}
);

/**
 * Links may be full URLs, "#" or "mailto:/tel:" links.
 *
 * @param string $value Value.
 * @return string
 */
function furniq_sanitize_link( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value || '#' === $value ) {
		return $value;
	}
	return esc_url_raw( $value, array( 'http', 'https', 'mailto', 'tel' ) );
}

/**
 * Numbers for the counters.
 *
 * @param string $value Value.
 * @return string
 */
function furniq_sanitize_number( $value ) {
	return is_numeric( $value ) ? (string) $value : '';
}
