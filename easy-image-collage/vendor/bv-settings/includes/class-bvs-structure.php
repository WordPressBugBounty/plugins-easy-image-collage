<?php
/**
 * Handle the settings structure.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.1.0
 *
 * @package    BV_Settings
 */

if ( ! class_exists( 'BV_Settings_V1_1_0_Structure' ) ) {
	/**
	 * Handle the settings structure.
	 *
	 * @since 1.1.0
	 */
	class BV_Settings_V1_1_0_Structure {
		private $bvs;
		private $structure = array();
		private $defaults = array();
		private $settings = array();

		/**
		 * Store main instance and initialize.
		 *
		 * @since   1.1.0
		 * @param   object $bvs Versioned settings implementation.
		 */
		public function __construct( $bvs ) {
			$this->bvs = $bvs;
			$this->set_structure( $this->bvs->atts['settings'] );
		}

		/**
		 * Set the settings structure.
		 *
		 * @since   1.1.0
		 * @param   array $settings_structure Settings structure.
		 */
		public function set_structure( $settings_structure ) {
			$structure = array();
			$index     = 1;

			foreach ( $settings_structure as $group ) {
				if ( isset( $group['id'] ) ) {
					$id = $group['id'];
				} else {
					$id = 'group_' . $index;
					$index++;
				}

				$structure[ $id ] = $group;
			}

			$this->structure = $structure;
		}

		/**
		 * Get the settings structure.
		 *
		 * @since   1.1.0
		 * @param   mixed $resolve_callbacks Whether to resolve callbacks.
		 * @return  array
		 */
		public function get_structure( $resolve_callbacks = false ) {
			$structure = apply_filters( $this->bvs->atts['uid'] . '_settings_structure', $this->structure );

			if ( $resolve_callbacks ) {
				foreach ( $structure as $group_id => $group ) {
					if ( isset( $group['settings'] ) ) {
						foreach ( $group['settings'] as $setting_id => $setting ) {
							if ( isset( $setting['optionsCallback'] ) ) {
								$structure[ $group_id ]['settings'][ $setting_id ]['options'] = call_user_func( $setting['optionsCallback'], $setting );
							}
						}
					}

					if ( isset( $group['subGroups'] ) ) {
						foreach ( $group['subGroups'] as $sub_group_id => $sub_group ) {
							if ( isset( $sub_group['settings'] ) ) {
								foreach ( $sub_group['settings'] as $setting_id => $setting ) {
									if ( isset( $setting['optionsCallback'] ) ) {
										$structure[ $group_id ]['subGroups'][ $sub_group_id ]['settings'][ $setting_id ]['options'] = call_user_func( $setting['optionsCallback'], $setting );
									}
								}
							}
						}
					}
				}

				$structure = apply_filters( $this->bvs->atts['uid'] . '_settings_structure_callbacks', $structure );
			}

			return $structure;
		}

		/**
		 * Get the value for a specific setting.
		 *
		 * @since   1.1.0
		 * @param   mixed $setting Setting to get the value for.
		 * @return  mixed
		 */
		public function get( $setting ) {
			$settings = $this->get_settings();

			if ( isset( $settings[ $setting ] ) ) {
				return $settings[ $setting ];
			}

			return $this->get_default( $setting );
		}

		/**
		 * Get all settings.
		 *
		 * @since   1.1.0
		 * @return  array
		 */
		public function get_settings() {
			if ( empty( $this->settings ) ) {
				$this->set_settings( apply_filters( $this->bvs->atts['uid'] . '_settings', get_option( $this->bvs->atts['uid'] . '_settings', array() ) ) );
			}

			return $this->settings;
		}

		/**
		 * Set the settings.
		 *
		 * @since   1.1.0
		 * @param   array $settings Settings to set.
		 */
		public function set_settings( $settings ) {
			$this->settings = $settings;
		}

		/**
		 * Get all settings with defaults if not set.
		 *
		 * @since   1.1.0
		 * @return  array
		 */
		public function get_settings_with_defaults() {
			$settings = $this->get_settings();
			$defaults = $this->get_defaults();

			return array_merge( $defaults, $settings );
		}

		/**
		 * Get the default for a specific setting.
		 *
		 * @since   1.1.0
		 * @param   mixed $setting Setting to get the default for.
		 * @return  mixed
		 */
		public function get_default( $setting ) {
			$defaults = $this->get_defaults();

			if ( isset( $defaults[ $setting ] ) ) {
				return $defaults[ $setting ];
			}

			$defaults = $this->get_defaults( true );

			if ( isset( $defaults[ $setting ] ) ) {
				return $defaults[ $setting ];
			}

			return false;
		}

		/**
		 * Get the default settings.
		 *
		 * @since   1.1.0
		 * @param   boolean $force_update Whether to force a cache refresh.
		 * @return  array
		 */
		public function get_defaults( $force_update = false ) {
			if ( $force_update || empty( $this->defaults ) ) {
				$defaults  = array();
				$structure = $this->get_structure();

				foreach ( $structure as $group ) {
					if ( isset( $group['settings'] ) ) {
						foreach ( $group['settings'] as $setting ) {
							if ( isset( $setting['id'] ) && isset( $setting['default'] ) ) {
								$defaults[ $setting['id'] ] = $setting['default'];
							}
						}
					}

					if ( isset( $group['subGroups'] ) ) {
						foreach ( $group['subGroups'] as $sub_group ) {
							if ( isset( $sub_group['settings'] ) ) {
								foreach ( $sub_group['settings'] as $setting ) {
									if ( isset( $setting['id'] ) && isset( $setting['default'] ) ) {
										$defaults[ $setting['id'] ] = $setting['default'];
									}
								}
							}
						}
					}
				}

				$this->defaults = $defaults;
			}

			return $this->defaults;
		}

		/**
		 * Get the settings details.
		 *
		 * @since   1.1.0
		 * @return  array
		 */
		public function get_details() {
			$details   = array();
			$structure = $this->get_structure();

			foreach ( $structure as $group ) {
				if ( isset( $group['settings'] ) ) {
					foreach ( $group['settings'] as $setting ) {
						if ( isset( $setting['id'] ) ) {
							$details[ $setting['id'] ] = $setting;
						}
					}
				}

				if ( isset( $group['subGroups'] ) ) {
					foreach ( $group['subGroups'] as $sub_group ) {
						if ( isset( $sub_group['settings'] ) ) {
							foreach ( $sub_group['settings'] as $setting ) {
								if ( isset( $setting['id'] ) ) {
									$details[ $setting['id'] ] = $setting;
								}
							}
						}
					}
				}
			}

			return $details;
		}
	}
}
