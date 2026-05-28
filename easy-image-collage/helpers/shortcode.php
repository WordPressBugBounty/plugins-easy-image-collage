<?php

class EIC_Shortcode {

    public function __construct()
    {
        add_shortcode( 'easy-image-collage', array( $this, 'eic_shortcode' ) );
    }

    function eic_shortcode( $options )
    {
        return $this->render_collage( $options, false );
    }

    public function render_collage( $options, $allow_trash = false )
    {
        $options = shortcode_atts( array(
            'id' => '0', // If no ID given, show a random recipe
        ), $options );

        $post = get_post( intval( $options['id'] ) );

        $output = '';

        if( !is_null( $post ) && $post->post_type == EIC_POST_TYPE && ( $allow_trash || 'trash' !== $post->post_status ) ) {
	        $grid = new EIC_Grid( $post );

            if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
                foreach( $grid->images() as $id => $image ) {
                    if ( $image && isset( $image['type'] ) && 'text' === $image['type'] ) {
                        $style = isset( $image['text_style'] ) && is_array( $image['text_style'] ) ? $image['text_style'] : array();
                        $output .= '<div style="text-align: center; margin-bottom: 10px; padding: ' . intval( isset( $style['padding'] ) ? $style['padding'] : 12 ) . 'px; color: ' . esc_attr( isset( $style['color'] ) ? $style['color'] : '#111111' ) . '; background-color: ' . esc_attr( isset( $style['backgroundColor'] ) ? $style['backgroundColor'] : '#ffffff' ) . ';">' . wp_kses_post( isset( $image['text_content'] ) ? $image['text_content'] : '' ) . '</div>';
                        continue;
                    }

                    if ( $image && ( ! isset( $image['type'] ) || 'text' !== $image['type'] ) ) {
                        $thumb = wp_get_attachment_image( absint( $image['attachment_id'] ), 'large' );

                        if ( $thumb ) {
                            $output .= $thumb;

                            if( EasyImageCollage::is_addon_active( 'captions' ) ) {
                                if( isset( $image['custom_caption'] ) && $image['custom_caption'] ) {
                                    $output .= '<div style="text-align: center; margin-bottom: 10px; font-size: 0.8em;">' . wp_kses_post( $image['custom_caption'] ) . '</div>';
                                }
                            }
                        }
                    }
                }
            } else {
                // Styling
                $border_color = $grid->border_color();
                $border_width = $grid->border_width();
                $border_radius = $grid->border_radius();
                $inner_border_radius = max( 0, $border_radius - $border_width );

                $output .= '<style>';
                $output .= '.eic-frame-' . intval( $grid->ID() ) . ' { width: ' . $grid->width() . 'px; height:' . $grid->height() . 'px; background-color: ' . $border_color . '; border: ' . $border_width . 'px solid ' . $border_color . '; border-radius: ' . $border_radius . 'px; overflow: hidden; }';
                $output .= '.eic-frame-' . intval( $grid->ID() ) . ' .eic-image { border: ' . $border_width . 'px solid ' . $border_color . '; }';
                $output .= '.eic-frame-' . intval( $grid->ID() ) . ' .eic-corner-top-left { border-top-left-radius: ' . $inner_border_radius . 'px; }';
                $output .= '.eic-frame-' . intval( $grid->ID() ) . ' .eic-corner-top-right { border-top-right-radius: ' . $inner_border_radius . 'px; }';
                $output .= '.eic-frame-' . intval( $grid->ID() ) . ' .eic-corner-bottom-left { border-bottom-left-radius: ' . $inner_border_radius . 'px; }';
                $output .= '.eic-frame-' . intval( $grid->ID() ) . ' .eic-corner-bottom-right { border-bottom-right-radius: ' . $inner_border_radius . 'px; }';

                if( EasyImageCollage::option( 'default_style_display', 'image' ) == 'background' ) {
                    foreach( $grid->images() as $id => $image ) {
                        if( $image && ( ! isset( $image['type'] ) || 'text' !== $image['type'] ) ) {
                            $url = $image['attachment_url'];

                            $width = max( 1, intval( $image['size_x'] ) );
                            $height = max( 1, intval( $image['size_y'] ) );
                            $ratio = $width / $height;

                            $thumb = wp_get_attachment_image_src( absint( $image['attachment_id'] ), array( $width, $height ) );

                            if( $thumb ) {
                                $full_file_name = get_attached_file( absint( $image['attachment_id'] ) );
                                $path = str_ireplace( wp_basename( $full_file_name ), '', $full_file_name );
                            
                                $thumb_url = $thumb[0];
                                $thumb_file = $path . wp_basename( $thumb_url );

                                // Try path first for performance reasons, fall back on URL.
                                @list( $thumb_width, $thumb_height ) = getimagesize( $thumb_file );

                                if ( !$thumb_width || !$thumb_height ) {
                                    @list( $thumb_width, $thumb_height ) = getimagesize( $thumb_url );
                                }

                                if( $thumb_width && $thumb_height ) {
                                    $thumb_ratio = $thumb_width / $thumb_height;

                                    if( abs( $thumb_ratio - $ratio ) < 0.05 ) {
                                        $url = $thumb_url; // Only use the thumbnail if the ratios match
                                    }
                                }
                            }

                            $output .= '.eic-frame-' . intval( $grid->ID() ) . ' .eic-image-' . intval( $id ) . ' {';
                            $output .= 'background-image: url("' . $this->css_url( $url ) . '");';
                            $output .= 'background-size: ' . $width . 'px ' . $height . 'px;';
                            $output .= 'background-position: ' . intval( $image['pos_x'] ) . 'px ' . intval( $image['pos_y'] ) . 'px;';
                            $output .= '}';
                        }
                    }
                }

                $output .= '</style>';

                $container_class = '';
                $container_style = '';

                switch( $grid->align() ) {
                    case 'float-left':
                        $container_class = ' eic-float-left';
                        break;
                    case 'float-right':
                        $container_class = ' eic-float-right';
                        break;
                    case 'left':
                        $container_style = ' style="text-align: left;"';
                        break;
                    case 'right':
                        $container_style = ' style="text-align: right;"';
                        break;
                }

                // Draw frame
                $output .= '<div class="eic-container' . $container_class . '"' . $container_style . '>';
                $output .= $grid->draw();
                $output .= '</div>';
            }
        }

        return $output;
    }

    private function css_url( $url )
    {
        return str_replace( array( '\\', '"', "\n", "\r", ')' ), '', esc_url( $url ) );
    }
}
