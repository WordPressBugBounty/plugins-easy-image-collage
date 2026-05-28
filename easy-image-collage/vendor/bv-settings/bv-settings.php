<?php

if ( ! defined( 'BVS_VERSION' ) ) {
	define( 'BVS_VERSION', '1.1.1' );
}

if ( ! defined( 'BVS_DIR' ) ) {
	define( 'BVS_DIR', trailingslashit( dirname( __FILE__ ) ) );
}

if ( ! defined( 'BVS_URL' ) ) {
	define( 'BVS_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! function_exists( 'bv_settings_register_component' ) ) {
	/**
	 * Register a BV Settings component implementation.
	 *
	 * @since	1.1.0
	 * @param	string $version Component version.
	 * @param	string $dir     Component base directory.
	 * @param	string $url     Component base URL.
	 * @param	string $loader  Loader file for the implementation.
	 * @param	string $class   Implementation class name.
	 */
	function bv_settings_register_component( $version, $dir, $url, $loader, $class ) {
		if ( ! isset( $GLOBALS['bv_settings_components'] ) || ! is_array( $GLOBALS['bv_settings_components'] ) ) {
			$GLOBALS['bv_settings_components'] = array();
		}

		$component = array(
			'version' => $version,
			'dir'     => trailingslashit( $dir ),
			'url'     => trailingslashit( $url ),
			'loader'  => $loader,
			'class'   => $class,
		);

		$GLOBALS['bv_settings_components'][ $version ] = $component;

		if (
			! isset( $GLOBALS['bv_settings_active_component'] )
			|| version_compare( $version, $GLOBALS['bv_settings_active_component']['version'], '>' )
		) {
			$GLOBALS['bv_settings_active_component'] = $component;
		}
	}
}

if ( ! function_exists( 'bv_settings_get_active_component' ) ) {
	/**
	 * Get the active BV Settings component implementation.
	 *
	 * @since	1.1.0
	 * @return array|null
	 */
	function bv_settings_get_active_component() {
		if ( isset( $GLOBALS['bv_settings_active_component'] ) ) {
			return $GLOBALS['bv_settings_active_component'];
		}

		return null;
	}
}

if ( ! class_exists( 'BV_Settings' ) ) {
	require_once dirname( __FILE__ ) . '/includes/class-bv-settings.php';
}

bv_settings_register_component(
	'1.1.1',
	dirname( __FILE__ ),
	plugin_dir_url( __FILE__ ),
	trailingslashit( dirname( __FILE__ ) ) . 'includes/class-bv-settings-implementation.php',
	'BV_Settings_V1_1_0'
);
