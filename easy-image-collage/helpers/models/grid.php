<?php

class EIC_Grid {

    private $post;
    private $data;

    public function __construct( $post )
    {
        // Get associated post
        if( is_object( $post ) && $post instanceof WP_Post ) {
            $this->post = $post;
        } else if( is_numeric( $post ) ) {
            $this->post = get_post( $post );
        } else {
            throw new InvalidArgumentException( 'Grids can only be instantiated with a Post object or Post ID.' );
        }

        // Get metadata
        $this->data = $this->sanitize_data( get_post_meta( $this->post->ID, 'eic_grid_data', true ) );
    }

    public function get_data()
    {
		$data = $this->data;

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$data['id'] = $this->ID();

		if ( ! isset( $data['layout'] ) ) {
			$data['layout'] = 'square';
		}

		// Prevent issues with unset details.
		if ( ! isset( $data['images'] ) || ! is_array( $data['images'] ) ) {
			$data['images'] = array();
		}

		if ( ! isset( $data['properties'] ) || ! is_array( $data['properties'] ) ) {
			$data['properties'] = array();
		}

		$data['properties'] = array_merge(
			array(
				'align'       => EasyImageCollage::option( 'default_style_grid_align', 'center' ),
				'width'       => intval( EasyImageCollage::option( 'default_style_grid_width', 500 ) ),
				'ratio'       => floatval( EasyImageCollage::option( 'default_style_grid_ratio', 1 ) ),
				'borderWidth' => intval( EasyImageCollage::option( 'default_style_border_width', 4 ) ),
				'borderColor' => EasyImageCollage::option( 'default_style_border_color', '#444444' ),
				'borderRadius' => intval( EasyImageCollage::option( 'default_style_border_radius', 0 ) ),
			),
			$data['properties']
		);

	    return $data;
    }

	public function update_data( $data )
	{
		$data = $this->sanitize_data( $data );
		$data['id'] = $this->ID();
		$data['version'] = EIC_VERSION;
		update_post_meta( $this->ID(), 'eic_grid_data', $data );
		update_post_meta( $this->ID(), '_eic_image_count', $this->get_image_count_from_data( $data ) );

		return $data;
	}

	private function get_image_count_from_data( $data )
	{
		$images = isset( $data['images'] ) && is_array( $data['images'] ) ? $data['images'] : array();

		return count( array_filter( $images ) );
	}

	private function sanitize_data( $data )
	{
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		unset( $data['name'] );

		$data['id'] = isset( $data['id'] ) ? intval( $data['id'] ) : $this->ID();
		$data['version'] = isset( $data['version'] ) ? sanitize_text_field( $data['version'] ) : EIC_VERSION;

		if ( ! isset( $data['layout'] ) ) {
			$data['layout'] = 'square';
		} elseif ( is_array( $data['layout'] ) ) {
			$data['layout'] = $this->sanitize_layout( $data['layout'] );
		} else {
			$data['layout'] = sanitize_key( $data['layout'] );
		}

		$data['properties'] = $this->sanitize_properties( isset( $data['properties'] ) ? $data['properties'] : array() );
		$data['images'] = $this->sanitize_images( isset( $data['images'] ) ? $data['images'] : array() );

		if ( isset( $data['dividers'] ) && is_array( $data['dividers'] ) ) {
			$dividers = array();

			foreach ( $data['dividers'] as $id => $divider ) {
				$dividers[ intval( $id ) ] = floatval( $divider );
			}

			$data['dividers'] = $dividers;
		}

		return $data;
	}

