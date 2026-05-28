<?php

class EIC_Settings {

	public $bvs;

	private $toggle_settings = null;

	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'init_settings' ) );
		add_action( 'admin_init', array( $this, 'migrate_legacy_settings' ), 1 );

		add_filter( 'eic_sanitized', array( $this, 'sanitize_setting' ), 10, 4 );
	}

	public function init_settings() {
		if ( ! is_null( $this->bvs ) ) {
			return;
		}

		require_once( EasyImageCollage::get()->coreDir . '/helpers/settings_structure.php' );
		require_once( EasyImageCollage::get()->coreDir . '/vendor/bv-settings/bv-settings.php' );

		$this->bvs = new BV_Settings( array(
			'uid' => 'eic',
			'page_slug' => 'eic_settings',
			'menu_parent' => 'eic_collages',
			'menu_title' => __( 'Settings', 'easy-image-collage' ),
			'page_title' => 'Easy Image Collage',
			'settings' => $settings_structure,
			'required_addons' => array(
				'premium' => array(
					'active' => EasyImageCollage::is_premium_active(),
					'label' => __( 'Easy Image Collage Premium Required', 'easy-image-collage' ),
					'url' => 'https://bootstrapped.ventures/easy-image-collage/',
				),
			),
		) );
	}

	private function get_bvs() {
		if ( is_null( $this->bvs ) ) {
			$this->init_settings();
		}

		return $this->bvs;
	}

	public function get( $setting, $default = null ) {
		$bvs = $this->get_bvs();

		$settings = $bvs->get_settings();
		$defaults = $bvs->get_defaults();

		if ( is_array( $settings ) && array_key_exists( $setting, $settings ) ) {
			$value = $settings[ $setting ];
		} else {
			$legacy_settings = $this->get_legacy_settings();

			if ( array_key_exists( $setting, $legacy_settings ) ) {
				$value = $legacy_settings[ $setting ];
			} else {
				$value = $bvs->get( $setting );
			}
		}

		if ( false === $value && ! array_key_exists( $setting, $defaults ) && ! is_null( $default ) ) {
			$value = $default;
		}

		if ( is_null( $value ) ) {
			$value = $default;
		}

		if ( $this->is_toggle_setting( $setting ) ) {
			return $this->to_legacy_toggle_value( $value );
		}

		return $value;
	}

	public function get_default( $setting ) {
		$value = $this->get_bvs()->get_default( $setting );

		if ( $this->is_toggle_setting( $setting ) ) {
			return $this->to_legacy_toggle_value( $value );
		}

		return $value;
	}

	public function update_settings( $settings_to_update ) {
		return $this->get_bvs()->update_settings( $settings_to_update );
	}

	public function migrate_legacy_settings() {
		if ( get_option( 'eic_settings_migrated_from_vafpress', false ) ) {
			return;
		}

		$legacy_settings = $this->get_legacy_settings();
		$current_settings = get_option( 'eic_settings', array() );

		if ( ! empty( $legacy_settings ) && empty( $current_settings ) ) {
			$this->update_settings( $legacy_settings );
		}

		update_option( 'eic_settings_migrated_from_vafpress', EIC_VERSION, false );
	}

	public function sanitize_setting( $sanitized_value, $value, $id, $details ) {
		if ( isset( $details['type'] ) ) {
				if ( 'toggle' === $details['type'] ) {
					return $this->to_bool( $value );
				}

				if ( 'number' === $details['type'] ) {
					return $this->sanitize_number_setting( $value, $details );
				}

				if ( 'dropdown' === $details['type'] && is_array( $value ) ) {
					$value = reset( $value );

				if ( isset( $details['options'] ) && array_key_exists( $value, $details['options'] ) ) {
					return sanitize_text_field( $value );
				}
			}
		}

		return $sanitized_value;
	}

	private function get_legacy_settings() {
		$legacy_settings = get_option( 'eic_option', array() );

		return is_array( $legacy_settings ) ? $legacy_settings : array();
	}

	private function is_toggle_setting( $setting ) {
		if ( is_null( $this->toggle_settings ) ) {
			$this->toggle_settings = array();
			$structure = $this->get_bvs()->get_structure();

			foreach ( $structure as $group ) {
				$this->add_toggle_settings_from_settings( isset( $group['settings'] ) ? $group['settings'] : array() );

				if ( isset( $group['subGroups'] ) && is_array( $group['subGroups'] ) ) {
					foreach ( $group['subGroups'] as $sub_group ) {
						$this->add_toggle_settings_from_settings( isset( $sub_group['settings'] ) ? $sub_group['settings'] : array() );
					}
				}
			}
		}

		return in_array( $setting, $this->toggle_settings, true );
	}

	private function add_toggle_settings_from_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return;
		}

		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) && isset( $setting['type'] ) && 'toggle' === $setting['type'] ) {
				$this->toggle_settings[] = $setting['id'];
			}
		}
	}

	private function sanitize_number_setting( $value, $details ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return isset( $details['default'] ) ? $details['default'] : '';
		}

		$value = sanitize_text_field( $value );

		if ( '' === $value || ! is_numeric( $value ) ) {
			return isset( $details['default'] ) ? $details['default'] : '';
		}

		$number = (float) $value;

		if ( isset( $details['min'] ) && is_numeric( $details['min'] ) && $number < (float) $details['min'] ) {
			return (string) $details['min'];
		}

		if ( isset( $details['max'] ) && is_numeric( $details['max'] ) && $number > (float) $details['max'] ) {
			return (string) $details['max'];
		}

		return $value;
	}

	private function to_legacy_toggle_value( $value ) {
		return $this->to_bool( $value ) ? '1' : '0';
	}

	private function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );

			if ( in_array( $value, array( '', '0', 'false', 'no', 'off' ), true ) ) {
				return false;
			}

			if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
				return true;
			}
		}

		return (bool) $value;
	}
}
