<?php

class EIC_Shortcode_Button {

    public function __construct()
    {
        add_action( 'media_buttons',  array( $this, 'add_shortcode_button' ) );
        add_action( 'admin_footer',  array( $this, 'add_modal_content' ) );
    }

    public function add_shortcode_button( $editor_id )
    {
        $screen = get_current_screen();

        if( $screen->base == 'post' ) {
            $title = __( 'Add Image Collage', 'easy-image-collage' );

            echo '<a href="#" id="eic-button" class="button" data-editor="content" title="' . $title . '">' . $title . '</a>';
        }
    }

    public function add_modal_content()
    {
        $screen = get_current_screen();

        if( $screen->base == 'post' ) {
            $post = get_post();
            $grid_ids = $this->get_grids_in_content( $post->post_content );

            $this->render_modal_content( $grid_ids );
        }
    }

    public function render_modal_content( $grid_ids = array() )
    {
        $grid_ids = array_unique( array_filter( array_map( 'intval', $grid_ids ) ) );
        $grids = array();
        $grid_custom_layouts = array();

        foreach( $grid_ids as $grid_id ) {
            $post = get_post( $grid_id );

            if( is_null( $post ) || $post->post_type !== EIC_POST_TYPE ) {
                continue;
            }

            $grid = new EIC_Grid( $grid_id );
            $grids[$grid_id] = $grid->get_data();
            $grids[$grid_id]['name'] = $this->get_collage_title( $post );

            if( $grid->layout() ) {
                $grid_custom_layouts[ $grid->layout_name() ] = $grid->layout();
            }
        }

        include( EasyImageCollage::get()->coreDir . '/helpers/modal.php' );

        wp_localize_script( 'eic_admin', 'eic_admin_grids', $grids );
        wp_localize_script( 'eic_admin', 'eic_default_grid', array(
            'id' => 0,
            'name' => '',
            'layout' => 'square',
            'images' => array(),
            'properties' => array(
                'align' => EasyImageCollage::option( 'default_style_grid_align', 'center' ),
                'width' => intval( EasyImageCollage::option( 'default_style_grid_width', 500 ) ),
                'ratio' => floatval( EasyImageCollage::option( 'default_style_grid_ratio', 1 ) ),
                'borderWidth' => intval( EasyImageCollage::option( 'default_style_border_width', 4 ) ),
                'borderColor' => EasyImageCollage::option( 'default_style_border_color', '#444444' ),
                'borderRadius' => intval( EasyImageCollage::option( 'default_style_border_radius', 0 ) ),
            ),
        ) );
    }

	private function get_collage_title( $post )
	{
		return $post->post_title ? html_entity_decode( $post->post_title, ENT_QUOTES, get_bloginfo( 'charset' ) ) : sprintf( __( 'Collage #%d', 'easy-image-collage' ), $post->ID );
	}

    public function get_grids_in_content( $content )
    {
        if ( ! is_string( $content ) || '' === $content ) {
            return array();
        }

        $grid_ids = array();

        if ( false !== strpos( $content, '[easy-image-collage' ) ) {
            preg_match_all( '/' . get_shortcode_regex( array( 'easy-image-collage' ) ) . '/', $content, $shortcodes, PREG_SET_ORDER );

            foreach( $shortcodes as $shortcode ) {
                if ( '[' === $shortcode[1] && ']' === $shortcode[6] ) {
                    continue;
                }

                $atts = shortcode_parse_atts( $shortcode[3] );

                if ( is_array( $atts ) && isset( $atts['id'] ) ) {
                    $grid_ids[] = intval( $atts['id'] );
                }
            }
        }

        if ( function_exists( 'parse_blocks' ) ) {
            $this->add_grid_ids_from_blocks( parse_blocks( $content ), $grid_ids );
        }

        $grid_ids = array_values( array_unique( array_filter( array_map( 'intval', $grid_ids ) ) ) );

        return $grid_ids;
    }

    private function add_grid_ids_from_blocks( $blocks, &$grid_ids )
    {
        if ( ! is_array( $blocks ) ) {
            return;
        }

        foreach ( $blocks as $block ) {
            if ( isset( $block['blockName'] ) && 'easy-image-collage/collage' === $block['blockName'] && isset( $block['attrs']['id'] ) ) {
                $grid_ids[] = intval( $block['attrs']['id'] );
            }

            if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
                $this->add_grid_ids_from_blocks( $block['innerBlocks'], $grid_ids );
            }
        }
    }
}
