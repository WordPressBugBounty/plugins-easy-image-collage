<?php

class EIC_Layouts {

    private $layouts;

    public function __construct()
    {
        $this->layouts();
    }

    public function draw_layouts( $controls = false )
    {
        $output = '';
        foreach( $this->layouts as $name => $layout ) {
	        $layout['name'] = $name;
            $output .= $this->draw_layout( $layout, false, $controls );
        }

        return $output;
    }

    public function draw_layout( $layout, $grid, $controls = false )
    {
        $grid_id = $grid ? $grid->ID() : 0;

        $output = '<div class="eic-frame eic-frame-' . intval( $grid_id ) . ' eic-frame-' . sanitize_html_class( $layout['name'] ) . '" data-layout-name="' . esc_attr( $layout['name'] ) . '"';
        if( $grid ) {
            $output .= ' data-orig-width="' . esc_attr( $grid->width() ) . '"';
            $output .= ' data-orig-border="' . esc_attr( $grid->border_width() ) . '"';
            $output .= ' data-orig-radius="' . esc_attr( $grid->border_radius() ) . '"';
            $output .= ' data-ratio="' . esc_attr( $grid->ratio() ) . '"';
        }
        $output .= '>';
        $output .= $this->draw_block( $layout, $grid, $controls, $this->corner_defaults() );
        $output .= '</div>';
        return $output;
    }

