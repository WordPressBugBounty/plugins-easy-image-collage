<?php
/**
 * Handle the settings menu.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.1.0
 *
 * @package    BV_Settings
 */

if ( ! class_exists( 'BV_Settings_V1_1_0_Menu' ) ) {
	/**
	 * Handle the settings menu.
	 *
	 * @since 1.1.0
	 */
	class BV_Settings_V1_1_0_Menu {
		private $bvs;

		/**
		 * Store main instance and initialize.
		 *
		 * @since   1.1.0
		 * @param   object $bvs Versioned settings implementation.
		 */
		public function __construct( $bvs ) {
			$this->bvs = $bvs;
			$this->init();
		}

		/**
		 * Register actions and filters.
		 *
		 * @since 1.1.0
		 */
		private function init() {
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ), $this->bvs->atts['menu_priority'] );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
		}

		/**
		 * Add the settings menu page.
		 *
		 * @since 1.1.0
		 */
		public function add_submenu_page() {
			add_submenu_page(
				$this->bvs->atts['menu_parent'],
				$this->bvs->atts['page_title'],
				$this->bvs->atts['menu_title'],
				$this->bvs->atts['required_capability'],
				$this->bvs->atts['page_slug'],
				array( $this, 'settings_page_template' )
			);
		}

		/**
		 * Get the template for the settings page.
		 *
		 * @since 1.1.0
		 */
		public function settings_page_template() {
			wp_localize_script(
				'bv-settings',
				'bv_settings',
				array(
					'structure'       => array_values( $this->bvs->get_structure( true ) ),
					'settings'        => $this->bvs->get_settings_with_defaults(),
					'defaults'        => $this->bvs->get_defaults(),
					'required_addons' => apply_filters( $this->bvs->atts['uid'] . '_settings_required_addons', $this->bvs->atts['required_addons'] ),
					'eol'             => PHP_EOL,
					'api'             => array(
						'endpoint' => get_rest_url( null, 'bv-settings/v1/' . $this->bvs->atts['uid'] ),
						'nonce'    => wp_create_nonce( 'wp_rest' ),
					),
				)
			);

			echo '<div id="bvs-settings" class="wrap">Loading...</div>';
		}

		/**
		 * Enqueue admin assets for the settings page.
		 *
		 * @since 1.1.0
		 */
		public function enqueue_admin() {
			$screen = get_current_screen();

			// Only enqueue on settings page.
			if ( $screen && $this->bvs->atts['page_slug'] === substr( $screen->id, -1 * strlen( $this->bvs->atts['page_slug'] ) ) ) {
				wp_enqueue_style( 'bv-settings', $this->bvs->component['url'] . 'dist/admin.css', array(), $this->bvs->component['version'], 'all' );
				wp_enqueue_script( 'bv-settings', $this->bvs->component['url'] . 'dist/admin.js', array(), $this->bvs->component['version'], true );
			}
		}
	}
}
