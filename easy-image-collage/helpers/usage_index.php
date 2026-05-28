<?php

class EIC_Usage_Index {

	const CONTENT_META_GRID_IDS = '_eic_used_grid_ids';
	const GRID_META_POST_IDS = '_eic_used_in_posts';
	const INDEX_VERSION_OPTION = 'eic_usage_index_version';

	private $supported_post_types = null;

	public function __construct() {
		add_action( 'save_post', array( $this, 'save_post_usage' ), 20, 3 );
		add_action( 'trashed_post', array( $this, 'remove_post_usage' ), 10, 1 );
		add_action( 'before_delete_post', array( $this, 'before_delete_post' ), 10, 1 );
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 20, 3 );
	}

	public function maybe_rebuild_index() {
		if ( EIC_VERSION === get_option( self::INDEX_VERSION_OPTION ) ) {
			return;
		}

		$this->rebuild_index();
	}

	public function save_post_usage( $post_id, $post, $update ) {
		if ( $this->should_skip_post_save( $post_id, $post ) ) {
			return;
		}

		if ( ! $this->is_indexable_post( $post ) ) {
			$this->remove_post_usage( $post_id );
			return;
		}

		$this->sync_post_usage( $post_id, $post );
	}

	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( ! $post || $this->should_skip_post_save( $post->ID, $post ) ) {
			return;
		}

		if ( in_array( $new_status, array( 'trash', 'auto-draft' ), true ) ) {
			$this->remove_post_usage( $post->ID );
			return;
		}

		if ( in_array( $old_status, array( 'trash', 'auto-draft' ), true ) && $this->is_indexable_post( $post ) ) {
			$this->sync_post_usage( $post->ID, $post );
		}
	}

	public function remove_post_usage( $post_id ) {
		$post_id = absint( $post_id );
		$previous_grid_ids = $this->get_content_grid_ids_meta( $post_id );

		foreach ( $previous_grid_ids as $grid_id ) {
			$this->remove_post_from_grid( $grid_id, $post_id );
		}

		delete_post_meta( $post_id, self::CONTENT_META_GRID_IDS );
	}

	public function before_delete_post( $post_id ) {
		$post = get_post( $post_id );

		if ( $post && EIC_POST_TYPE === $post->post_type ) {
			$this->remove_grid_usage( $post_id );
			return;
		}

		$this->remove_post_usage( $post_id );
	}

	public function get_usage_for_grid_ids( $grid_ids ) {
		$grid_ids = $this->sanitize_id_list( $grid_ids );
		$usage = array();

		foreach ( $grid_ids as $grid_id ) {
			$usage[ $grid_id ] = array();
		}

		if ( empty( $grid_ids ) ) {
			return $usage;
		}

		foreach ( $grid_ids as $grid_id ) {
			$post_ids = $this->get_grid_post_ids_meta( $grid_id );

			foreach ( $post_ids as $post_id ) {
				$post = get_post( $post_id );

				if ( ! $post || ! $this->is_indexable_post( $post ) ) {
					continue;
				}

				$post_type = get_post_type_object( $post->post_type );
				$type_label = $post_type ? $post_type->labels->singular_name : $post->post_type;
				$title = $post->post_title ? html_entity_decode( $post->post_title, ENT_QUOTES, get_bloginfo( 'charset' ) ) : sprintf( __( '#%d', 'easy-image-collage' ), $post->ID );

				$usage[ $grid_id ][] = array(
					'id'     => $post->ID,
					'title'  => $title,
					'type'   => $type_label,
					'status' => $post->post_status,
					'url'    => get_edit_post_link( $post->ID, '' ),
				);
			}
		}

		return $usage;
	}

	private function rebuild_index() {
		global $wpdb;

		delete_metadata( 'post', 0, self::CONTENT_META_GRID_IDS, '', true );
		delete_metadata( 'post', 0, self::GRID_META_POST_IDS, '', true );

		$post_types = $this->get_supported_post_types();

		if ( empty( $post_types ) ) {
			update_option( self::INDEX_VERSION_OPTION, EIC_VERSION, false );
			return;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$sql = "SELECT ID, post_type, post_status, post_content FROM {$wpdb->posts} WHERE post_type IN ($placeholders) AND post_status NOT IN ('trash', 'auto-draft') AND post_content LIKE %s";
		$query_args = array_merge( $post_types, array( '%' . $wpdb->esc_like( 'easy-image-collage' ) . '%' ) );
		$posts = $wpdb->get_results( $wpdb->prepare( $sql, $query_args ) );

		foreach ( $posts as $post ) {
			$this->sync_post_usage( $post->ID, $post );
		}

		update_option( self::INDEX_VERSION_OPTION, EIC_VERSION, false );
	}

	private function sync_post_usage( $post_id, $post ) {
		$post_id = absint( $post_id );
		$previous_grid_ids = $this->get_content_grid_ids_meta( $post_id );
		$current_grid_ids = $this->get_grids_in_post_content( $post );

		foreach ( array_diff( $previous_grid_ids, $current_grid_ids ) as $grid_id ) {
			$this->remove_post_from_grid( $grid_id, $post_id );
		}

		foreach ( array_diff( $current_grid_ids, $previous_grid_ids ) as $grid_id ) {
			$this->add_post_to_grid( $grid_id, $post_id );
		}

		if ( empty( $current_grid_ids ) ) {
			delete_post_meta( $post_id, self::CONTENT_META_GRID_IDS );
		} else {
			update_post_meta( $post_id, self::CONTENT_META_GRID_IDS, $current_grid_ids );
		}
	}

	private function remove_grid_usage( $grid_id ) {
		$grid_id = absint( $grid_id );
		$post_ids = $this->get_grid_post_ids_meta( $grid_id );

		foreach ( $post_ids as $post_id ) {
			$grid_ids = array_values( array_diff( $this->get_content_grid_ids_meta( $post_id ), array( $grid_id ) ) );

			if ( empty( $grid_ids ) ) {
				delete_post_meta( $post_id, self::CONTENT_META_GRID_IDS );
			} else {
				update_post_meta( $post_id, self::CONTENT_META_GRID_IDS, $grid_ids );
			}
		}

		delete_post_meta( $grid_id, self::GRID_META_POST_IDS );
	}

	private function add_post_to_grid( $grid_id, $post_id ) {
		if ( ! $this->is_grid_post( $grid_id ) ) {
			return;
		}

		$post_ids = $this->get_grid_post_ids_meta( $grid_id );

		if ( ! in_array( $post_id, $post_ids, true ) ) {
			$post_ids[] = $post_id;
			update_post_meta( $grid_id, self::GRID_META_POST_IDS, $this->sanitize_id_list( $post_ids ) );
		}
	}

	private function remove_post_from_grid( $grid_id, $post_id ) {
		$post_ids = $this->get_grid_post_ids_meta( $grid_id );
		$post_ids = array_values( array_diff( $post_ids, array( absint( $post_id ) ) ) );

		if ( empty( $post_ids ) ) {
			delete_post_meta( $grid_id, self::GRID_META_POST_IDS );
		} else {
			update_post_meta( $grid_id, self::GRID_META_POST_IDS, $post_ids );
		}
	}

	private function get_grids_in_post_content( $post ) {
		if ( ! $post || ! isset( $post->post_content ) ) {
			return array();
		}

		$shortcode_button = EasyImageCollage::get()->helper( 'shortcode_button' );

		$grid_ids = $this->sanitize_id_list( $shortcode_button->get_grids_in_content( $post->post_content ) );

		return array_values( array_filter( $grid_ids, array( $this, 'is_grid_post' ) ) );
	}

	private function get_content_grid_ids_meta( $post_id ) {
		return $this->sanitize_id_list( get_post_meta( $post_id, self::CONTENT_META_GRID_IDS, true ) );
	}

	private function get_grid_post_ids_meta( $grid_id ) {
		return $this->sanitize_id_list( get_post_meta( $grid_id, self::GRID_META_POST_IDS, true ) );
	}

	private function sanitize_id_list( $ids ) {
		if ( ! is_array( $ids ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	}

	private function is_grid_post( $grid_id ) {
		$post = get_post( $grid_id );

		return $post && EIC_POST_TYPE === $post->post_type;
	}

	private function is_indexable_post( $post ) {
		if ( ! $post || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
			return false;
		}

		return in_array( $post->post_type, $this->get_supported_post_types(), true );
	}

	private function should_skip_post_save( $post_id, $post ) {
		if ( ! $post || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return true;
		}

		return EIC_POST_TYPE === $post->post_type || 'attachment' === $post->post_type;
	}

	private function get_supported_post_types() {
		if ( is_array( $this->supported_post_types ) ) {
			return $this->supported_post_types;
		}

		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = array_values( array_diff( $post_types, array( 'attachment', EIC_POST_TYPE ) ) );

		if ( post_type_exists( 'wp_block' ) ) {
			$post_types[] = 'wp_block';
		}

		$this->supported_post_types = array_values( array_unique( $post_types ) );

		return $this->supported_post_types;
	}
}
