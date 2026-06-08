<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RankPilot_Plugin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 5 );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'rankpilot-seo', false, dirname( RP_SEO_PLUGIN_BASE ) . '/languages' );
	}

	public function init() {
		$this->load_classes();
		$this->boot_classes();
		// Schedule flush AFTER init so all rewrite rules are registered first
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 999 );
	}

	/**
	 * Flush rewrite rules once whenever the plugin version changes.
	 * Runs late on 'init' so all rewrite rules are registered before flushing.
	 */
	public function maybe_flush_rewrite_rules() {
		$flushed_version = get_option( 'rp_seo_flushed_version', '' );
		if ( $flushed_version !== RP_SEO_VERSION ) {
			update_option( 'rp_seo_flushed_version', RP_SEO_VERSION );
			flush_rewrite_rules( false );
		}
	}

	private function load_classes() {
		require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-admin.php';
		require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-meta-box.php';
		require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-public.php';
		require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-sitemap.php';
		require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-breadcrumbs.php';
		require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-schema.php';
		require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-redirects.php';
		require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-rest-api.php';

		if ( class_exists( 'WooCommerce' ) ) {
			require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-woocommerce.php';
		}
	}

	private function boot_classes() {
		new RankPilot_Admin();
		new RankPilot_Meta_Box();
		new RankPilot_Public();
		new RankPilot_Sitemap();
		new RankPilot_Breadcrumbs();
		new RankPilot_Schema();
		new RankPilot_Redirects();
		new RankPilot_REST_API();

		if ( class_exists( 'WooCommerce' ) ) {
			new RankPilot_WooCommerce();
		}
	}

	/**
	 * Helper: get a plugin option with fallback defaults.
	 */
	public static function get_option( $group, $key = null, $default = '' ) {
		$options = get_option( $group, array() );
		if ( null === $key ) {
			return $options;
		}
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	/**
	 * Helper: resolve a title template replacing %%tokens%%.
	 */
	public static function resolve_title_template( $template, $post = null ) {
		$general  = get_option( 'rp_seo_general', array() );
		$sep      = isset( $general['separator'] ) ? $general['separator'] : '&#8211;';
		$sitename = isset( $general['site_name'] ) ? $general['site_name'] : get_bloginfo( 'name' );

		$title     = $post ? get_the_title( $post ) : '';
		$term_title = '';
		if ( is_tax() || is_category() || is_tag() ) {
			$term       = get_queried_object();
			$term_title = $term ? $term->name : '';
		}

		$tokens = array(
			'%%title%%'      => $title,
			'%%sitename%%'   => $sitename,
			'%%sep%%'        => html_entity_decode( $sep ),
			'%%term_title%%' => $term_title,
			'%%page%%'       => get_query_var( 'paged' ) > 1 ? sprintf( __( 'Page %d', 'rankpilot-seo' ), get_query_var( 'paged' ) ) : '',
		);

		return trim( str_replace( array_keys( $tokens ), array_values( $tokens ), $template ) );
	}

	/**
	 * Return all public post types eligible for SEO meta boxes.
	 */
	public static function get_seo_post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		return array_values( array_diff( $post_types, array( 'attachment' ) ) );
	}
}
