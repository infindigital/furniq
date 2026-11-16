<?php
/**
 * Analytics & tracking: Google Analytics 4 ID, free code boxes for <head> and
 * before </body>, and GA events for form submissions and WhatsApp clicks.
 *
 * Analytics plugins (Site Kit, MonsterInsights, Meta Pixel plugins…) keep working
 * as well – the theme prints everything through wp_head() / wp_footer().
 *
 * @package Furniq_Fusion
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$wp_customize->add_section(
			'furniq_analytics',
			array(
				'title'       => __( 'Furniq: Analytics & tracking', 'furniq-fusion' ),
				'description' => __( 'If an analytics plugin (e.g. Site Kit) already adds Google Analytics, leave the ID empty so visits are not counted twice.', 'furniq-fusion' ),
				'priority'    => 29,
			)
		);
		$wp_customize->add_setting(
			'furniq_ga_id',
			array(
				'default'           => '',
				'sanitize_callback' => 'furniq_sanitize_ga_id',
			)
		);
		$wp_customize->add_control(
			'furniq_ga_id',
			array(
				'label'       => __( 'Google Analytics 4 Measurement ID', 'furniq-fusion' ),
				'description' => __( 'Looks like G-XXXXXXXXXX (Google Analytics → Admin → Data streams). Also sends “generate_lead” when a form is sent and “whatsapp_click” when a WhatsApp button is clicked.', 'furniq-fusion' ),
				'section'     => 'furniq_analytics',
				'type'        => 'text',
			)
		);
		foreach ( array(
			'furniq_head_code'   => __( 'Code in <head> (Tag Manager, Meta Pixel, Clarity, site verification…)', 'furniq-fusion' ),
			'furniq_footer_code' => __( 'Code before </body>', 'furniq-fusion' ),
		) as $id => $label ) {
			$wp_customize->add_setting(
				$id,
				array(
					'default'           => '',
					'sanitize_callback' => 'furniq_sanitize_code',
					'capability'        => 'unfiltered_html',
				)
			);
			$wp_customize->add_control(
				$id,
				array(
					'label'   => $label,
					'section' => 'furniq_analytics',
					'type'    => 'textarea',
				)
			);
		}
	}
);

/**
 * GA4 ids only.
 *
 * @param string $value Value.
 * @return string
 */
function furniq_sanitize_ga_id( $value ) {
	$value = strtoupper( trim( (string) $value ) );
	return preg_match( '/^G-[A-Z0-9]{4,20}$/', $value ) ? $value : '';
}

/**
 * Tracking code is stored as pasted; only administrators allowed to post
 * unfiltered HTML can change it (setting capability = unfiltered_html).
 *
 * @param string $value Value.
 * @return string
 */
function furniq_sanitize_code( $value ) {
	return current_user_can( 'unfiltered_html' ) ? (string) $value : wp_kses_post( $value );
}

add_action(
	'wp_head',
	function () {
		if ( is_customize_preview() ) {
			return; // Do not count Customizer previews as visits.
		}
		$id = get_theme_mod( 'furniq_ga_id', '' );
		if ( $id ) {
			?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $id ); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', <?php echo wp_json_encode( $id ); ?>);
</script>
			<?php
		}
		echo get_theme_mod( 'furniq_head_code', '' ); // phpcs:ignore WordPress.Security.EscapeOutput -- admin supplied tracking code (unfiltered_html).
	},
	1
);

add_action(
	'wp_footer',
	function () {
		if ( is_customize_preview() ) {
			return;
		}
		echo get_theme_mod( 'furniq_footer_code', '' ); // phpcs:ignore WordPress.Security.EscapeOutput -- admin supplied tracking code (unfiltered_html).
		?>
<script>
(function () {
	function track(name, params) {
		if (typeof window.gtag === 'function') { window.gtag('event', name, params || {}); }
		if (window.dataLayer && typeof window.gtag !== 'function') { window.dataLayer.push(Object.assign({ event: name }, params || {})); }
	}
	if (window.jQuery) {
		jQuery(document).on('submit_success', '.elementor-form', function () {
			var product = jQuery(this).find('[name="form_fields[field_a0eac2e]"]').val() || '';
			track('generate_lead', { form_name: this.getAttribute('name') || 'form', product_name: product, page_path: location.pathname });
		});
	}
	document.addEventListener('click', function (e) {
		var a = e.target.closest ? e.target.closest('a[href*="whatsapp.com"], a[href*="wa.me"], a[href*="wa.link"]') : null;
		if (a) { track('whatsapp_click', { page_path: location.pathname, link_url: a.href }); }
	});
})();
</script>
		<?php
	},
	40
);