	private function sanitize_properties( $properties )
	{
		$properties = is_array( $properties ) ? $properties : array();
		$default_color = sanitize_hex_color( EasyImageCollage::option( 'default_style_border_color', '#444444' ) );
		$default_color = $default_color ? $default_color : '#444444';
		$border_color = isset( $properties['borderColor'] ) ? sanitize_hex_color( $properties['borderColor'] ) : false;
		$align = isset( $properties['align'] ) ? sanitize_key( $properties['align'] ) : EasyImageCollage::option( 'default_style_grid_align', 'center' );

		if ( ! in_array( $align, array( 'left', 'center', 'right', 'float-left', 'float-right' ), true ) ) {
			$align = 'center';
		}

		$ratio = isset( $properties['ratio'] ) ? floatval( $properties['ratio'] ) : floatval( EasyImageCollage::option( 'default_style_grid_ratio', 1 ) );
		$ratio = $ratio > 0 ? $ratio : 1;

		return array(
			'align'       => $align,
			'width'       => isset( $properties['width'] ) ? absint( $properties['width'] ) : intval( EasyImageCollage::option( 'default_style_grid_width', 500 ) ),
			'ratio'       => $ratio,
			'borderWidth' => isset( $properties['borderWidth'] ) ? absint( $properties['borderWidth'] ) : intval( EasyImageCollage::option( 'default_style_border_width', 4 ) ),
			'borderColor' => $border_color ? $border_color : $default_color,
			'borderRadius' => intval( $this->clamp_number( isset( $properties['borderRadius'] ) ? $properties['borderRadius'] : 0, 0, 250, 0 ) ),
		);
	}

	private function sanitize_images( $images )
	{
		if ( ! is_array( $images ) ) {
			return array();
		}

		$sanitized_images = array();

		foreach ( $images as $id => $image ) {
			$sanitized_images[ intval( $id ) ] = is_array( $image ) ? $this->sanitize_image( $image, $id ) : false;
		}

		return $sanitized_images;
	}

	private function sanitize_image( $image, $id )
	{
		$type = isset( $image['type'] ) && 'text' === sanitize_key( $image['type'] ) ? 'text' : 'image';

		if ( 'text' === $type ) {
			return array(
				'id'           => intval( $id ),
				'type'         => 'text',
				'text_content' => isset( $image['text_content'] ) ? wp_kses_post( $image['text_content'] ) : '',
				'text_style'   => $this->sanitize_text_style( isset( $image['text_style'] ) ? $image['text_style'] : array() ),
			);
		}

		$attachment_id = isset( $image['attachment_id'] ) ? absint( $image['attachment_id'] ) : 0;

		$sanitized = array(
			'id'                       => intval( $id ),
			'type'                     => 'image',
			'attachment_id'            => $attachment_id,
			'attachment_url'           => isset( $image['attachment_url'] ) ? $this->sanitize_attachment_url( $image['attachment_url'] ) : '',
			'attachment_width'         => isset( $image['attachment_width'] ) ? absint( $image['attachment_width'] ) : 0,
			'attachment_height'        => isset( $image['attachment_height'] ) ? absint( $image['attachment_height'] ) : 0,
			'size_x'                   => isset( $image['size_x'] ) ? max( 0, floatval( $image['size_x'] ) ) : 0,
			'size_y'                   => isset( $image['size_y'] ) ? max( 0, floatval( $image['size_y'] ) ) : 0,
			'pos_x'                    => isset( $image['pos_x'] ) ? intval( $image['pos_x'] ) : 0,
			'pos_y'                    => isset( $image['pos_y'] ) ? intval( $image['pos_y'] ) : 0,
			'custom_link'              => isset( $image['custom_link'] ) ? esc_url_raw( $image['custom_link'] ) : '',
			'custom_caption'           => isset( $image['custom_caption'] ) ? wp_kses_post( $image['custom_caption'] ) : '',
		);

		if ( isset( $image['attachment_thumb'] ) && $image['attachment_thumb'] ) {
			$sanitized['attachment_thumb'] = $this->sanitize_attachment_url( $image['attachment_thumb'] );
		}

		if ( $attachment_id ) {
			$full = wp_get_attachment_image_src( $attachment_id, 'full' );

			if ( $full ) {
				if ( '' === $sanitized['attachment_url'] ) {
					$sanitized['attachment_url'] = $this->sanitize_attachment_url( $full[0] );
				}

				if ( ! $sanitized['attachment_width'] ) {
					$sanitized['attachment_width'] = absint( $full[1] );
				}

				if ( ! $sanitized['attachment_height'] ) {
					$sanitized['attachment_height'] = absint( $full[2] );
				}
			}

			if ( empty( $sanitized['attachment_thumb'] ) ) {
				$thumb = wp_get_attachment_image_src( $attachment_id, 'medium' );

				if ( $thumb ) {
					$sanitized['attachment_thumb'] = $this->sanitize_attachment_url( $thumb[0] );
				}
			}
		}

		if ( array_key_exists( 'custom_link_new_tab', $image ) ) {
			$sanitized['custom_link_new_tab'] = $this->to_bool( $image['custom_link_new_tab'] );
		}

		if ( array_key_exists( 'custom_link_nofollow', $image ) ) {
			$sanitized['custom_link_nofollow'] = $this->to_bool( $image['custom_link_nofollow'] );
		}

		return $sanitized;
	}

