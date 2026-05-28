<?php

class EIC_Assets {

    private $url;

    public function __construct()
    {
        $this->url = EasyImageCollage::get()->coreUrl;

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
        add_action( 'wp_head', array( $this, 'pinterest_css' ) );
        add_action( 'wp_head', array( $this, 'captions_css' ) );
        add_action( 'wp_head', array( $this, 'custom_css' ), 20 );
        add_action( 'admin_head', array( $this, 'admin_captions_css' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'block_assets' ) );
        add_action( 'enqueue_block_assets', array( $this, 'block_content_assets' ) );

        add_filter( 'mce_external_plugins', array( $this, 'tinymce_plugin' ) );
    }

    public function block_assets() {
		wp_enqueue_script( 'eic-blocks', EasyImageCollage::get()->coreUrl . '/dist/blocks.js', array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-server-side-render', 'wp-api-fetch' ), EIC_VERSION );
        wp_localize_script( 'eic-blocks', 'eic_blocks', array(
            'endpoints' => array(
                'collage_search' => '/easy-image-collage/v1/manage/collages/search',
                'previews'       => '/easy-image-collage/v1/manage/collages/previews',
            ),
        ) );
	}

    public function block_content_assets() {
        if ( is_admin() ) {
            wp_enqueue_style( 'eic-blocks', EasyImageCollage::get()->coreUrl . '/dist/blocks.css', array(), EIC_VERSION, 'all' );
        }
    }

    public function enqueue_public()
    {
        wp_enqueue_style( 'eic_public', $this->url . '/css/public.css', array(), EIC_VERSION, 'screen' );
        wp_enqueue_script( 'eic_public', $this->url . '/js/public.js', array( 'jquery' ), EIC_VERSION, true );

        if( EasyImageCollage::option( 'pinterest_enable', '0' ) == '1' ) {
            wp_enqueue_script( 'eic_pinterest', '//assets.pinterest.com/js/pinit.js', array(), EIC_VERSION, true );
        }

        // Pass on data
        $data = array(
            'responsive_breakpoint' => EasyImageCollage::option( 'responsive_breakpoint', '300' ),
            'responsive_layout' => EasyImageCollage::option( 'responsive_layout', '' ),
        );
        wp_localize_script( 'eic_public', 'eic_public', $data );
    }

    public function enqueue_admin()
    {
        $screen = get_current_screen();
        $is_collages_screen = class_exists( 'EIC_Admin_Menu' ) && EIC_Admin_Menu::is_collages_screen( $screen );

        if( $screen->base == 'post' || $is_collages_screen ) {
            if ( $is_collages_screen ) {
                wp_enqueue_media();
            }
            wp_enqueue_editor();

            // Vendor assets
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_style( 'font-awesome', $this->url . '/vendor/font-awesome/css/font-awesome.min.css', array(), EIC_VERSION, 'screen' );
            wp_enqueue_style( 'simple-slider', $this->url . '/vendor/loopj-jquery-simple-slider/css/simple-slider.css', array(), EIC_VERSION, 'screen' );
            wp_enqueue_script( 'simple-slider', $this->url . '/vendor/loopj-jquery-simple-slider/js/simple-slider.min.js', array( 'jquery' ), EIC_VERSION, true );
            wp_enqueue_script( 'featherlight', $this->url . '/vendor/featherlight/featherlight.min.js', array( 'jquery' ), EIC_VERSION, true );

            // Plugin assets
            wp_enqueue_style( 'eic_admin', $this->url . '/css/admin.css', array(), EIC_VERSION, 'screen' );
            wp_enqueue_script( 'eic_admin', $this->url . '/js/admin.js', array( 'jquery', 'simple-slider', 'featherlight', 'wp-color-picker' ), EIC_VERSION, true );

            if ( $is_collages_screen ) {
                $empty_trash_days = $this->get_empty_trash_days();

                wp_enqueue_style( 'eic_admin_collages', $this->url . '/dist/admin-collages.css', array(), EIC_VERSION, 'screen' );
                wp_enqueue_script( 'eic_admin_collages', $this->url . '/dist/admin-collages.js', array( 'eic_admin' ), EIC_VERSION, true );
                wp_localize_script( 'eic_admin_collages', 'eic_collages_admin', array(
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                    'endpoints' => array(
							'collages' => get_rest_url( null, 'easy-image-collage/v1/manage/collages' ),
							'previews' => get_rest_url( null, 'easy-image-collage/v1/manage/collages/previews' ),
							'duplicate' => get_rest_url( null, 'easy-image-collage/v1/manage/collages/duplicate' ),
							'delete' => get_rest_url( null, 'easy-image-collage/v1/manage/collages/%id%' ),
							'restore' => get_rest_url( null, 'easy-image-collage/v1/manage/collages/%id%/restore' ),
							'permanent_delete' => get_rest_url( null, 'easy-image-collage/v1/manage/collages/%id%/permanent' ),
						),
                    'can_create' => current_user_can( 'publish_posts' ),
                    'trash_count' => $this->get_collage_trash_count(),
                    'trash_auto_delete_days' => $empty_trash_days,
                    'text' => array(
                        'add_new' => __( 'Add New', 'easy-image-collage' ),
                        'copy_shortcode' => __( 'Copy shortcode', 'easy-image-collage' ),
                        'copy_to_clipboard' => __( 'Copy to clipboard', 'easy-image-collage' ),
                        'copied' => __( 'Copied', 'easy-image-collage' ),
                        'copied_exclamation' => __( 'Copied!', 'easy-image-collage' ),
							'delete_confirm' => __( 'Move this collage to trash?', 'easy-image-collage' ),
							'delete_confirm_used' => __( 'This collage is used in %d place(s). Move it to trash?', 'easy-image-collage' ),
							'permanent_delete_confirm' => __( 'Permanently delete this collage?', 'easy-image-collage' ),
							'permanent_delete_confirm_used' => __( 'This collage is used in %d place(s). Permanently delete it?', 'easy-image-collage' ),
							'delete' => __( 'Delete', 'easy-image-collage' ),
							'delete_permanently' => __( 'Delete Permanently', 'easy-image-collage' ),
							'duplicate' => __( 'Duplicate', 'easy-image-collage' ),
							'edit' => __( 'Edit', 'easy-image-collage' ),
							'restore' => __( 'Restore', 'easy-image-collage' ),
							'image_collages' => __( 'Image Collages', 'easy-image-collage' ),
							'overview' => __( 'Overview', 'easy-image-collage' ),
							'trash' => __( 'Trash', 'easy-image-collage' ),
							'trash_auto_delete_notice' => sprintf(
								_n(
									'Collages in the trash will automatically be removed after %d day.',
									'Collages in the trash will automatically be removed after %d days.',
									$empty_trash_days,
									'easy-image-collage'
								),
								$empty_trash_days
							),
                        'change_columns' => __( 'Change Columns', 'easy-image-collage' ),
                        'columns' => __( 'Columns', 'easy-image-collage' ),
                        'sort' => __( 'Sort:', 'easy-image-collage' ),
                        'filter' => __( 'Filter:', 'easy-image-collage' ),
                        'preview' => __( 'Preview', 'easy-image-collage' ),
                        'collage' => __( 'Collage', 'easy-image-collage' ),
                        'name' => __( 'Name', 'easy-image-collage' ),
                        'shortcode' => __( 'Shortcode', 'easy-image-collage' ),
                        'used_in' => __( 'Used In', 'easy-image-collage' ),
                        'created' => __( 'Created', 'easy-image-collage' ),
                        'last_modified' => __( 'Last Modified', 'easy-image-collage' ),
                        'images' => __( 'Images', 'easy-image-collage' ),
                        'not_used' => __( 'Not used', 'easy-image-collage' ),
                        'no_images' => __( 'No images', 'easy-image-collage' ),
                        'more' => __( 'more', 'easy-image-collage' ),
                        'collages' => __( 'collages', 'easy-image-collage' ),
                        'of' => __( 'of', 'easy-image-collage' ),
                        'showing' => __( 'Showing', 'easy-image-collage' ),
                        'filtered_of' => __( 'filtered of', 'easy-image-collage' ),
                        'total' => __( 'total', 'easy-image-collage' ),
                        'rows' => __( 'rows', 'easy-image-collage' ),
                        'loading' => __( 'Loading...', 'easy-image-collage' ),
                    ),
                ) );
            }

            // Pass on data
            $data = array(
                'ajaxurl' => EasyImageCollage::get()->helper('ajax')->url(),
                'nonce' => wp_create_nonce( 'eic_image_collage' ),
                'shortcode_image' => $this->url . '/img/eic_shortcode.png',
                'default_link_new_tab' => EasyImageCollage::option( 'custom_link_new_tab', '0' ) == '1' ? true : false,
                'default_link_nofollow' => EasyImageCollage::option( 'custom_link_nofollow', '0' ) == '1' ? true : false,
                'text_link_new_tab' => __( 'Open in New Tab', 'easy-image-collage' ),
                'text_link_nofollow' => __( 'Use Nofollow', 'easy-image-collage' ),
                'text_collage_name_default' => __( 'Collage #id', 'easy-image-collage' ),
                'text_choose_image' => __( 'Choose image', 'easy-image-collage' ),
                'text_change_image' => __( 'Change Image', 'easy-image-collage' ),
                'text_remove_image' => __( 'Remove image', 'easy-image-collage' ),
                'text_add_text_frame' => __( 'Add text frame', 'easy-image-collage' ),
                'text_change_text' => __( 'Change text', 'easy-image-collage' ),
                'text_remove_text_frame' => __( 'Remove text frame', 'easy-image-collage' ),
                'captions_autofill' => EasyImageCollage::option( 'captions_autofill', 'disabled' ),
                'captions_enabled' => EasyImageCollage::is_addon_active( 'captions' ),
                'captions_hover_only' => EasyImageCollage::option( 'captions_hover_only', '1' ) == '1',
            );
            wp_localize_script( 'eic_admin', 'eic_admin', $data );
        }
    }

    private function get_empty_trash_days() {
        return defined( 'EMPTY_TRASH_DAYS' ) ? max( 0, intval( EMPTY_TRASH_DAYS ) ) : 30;
    }

    private function get_collage_trash_count() {
        $counts = wp_count_posts( EIC_POST_TYPE );

        return isset( $counts->trash ) ? absint( $counts->trash ) : 0;
    }

    public function pinterest_css()
    {
        if( EasyImageCollage::option( 'pinterest_enable', '0' ) == '1' ) {
            echo '<style type="text/css">';
            echo '.eic-image [data-pin-log="button_pinit"] {';
            echo 'display: none;';
            echo 'position: absolute;';

            switch( EasyImageCollage::option( 'pinterest_location', 'top_left' ) ) {
                case 'top_left':
                    echo 'top: 5px;';
                    echo 'left: 5px;';
                    break;
                case 'top_right':
                    echo 'top: 5px;';
                    echo 'right: 5px;';
                    break;
                case 'bottom_left':
                    echo 'bottom: 5px;';
                    echo 'left: 5px;';
                    break;
                case 'bottom_right':
                    echo 'bottom: 5px;';
                    echo 'right: 5px;';
                    break;
            }

            echo '}';
            echo '</style>';
        }
    }

    public function captions_css()
    {
        echo '<style type="text/css">';
        echo $this->get_captions_css( true );
        echo '</style>';
    }

    public function admin_captions_css()
    {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
        $is_collages_screen = $screen && class_exists( 'EIC_Admin_Menu' ) && EIC_Admin_Menu::is_collages_screen( $screen );

        if( ! $screen || ( $screen->base != 'post' && ! $is_collages_screen ) ) {
            return;
        }

        echo '<style type="text/css">';
        echo $this->get_captions_css( false );
        echo '</style>';
    }

    private function get_captions_css( $include_responsive_rules = true )
    {
        $css = '.eic-image .eic-image-caption {';
        switch( EasyImageCollage::option( 'captions_location', 'bottom' ) ) {
            case 'bottom':
                $css .= 'bottom: 0;';
                $css .= 'left: 0;';
                $css .= 'right: 0;';
                break;
            case 'top':
                $css .= 'top: 0;';
                $css .= 'left: 0;';
                $css .= 'right: 0;';
                break;
        }

        $css .= 'text-align: ' . EasyImageCollage::option( 'captions_text_alignment', 'left' ) . ';';
        $css .= 'font-size: ' . intval( EasyImageCollage::option( 'captions_font_size', 12 ) )  . 'px;';
        $css .= 'color: ' . EasyImageCollage::option( 'captions_text_color', 'rgba(255,255,255,1)' )  . ';';
        $css .= 'background-color: ' . EasyImageCollage::option( 'captions_background_color', 'rgba(0,0,0,0.7)' )  . ';';

        $css .= '}';

        // Hide on mobile.
        if ( $include_responsive_rules && EasyImageCollage::option( 'responsive_hide_captions', '' ) == '1' ) {
            $css .= ' .eic-container-mobile .eic-image .eic-image-caption { display: none; }';
        }

        return $css;
    }

    public function custom_css()
    {
        if( EasyImageCollage::option( 'custom_code_public_css', '' ) !== '' ) {
            echo '<style type="text/css">';
            echo EasyImageCollage::option( 'custom_code_public_css', '' );
            echo '</style>';
        }
    }

    public function tinymce_plugin( $plugin_array )
    {
        $plugin_array['easyimagecollage'] = $this->url . '/js/tinymce_shortcode_preview.js';
        return $plugin_array;
    }
}