    private function draw_block( $block, $grid = false, $controls = false, $corners = null )
    {
        $corners = is_array( $corners ) ? $corners : $this->corner_defaults();

        if( $block['type'] == 'img' ) {
            $output = '<div class="eic-image eic-image-' . intval( $block['id'] ) . $this->corner_classes( $corners ) . '"';
            if( $grid ) {
                $image = $grid->image( $block['id'] );

                if( $image ) {
                    if ( $this->is_text_frame( $image ) ) {
                        $style = $this->text_frame_style( $image );
                        $output .= ' data-frame-type="text" style="' . esc_attr( 'background-color: ' . $style['backgroundColor'] . ';' ) . '">';
                        $output .= $this->draw_text_frame( $image );
                    } else {
                        $output .= ' data-size-x="' . esc_attr( intval( $image['size_x'] ) ) . '"';
                        $output .= ' data-size-y="' . esc_attr( intval( $image['size_y'] ) ) . '"';
                        $output .= ' data-pos-x="' . esc_attr( intval( $image['pos_x'] ) ) . '"';
                        $output .= ' data-pos-y="' . esc_attr( intval( $image['pos_y'] ) ) . '"';
                        $output .= '>';

                        $image_post = get_post( absint( $image['attachment_id'] ) );
                        $image_title = $image_post ? $image_post->post_title : '';

                        if( isset( $image['custom_link'] ) && $image['custom_link'] !== '' ) {
                            $new_tab = empty( $image['custom_link_new_tab'] ) || $image['custom_link_new_tab'] === 'false' ? '' : ' target="_blank"';
                            $nofollow = empty( $image['custom_link_nofollow'] ) || $image['custom_link_nofollow'] === 'false' ? '' : ' rel="nofollow"';

                            $output .= '<a href="' . esc_url( $image['custom_link'] ) . '" title="' . esc_attr( $image_title ) . '" class="eic-image-custom-link"' . $new_tab . $nofollow . '></a>';
                        } elseif( EasyImageCollage::option( 'clickable_images', '0' ) == '1' ) {
                            $class = EasyImageCollage::option( 'lightbox_class', '' );
                            $rel = EasyImageCollage::option( 'lightbox_rel', 'lightbox' );
                            $target = EasyImageCollage::option( 'clickable_images_new_tab', '0' ) == '1' ? ' target="_blank"' : '';

                            $output .= '<a href="' . esc_url( $image['attachment_url'] ) . '" rel="' . esc_attr( $rel ) . '" title="' . esc_attr( $image_title ) . '" class="eic-image-link ' . esc_attr( $class ) . '"' . $target . '></a>';
                        }

                        // Pinterest Button
                        if( !$controls && EasyImageCollage::option( 'pinterest_enable', '0' ) == '1' ) {
                            $pin_image = esc_url_raw( $image['attachment_url'] );

                            $image_caption = isset( $image['custom_caption'] ) ? $image['custom_caption'] : '';
                            $image_alt = get_post_meta( absint( $image['attachment_id'] ), '_wp_attachment_image_alt', true );

                            $pin_description = EasyImageCollage::option( 'pinterest_description', '%title%' );
                            $pin_description = str_ireplace( '%title%', $image_title, $pin_description );
                            $pin_description = str_ireplace( '%alt%', $image_alt, $pin_description );
                            $pin_description = str_ireplace( '%caption%', $image_caption, $pin_description );

                            switch( EasyImageCollage::option( 'pinterest_style', 'default' ) ) {
                                case 'red':
                                    $pin_style = ' data-pin-color="red"';
                                    break;
                                case 'white':
                                    $pin_style = ' data-pin-color="white"';
                                    break;
                                case 'round':
                                    $pin_style = ' data-pin-shape="round"';
                                    break;
                                default:
                                    $pin_style = '';
                            }

                            switch( EasyImageCollage::option( 'pinterest_size', 'default' ) ) {
                                case 'large':
                                    $pin_size = ' data-pin-tall="true"';
                                    break;
                                default:
                                    $pin_size = '';
                            }

                            $output .= apply_filters( 'eic_layouts_output_pin_button', '<a data-pin-do="buttonPin" href="https://www.pinterest.com/pin/create/button/?media=' . rawurlencode( $pin_image ) . '&description=' . rawurlencode( $pin_description ) . '"' . $pin_style . $pin_size . '></a>', $image );
                        }

                        $output .= $this->draw_caption( $image );
                    }

                } else {
                    $output .= '>';
                }
            } else {
                $output .= '>';
            }

            if( $controls ) {
                if( EasyImageCollage::is_premium_active() ) {
                    $output .= '<div class="eic-image-size">';
                    $output .= '<span class="eic-image-width">0</span> x <span class="eic-image-height">0</span>';
                    $output .= '</div>';
                }
                $block_id = intval( $block['id'] );

                $output .= '<div class="eic-image-controls">';
                $output .= '<div class="eic-image-control eic-image-control-image"' . $this->image_control_tooltip_attrs( __( 'Choose image', 'easy-image-collage' ) ) . ' onclick="event.preventDefault(); EasyImageCollage.btnImage(' . $block_id . ')"><i class="fa fa-picture-o"></i></div>';
                $output .= '<div class="eic-image-control eic-image-control-text-frame"' . $this->image_control_tooltip_attrs( __( 'Add text frame', 'easy-image-collage' ) ) . ' onclick="event.preventDefault(); EasyImageCollage.btnTextFrame(' . $block_id . ')"><i class="fa fa-align-left"></i></div>';
                $output .= '<div class="eic-image-control eic-image-control-manipulate"' . $this->image_control_tooltip_attrs( __( 'Manipulate image', 'easy-image-collage' ) ) . ' onclick="event.preventDefault(); EasyImageCollage.btnManipulate(' . $block_id . ')"><i class="fa fa-wrench"></i></div>';
                $output .= '<div class="eic-image-control eic-image-control-link"' . $this->image_control_tooltip_attrs( __( 'Image link', 'easy-image-collage' ) ) . ' onclick="event.preventDefault(); EasyImageCollage.btnLink(' . $block_id . ')"><i class="fa fa-link"></i></div>';
                $output .= '<div class="eic-image-control eic-image-control-caption"' . $this->image_control_tooltip_attrs( __( 'Image caption', 'easy-image-collage' ) ) . ' onclick="event.preventDefault(); EasyImageCollage.btnCaption(' . $block_id . ')"><i class="fa fa-font"></i></div>';
                $output .= '<div class="eic-image-control eic-image-control-remove"' . $this->image_control_tooltip_attrs( __( 'Remove image', 'easy-image-collage' ) ) . ' onclick="event.preventDefault(); EasyImageCollage.removeImage(' . $block_id . ')"><i class="fa fa-ban"></i></div>';
                $output .= '</div>';
            }

            $output .= '</div>';

            return $output;
        } else {
	        if( $grid && isset( $block['id'] ) ) {
		        $pos = $grid->divider_adjust( $block['id'] );

		        if( $pos ) {
			        $block['pos'] = $pos;
		        }
	        }

            $percentage1 = str_replace( ',', '.', $block['pos'] * 100 );
            $percentage2 = str_replace( ',', '.', 100 - $percentage1 );

            if( $block['type'] == 'row' ) {
                $style1 = 'top: 0; left: 0; right: 0; bottom: ' . $percentage1 . '%; height: ' . $percentage1 . '%;';
                $style2 = 'bottom: 0; left: 0; right: 0; top: ' . $percentage1 . '%; height: ' . $percentage2 . '%;';
            } else {
                $style1 = 'top: 0; bottom: 0; left: 0; right: ' . $percentage1 . '%; width: ' . $percentage1 . '%;';
                $style2 = 'top: 0; bottom: 0; right: 0; left: ' . $percentage1 . '%; width: ' . $percentage2 . '%;';
            }

            $output = '<div class="eic-' . sanitize_html_class( $block['type'] ) . 's">';
            $output .= '<div class="eic-' . sanitize_html_class( $block['type'] ) . ' eic-child-1" style="' . esc_attr( $style1 ) . '">';
            $output .= $this->draw_block( $block['children'][0], $grid, $controls, $this->child_corners( $corners, $block['type'], 1 ) );
            $output .= '</div>';

	        if( $controls ) {
		        if( $block['type'] == 'row' ) {
			        $divider_style = 'left: 10%; right: 0; top: ' . $percentage1 . '%; height: 4px; width: 80%; margin-top: -2px; cursor: row-resize;';
		        } else {
			        $divider_style = 'top: 10%; bottom: 0; left: ' . $percentage1 . '%; height: 80%; width: 4px; margin-left: -2px; cursor: col-resize;';
		        }
		        $output .= '<div class="eic-divider eic-divider-' . sanitize_html_class( $block['type'] ) . ' eic-divider-' . intval( $block['id'] ) . '" style="' . esc_attr( $divider_style ) . '" data-divider-type="' . esc_attr( $block['type'] ) . '" data-divider-id="' . esc_attr( intval( $block['id'] ) ) . '"></div>';
	        }

            $output .= '<div class="eic-' . sanitize_html_class( $block['type'] ) . ' eic-child-2" style="' . esc_attr( $style2 ) . '">';
            $output .= $this->draw_block( $block['children'][1], $grid, $controls, $this->child_corners( $corners, $block['type'], 2 ) );
            $output .= '</div>';
            $output .= '</div>';

            return $output;
        }
    }

