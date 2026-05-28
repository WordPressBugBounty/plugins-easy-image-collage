<?php

class EIC_Admin_Menu {

	const PAGE_SLUG = 'eic_collages';
	const PREMIUM_PAGE_SLUG = 'eic_premium_version';
	const REST_NAMESPACE = 'easy-image-collage/v1';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 5 );
		add_action( 'admin_menu', array( $this, 'add_premium_submenu_page' ), 99 );
		add_action( 'admin_init', array( $this, 'redirect_legacy_settings_page' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	public static function is_collages_screen( $screen = null ) {
		if ( is_null( $screen ) ) {
			$screen = get_current_screen();
		}

		return $screen && (
			'toplevel_page_' . self::PAGE_SLUG === $screen->id
			|| false !== strpos( $screen->id, '_page_' . self::PAGE_SLUG )
		);
	}

	public static function get_collages_url() {
		return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}

	public static function get_settings_url() {
		return admin_url( 'admin.php?page=eic_settings' );
	}

	public function add_menu_page() {
		add_menu_page(
			__( 'Easy Image Collage', 'easy-image-collage' ),
			__( 'Image Collage', 'easy-image-collage' ),
			'edit_posts',
			self::PAGE_SLUG,
			array( $this, 'collages_page' ),
			'dashicons-format-gallery',
			58
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Collages', 'easy-image-collage' ),
			__( 'Collages', 'easy-image-collage' ),
			'edit_posts',
			self::PAGE_SLUG,
			array( $this, 'collages_page' )
		);
	}

	public function add_premium_submenu_page() {
		if ( EasyImageCollage::is_premium_active() ) {
			return;
		}

		$campaign = EasyImageCollage::get()->helper( 'marketing' )->get_campaign();
		$menu_title = false === $campaign ? __( 'Premium', 'easy-image-collage' ) : __( 'Premium ~ now at a 30% discount!', 'easy-image-collage' );

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Premium Version', 'easy-image-collage' ),
			$menu_title,
			'manage_options',
			self::PREMIUM_PAGE_SLUG,
			array( $this, 'premium_page' )
		);
	}

	public function redirect_legacy_settings_page() {
		global $pagenow;

		if ( 'options-general.php' !== $pagenow ) {
			return;
		}

		if ( ! isset( $_GET['page'] ) || 'eic_settings' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		wp_safe_redirect( self::get_settings_url() );
		exit;
	}

	public function collages_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to view image collages.', 'easy-image-collage' ) );
		}

		EasyImageCollage::get()->helper( 'usage_index' )->maybe_rebuild_index();

		echo '<div class="wrap">';
		echo '<div id="eic-admin-manage">' . esc_html__( 'Loading...', 'easy-image-collage' ) . '</div>';
		echo '</div>';

		EasyImageCollage::get()->helper( 'shortcode_button' )->render_modal_content();
	}

	public function premium_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'easy-image-collage' ) );
		}

		$premium_url = 'https://bootstrapped.ventures/easy-image-collage/';
		$marketing = EasyImageCollage::get()->helper( 'marketing' );
		$campaign = $marketing->get_campaign();

		if ( false !== $campaign ) {
			$premium_url = $marketing->get_campaign_url();
		}

		$features = array(
			__( 'Add captions to images in your collages.', 'easy-image-collage' ),
			__( 'Set custom links for individual images.', 'easy-image-collage' ),
			__( 'Create your own custom collage layouts.', 'easy-image-collage' ),
			__( 'Adjust borders and show image sizes for pixel-perfect collages.', 'easy-image-collage' ),
			__( 'Use image manipulation and Instagram-like filters.', 'easy-image-collage' ),
			__( 'Add text frames to your collages.', 'easy-image-collage' ),
		);

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Premium Version', 'easy-image-collage' ) . '</h1>';

		if ( false !== $campaign ) {
			echo '<div class="notice notice-success inline">';
			echo '<p><strong>' . esc_html( $campaign['page_title'] ) . '</strong></p>';
			echo '<p>' . wp_kses_post( $campaign['page_text'] ) . '</p>';
			echo '</div>';
		}

		echo '<p>' . esc_html__( 'Upgrade to Easy Image Collage Premium to unlock more layout and styling options for your image collages.', 'easy-image-collage' ) . '</p>';
		echo '<h2>' . esc_html__( 'Extra Premium Features', 'easy-image-collage' ) . '</h2>';
		echo '<ul style="list-style: disc; margin-left: 20px;">';

		foreach ( $features as $feature ) {
			echo '<li>' . esc_html( $feature ) . '</li>';
		}

		echo '</ul>';
		echo '<p><a class="button button-primary" href="' . esc_url( $premium_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( false === $campaign ? __( 'Learn More about Easy Image Collage Premium', 'easy-image-collage' ) : $campaign['notice_text'] ) . '</a></p>';
		echo '</div>';
	}

	public function register_rest_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/manage/collages',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_get_collages' ),
				'permission_callback' => array( $this, 'can_manage_collages' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/manage/collages/previews',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_get_collage_previews' ),
				'permission_callback' => array( $this, 'can_manage_collages' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/manage/collages/search',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_search_collages' ),
				'permission_callback' => array( $this, 'can_manage_collages' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/manage/collages/duplicate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_duplicate_collage' ),
				'permission_callback' => array( $this, 'can_manage_collages' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/manage/collages/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_delete_collage' ),
				'permission_callback' => array( $this, 'can_manage_collages' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/manage/collages/(?P<id>\d+)/restore',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_restore_collage' ),
				'permission_callback' => array( $this, 'can_manage_collages' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/manage/collages/(?P<id>\d+)/permanent',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_permanently_delete_collage' ),
				'permission_callback' => array( $this, 'can_manage_collages' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	public function can_manage_collages() {
		return current_user_can( 'edit_posts' );
	}

	public function rest_get_collages( $request ) {
		EasyImageCollage::get()->helper( 'usage_index' )->maybe_rebuild_index();

		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();

		$page = isset( $params['page'] ) ? max( 0, intval( $params['page'] ) ) : 0;
		$page_size = isset( $params['pageSize'] ) ? intval( $params['pageSize'] ) : 20;
		$page_size = min( 100, max( 5, $page_size ) );
		$sorted = isset( $params['sorted'] ) && is_array( $params['sorted'] ) ? $params['sorted'] : array( array( 'id' => 'created', 'desc' => true ) );
		$filtered = isset( $params['filtered'] ) && is_array( $params['filtered'] ) ? $params['filtered'] : array();

		$status = isset( $params['status'] ) && 'trash' === $params['status'] ? 'trash' : 'active';
		$total = $this->get_collage_total_for_status( $status );
		$query_args = $this->get_collage_query_args( $status, $page, $page_size, $sorted, $filtered );

		if ( empty( $query_args ) ) {
			return array(
				'rows'     => array(),
				'total'    => $total,
				'filtered' => 0,
				'pages'    => 0,
				'status'   => $status,
				'counts'   => $this->get_collage_counts(),
			);
		}

		add_filter( 'posts_clauses', array( $this, 'filter_image_count_orderby' ), 10, 2 );
		$query = new WP_Query( $query_args );
		remove_filter( 'posts_clauses', array( $this, 'filter_image_count_orderby' ), 10 );

		$posts = $query->posts;
		$usage = $this->get_usage_for_grid_ids( wp_list_pluck( $posts, 'ID' ) );
		$rows = array();

		foreach ( $posts as $post ) {
			$rows[] = $this->get_collage_row( $post, isset( $usage[ $post->ID ] ) ? $usage[ $post->ID ] : array() );
		}

		$filtered_count = absint( $query->found_posts );

		return array(
			'rows'     => array_values( $rows ),
			'total'    => $total,
			'filtered' => $filtered_count,
			'pages'    => $page_size ? ceil( $filtered_count / $page_size ) : 0,
			'status'   => $status,
			'counts'   => $this->get_collage_counts(),
		);
	}

	public function rest_get_collage_previews( $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();
		$ids = isset( $params['ids'] ) && is_array( $params['ids'] ) ? $params['ids'] : array();
		$ids = array_slice( array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ), 0, 100 );
		$previews = array();

		foreach ( $ids as $grid_id ) {
			$post = get_post( $grid_id );

			if ( ! $post || EIC_POST_TYPE !== $post->post_type ) {
				continue;
			}

			$grid = new EIC_Grid( $post );
			$images = $this->get_grid_images( $grid->get_data() );

			$previews[ $grid_id ] = array(
				'previewHtml' => count( $images ) ? $this->get_preview_html( $grid_id ) : '',
			);
		}

		return array(
			'previews' => $previews,
		);
	}

	public function rest_search_collages( $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();
		$search = isset( $params['search'] ) ? strtolower( trim( sanitize_text_field( (string) $params['search'] ) ) ) : '';
		$query_args = array(
			'post_type'      => EIC_POST_TYPE,
			'post_status'    => $this->get_collage_post_statuses( 'active' ),
			'posts_per_page' => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		);

		if ( '' !== $search ) {
			$matching_ids = array_values( array_unique( array_merge(
				$this->get_collage_ids_matching_title_filter( 'active', $search ),
				$this->get_collage_ids_matching_id_filter( 'active', $search )
			) ) );

			if ( empty( $matching_ids ) ) {
				return array(
					'rows' => array(),
				);
			}

			$query_args['post__in'] = $matching_ids;
		}

		$query = new WP_Query( $query_args );
		$rows = array();

		foreach ( $query->posts as $post ) {
			$rows[] = $this->get_collage_picker_row( $post );
		}

		return array(
			'rows' => $rows,
		);
	}

	public function rest_duplicate_collage( $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();
		$grid_id = isset( $params['id'] ) ? absint( $params['id'] ) : 0;

		$post = get_post( $grid_id );
		if ( ! $post || EIC_POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'eic_collage_not_found', __( 'The image collage could not be found.', 'easy-image-collage' ), array( 'status' => 404 ) );
		}

		if ( ! current_user_can( 'edit_post', $grid_id ) || ! current_user_can( 'publish_posts' ) ) {
			return new WP_Error( 'eic_collage_duplicate_forbidden', __( 'You do not have permission to duplicate this image collage.', 'easy-image-collage' ), array( 'status' => 403 ) );
		}

		$new_grid_id = wp_insert_post(
			array(
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
				'post_type'    => EIC_POST_TYPE,
				'post_content' => '',
				'post_title'   => sprintf( __( 'Copy of %s', 'easy-image-collage' ), $this->get_collage_title( $post ) ),
			),
			true
		);

		if ( is_wp_error( $new_grid_id ) ) {
			return $new_grid_id;
		}

		$grid_data = get_post_meta( $grid_id, 'eic_grid_data', true );
		if ( is_array( $grid_data ) ) {
			$grid = new EIC_Grid( $new_grid_id );
			$grid->update_data( $grid_data );
		}

		return $this->get_collage_row( get_post( $new_grid_id ), array() );
	}

	public function rest_delete_collage( $request ) {
		$grid_id = absint( $request['id'] );

		$post = get_post( $grid_id );
		if ( ! $post || EIC_POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'eic_collage_not_found', __( 'The image collage could not be found.', 'easy-image-collage' ), array( 'status' => 404 ) );
		}

		if ( ! current_user_can( 'delete_post', $grid_id ) ) {
			return new WP_Error( 'eic_collage_delete_forbidden', __( 'You do not have permission to delete this image collage.', 'easy-image-collage' ), array( 'status' => 403 ) );
		}

		wp_trash_post( $grid_id );

		return array(
			'success' => true,
			'id'      => $grid_id,
		);
	}

	public function rest_restore_collage( $request ) {
		$grid_id = absint( $request['id'] );

		$post = get_post( $grid_id );
		if ( ! $post || EIC_POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'eic_collage_not_found', __( 'The image collage could not be found.', 'easy-image-collage' ), array( 'status' => 404 ) );
		}

		if ( ! current_user_can( 'edit_post', $grid_id ) ) {
			return new WP_Error( 'eic_collage_restore_forbidden', __( 'You do not have permission to restore this image collage.', 'easy-image-collage' ), array( 'status' => 403 ) );
		}

		if ( 'trash' === $post->post_status ) {
			wp_untrash_post( $grid_id );
		}

		return array(
			'success' => true,
			'id'      => $grid_id,
		);
	}

	public function rest_permanently_delete_collage( $request ) {
		$grid_id = absint( $request['id'] );

		$post = get_post( $grid_id );
		if ( ! $post || EIC_POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'eic_collage_not_found', __( 'The image collage could not be found.', 'easy-image-collage' ), array( 'status' => 404 ) );
		}

		if ( ! current_user_can( 'delete_post', $grid_id ) ) {
			return new WP_Error( 'eic_collage_delete_forbidden', __( 'You do not have permission to delete this image collage.', 'easy-image-collage' ), array( 'status' => 403 ) );
		}

		wp_delete_post( $grid_id, true );

		return array(
			'success' => true,
			'id'      => $grid_id,
		);
	}

	private function get_collage_counts() {
		$counts = wp_count_posts( EIC_POST_TYPE );

		return array(
			'trash' => isset( $counts->trash ) ? absint( $counts->trash ) : 0,
		);
	}

	private function get_collage_total_for_status( $status ) {
		$counts = wp_count_posts( EIC_POST_TYPE );
		$total = 0;

		foreach ( $this->get_collage_post_statuses( $status ) as $post_status ) {
			$total += isset( $counts->{$post_status} ) ? absint( $counts->{$post_status} ) : 0;
		}

		return $total;
	}

	private function get_collage_post_statuses( $status ) {
		return 'trash' === $status
			? array( 'trash' )
			: array_values( array_diff( get_post_stati( array(), 'names' ), array( 'trash', 'auto-draft', 'inherit' ) ) );
	}

	private function get_collage_query_args( $status, $page, $page_size, $sorted, $filtered ) {
		$filter_args = $this->get_collage_filter_args( $status, $filtered );

		if ( isset( $filter_args['empty'] ) && $filter_args['empty'] ) {
			return array();
		}

		$sort_args = $this->get_collage_sort_args( $sorted );
		$query_args = array_merge(
			array(
				'post_type'      => EIC_POST_TYPE,
				'post_status'    => $this->get_collage_post_statuses( $status ),
				'posts_per_page' => $page_size,
				'paged'          => $page + 1,
			),
			$sort_args
		);

		if ( isset( $filter_args['post__in'] ) ) {
			$query_args['post__in'] = $filter_args['post__in'];
		}

		if ( isset( $filter_args['meta_query'] ) ) {
			$query_args['meta_query'] = $filter_args['meta_query'];
		}

		return $query_args;
	}

	private function get_collage_sort_args( $sorted ) {
		$sort = isset( $sorted[0] ) && is_array( $sorted[0] ) ? $sorted[0] : array( 'id' => 'created', 'desc' => true );
		$id = isset( $sort['id'] ) ? preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $sort['id'] ) : 'created';
		$order = isset( $sort['desc'] ) && $sort['desc'] ? 'DESC' : 'ASC';

		switch ( $id ) {
			case 'id':
			case 'shortcode':
				return array(
					'orderby' => 'ID',
					'order'   => $order,
				);
			case 'title':
				return array(
					'orderby' => 'title',
					'order'   => $order,
				);
			case 'modified':
				return array(
					'orderby' => 'modified',
					'order'   => $order,
				);
			case 'imageCount':
				return array(
					'orderby'                  => 'ID',
					'order'                    => 'DESC',
					'eic_orderby_image_count'  => true,
					'eic_image_count_order'    => $order,
				);
			case 'created':
			default:
				return array(
					'orderby' => 'date',
					'order'   => $order,
				);
		}
	}

	public function filter_image_count_orderby( $clauses, $query ) {
		if ( ! $query->get( 'eic_orderby_image_count' ) ) {
			return $clauses;
		}

		global $wpdb;

		$order = 'ASC' === $query->get( 'eic_image_count_order' ) ? 'ASC' : 'DESC';
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS eic_image_count_meta ON ({$wpdb->posts}.ID = eic_image_count_meta.post_id AND eic_image_count_meta.meta_key = '_eic_image_count')";
		$clauses['orderby'] = "CAST(COALESCE(eic_image_count_meta.meta_value, '0') AS UNSIGNED) {$order}, {$wpdb->posts}.ID DESC";

		return $clauses;
	}

	private function get_collage_filter_args( $status, $filtered ) {
		$possible_ids = null;
		$meta_query = array();

		foreach ( $filtered as $filter ) {
			if ( ! isset( $filter['id'] ) || ! array_key_exists( 'value', $filter ) ) {
				continue;
			}

			$id = preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $filter['id'] );
			$value = strtolower( trim( (string) $filter['value'] ) );

			if ( '' === $value || 'all' === $value ) {
				continue;
			}

			switch ( $id ) {
				case 'id':
				case 'shortcode':
					$possible_ids = $this->intersect_possible_ids( $possible_ids, $this->get_collage_ids_matching_id_filter( $status, $value ) );
					break;
				case 'title':
					$possible_ids = $this->intersect_possible_ids( $possible_ids, $this->get_collage_ids_matching_title_filter( $status, $value ) );
					break;
				case 'imageCount':
					$meta_query[] = array(
						'key'     => '_eic_image_count',
						'value'   => $value,
						'compare' => 'LIKE',
					);
					break;
				case 'usage':
					$possible_ids = $this->intersect_possible_ids( $possible_ids, $this->get_collage_ids_matching_usage_filter( $status, $value ) );
					break;
				case 'created':
				case 'modified':
					$possible_ids = $this->intersect_possible_ids( $possible_ids, $this->get_collage_ids_matching_date_filter( $status, $id, $value ) );
					break;
			}

			if ( is_array( $possible_ids ) && empty( $possible_ids ) ) {
				return array( 'empty' => true );
			}
		}

		$args = array();

		if ( is_array( $possible_ids ) ) {
			$args['post__in'] = $possible_ids;
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		return $args;
	}

	private function intersect_possible_ids( $current_ids, $next_ids ) {
		$next_ids = array_values( array_unique( array_filter( array_map( 'absint', $next_ids ) ) ) );

		if ( is_null( $current_ids ) ) {
			return $next_ids;
		}

		return array_values( array_intersect( $current_ids, $next_ids ) );
	}

	private function get_collage_ids_matching_id_filter( $status, $value ) {
		global $wpdb;

		$post_statuses = $this->get_collage_post_statuses( $status );
		$placeholders = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );
		$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ($placeholders) AND CAST(ID AS CHAR) LIKE %s";
		$args = array_merge( array( EIC_POST_TYPE ), $post_statuses, array( '%' . $wpdb->esc_like( $value ) . '%' ) );

		return array_map( 'absint', $wpdb->get_col( $wpdb->prepare( $sql, $args ) ) );
	}

	private function get_collage_ids_matching_title_filter( $status, $value ) {
		global $wpdb;

		$post_statuses = $this->get_collage_post_statuses( $status );
		$placeholders = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );
		$like = '%' . $wpdb->esc_like( $value ) . '%';
		$sql = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ($placeholders) AND (post_title LIKE %s OR post_title = '')";
		$args = array_merge( array( EIC_POST_TYPE ), $post_statuses, array( $like ) );
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
		$matching_grid_ids = array();

		foreach ( $rows as $row ) {
			if ( '' !== $row->post_title ) {
				$matching_grid_ids[] = absint( $row->ID );
				continue;
			}

			if ( false !== strpos( strtolower( $this->get_default_collage_title( $row->ID ) ), $value ) ) {
				$matching_grid_ids[] = absint( $row->ID );
			}
		}

		return $matching_grid_ids;
	}

	private function get_collage_ids_matching_date_filter( $status, $id, $value ) {
		global $wpdb;

		$date_field = 'modified' === $id ? 'post_modified' : 'post_date';
		$date_gmt_field = 'modified' === $id ? 'post_modified_gmt' : 'post_date_gmt';
		$post_statuses = $this->get_collage_post_statuses( $status );
		$placeholders = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );
		$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ($placeholders) AND ({$date_field} LIKE %s OR {$date_gmt_field} LIKE %s)";
		$like = '%' . $wpdb->esc_like( $value ) . '%';
		$args = array_merge( array( EIC_POST_TYPE ), $post_statuses, array( $like, $like ) );

		return array_map( 'absint', $wpdb->get_col( $wpdb->prepare( $sql, $args ) ) );
	}

	private function get_collage_ids_matching_usage_filter( $status, $value ) {
		$not_used_label = strtolower( __( 'Not used', 'easy-image-collage' ) );
		$matching_grid_ids = array();

		if ( false !== strpos( $not_used_label, $value ) ) {
			$matching_grid_ids = $this->get_unused_collage_ids( $status );
		}

		global $wpdb;

		$post_statuses = $this->get_collage_post_statuses( $status );
		$placeholders = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );
		$sql = "SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = %s AND p.post_status IN ($placeholders) AND pm.meta_key = %s";
		$args = array_merge( array( EIC_POST_TYPE ), $post_statuses, array( EIC_Usage_Index::GRID_META_POST_IDS ) );
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );

		foreach ( $rows as $row ) {
			$post_ids = maybe_unserialize( $row->meta_value );
			$post_ids = is_array( $post_ids ) ? array_filter( array_map( 'absint', $post_ids ) ) : array();

			foreach ( $post_ids as $post_id ) {
				$post = get_post( $post_id );

				if ( ! $post || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
					continue;
				}

				$post_type = get_post_type_object( $post->post_type );
				$type_label = $post_type ? $post_type->labels->singular_name : $post->post_type;
				$title = $post->post_title ? html_entity_decode( $post->post_title, ENT_QUOTES, get_bloginfo( 'charset' ) ) : sprintf( __( '#%d', 'easy-image-collage' ), $post->ID );
				$haystack = strtolower( implode( ' ', array( $title, $type_label, $post->post_status ) ) );

				if ( false !== strpos( $haystack, $value ) ) {
					$matching_grid_ids[] = absint( $row->post_id );
					break;
				}
			}
		}

		return array_values( array_unique( $matching_grid_ids ) );
	}

	private function get_unused_collage_ids( $status ) {
		global $wpdb;

		$post_statuses = $this->get_collage_post_statuses( $status );
		$placeholders = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );
		$sql = "SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = %s) WHERE p.post_type = %s AND p.post_status IN ($placeholders) AND pm.post_id IS NULL";
		$args = array_merge( array( EIC_Usage_Index::GRID_META_POST_IDS, EIC_POST_TYPE ), $post_statuses );

		return array_map( 'absint', $wpdb->get_col( $wpdb->prepare( $sql, $args ) ) );
	}

	private function get_collage_row( $post, $usage ) {
		$grid = new EIC_Grid( $post );
		$grid_data = $grid->get_data();
		$images = $this->get_grid_images( $grid_data );
		$this->maybe_update_collage_metrics( $post->ID, $images );
		$preview_images = array();

		foreach ( array_slice( $images, 0, 4 ) as $image ) {
			$url = $this->get_image_preview_url( $image );

			if ( $url ) {
				$preview_images[] = $url;
			}
		}

		$title = $this->get_collage_title( $post );
		$shortcode = '[easy-image-collage id="' . $post->ID . '"]';

		return array(
			'id'               => $post->ID,
			'title'            => $title,
			'shortcode'        => $shortcode,
			'imageCount'       => count( $images ),
			'previewImages'    => $preview_images,
			'previewHtml'      => '',
			'previewWidth'     => $grid->width(),
			'previewHeight'    => $grid->height(),
			'previewBorderWidth' => $grid->border_width(),
			'previewBorderColor' => $grid->border_color(),
			'previewBorderRadius' => $grid->border_radius(),
			'usage'            => array_values( $usage ),
			'usageCount'       => count( $usage ),
			'postStatus'       => $post->post_status,
			'isTrash'          => 'trash' === $post->post_status,
			'created'          => get_date_from_gmt( $post->post_date_gmt, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'createdRaw'       => $post->post_date_gmt,
			'modified'         => get_date_from_gmt( $post->post_modified_gmt, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'modifiedRaw'      => $post->post_modified_gmt,
			'gridData'         => $grid_data,
			'customLayoutHtml' => $this->get_custom_layout_html( $post->ID, $grid_data ),
			'canEdit'          => current_user_can( 'edit_post', $post->ID ),
			'canDuplicate'     => current_user_can( 'edit_post', $post->ID ) && current_user_can( 'publish_posts' ),
			'canDelete'        => current_user_can( 'delete_post', $post->ID ),
			'canRestore'       => current_user_can( 'edit_post', $post->ID ),
		);
	}

	private function get_collage_picker_row( $post ) {
		$grid = new EIC_Grid( $post );
		$grid_data = $grid->get_data();
		$images = $this->get_grid_images( $grid_data );
		$this->maybe_update_collage_metrics( $post->ID, $images );

		return array(
			'id'                 => $post->ID,
			'title'              => $this->get_collage_title( $post ),
			'imageCount'         => count( $images ),
			'previewHtml'        => '',
			'previewLoaded'      => false,
			'previewWidth'       => $grid->width(),
			'previewHeight'      => $grid->height(),
			'previewBorderWidth' => $grid->border_width(),
			'previewBorderColor' => $grid->border_color(),
			'previewBorderRadius' => $grid->border_radius(),
		);
	}

	private function get_collage_title( $post ) {
		return $post->post_title ? html_entity_decode( $post->post_title, ENT_QUOTES, get_bloginfo( 'charset' ) ) : $this->get_default_collage_title( $post->ID );
	}

	private function get_default_collage_title( $grid_id ) {
		return sprintf( __( 'Collage #%d', 'easy-image-collage' ), $grid_id );
	}

	private function maybe_update_collage_metrics( $grid_id, $images ) {
		$image_count = count( $images );
		$stored_image_count = get_post_meta( $grid_id, '_eic_image_count', true );

		if ( '' === $stored_image_count || intval( $stored_image_count ) !== $image_count ) {
			update_post_meta( $grid_id, '_eic_image_count', $image_count );
		}
	}

	private function get_custom_layout_html( $grid_id, $grid_data ) {
		if ( ! isset( $grid_data['layout'] ) || ! is_array( $grid_data['layout'] ) ) {
			return '';
		}

		$layout = $grid_data['layout'];
		$layout['name'] = 'custom-' . $grid_id;

		return EasyImageCollage::get()->helper( 'layouts' )->draw_layout( $layout, false, true );
	}

	private function get_preview_html( $grid_id ) {
		add_filter( 'eic_layouts_output_image', array( $this, 'add_preview_image_pin_opt_out' ), 10, 2 );
		add_filter( 'eic_layouts_output_pin_button', array( $this, 'suppress_preview_pin_button' ), 10, 2 );

		$preview_html = EasyImageCollage::get()->helper( 'shortcode' )->render_collage(
			array(
				'id' => $grid_id,
			),
			true
		);

		remove_filter( 'eic_layouts_output_pin_button', array( $this, 'suppress_preview_pin_button' ), 10 );
		remove_filter( 'eic_layouts_output_image', array( $this, 'add_preview_image_pin_opt_out' ), 10 );

		return $preview_html;
	}

	public function add_preview_image_pin_opt_out( $image_html, $image ) {
		if ( false !== strpos( $image_html, 'data-pin-nopin=' ) ) {
			return $image_html;
		}

		return str_replace( '<img ', '<img data-pin-nopin="true" data-pin-no-hover="true" ', $image_html );
	}

	public function suppress_preview_pin_button( $pin_button, $image ) {
		return '';
	}

	private function get_grid_images( $grid_data ) {
		$images = isset( $grid_data['images'] ) && is_array( $grid_data['images'] ) ? $grid_data['images'] : array();

		return array_values( array_filter( $images ) );
	}

	private function get_image_preview_url( $image ) {
		if ( isset( $image['attachment_id'] ) ) {
			$thumb = wp_get_attachment_image_url( $image['attachment_id'], 'thumbnail' );

			if ( $thumb ) {
				return $thumb;
			}
		}

		if ( isset( $image['attachment_thumb'] ) && $image['attachment_thumb'] ) {
			return $image['attachment_thumb'];
		}

		return isset( $image['attachment_url'] ) ? $image['attachment_url'] : '';
	}

	private function get_usage_for_grid_ids( $grid_ids ) {
		return EasyImageCollage::get()->helper( 'usage_index' )->get_usage_for_grid_ids( $grid_ids );
	}
}
