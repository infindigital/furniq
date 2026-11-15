<script>
				;
				(function($, w) {
					'use strict';
					let $window = $(w);

					$(document).ready(function() {

						let isEnable = "";
						let isEnableLazyMove = "";
						let speed = isEnableLazyMove ? '0.7' : '0.2';

						if( !isEnable ) {
							return;
						}

						if (typeof haCursor == 'undefined' || haCursor == null) {
							initiateHaCursorObject(speed);
						}

						setTimeout(function() {
							let targetCursor = $('.ha-cursor');
							if (targetCursor) {
								if (!isEnable) {
									$('body').removeClass('hm-init-default-cursor-none');
									$('.ha-cursor').addClass('ha-init-hide');
								} else {
									$('body').addClass('hm-init-default-cursor-none');
									$('.ha-cursor').removeClass('ha-init-hide');
								}
							}
						}, 500);

					});

				}(jQuery, window));
			</script>
<script>
				( () => {
					const lazyloadRunObserver = () => {
						const lazyloadBackgrounds = document.querySelectorAll( `.e-con.e-parent:not(.e-lazyloaded)` );
						const lazyloadBackgroundObserver = new IntersectionObserver( ( entries ) => {
							entries.forEach( ( entry ) => {
								if ( entry.isIntersecting ) {
									let lazyloadBackground = entry.target;
									if( lazyloadBackground ) {
										lazyloadBackground.classList.add( 'e-lazyloaded' );
									}
									lazyloadBackgroundObserver.unobserve( entry.target );
								}
							});
						}, { rootMargin: '200px 0px 200px 0px' } );
						lazyloadBackgrounds.forEach( ( lazyloadBackground ) => {
							lazyloadBackgroundObserver.observe( lazyloadBackground );
						} );
					};
					const events = [
						'DOMContentLoaded',
						'elementor/lazyload/observe',
					];
					events.forEach( ( event ) => {
						document.addEventListener( event, lazyloadRunObserver );
					} );
				} )();
			</script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/elementskit-lite/widgets/init/assets/js/widget-scripts.js?ver=3.10.02" id="ekit-widget-scripts-js"></script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/themes/hello-elementor/assets/js/hello-frontend.js?ver=3.4.9" id="hello-theme-frontend-js"></script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/elementor/assets/js/webpack.runtime.min.js?ver=4.2.0" id="elementor-webpack-runtime-js"></script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/elementor/assets/js/frontend-modules.min.js?ver=4.2.0" id="elementor-frontend-modules-js"></script>