	private function sanitize_text_style( $style )
	{
		$style = is_array( $style ) ? $style : array();
		$font_families = array(
			'',
			'Arial, Helvetica, sans-serif',
			'Helvetica, Arial, sans-serif',
			'Georgia, serif',
			"'Times New Roman', Times, serif",
			'Verdana, Geneva, sans-serif',
			'Tahoma, Geneva, sans-serif',
			"'Trebuchet MS', Helvetica, sans-serif",
			"'Courier New', Courier, monospace",
		);

		$font_family = isset( $style['fontFamily'] ) ? wp_unslash( $style['fontFamily'] ) : '';
		if ( ! in_array( $font_family, $font_families, true ) ) {
			$font_family = '';
		}

		$font_weight = isset( $style['fontWeight'] ) ? sanitize_key( $style['fontWeight'] ) : 'normal';
		if ( ! in_array( $font_weight, array( 'normal', 'bold' ), true ) ) {
			$font_weight = 'normal';
		}

		$font_style = isset( $style['fontStyle'] ) ? sanitize_key( $style['fontStyle'] ) : 'normal';
		if ( ! in_array( $font_style, array( 'normal', 'italic' ), true ) ) {
			$font_style = 'normal';
		}

		$text_transform = isset( $style['textTransform'] ) ? sanitize_key( $style['textTransform'] ) : 'none';
		if ( ! in_array( $text_transform, array( 'none', 'uppercase', 'lowercase', 'capitalize' ), true ) ) {
			$text_transform = 'none';
		}

		$text_align = isset( $style['textAlign'] ) ? sanitize_key( $style['textAlign'] ) : 'center';
		if ( ! in_array( $text_align, array( 'left', 'center', 'right' ), true ) ) {
			$text_align = 'center';
		}

		$vertical_align = isset( $style['verticalAlign'] ) ? sanitize_key( $style['verticalAlign'] ) : 'center';
		if ( ! in_array( $vertical_align, array( 'top', 'center', 'bottom' ), true ) ) {
			$vertical_align = 'center';
		}

		$color = isset( $style['color'] ) ? sanitize_hex_color( $style['color'] ) : false;
		$background_color = isset( $style['backgroundColor'] ) ? sanitize_hex_color( $style['backgroundColor'] ) : false;

		return array(
			'fontFamily'      => $font_family,
			'fontSize'        => $this->clamp_number( isset( $style['fontSize'] ) ? $style['fontSize'] : 24, 8, 120, 24 ),
			'lineHeight'      => $this->clamp_number( isset( $style['lineHeight'] ) ? $style['lineHeight'] : 1.3, 0.8, 3, 1.3 ),
			'letterSpacing'   => $this->clamp_number( isset( $style['letterSpacing'] ) ? $style['letterSpacing'] : 0, -10, 20, 0 ),
			'fontWeight'      => $font_weight,
			'fontStyle'       => $font_style,
			'textTransform'   => $text_transform,
			'color'           => $color ? $color : '#111111',
			'backgroundColor' => $background_color ? $background_color : '#ffffff',
			'textAlign'       => $text_align,
			'verticalAlign'   => $vertical_align,
			'padding'         => $this->clamp_number( isset( $style['padding'] ) ? $style['padding'] : 12, 0, 100, 12 ),
		);
	}

