<?php
/**
 * Handle the settings saving.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.1.0
 *
 * @package    BV_Settings
 */

if ( ! class_exists( 'BV_Settings_V1_1_0_Saver' ) ) {
	/**
	 * Handle the settings saving.
	 *
	 * @since 1.1.0
	 */
	class BV_Settings_V1_1_0_Saver {
		private $bvs;

		/**
		 * Store main instance and initialize.
		 *
		 * @since   1.1.0
		 * @param   object $bvs Versioned settings implementation.
		 */
		public function __construct( $bvs ) {
			$this->bvs = $bvs;
		}

		/**
		 * Update the settings.
		 *
		 * @since   1.1.0
		 * @param   array $settings_to_update Settings to update.
		 * @return  array
		 */
		public function update_settings( $settings_to_update ) {
			$old_settings = $this->bvs->get_settings();

			if ( is_array( $settings_to_update ) ) {
				$settings_to_update = $this->sanitize_settings( $settings_to_update );
				$new_settings       = array_merge( $old_settings, $settings_to_update );

				$new_settings = apply_filters( $this->bvs->atts['uid'] . '_settings_update', $new_settings, $old_settings );

				update_option( $this->bvs->atts['uid'] . '_settings', $new_settings );
				$this->bvs->helpers['structure']->set_settings( $new_settings );
			}

			return $this->bvs->get_settings();
		}

		/**
		 * Flatten setting options to a list of valid option values.
		 *
		 * @since   1.1.0
		 * @param   array $options Options to flatten.
		 * @return  array
		 */
		private function get_option_values( $options ) {
			$values = array();

			if ( ! is_array( $options ) ) {
				return $values;
			}

			foreach ( $options as $key => $option ) {
				if ( is_array( $option ) && isset( $option['options'] ) ) {
					$values = array_merge( $values, $this->get_option_values( $option['options'] ) );
				} elseif ( is_array( $option ) && isset( $option['value'] ) ) {
					$values[] = $option['value'];
				} elseif ( is_array( $option ) ) {
					$values = array_merge( $values, $this->get_option_values( $option ) );
				} else {
					$values[] = $key;
				}
			}

			return $values;
		}

		/**
		 * Sanitize an async dropdown value.
		 *
		 * @since   1.1.0
		 * @param   mixed $value   Value to sanitize.
		 * @param   array $details Setting details.
		 * @return  mixed
		 */
		private function sanitize_dropdown_async( $value, $details ) {
			$value_key     = isset( $details['valueKey'] ) ? $details['valueKey'] : 'value';
			$label_key     = isset( $details['labelKey'] ) ? $details['labelKey'] : 'label';
			$value_storage = isset( $details['valueStorage'] ) ? $details['valueStorage'] : '';

			if ( 'object' === $value_storage || is_array( $value ) ) {
				if ( is_array( $value ) ) {
					$value_id   = isset( $value[ $value_key ] ) ? $value[ $value_key ] : '';
					$value_text = isset( $value[ $label_key ] ) ? $value[ $label_key ] : $value_id;
				} else {
					$value_id   = $value;
					$value_text = $value;
				}

				$value_id   = is_scalar( $value_id ) ? $value_id : '';
				$value_text = is_scalar( $value_text ) ? $value_text : '';

				return array(
					$value_key => sanitize_text_field( $value_id ),
					$label_key => sanitize_text_field( $value_text ),
				);
			}

			return sanitize_text_field( $value );
		}

		/**
		 * Sanitize an object table value.
		 *
		 * @since   1.1.0
		 * @param   mixed $value   Value to sanitize.
		 * @param   array $details Setting details.
		 * @return  array
		 */
		private function sanitize_object_table( $value, $details ) {
			$sanitized_value = array();

			if ( ! is_array( $value ) ) {
				return $sanitized_value;
			}

			$columns       = isset( $details['columns'] ) && is_array( $details['columns'] ) ? $details['columns'] : array();
			$default_rows  = isset( $details['default'] ) && is_array( $details['default'] ) ? $details['default'] : array();
			$row_keys      = ! empty( $default_rows ) ? array_keys( $default_rows ) : array_keys( $value );
			$row_label_key = isset( $details['rowLabelKey'] ) ? $details['rowLabelKey'] : 'label';

			foreach ( $row_keys as $row_key ) {
				$row = isset( $value[ $row_key ] ) && is_array( $value[ $row_key ] ) ? $value[ $row_key ] : array();
				$sanitized_row = array();

				if ( isset( $default_rows[ $row_key ][ $row_label_key ] ) ) {
					$sanitized_row[ $row_label_key ] = sanitize_text_field( $default_rows[ $row_key ][ $row_label_key ] );
				} elseif ( isset( $row[ $row_label_key ] ) ) {
					$sanitized_row[ $row_label_key ] = sanitize_text_field( $row[ $row_label_key ] );
				}

				foreach ( $columns as $column ) {
					if ( ! isset( $column['key'] ) ) {
						continue;
					}

					$column_key = $column['key'];
					$column_value = isset( $row[ $column_key ] ) ? $row[ $column_key ] : '';

					if ( isset( $column['format'] ) && 'semicolonList' === $column['format'] ) {
						if ( is_string( $column_value ) ) {
							$column_value = explode( ';', $column_value );
						}

						$sanitized_items = array();
						if ( is_array( $column_value ) ) {
							foreach ( $column_value as $item ) {
								$item = trim( sanitize_text_field( $item ) );

								if ( strlen( $item ) ) {
									$sanitized_items[] = $item;
								}
							}
						}

						$sanitized_row[ $column_key ] = $sanitized_items;
					} else {
						$sanitized_row[ $column_key ] = sanitize_text_field( $column_value );
					}
				}

				$sanitized_row_key = ! empty( $default_rows ) ? $row_key : sanitize_key( $row_key );
				$sanitized_value[ $sanitized_row_key ] = $sanitized_row;
			}

			return $sanitized_value;
		}

		/**
		 * Sanitize the settings.
		 *
		 * @since   1.1.0
		 * @param   array $settings Settings to sanitize.
		 * @return  array
		 */
		public function sanitize_settings( $settings ) {
			$sanitized_settings = array();
			$settings_details   = $this->bvs->helpers['structure']->get_details();

			foreach ( $settings as $id => $value ) {
				if ( array_key_exists( $id, $settings_details ) ) {
					$details = $settings_details[ $id ];

					$sanitized_value = null;

					// Check for custom sanitization function.
					if ( isset( $details['sanitize'] ) && is_callable( $details['sanitize'] ) ) {
						$sanitized_value = call_user_func( $details['sanitize'], $value );
					}

					// Options callback.
					if ( isset( $details['optionsCallback'] ) ) {
						$details['options'] = call_user_func( $details['optionsCallback'], $details );
					}

					// Value format sanitization.
					if ( is_null( $sanitized_value ) && isset( $details['valueFormat'] ) && 'newlineList' === $details['valueFormat'] ) {
						if ( is_string( $value ) ) {
							$value = preg_split( '/\r\n|\r|\n/', $value );
						}

						$sanitized_value = array();

						if ( is_array( $value ) ) {
							foreach ( $value as $item ) {
								$sanitized_value[] = sanitize_text_field( $item );
							}
						}
					}

					// Default sanitization based on type.
					if ( is_null( $sanitized_value ) && isset( $details['type'] ) ) {
						switch ( $details['type'] ) {
							case 'code':
								$sanitized_value = wp_kses_post( $value );

								// Fix for CSS code.
								$sanitized_value = str_replace( '&gt;', '>', $sanitized_value );
								break;
							case 'color':
								$sanitized_value = sanitize_text_field( $value );
								break;
							case 'dropdown':
								if ( isset( $details['options'] ) && array_key_exists( $value, $details['options'] ) ) {
									$sanitized_value = $value;
								}
								break;
							case 'dropdownGrouped':
								$option_values = isset( $details['options'] ) ? $this->get_option_values( $details['options'] ) : array();
								$option_values = array_map( 'strval', $option_values );

								if ( empty( $option_values ) || in_array( (string) $value, $option_values, true ) ) {
									$sanitized_value = sanitize_text_field( $value );
								}
								break;
							case 'dropdownAsync':
								$sanitized_value = $this->sanitize_dropdown_async( $value, $details );
								break;
							case 'dropdownMultiselect':
								$sanitized_value = array();

								if ( isset( $details['options'] ) && is_array( $value ) ) {
									foreach ( $value as $option ) {
										if ( array_key_exists( $option, $details['options'] ) ) {
											$sanitized_value[] = $option;
										}
									}
								}
								break;
							case 'email':
								$sanitized_value = sanitize_email( $value );
								break;
							case 'number':
								$sanitized_value = sanitize_text_field( $value );
								break;
							case 'objectTable':
								$sanitized_value = $this->sanitize_object_table( $value, $details );
								break;
							case 'richTextarea':
								$sanitized_value = wp_kses_post( $value );
								break;
							case 'text':
								$sanitized_value = sanitize_text_field( $value );
								break;
							case 'textarea':
								$sanitized_value = wp_kses_post( $value );
								break;
							case 'toggle':
								$sanitized_value = $value ? true : false;
								break;
						}
					}

					$sanitized_value = apply_filters( $this->bvs->atts['uid'] . '_sanitized', $sanitized_value, $value, $id, $details );

					if ( ! is_null( $sanitized_value ) ) {
						$sanitized_settings[ $id ] = $sanitized_value;
					}
				}
			}

			return $sanitized_settings;
		}
	}
}
