<?php
/**
 * Versioned BV Settings implementation loader.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.1.0
 *
 * @package    BV_Settings
 */

require_once dirname( __FILE__ ) . '/class-bvs-api.php';
require_once dirname( __FILE__ ) . '/class-bvs-menu.php';
require_once dirname( __FILE__ ) . '/class-bvs-saver.php';
require_once dirname( __FILE__ ) . '/class-bvs-structure.php';

if ( ! class_exists( 'BV_Settings_V1_1_0' ) ) {
	/**
	 * Versioned core component implementation.
	 *
	 * @since 1.1.0
	 */
	class BV_Settings_V1_1_0 {
		public $atts;
		public $helpers;
		public $component;

		/**
		 * Make sure all is set up for the component to load.
		 *
		 * @since   1.1.0
		 * @param   array $atts      Component attributes.
		 * @param   array $component Registered component metadata.
		 */
		public function __construct( $atts = array(), $component = array() ) {
			$this->component = $component;

			// Set defaults.
			$atts = shortcode_atts(
				array(
					'uid'                 => '',
					'menu_priority'       => 10,
					'menu_title'          => __( 'Settings', 'bv-settings' ),
					'menu_parent'         => 'options-general.php',
					'page_title'          => __( 'Settings', 'bv-settings' ),
					'page_slug'           => false,
					'required_capability' => 'manage_options',
					'settings'            => array(),
					'required_addons'     => array(),
				),
				$atts
			);

			// Make sure required fields are set.
			$atts['uid'] = sanitize_title( $atts['uid'] );
			if ( ! $atts['uid'] ) {
				throw new Exception( 'You need to initialize the settings with a UID.' );
			}

			// Calculated defaults.
			if ( ! $atts['page_slug'] ) {
				$atts['page_slug'] = 'bv_settings_' . $atts['uid'];
			}

			$this->atts = $atts;
			$this->load_helpers();
		}

		/**
		 * Load helper classes.
		 *
		 * @since 1.1.0
		 */
		private function load_helpers() {
			$this->helpers['api']       = new BV_Settings_V1_1_0_API( $this );
			$this->helpers['menu']      = new BV_Settings_V1_1_0_Menu( $this );
			$this->helpers['saver']     = new BV_Settings_V1_1_0_Saver( $this );
			$this->helpers['structure'] = new BV_Settings_V1_1_0_Structure( $this );
		}

		/**
		 * Get the settings structure.
		 *
		 * @since   1.1.0
		 * @param   mixed $resolve_callbacks Whether to resolve callbacks.
		 * @return  array
		 */
		public function get_structure( $resolve_callbacks = false ) {
			return $this->helpers['structure']->get_structure( $resolve_callbacks );
		}

		/**
		 * Get the value for a specific setting.
		 *
		 * @since   1.1.0
		 * @param   mixed $setting Setting to get the value for.
		 * @return  mixed
		 */
		public function get( $setting ) {
			return $this->helpers['structure']->get( $setting );
		}

		/**
		 * Get all settings.
		 *
		 * @since   1.1.0
		 * @return  array
		 */
		public function get_settings() {
			return $this->helpers['structure']->get_settings();
		}

		/**
		 * Get all settings with defaults if not set.
		 *
		 * @since   1.1.0
		 * @return  array
		 */
		public function get_settings_with_defaults() {
			return $this->helpers['structure']->get_settings_with_defaults();
		}

		/**
		 * Get the default for a specific setting.
		 *
		 * @since   1.1.0
		 * @param   mixed $setting Setting to get the default for.
		 * @return  mixed
		 */
		public function get_default( $setting ) {
			return $this->helpers['structure']->get_default( $setting );
		}

		/**
		 * Get all defaults.
		 *
		 * @since   1.1.0
		 * @param   boolean $force_update Whether to refresh the cache.
		 * @return  array
		 */
		public function get_defaults( $force_update = false ) {
			return $this->helpers['structure']->get_defaults( $force_update );
		}

		/**
		 * Update settings.
		 *
		 * @since   1.1.0
		 * @param   array $settings_to_update Settings to update.
		 * @return  array
		 */
		public function update_settings( $settings_to_update ) {
			return $this->helpers['saver']->update_settings( $settings_to_update );
		}
	}
}