    public function draw_layout_frontend( $layout, $grid )
    {
        $grid_id = $grid ? $grid->ID() : 0;

        $output = '<div class="eic-frame eic-frame-' . intval( $grid_id ) . ' eic-frame-' . sanitize_html_class( $layout['name'] ) . '" data-layout-name="' . esc_attr( $layout['name'] ) . '"';
        $output .= ' data-orig-width="' . esc_attr( $grid->width() ) . '"';
        $output .= ' data-orig-border="' . esc_attr( $grid->border_width() ) . '"';
        $output .= ' data-orig-radius="' . esc_attr( $grid->border_radius() ) . '"';
        $output .= ' data-ratio="' . esc_attr( $grid->ratio() ) . '"';
        $output .= '>';
        $output .= $this->draw_block_frontend( $layout, $grid, $this->corner_defaults() );
        $output .= '</div>';
        return $output;
    }

    private function draw_block_frontend( $block, $grid, $corners = null )
    {
        $corners = is_array( $corners ) ? $corners : $this->corner_defaults();

        if( $block['type'] == 'img' ) {
            $output = '<div class="eic-image eic-image-' . intval( $block['id'] ) . $this->corner_classes( $corners ) . '"';
            $image = $grid->image( $block['id'] );

            if( $image ) {
                if ( $this->is_text_frame( $image ) ) {
                    $style = $this->text_frame_style( $image );
                    $output .= ' data-frame-type="text" style="' . esc_attr( 'background-color: ' . $style['backgroundColor'] . ';' ) . '">';
                    $output .= $this->draw_text_frame( $image );
                } else {
                $output .= ' data-size-x="' . esc_attr( intval( $image['size_x'] ) ) . '"';
                $output .= ' data-size-y="' . esc_attr( intval( $image['size_y'] ) ) . '"';
                $output .= ' data-pos-x="' . esc_attr( intval( $image['pos_x'] ) ) . '"';
                $output .= ' data-pos-y="' . esc_attr( intval( $image['pos_y'] ) ) . '"';
                $output .= '>';

                $image_post = get_post( absint( $image['attachment_id'] ) );
                $image_title = $image_post ? $image_post->post_title : '';
                
                // Get thumbnail to output.
                $url = $image['attachment_url'];
                $has_responsive_image = false;
                $responsive_image_width = 0;
                $responsive_image_height = 0;

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
                            $has_responsive_image = true;
                            $responsive_image_width = $thumb_width;
                            $responsive_image_height = $thumb_height;
                        }
                    }
                }

                // Output image.
                $img_style = 'width: ' . $width . 'px !important;';
                $img_style .= 'height: ' . $height . 'px !important;';
                $img_style .= 'max-width: none !important;';
                $img_style .= 'max-height: none !important;';
                $img_style .= 'position: absolute !important;';
                $img_style .= 'left: ' . intval( $image['pos_x'] ) . 'px !important;';
                $img_style .= 'top: ' . intval( $image['pos_y'] ) . 'px !important;';
                $img_style .= 'padding: 0 !important;';
                $img_style .= 'margin: 0 !important;';
                $img_style .= 'border: none !important;';

                $image_caption = isset( $image['custom_caption'] ) ? $image['custom_caption'] : '';
                $image_alt = get_post_meta( absint( $image['attachment_id'] ), '_wp_attachment_image_alt', true );
                
                $title = $image_caption ? $image_caption : $image_title;
                $alt = $image_alt ? $image_alt : ( $image_caption ? $image_caption : $image_title );
                $responsive_image_attributes = $has_responsive_image ? $this->responsive_image_attributes( absint( $image['attachment_id'] ), $url, $responsive_image_width, $responsive_image_height, $width, $grid ) : '';

                $output .= apply_filters( 'eic_layouts_output_image', '<img src="' . esc_url( $url ) . '"' . $responsive_image_attributes . ' style="' . esc_attr( $img_style ) . '" title="' . esc_attr( $title ) . '" alt="' . esc_attr( $alt ) . '" />', $image );

                // Other image options.
                if( isset( $image['custom_link'] ) && $image['custom_link'] !== '' ) {
                    $new_tab = empty( $image['custom_link_new_tab'] ) || $image['custom_link_new_tab'] === 'false' ? '' : ' target="_blank"';
                    $nofollow = empty( $image['custom_link_nofollow'] ) || $image['custom_link_nofollow'] === 'false' ? '' : ' rel="nofollow"';

                    $output .= '<a href="' . esc_url( $image['custom_link'] ) . '" title="' . esc_attr( $title ) . '" class="eic-image-custom-link"' . $new_tab . $nofollow . '></a>';
                } elseif( EasyImageCollage::option( 'clickable_images', '0' ) == '1' ) {
                    $class = EasyImageCollage::option( 'lightbox_class', '' );
                    $rel = EasyImageCollage::option( 'lightbox_rel', 'lightbox' );
                    $target = EasyImageCollage::option( 'clickable_images_new_tab', '0' ) == '1' ? ' target="_blank"' : '';

                    $output .= '<a href="' . esc_url( $image['attachment_url'] ) . '" rel="' . esc_attr( $rel ) . '" title="' . esc_attr( $title ) . '" class="eic-image-link ' . esc_attr( $class ) . '"' . $target . '></a>';
                }

                // Pinterest Button
                if( EasyImageCollage::option( 'pinterest_enable', '0' ) == '1' ) {
                    $pin_image = esc_url_raw( $image['attachment_url'] );

                    $pin_description = EasyImageCollage::option( 'pinterest_description', '%title%' );
                    $pin_description = str_ireplace( '%title%', $image_title, $pin_description );
                    $pin_description = str_ireplace( '%alt%', $image_alt, $pin_description );
                    $pin_description = str_ireplace( '%caption%', $image_caption, $pin_description );
                    $pin_description = esc_attr( $pin_description );

                    switch( EasyImageCollage::option( 'pinterest_style', 'default' ) ) {
                        case 'red':
                            $pin_style = ' data-pin-color="red"';
                            break;
                        case 'white':
                            $pin_style = ' data-pin-color="white"';
                            break;
                        case 'round':
                            $pin_style = ' data-pin-shape="round"';
                            break;
                        default:
                            $pin_style = '';
                    }

                    switch( EasyImageCollage::option( 'pinterest_size', 'default' ) ) {
                        case 'large':
                            $pin_size = ' data-pin-tall="true"';
                            break;
                        default:
                            $pin_size = '';
                    }

                    $output .= apply_filters( 'eic_layouts_output_pin_button', '<a data-pin-do="buttonPin" href="https://www.pinterest.com/pin/create/button/?media=' . rawurlencode( $pin_image ) . '&description=' . rawurlencode( $pin_description ) . '"' . $pin_style . $pin_size . '></a>', $image );
                }

                $output .= $this->draw_caption( $image );
                }

            } else {
                $output .= '>';
            }

            $output .= '</div>';

            return $output;
        } else {
	        if( isset( $block['id'] ) ) {
		        $pos = $grid->divider_adjust( $block['id'] );

		        if( $pos ) {
			        $block['pos'] = $pos;
		        }
	        }

            $percentage1 = str_replace( ',', '.', $block['pos'] * 100 );
            $percentage2 = str_replace( ',', '.', 100 - $percentage1 );

            if( $block['type'] == 'row' ) {
                $style1 = 'top: 0; left: 0; right: 0; bottom: ' . $percentage1 . '%; height: ' . $percentage1 . '%;';
                $style2 = 'bottom: 0; left: 0; right: 0; top: ' . $percentage1 . '%; height: ' . $percentage2 . '%;';
            } else {
                $style1 = 'top: 0; bottom: 0; left: 0; right: ' . $percentage1 . '%; width: ' . $percentage1 . '%;';
                $style2 = 'top: 0; bottom: 0; right: 0; left: ' . $percentage1 . '%; width: ' . $percentage2 . '%;';
            }

            $output = '<div class="eic-' . sanitize_html_class( $block['type'] ) . 's">';
            $output .= '<div class="eic-' . sanitize_html_class( $block['type'] ) . ' eic-child-1" style="' . esc_attr( $style1 ) . '">';
            $output .= $this->draw_block_frontend( $block['children'][0], $grid, $this->child_corners( $corners, $block['type'], 1 ) );
            $output .= '</div>';

            $output .= '<div class="eic-' . sanitize_html_class( $block['type'] ) . ' eic-child-2" style="' . esc_attr( $style2 ) . '">';
            $output .= $this->draw_block_frontend( $block['children'][1], $grid, $this->child_corners( $corners, $block['type'], 2 ) );
            $output .= '</div>';
            $output .= '</div>';

            return $output;
        }
    }

    private function is_text_frame( $image )
    {
        return is_array( $image ) && isset( $image['type'] ) && 'text' === $image['type'];
    }

    private function draw_text_frame( $image )
    {
        $style = $this->text_frame_style( $image );
        $content_style = 'display: flex;';
        $content_style .= 'flex-direction: column;';
        $content_style .= 'justify-content: ' . $this->text_frame_vertical_align( $style['verticalAlign'] ) . ';';
        $content_style .= 'width: 100%;';
        $content_style .= 'height: 100%;';
        $content_style .= 'padding: ' . intval( $style['padding'] ) . 'px;';
        $content_style .= 'color: ' . $style['color'] . ';';
        $content_style .= $style['fontFamily'] ? 'font-family: ' . $style['fontFamily'] . ';' : '';
        $content_style .= 'font-size: ' . intval( $style['fontSize'] ) . 'px;';
        $content_style .= 'line-height: ' . floatval( $style['lineHeight'] ) . ';';
        $content_style .= 'letter-spacing: ' . floatval( $style['letterSpacing'] ) . 'px;';
        $content_style .= 'text-transform: ' . $style['textTransform'] . ';';
        $content_style .= 'text-align: ' . $style['textAlign'] . ';';

        return '<div class="eic-text-frame-content" style="' . esc_attr( $content_style ) . '">' . wp_kses_post( isset( $image['text_content'] ) ? $image['text_content'] : '' ) . '</div>';
    }

    private function text_frame_style( $image )
    {
        $style = isset( $image['text_style'] ) && is_array( $image['text_style'] ) ? $image['text_style'] : array();

        return array_merge(
            array(
                'fontFamily'      => '',
                'fontSize'        => 24,
                'lineHeight'      => 1.3,
                'letterSpacing'   => 0,
                'textTransform'   => 'none',
                'color'           => '#111111',
                'backgroundColor' => '#ffffff',
                'textAlign'       => 'center',
                'verticalAlign'   => 'center',
                'padding'         => 12,
            ),
            $style
        );
    }

    private function text_frame_vertical_align( $align )
    {
        switch( $align ) {
            case 'top':
                return 'flex-start';
            case 'bottom':
                return 'flex-end';
            default:
                return 'center';
        }
    }

    private function draw_caption( $image )
    {
        if( ! EasyImageCollage::is_addon_active( 'captions' ) ) {
            return '';
        }

        if( ! isset( $image['custom_caption'] ) || ! $image['custom_caption'] ) {
            return '';
        }

        $hover = EasyImageCollage::option( 'captions_hover_only', '1' ) == '1' ? ' eic-image-caption-hover' : '';

        return '<span class="eic-image-caption' . esc_attr( $hover ) . '">' . wp_kses_post( $image['custom_caption'] ) . '</span>';
    }

    private function responsive_image_attributes( $attachment_id, $url, $src_width, $src_height, $display_width, $grid )
    {
        if( ! function_exists( 'wp_calculate_image_srcset' ) ) {
            return '';
        }

        if( ! $src_width || ! $src_height ) {
            return '';
        }

        $image_meta = wp_get_attachment_metadata( $attachment_id );

        if( ! is_array( $image_meta ) ) {
            return '';
        }

        $image_meta = $this->filter_image_meta_by_ratio( $image_meta, $src_width, $src_height );
        $srcset_urls = $this->image_meta_srcset_urls( $image_meta );
        $filter_sources = function( $sources ) use ( $srcset_urls ) {
            foreach( $sources as $width => $source ) {
                if( ! isset( $source['url'] ) || ! isset( $srcset_urls[ $source['url'] ] ) ) {
                    unset( $sources[ $width ] );
                }
            }

            return $sources;
        };

        add_filter( 'wp_calculate_image_srcset', $filter_sources, PHP_INT_MAX );
        $srcset = wp_calculate_image_srcset(
            array( $src_width, $src_height ),
            $url,
            $image_meta,
            $attachment_id
        );
        remove_filter( 'wp_calculate_image_srcset', $filter_sources, PHP_INT_MAX );

        if( ! $srcset ) {
            return '';
        }

        $sizes = $this->responsive_image_sizes( $display_width, $grid );

        if( ! $sizes ) {
            return '';
        }

        return ' srcset="' . esc_attr( $srcset ) . '" sizes="' . esc_attr( $sizes ) . '"';
    }

    private function filter_image_meta_by_ratio( $image_meta, $target_width, $target_height )
    {
        if( isset( $image_meta['width'], $image_meta['height'] ) && ! $this->image_size_matches_ratio( $image_meta['width'], $image_meta['height'], $target_width, $target_height ) ) {
            $image_meta['width'] = 0;
            $image_meta['height'] = 0;
        }

        if( empty( $image_meta['sizes'] ) || ! is_array( $image_meta['sizes'] ) ) {
            return $image_meta;
        }

        foreach( $image_meta['sizes'] as $size => $size_meta ) {
            if( ! isset( $size_meta['width'], $size_meta['height'] ) || ! $this->image_size_matches_ratio( $size_meta['width'], $size_meta['height'], $target_width, $target_height ) ) {
                unset( $image_meta['sizes'][ $size ] );
            }
        }

        return $image_meta;
    }

    private function image_meta_srcset_urls( $image_meta )
    {
        $urls = array();

        if( empty( $image_meta['file'] ) ) {
            return $urls;
        }

        $dirname = _wp_get_attachment_relative_path( $image_meta['file'] );

        if( $dirname ) {
            $dirname = trailingslashit( $dirname );
        }

        $upload_dir = wp_get_upload_dir();
        $image_baseurl = trailingslashit( $upload_dir['baseurl'] ) . $dirname;

        if( is_ssl() && strpos( $image_baseurl, 'https' ) !== 0 ) {
            $parsed = parse_url( $image_baseurl );
            $domain = isset( $parsed['host'] ) ? $parsed['host'] : '';

            if( isset( $parsed['port'] ) ) {
                $domain .= ':' . $parsed['port'];
            }

            if( isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] === $domain ) {
                $image_baseurl = set_url_scheme( $image_baseurl, 'https' );
            }
        }

        if( ! empty( $image_meta['width'] ) && ! empty( $image_meta['height'] ) ) {
            $urls[ $image_baseurl . wp_basename( $image_meta['file'] ) ] = true;
        }

        if( empty( $image_meta['sizes'] ) || ! is_array( $image_meta['sizes'] ) ) {
            return $urls;
        }

        foreach( $image_meta['sizes'] as $size_meta ) {
            if( ! empty( $size_meta['file'] ) ) {
                $urls[ $image_baseurl . $size_meta['file'] ] = true;
            }
        }

        return $urls;
    }

    private function image_size_matches_ratio( $width, $height, $target_width, $target_height )
    {
        $width = intval( $width );
        $height = intval( $height );
        $target_width = intval( $target_width );
        $target_height = intval( $target_height );

        if( $width < 1 || $height < 1 || $target_width < 1 || $target_height < 1 ) {
            return false;
        }

        return $width * $target_height === $height * $target_width;
    }

    private function responsive_image_sizes( $width, $grid )
    {
        $grid_width = max( 1, intval( $grid->width() ) );
        $cell_scale = ( $width / $grid_width ) * 100;

        $sizes = '(max-width: ' . $grid_width . 'px) ' . $this->format_css_number( $cell_scale ) . 'vw, ' . intval( $width ) . 'px';

        if( EasyImageCollage::option( 'responsive_layout', '0' ) == '1' ) {
            $responsive_breakpoint = max( 1, intval( EasyImageCollage::option( 'responsive_breakpoint', '300' ) ) );
            $sizes = '(max-width: ' . $responsive_breakpoint . 'px) 100vw, ' . $sizes;
        }

        return $sizes;
    }

    private function format_css_number( $number )
    {
        return rtrim( rtrim( str_replace( ',', '.', sprintf( '%.4F', $number ) ), '0' ), '.' );
    }

    private function image_control_tooltip_attrs( $tooltip )
    {
        $tooltip = esc_attr( $tooltip );

        return ' data-eic-tooltip="' . $tooltip . '" aria-label="' . $tooltip . '" title="' . $tooltip . '"';
    }

    private function corner_defaults()
    {
        return array(
            'top_left'     => true,
            'top_right'    => true,
            'bottom_left'  => true,
            'bottom_right' => true,
        );
    }

    private function child_corners( $corners, $type, $child )
    {
        if ( 'row' === $type ) {
            return 1 === intval( $child )
                ? array(
                    'top_left'     => ! empty( $corners['top_left'] ),
                    'top_right'    => ! empty( $corners['top_right'] ),
                    'bottom_left'  => false,
                    'bottom_right' => false,
                )
                : array(
                    'top_left'     => false,
                    'top_right'    => false,
                    'bottom_left'  => ! empty( $corners['bottom_left'] ),
                    'bottom_right' => ! empty( $corners['bottom_right'] ),
                );
        }

        return 1 === intval( $child )
            ? array(
                'top_left'     => ! empty( $corners['top_left'] ),
                'top_right'    => false,
                'bottom_left'  => ! empty( $corners['bottom_left'] ),
                'bottom_right' => false,
            )
            : array(
                'top_left'     => false,
                'top_right'    => ! empty( $corners['top_right'] ),
                'bottom_left'  => false,
                'bottom_right' => ! empty( $corners['bottom_right'] ),
            );
    }

    private function corner_classes( $corners )
    {
        $classes = '';

        foreach ( array(
            'top_left'     => 'top-left',
            'top_right'    => 'top-right',
            'bottom_left'  => 'bottom-left',
            'bottom_right' => 'bottom-right',
        ) as $key => $class ) {
            if ( ! empty( $corners[ $key ] ) ) {
                $classes .= ' eic-corner-' . $class;
            }
        }

        return $classes;
    }

	public function get( $name )
	{
		return isset( $this->layouts[ $name ] ) ? $this->layouts[ $name ] : false;
	}

    private function layouts()
    {
        $this->layouts = array(
            'square' => array(
                'type' => 'img',
                'id' => 0
            ),
            '4-squares' => array(
                'type' => 'col',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                        )
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 2,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'img',
                                'id' => 4
                            ),
                        )
                    ),
                ),
            ),
            '2-col' => array(
                'type' => 'col',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'img',
                        'id' => 1
                    ),
                ),
            ),
            '2-row' => array(
                'type' => 'row',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'img',
                        'id' => 1
                    ),
                ),
            ),
            '2-row-bottom-2-col' => array(
                'type' => 'row',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                        ),
                    ),
                ),
            ),
            '2-row-top-2-col' => array(
                'type' => 'row',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                        ),
                    ),
                    array(
                        'type' => 'img',
                        'id' => 2
                    ),
                ),
            ),
            '2-col-right-2-row' => array(
                'type' => 'col',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                        ),
                    ),
                ),
            ),
            '2-col-left-2-row' => array(
                'type' => 'col',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                        ),
                    ),
                    array(
                        'type' => 'img',
                        'id' => 2
                    ),
                ),
            ),
            '3-row' => array(
                'type' => 'row',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                        ),
                    ),
                ),
            ),
            '3-col' => array(
                'type' => 'col',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                        ),
                    ),
                ),
            ),
            '4-row' => array(
                'type' => 'row',
                'pos' => 0.25,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.33333,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 3
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            '4-col' => array(
                'type' => 'col',
                'pos' => 0.25,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.33333,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'col',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 3
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            '2-row-bottom-3-col' => array(
                'type' => 'row',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.33333,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'col',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 3
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            '2-row-top-3-col' => array(
                'type' => 'row',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'col',
                        'pos' => 0.33333,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'col',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'img',
                        'id' => 3
                    ),
                ),
            ),
            '2-col-right-3-row' => array(
                'type' => 'col',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.33333,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 3
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            '2-col-left-3-row' => array(
                'type' => 'col',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'row',
                        'pos' => 0.33333,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'img',
                        'id' => 3
                    ),
                ),
            ),
            '4-squares-odd-left' => array(
                'type' => 'row',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'col',
                        'pos' => 0.66666,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                        )
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.33333,
                        'id' => 2,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'img',
                                'id' => 4
                            ),
                        )
                    ),
                ),
            ),
            '4-squares-odd-right' => array(
                'type' => 'row',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'col',
                        'pos' => 0.33333,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                        )
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.66666,
                        'id' => 2,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'img',
                                'id' => 4
                            ),
                        )
                    ),
                ),
            ),
            '4-squares-odd-bottom' => array(
                'type' => 'col',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'row',
                        'pos' => 0.66666,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                        )
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.33333,
                        'id' => 2,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'img',
                                'id' => 4
                            ),
                        )
                    ),
                ),
            ),
            '4-squares-odd-top' => array(
                'type' => 'col',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'row',
                        'pos' => 0.33333,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                        )
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.66666,
                        'id' => 2,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'img',
                                'id' => 4
                            ),
                        )
                    ),
                ),
            ),
            '3-row-first-2-col' => array(
                'type' => 'row',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                        ),
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 2,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                            array(
                                'type' => 'img',
                                'id' => 3
                            ),
                        ),
                    ),
                ),
            ),
            '3-row-second-2-col' => array(
                'type' => 'row',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'col',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                ),
                            ),
                            array(
                                'type' => 'img',
                                'id' => 3
                            ),
                        ),
                    ),
                ),
            ),
            '3-row-third-2-col' => array(
                'type' => 'row',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'col',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 3
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            '3-col-first-2-row' => array(
                'type' => 'col',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                        ),
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 2,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                            array(
                                'type' => 'img',
                                'id' => 3
                            ),
                        ),
                    ),
                ),
            ),
            '3-col-second-2-row' => array(
                'type' => 'col',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                ),
                            ),
                            array(
                                'type' => 'img',
                                'id' => 3
                            ),
                        ),
                    ),
                ),
            ),
            '3-col-third-2-row' => array(
                'type' => 'col',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 3
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            '2-row-bottom-2-col-right-2-row' => array(
                'type' => 'row',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 3
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            '2-row-top-2-col-right-2-row' => array(
                'type' => 'row',
                'pos' => 0.66666,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'img',
                        'id' => 3
                    ),
                ),
            ),
            '2-row-bottom-2-col-left-2-row' => array(
                'type' => 'row',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                ),
                            ),
                            array(
                                'type' => 'img',
                                'id' => 3
                            ),
                        ),
                    ),
                ),
            ),
            '2-row-top-2-col-left-2-row' => array(
                'type' => 'row',
                'pos' => 0.66666,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 0
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                ),
                            ),
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                        ),
                    ),
                    array(
                        'type' => 'img',
                        'id' => 3
                    ),
                ),
            ),
            '2-col-right-2-row-bottom-2-col' => array(
                'type' => 'col',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 1
                            ),
                            array(
                                'type' => 'col',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 3
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            '2-col-left-2-row-bottom-2-col' => array(
                'type' => 'col',
                'pos' => 0.66666,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'col',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'img',
                        'id' => 3
                    ),
                ),
            ),
            '2-col-right-2-row-top-2-col' => array(
                'type' => 'col',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'img',
                        'id' => 0
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'col',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                ),
                            ),
                            array(
                                'type' => 'img',
                                'id' => 3
                            ),
                        ),
                    ),
                ),
            ),
            '2-col-left-2-row-top-2-col' => array(
                'type' => 'col',
                'pos' => 0.66666,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'row',
                        'pos' => 0.5,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'col',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 0
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                ),
                            ),
                            array(
                                'type' => 'img',
                                'id' => 2
                            ),
                        ),
                    ),
                    array(
                        'type' => 'img',
                        'id' => 3
                    ),
                ),
            ),
            '2-row-3-col' => array(
                'type' => 'row',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'col',
                        'pos' => 0.33333,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'col',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.33333,
                        'id' => 3,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 3
                            ),
                            array(
                                'type' => 'col',
                                'pos' => 0.5,
                                'id' => 4,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 4
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 5
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            '2-col-3-row' => array(
                'type' => 'col',
                'pos' => 0.5,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'row',
                        'pos' => 0.33333,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'row',
                        'pos' => 0.33333,
                        'id' => 3,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 3
                            ),
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 4,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 4
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 5
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            '9-squares' => array(
                'type' => 'col',
                'pos' => 0.33333,
                'id' => 0,
                'children' => array(
                    array(
                        'type' => 'row',
                        'pos' => 0.33333,
                        'id' => 1,
                        'children' => array(
                            array(
                                'type' => 'img',
                                'id' => 0
                            ),
                            array(
                                'type' => 'row',
                                'pos' => 0.5,
                                'id' => 2,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 1
                                    ),
                                    array(
                                        'type' => 'img',
                                        'id' => 2
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'col',
                        'pos' => 0.5,
                        'id' => 3,
                        'children' => array(
                            array(
                                'type' => 'row',
                                'pos' => 0.33333,
                                'id' => 4,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 3
                                    ),
                                    array(
                                        'type' => 'row',
                                        'pos' => 0.5,
                                        'id' => 5,
                                        'children' => array(
                                            array(
                                                'type' => 'img',
                                                'id' => 4
                                            ),
                                            array(
                                                'type' => 'img',
                                                'id' => 5
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            array(
                                'type' => 'row',
                                'pos' => 0.33333,
                                'id' => 6,
                                'children' => array(
                                    array(
                                        'type' => 'img',
                                        'id' => 6
                                    ),
                                    array(
                                        'type' => 'row',
                                        'pos' => 0.5,
                                        'id' => 7,
                                        'children' => array(
                                            array(
                                                'type' => 'img',
                                                'id' => 7
                                            ),
                                            array(
                                                'type' => 'img',
                                                'id' => 8
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
    }
}