<script id="elementor-frontend-js-before">
var elementorFrontendConfig = {"environmentMode":{"edit":false,"wpPreview":false,"isScriptDebug":false},"i18n":{"shareOnFacebook":"Share on Facebook","shareOnX":"Share on X","pinIt":"Pin it","download":"Download","downloadImage":"Download image","fullscreen":"Fullscreen","zoom":"Zoom","share":"Share","playVideo":"Play Video","previous":"Previous","next":"Next","close":"Close","a11yCarouselPrevSlideMessage":"Previous slide","a11yCarouselNextSlideMessage":"Next slide","a11yCarouselFirstSlideMessage":"This is the first slide","a11yCarouselLastSlideMessage":"This is the last slide","a11yCarouselPaginationBulletMessage":"Go to slide"},"is_rtl":false,"breakpoints":{"xs":0,"sm":480,"md":768,"lg":1025,"xl":1440,"xxl":1600},"responsive":{"breakpoints":{"mobile":{"label":"Mobile Portrait","value":767,"default_value":767,"direction":"max","is_enabled":true},"mobile_extra":{"label":"Mobile Landscape","value":880,"default_value":880,"direction":"max","is_enabled":false},"tablet":{"label":"Tablet Portrait","value":1024,"default_value":1024,"direction":"max","is_enabled":true},"tablet_extra":{"label":"Tablet Landscape","value":1200,"default_value":1200,"direction":"max","is_enabled":false},"laptop":{"label":"Laptop","value":1366,"default_value":1366,"direction":"max","is_enabled":false},"widescreen":{"label":"Widescreen","value":2400,"default_value":2400,"direction":"min","is_enabled":false}},"hasCustomBreakpoints":false},"version":"4.2.0","is_static":false,"experimentalFeatures":{"e_font_icon_svg":true,"additional_custom_breakpoints":true,"container":true,"e_optimized_markup":true,"e_panel_promotions":true,"theme_builder_v2":true,"hello-theme-header-footer":true,"e_pro_free_trial_popup":true,"nested-elements":true,"e_atomic_elements":true,"atomic_widgets_should_enforce_capabilities":true,"editor_mcp":true,"e_bc_migrations":true,"e_classes":true,"global_classes_should_enforce_capabilities":true,"e_variables":true,"e_variables_manager":true,"e_opt_in_v4_page":true,"e_opt_in_v4":true,"e_components":true,"e_interactions":true,"e_widget_creation":true,"import-export-customization":true,"e_pro_atomic_form":true,"mega-menu":true,"e_pro_variables":true,"e_pro_interactions":true},"urls":{"assets":"<?php echo FURNIQ_AJ; ?>\/wp-content\/plugins\/elementor\/assets\/","ajaxurl":"<?php echo furniq_json_url( admin_url( 'admin-ajax.php' ) ); ?>","uploadUrl":"<?php echo FURNIQ_AJ; ?>\/wp-content\/uploads"},"nonces":{"floatingButtonsClickTracking":"<?php echo esc_js( wp_create_nonce( 'furniq' ) ); ?>","atomicFormsSendForm":"<?php echo esc_js( wp_create_nonce( 'furniq-form' ) ); ?>"},"swiperClass":"swiper","settings":{"page":{"ha_cmc_init_switcher":"no"},"editorPreferences":[]},"kit":{"active_breakpoints":["viewport_mobile","viewport_tablet"],"global_image_lightbox":"yes","lightbox_enable_counter":"yes","lightbox_enable_fullscreen":"yes","lightbox_enable_zoom":"yes","lightbox_enable_share":"yes","lightbox_title_src":"title","lightbox_description_src":"description","woocommerce_notices_elements":[],"hello_header_logo_type":"logo","hello_header_menu_layout":"horizontal","hello_footer_logo_type":"logo","ha_rpb_enable":"no"},"post":<?php echo furniq_elementor_post_json(); ?>};
//# sourceURL=elementor-frontend-js-before
</script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/elementor/assets/js/frontend.min.js?ver=4.2.0" id="elementor-frontend-js"></script>
<script id="ekit-core-js-extra">
var ekit_config = {"ajaxurl":"<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>","nonce":"<?php echo esc_js( wp_create_nonce( 'furniq' ) ); ?>"};
//# sourceURL=ekit-core-js-extra
</script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/elementskit-lite/widgets/init/assets/js/widgets/core.js?ver=3.10.02" id="ekit-core-js"></script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/elementskit-lite/widgets/init/assets/js/widgets/nav-menu.js?ver=3.10.02" id="ekit-nav-menu-js"></script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/elementor/assets/lib/jquery-numerator/jquery-numerator.min.js?ver=0.2.1" id="jquery-numerator-js"></script>
<script id="happy-elementor-addons-js-extra">
var HappyLocalize = {"ajax_url":"<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>","nonce":"<?php echo esc_js( wp_create_nonce( 'furniq' ) ); ?>","pdf_js_lib":"<?php echo FURNIQ_A; ?>/wp-content/plugins/happy-elementor-addons/assets/vendor/pdfjs/lib"};
//# sourceURL=happy-elementor-addons-js-extra
</script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/happy-elementor-addons/assets/js/happy-addons.min.js?ver=3.23.0" id="happy-elementor-addons-js"></script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/happy-elementor-addons/assets/js/extension-reading-progress-bar.min.js?ver=3.23.0" id="happy-reading-progress-bar-js"></script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/happy-elementor-addons/assets/vendor/justifiedGallery/js/jquery.justifiedGallery.min.js?ver=3.23.0" id="jquery-justifiedGallery-js"></script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/happy-elementor-addons/assets/vendor/magnific-popup/jquery.magnific-popup.min.js?ver=3.23.0" id="jquery-magnific-popup-js"></script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/pro-elements/assets/js/webpack-pro.runtime.min.js?ver=4.1.0" id="elementor-pro-webpack-runtime-js"></script>
<script id="elementor-pro-frontend-js-before">
var ElementorProFrontendConfig = {"ajaxurl":"<?php echo furniq_json_url( admin_url( 'admin-ajax.php' ) ); ?>","nonce":"<?php echo esc_js( wp_create_nonce( 'furniq' ) ); ?>","urls":{"assets":"<?php echo FURNIQ_AJ; ?>\/wp-content\/plugins\/pro-elements\/assets\/","rest":"<?php echo furniq_json_url( rest_url() ); ?>"},"settings":{"lazy_load_background_images":true},"popup":{"hasPopUps":true},"shareButtonsNetworks":{"facebook":{"title":"Facebook","has_counter":true},"twitter":{"title":"Twitter"},"linkedin":{"title":"LinkedIn","has_counter":true},"pinterest":{"title":"Pinterest","has_counter":true},"reddit":{"title":"Reddit","has_counter":true},"vk":{"title":"VK","has_counter":true},"odnoklassniki":{"title":"OK","has_counter":true},"tumblr":{"title":"Tumblr"},"digg":{"title":"Digg"},"skype":{"title":"Skype"},"stumbleupon":{"title":"StumbleUpon","has_counter":true},"mix":{"title":"Mix"},"telegram":{"title":"Telegram"},"pocket":{"title":"Pocket","has_counter":true},"xing":{"title":"XING","has_counter":true},"whatsapp":{"title":"WhatsApp"},"email":{"title":"Email"},"print":{"title":"Print"},"x-twitter":{"title":"X"},"threads":{"title":"Threads"}},"woocommerce":{"menu_cart":{"cart_page_url":"<?php echo furniq_json_url( furniq_wc_url( 'cart' ) ); ?>","checkout_page_url":"<?php echo furniq_json_url( furniq_wc_url( 'checkout' ) ); ?>","fragments_nonce":"<?php echo esc_js( wp_create_nonce( 'furniq' ) ); ?>"}},"facebook_sdk":{"lang":"en_US","app_id":""},"lottie":{"defaultAnimationUrl":"<?php echo FURNIQ_AJ; ?>\/wp-content\/plugins\/pro-elements\/modules\/lottie\/assets\/animations\/default.json"}};
//# sourceURL=elementor-pro-frontend-js-before
</script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/pro-elements/assets/js/frontend.min.js?ver=4.1.0" id="elementor-pro-frontend-js"></script>
<script src="<?php echo FURNIQ_A; ?>/wp-content/plugins/pro-elements/assets/js/elements-handlers.min.js?ver=4.1.0" id="pro-elements-handlers-js"></script>