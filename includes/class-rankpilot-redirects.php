<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RankPilot_Redirects {

	private static $table_name = null;

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_redirect' ), 1 );
		add_action( 'save_post',         array( $this, 'detect_slug_change' ), 10, 3 );
		add_action( 'wp_ajax_rp_seo_save_redirect',   array( $this, 'ajax_save_redirect' ) );
		add_action( 'wp_ajax_rp_seo_delete_redirect',  array( $this, 'ajax_delete_redirect' ) );
		add_action( 'wp_ajax_rp_seo_get_redirects',    array( $this, 'ajax_get_redirects' ) );
	}

	public static function get_table_name() {
		global $wpdb;
		if ( null === self::$table_name ) {
			self::$table_name = $wpdb->prefix . 'rp_seo_redirects';
		}
		return self::$table_name;
	}

	public static function create_table() {
		global $wpdb;
		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source_url varchar(500) NOT NULL DEFAULT '',
			target_url varchar(500) NOT NULL DEFAULT '',
			redirect_type smallint(3) unsigned NOT NULL DEFAULT 301,
			hit_count bigint(20) unsigned NOT NULL DEFAULT 0,
			last_accessed datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY source_url (source_url(191))
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function handle_redirect() {
		if ( ! is_404() ) {
			return;
		}

		global $wpdb;
		$table      = self::get_table_name();
		$request    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path       = rtrim( parse_url( $request, PHP_URL_PATH ), '/' );
		$path_slash = $path . '/';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE source_url = %s OR source_url = %s LIMIT 1",
				$path,
				$path_slash
			)
		);

		if ( ! $row ) {
			return;
		}

		// Update hit count
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array(
				'hit_count'     => $row->hit_count + 1,
				'last_accessed' => current_time( 'mysql' ),
			),
			array( 'id' => $row->id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		$type = in_array( (int) $row->redirect_type, array( 301, 302, 307 ), true )
			? (int) $row->redirect_type
			: 301;

		wp_redirect( esc_url_raw( $row->target_url ), $type );
		exit;
	}

	/**
	 * Automatically create a redirect when a post slug changes.
	 */
	public function detect_slug_change( $post_id, $post, $update ) {
		if ( ! $update || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! in_array( $post->post_status, array( 'publish' ), true ) ) {
			return;
		}

		$old_permalink = get_post_meta( $post_id, '_rp_seo_old_permalink', true );
		$new_permalink = get_permalink( $post_id );

		if ( $old_permalink && $old_permalink !== $new_permalink ) {
			$old_path = rtrim( parse_url( $old_permalink, PHP_URL_PATH ), '/' );
			$new_path = $new_permalink;

			// Check if redirect already exists
			global $wpdb;
			$table = self::get_table_name();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$table} WHERE source_url = %s", $old_path )
			);

			if ( ! $exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert( $table, array(
					'source_url'    => $old_path,
					'target_url'    => $new_path,
					'redirect_type' => 301,
				), array( '%s', '%s', '%d' ) );
			}
		}

		update_post_meta( $post_id, '_rp_seo_old_permalink', $new_permalink );
	}

	public function ajax_save_redirect() {
		check_ajax_referer( 'rp_seo_redirects_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		global $wpdb;
		$table = self::get_table_name();

		$id          = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$source      = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$target      = isset( $_POST['target'] ) ? esc_url_raw( wp_unslash( $_POST['target'] ) ) : '';
		$type        = isset( $_POST['type'] ) ? absint( $_POST['type'] ) : 301;

		if ( ! $source || ! $target ) {
			wp_send_json_error( array( 'message' => __( 'Source and target URL are required.', 'rankpilot-seo' ) ) );
		}
		if ( ! in_array( $type, array( 301, 302, 307 ), true ) ) {
			$type = 301;
		}

		$source = rtrim( '/' . ltrim( $source, '/' ), '/' );

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table,
				array( 'source_url' => $source, 'target_url' => $target, 'redirect_type' => $type ),
				array( 'id' => $id ),
				array( '%s', '%s', '%d' ),
				array( '%d' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( $table,
				array( 'source_url' => $source, 'target_url' => $target, 'redirect_type' => $type ),
				array( '%s', '%s', '%d' )
			);
			$id = $wpdb->insert_id;
		}

		wp_send_json_success( array( 'id' => $id ) );
	}

	public function ajax_delete_redirect() {
		check_ajax_referer( 'rp_seo_redirects_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		global $wpdb;
		$id = absint( $_POST['id'] ?? 0 );
		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( self::get_table_name(), array( 'id' => $id ), array( '%d' ) );
		}
		wp_send_json_success();
	}

	public function ajax_get_redirects() {
		check_ajax_referer( 'rp_seo_redirects_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		global $wpdb;
		$table  = self::get_table_name();
		$page   = absint( $_POST['page'] ?? 1 );
		$limit  = 20;
		$offset = ( $page - 1 ) * $limit;
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		if ( $search ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE source_url LIKE %s OR target_url LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d",
					'%' . $wpdb->esc_like( $search ) . '%',
					'%' . $wpdb->esc_like( $search ) . '%',
					$limit,
					$offset
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
					$limit,
					$offset
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		wp_send_json_success( array( 'redirects' => $rows, 'total' => $total, 'pages' => ceil( $total / $limit ) ) );
	}
}
