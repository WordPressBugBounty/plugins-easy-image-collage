<?php

class EIC_Ajax {

    public function __construct()
    {
        add_action( 'wp_ajax_image_collage', array( $this, 'ajax_image_collage' ) );
        add_action( 'wp_ajax_image_collage_get', array( $this, 'ajax_image_collage_get' ) );
        add_action( 'wp_ajax_image_collage_preview', array( $this, 'ajax_image_collage_preview' ) );
    }

    public function ajax_image_collage()
    {
        if( check_ajax_referer( 'eic_image_collage', 'security', false ) )
        {

            $grid_data = isset( $_POST['grid'] ) && is_array( $_POST['grid'] ) ? wp_unslash( $_POST['grid'] ) : array();
            $grid_id = isset( $grid_data['id'] ) ? intval( $grid_data['id'] ) : 0;

            // Create new or update grid
            if( $grid_id === 0 ) {
                // Make sure user is allowed to create a new post.
                if ( ! current_user_can( 'publish_posts' ) ) {
                    die();
                }

                global $user_ID;

                $post = array(
                    'post_status' => 'publish',
                    'post_date' => date('Y-m-d H:i:s'),
                    'post_author' => $user_ID,
                    'post_type' => EIC_POST_TYPE,
                    'post_content' => '',
                );

                $grid_id = wp_insert_post( $post );

                if ( $grid_id ) {
                    wp_update_post( array(
                        'ID'         => $grid_id,
                        'post_title' => $this->get_collage_title_from_grid_data( $grid_data, $grid_id ),
                    ) );
                }
            } else {
                // Make sure user is allowed to edit this post.
                if ( ! current_user_can( 'edit_post', $grid_id ) ) {
                    die();
                }

                $post = array(
                    'ID' => $grid_id,
                    'post_content' => '',
                    'post_title' => $this->get_collage_title_from_grid_data( $grid_data, $grid_id ),
                );

                wp_update_post( $post );
            }

	        $grid = new EIC_Grid( $grid_id );
            $grid_data = $grid->update_data( $grid_data );

			do_action( 'eic_image_collage_saved', $grid_id, $grid_data, $grid );

            echo json_encode($grid_id);
        }

        die();
    }

	public function ajax_image_collage_get()
	{
		if ( ! check_ajax_referer( 'eic_image_collage', 'security', false ) ) {
			wp_send_json_error();
		}

		$grid_id = isset( $_POST['grid_id'] ) ? absint( wp_unslash( $_POST['grid_id'] ) ) : 0;
		$post = get_post( $grid_id );

		if ( ! $post || EIC_POST_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $grid_id ) ) {
			wp_send_json_error();
		}

		$grid = new EIC_Grid( $post );
		$grid_data = $grid->get_data();
		$grid_data['name'] = $this->get_collage_title( $post );

		wp_send_json_success( array(
			'grid'             => $grid_data,
			'customLayoutHtml' => $this->get_custom_layout_html( $grid_id, $grid_data ),
		) );
	}

	private function get_collage_title_from_grid_data( $grid_data, $grid_id )
	{
		$name = isset( $grid_data['name'] ) && is_scalar( $grid_data['name'] ) ? sanitize_text_field( $grid_data['name'] ) : '';
		$name = trim( $name );

		return '' === $name ? $this->get_default_collage_title( $grid_id ) : $name;
	}

	private function get_collage_title( $post )
	{
		return $post->post_title ? html_entity_decode( $post->post_title, ENT_QUOTES, get_bloginfo( 'charset' ) ) : $this->get_default_collage_title( $post->ID );
	}

	private function get_default_collage_title( $grid_id )
	{
		return sprintf( __( 'Collage #%d', 'easy-image-collage' ), $grid_id );
	}

	private function get_custom_layout_html( $grid_id, $grid_data )
	{
		if ( ! isset( $grid_data['layout'] ) || ! is_array( $grid_data['layout'] ) ) {
			return '';
		}

		$layout = $grid_data['layout'];
		$layout['name'] = 'custom-' . $grid_id;

		return EasyImageCollage::get()->helper( 'layouts' )->draw_layout( $layout, false, true );
	}

	public function ajax_image_collage_preview()
	{
		$preview = '';

		if( check_ajax_referer( 'eic_image_collage', 'security', false ) )
		{
			$grid_id = isset( $_POST['grid_id'] ) ? intval( $_POST['grid_id'] ) : 0;

			$post = get_post( $grid_id );

			$preview .= '<span contentEditable="false" style="font-weight: bold;" data-eic-grid="' . esc_attr( $grid_id ) . '">Easy Image Collage ' . esc_html( $grid_id ) . '</span>';
			$preview .= '<span contentEditable="false" style="float: right; color: darkred;" data-eic-grid-remove="' . esc_attr( $grid_id ) . '">' . esc_html__( 'remove', 'easy-image-collage' ) . '</span>';
			$preview .= '<br/><br/>';

			if( !is_null( $post ) && $post->post_type == EIC_POST_TYPE ) {
				$grid = new EIC_Grid( $grid_id );
				$images = $grid->images();

				if( !empty( $images ) ) {
					foreach( $images as $id => $image ) {
						if( $image ) {
							if ( isset( $image['type'] ) && 'text' === $image['type'] ) {
								$preview .= '<span contentEditable="false" style="display: inline-block; min-width: 100px; min-height: 60px; padding: 8px; border: 1px solid #dddddd;" data-eic-grid="' . esc_attr( $grid_id ) . '">' . wp_kses_post( isset( $image['text_content'] ) ? $image['text_content'] : '' ) . '</span>';
								continue;
							}

							$thumb = wp_get_attachment_image_src( absint( $image['attachment_id'] ), array( 100, 100 ) );

							if( $thumb ) {
								$preview .= '<span contentEditable="false" style="display: inline-block; background-image: url(\'' . esc_url( $thumb[0] ) . '\'); background-size: ' . intval( $thumb[1] ) . 'px ' . intval( $thumb[2] ) . 'px; width: ' . intval( $thumb[1] ) . 'px; height: ' . intval( $thumb[2] ) . 'px;" data-eic-grid="' . esc_attr( $grid_id ) . '">&nbsp;</span>';
							}
						}
					}
				} else {
					$preview .= '<span contentEditable="false" data-eic-grid="' . esc_attr( $grid_id ) . '">' . esc_html__( 'No images in this collage yet', 'easy-image-collage' ) . '</span>';
				}
			}
		}

		echo $preview;
		die();
	}

    public function url()
    {
        $ajaxurl = admin_url( 'admin-ajax.php' );
        $ajaxurl .= '?eic_ajax=1';

        // WPML AJAX Localization Fix
        global $sitepress;
        if( isset( $sitepress) ) {
            $ajaxurl .= '&lang='.$sitepress->get_current_language();
        }

        return $ajaxurl;
    }
}
