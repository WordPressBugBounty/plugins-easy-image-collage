<?php
/**
 * Stable public wrapper for the active BV Settings implementation.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.1.0
 *
 * @package    BV_Settings
 */

if ( ! class_exists( 'BV_Settings' ) ) {
	/**
	 * Stable public wrapper for the active BV Settings implementation.
	 *
	 * @since 1.1.0
	 */
	class BV_Settings {
		public $atts;
		public $helpers;
		public $component;

		/**
		 * Active implementation instance.
		 *
		 * @since 1.1.0
		 * @var object
		 */
		private $implementation;

		/**
		 * Resolve and instantiate the active component implementation.
		 *
		 * @since   1.1.0
		 * @param   array $atts Component attributes.
		 */
		public function __construct( $atts = array() ) {
			$component = bv_settings_get_active_component();

			if ( ! $component ) {
				throw new Exception( 'No BV Settings component is registered.' );
			}

			if ( ! class_exists( $component['class'] ) ) {
				require_once $component['loader'];
			}

			if ( ! class_exists( $component['class'] ) ) {
				throw new Exception( 'The active BV Settings implementation could not be loaded.' );
			}

			$class                = $component['class'];
			$this->implementation = new $class( $atts, $component );
			$this->atts           = &$this->implementation->atts;
			$this->helpers        = &$this->implementation->helpers;
			$this->component      = &$this->implementation->component;
		}

		/**
		 * Forward unknown method calls to the active implementation.
		 *
		 * @since   1.1.0
		 * @param   string $name      Method name.
		 * @param   array  $arguments Method arguments.
		 * @return  mixed
		 */
		public function __call( $name, $arguments ) {
			return call_user_func_array( array( $this->implementation, $name ), $arguments );
		}

		/**
		 * Get the settings structure.
		 *
		 * @since   1.0.0
		 * @param   mixed $resolve_callbacks Whether to resolve the callbacks.
		 * @return  array
		 */
		public function get_structure( $resolve_callbacks = false ) {
			return $this->implementation->get_structure( $resolve_callbacks );
		}

		/**
		 * Get the value for a specific setting.
		 *
		 * @since   1.0.0
		 * @param   mixed $setting Setting to get the value for.
		 * @return  mixed
		 */
		public function get( $setting ) {
			return $this->implementation->get( $setting );
		}

		/**
		 * Get all settings.
		 *
		 * @since   1.0.0
		 * @return  array
		 */
		public function get_settings() {
			return $this->implementation->get_settings();
		}

		/**
		 * Get all settings with defaults if not set.
		 *
		 * @since   1.0.0
		 * @return  array
		 */
		public function get_settings_with_defaults() {
			return $this->implementation->get_settings_with_defaults();
		}

		/**
		 * Get the default for a specific setting.
		 *
		 * @since   1.0.0
		 * @param   mixed $setting Setting to get the default for.
		 * @return  mixed
		 */
		public function get_default( $setting ) {
			return $this->implementation->get_default( $setting );
		}

		/**
		 * Get all defaults.
		 *
		 * @since   1.0.0
		 * @param   boolean $force_update Whether to force a cache refresh.
		 * @return  array
		 */
		public function get_defaults( $force_update = false ) {
			return $this->implementation->get_defaults( $force_update );
		}

		/**
		 * Update settings.
		 *
		 * @since   1.0.0
		 * @param   array $settings_to_update Settings to update.
		 * @return  array
		 */
		public function update_settings( $settings_to_update ) {
			return $this->implementation->update_settings( $settings_to_update );
		}
	}
}