	private function clamp_number( $value, $min, $max, $default )
	{
		$value = is_numeric( $value ) ? floatval( $value ) : $default;
		$value = max( $min, min( $max, $value ) );

		return $value;
	}

	private function sanitize_attachment_url( $url )
	{
		$url = is_string( $url ) ? trim( $url ) : '';
		$url = preg_replace( '/\s+/', '%20', $url );

		if ( '' === $url || preg_match( '/[<>"\']/', $url ) ) {
			return '';
		}

		return esc_url_raw( $url );
	}

	private function sanitize_layout( $layout )
	{
		$layout['type'] = isset( $layout['type'] ) && in_array( $layout['type'], array( 'img', 'row', 'col' ), true ) ? $layout['type'] : 'img';
		$layout['id'] = isset( $layout['id'] ) ? intval( $layout['id'] ) : 0;

		if ( isset( $layout['name'] ) ) {
			$layout['name'] = sanitize_key( $layout['name'] );
		}

		if ( isset( $layout['pos'] ) ) {
			$layout['pos'] = floatval( $layout['pos'] );
		}

		if ( isset( $layout['children'] ) && is_array( $layout['children'] ) ) {
			foreach ( $layout['children'] as $id => $child ) {
				$layout['children'][ $id ] = is_array( $child ) ? $this->sanitize_layout( $child ) : array(
					'type' => 'img',
					'id'   => 0,
				);
			}
		}

		return $layout;
	}

	private function to_bool( $value )
	{
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return in_array( strtolower( $value ), array( '1', 'true', 'yes', 'on' ), true );
		}

		return (bool) $value;
	}

	public function draw()
	{
        $layout = $this->layout() ? $this->layout() : EasyImageCollage::get()->helper( 'layouts' )->get( $this->layout_name() );
        $layout['name'] = $this->layout_name();

		if( EasyImageCollage::option( 'default_style_display', 'image' ) == 'background' ) {
			return EasyImageCollage::get()->helper( 'layouts' )->draw_layout( $layout, $this );
		} else {
			return EasyImageCollage::get()->helper( 'layouts' )->draw_layout_frontend( $layout, $this );
		}
	}

	// Grid Fields
	public function align()
	{
		return isset( $this->data['properties']['align'] ) ? $this->data['properties']['align'] : 'center';
	}

	public function border_color()
	{
		$data = $this->get_data();
		return $data['properties']['borderColor'];
	}

	public function border_width()
	{
		$data = $this->get_data();
		return intval( $data['properties']['borderWidth'] );
	}

	public function border_radius()
	{
		$data = $this->get_data();
		return intval( $data['properties']['borderRadius'] );
	}

	public function divider_adjust( $id )
	{
		if( isset( $this->data['dividers'] ) && isset( $this->data['dividers'][$id] ) ) {
			return floatval( $this->data['dividers'][$id] );
		}
		return false;
	}

	public function height()
	{
		return intval( $this->width() / $this->ratio() );
	}

	public function ID()
	{
		return $this->post->ID;
	}

	public function image( $id )
	{
		$images = $this->images();
		return isset( $images[$id] ) ? $images[$id] : false;
	}

	public function images()
	{
		$data = $this->get_data();
		$images = isset( $data['images'] ) && is_array( $data['images'] ) ? $data['images'] : array();
		return $images;
	}

    public function layout()
    {
        return isset( $this->data['layout'] ) && is_array( $this->data['layout'] ) ? $this->data['layout'] : false;
    }

	public function layout_name()
	{
		if ( isset( $this->data['layout'] ) && is_array( $this->data['layout'] ) ) {
			return 'custom-' . $this->ID();
		}

		return isset( $this->data['layout'] ) ? $this->data['layout'] : 'square';
	}

	public function ratio()
	{
		$data = $this->get_data();
		$ratio = floatval( $data['properties']['ratio'] );
		$ratio = $ratio == 0 ? 1 : $ratio;
		return $ratio;
	}

	public function version()
	{
		return isset( $this->data['version'] ) ? $this->data['version'] : '1.11.0';
	}

	public function width()
	{
		$data = $this->get_data();
		return intval( $data['properties']['width'] );
	}
}
