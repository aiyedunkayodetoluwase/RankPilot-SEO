<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RankPilot_Admin {

	private $settings_pages = array();

	public function __construct() {
		add_action( 'admin_menu',           array( $this, 'register_menu' ) );
		add_action( 'admin_init',           array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices',         array( $this, 'maybe_show_flush_notice' ) );
		add_filter( 'plugin_action_links_' . RP_SEO_PLUGIN_BASE, array( $this, 'plugin_action_links' ) );
	}

	public function register_menu() {
		$this->settings_pages['dashboard'] = add_menu_page(
			__( 'RankPilot SEO', 'rankpilot-seo' ),
			__( 'RankPilot SEO', 'rankpilot-seo' ),
			'manage_options',
			'rankpilot-seo',
			array( $this, 'page_dashboard' ),
			'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="white" d="M10 2L2 7v11h6v-6h4v6h6V7z"/></svg>' ),
			59
		);

		$this->settings_pages['general'] = add_submenu_page(
			'rankpilot-seo',
			__( 'General', 'rankpilot-seo' ),
			__( 'General', 'rankpilot-seo' ),
			'manage_options',
			'rankpilot-seo',
			array( $this, 'page_dashboard' )
		);

		$this->settings_pages['social'] = add_submenu_page(
			'rankpilot-seo',
			__( 'Social / OG', 'rankpilot-seo' ),
			__( 'Social / OG', 'rankpilot-seo' ),
			'manage_options',
			'rankpilot-seo-social',
			array( $this, 'page_social' )
		);

		$this->settings_pages['sitemap'] = add_submenu_page(
			'rankpilot-seo',
			__( 'Sitemaps', 'rankpilot-seo' ),
			__( 'Sitemaps', 'rankpilot-seo' ),
			'manage_options',
			'rankpilot-seo-sitemap',
			array( $this, 'page_sitemap' )
		);

		$this->settings_pages['breadcrumbs'] = add_submenu_page(
			'rankpilot-seo',
			__( 'Breadcrumbs', 'rankpilot-seo' ),
			__( 'Breadcrumbs', 'rankpilot-seo' ),
			'manage_options',
			'rankpilot-seo-breadcrumbs',
			array( $this, 'page_breadcrumbs' )
		);

		$this->settings_pages['redirects'] = add_submenu_page(
			'rankpilot-seo',
			__( 'Redirects', 'rankpilot-seo' ),
			__( 'Redirects', 'rankpilot-seo' ),
			'manage_options',
			'rankpilot-seo-redirects',
			array( $this, 'page_redirects' )
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$this->settings_pages['woocommerce'] = add_submenu_page(
				'rankpilot-seo',
				__( 'WooCommerce SEO', 'rankpilot-seo' ),
				__( 'WooCommerce SEO', 'rankpilot-seo' ),
				'manage_options',
				'rankpilot-seo-woocommerce',
				array( $this, 'page_woocommerce' )
			);
		}
	}

	public function enqueue_assets( $hook_suffix ) {
		$our_hooks = array_values( $this->settings_pages );
		if ( ! in_array( $hook_suffix, $our_hooks, true ) ) {
			return;
		}
		wp_enqueue_style( 'rp-seo-admin', RP_SEO_PLUGIN_URL . 'admin/css/admin.css', array(), RP_SEO_VERSION );
		wp_enqueue_script( 'rp-seo-admin', RP_SEO_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery' ), RP_SEO_VERSION, true );
		wp_enqueue_media();
		wp_localize_script( 'rp-seo-admin', 'rpSeoAdmin', array(
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'redirectsNonce'  => wp_create_nonce( 'rp_seo_redirects_nonce' ),
			'restUrl'         => rest_url( 'rankpilot/v1/' ),
			'restNonce'       => wp_create_nonce( 'wp_rest' ),
			'selectImageText' => __( 'Select Image', 'rankpilot-seo' ),
			'useImageText'    => __( 'Use This Image', 'rankpilot-seo' ),
		) );
	}

	public function register_settings() {
		// General
		register_setting( 'rp_seo_general_group', 'rp_seo_general', array(
			'sanitize_callback' => array( $this, 'sanitize_general' ),
		) );

		// Social
		register_setting( 'rp_seo_social_group', 'rp_seo_social', array(
			'sanitize_callback' => array( $this, 'sanitize_social' ),
		) );

		// Sitemap
		register_setting( 'rp_seo_sitemap_group', 'rp_seo_sitemap', array(
			'sanitize_callback' => array( $this, 'sanitize_sitemap' ),
		) );

		// Breadcrumbs
		register_setting( 'rp_seo_breadcrumbs_group', 'rp_seo_breadcrumbs', array(
			'sanitize_callback' => array( $this, 'sanitize_breadcrumbs' ),
		) );

		// WooCommerce
		register_setting( 'rp_seo_woocommerce_group', 'rp_seo_woocommerce', array(
			'sanitize_callback' => array( $this, 'sanitize_woocommerce' ),
		) );
	}

	// ──────────────────────────────────────────
	// SANITIZE CALLBACKS
	// ──────────────────────────────────────────

	public function sanitize_general( $input ) {
		$allowed_seps = array( '&#8211;', '&#8212;', '&#124;', '&#183;', '&#8250;', '&#8249;', '-', '~', '&raquo;' );
		$out = array();
		$out['separator']            = in_array( $input['separator'] ?? '', $allowed_seps, true ) ? $input['separator'] : '&#8211;';
		$out['site_name']            = sanitize_text_field( $input['site_name'] ?? '' );
		$out['homepage_title']       = sanitize_text_field( $input['homepage_title'] ?? '' );
		$out['homepage_desc']        = sanitize_textarea_field( $input['homepage_desc'] ?? '' );
		$out['post_title_tpl']       = sanitize_text_field( $input['post_title_tpl'] ?? '%%title%% %%sep%% %%sitename%%' );
		$out['page_title_tpl']       = sanitize_text_field( $input['page_title_tpl'] ?? '%%title%% %%sep%% %%sitename%%' );
		$out['product_title_tpl']    = sanitize_text_field( $input['product_title_tpl'] ?? '%%title%% %%sep%% %%sitename%%' );
		$out['archive_title_tpl']    = sanitize_text_field( $input['archive_title_tpl'] ?? '%%term_title%% Archives %%sep%% %%sitename%%' );
		$out['noindex_archives']     = isset( $input['noindex_archives'] ) ? 1 : 0;
		$out['noindex_search']       = isset( $input['noindex_search'] ) ? 1 : 0;
		$out['noindex_404']          = isset( $input['noindex_404'] ) ? 1 : 0;
		$out['noindex_attachment']   = isset( $input['noindex_attachment'] ) ? 1 : 0;
		$out['remove_wp_generator']  = isset( $input['remove_wp_generator'] ) ? 1 : 0;
		$out['schema_type']          = in_array( $input['schema_type'] ?? '', array( 'Organization', 'Person' ), true ) ? $input['schema_type'] : 'Organization';
		$allowed_providers           = array( 'none', 'groq', 'gemini', 'huggingface', 'ollama', 'anthropic', 'openai' );
		$out['ai_provider']          = in_array( $input['ai_provider'] ?? '', $allowed_providers, true ) ? $input['ai_provider'] : 'none';
		$out['ai_api_key']           = sanitize_text_field( $input['ai_api_key'] ?? '' );
		$out['ai_model']             = sanitize_text_field( $input['ai_model'] ?? '' );
		$out['ollama_url']           = esc_url_raw( $input['ollama_url'] ?? 'http://localhost:11434' );

		// Flush rewrite rules after saving sitemap-related settings
		add_action( 'shutdown', 'flush_rewrite_rules' );
		return $out;
	}

	public function sanitize_social( $input ) {
		$out = array();
		$out['og_enabled']        = isset( $input['og_enabled'] ) ? 1 : 0;
		$out['twitter_enabled']   = isset( $input['twitter_enabled'] ) ? 1 : 0;
		$out['twitter_card_type'] = in_array( $input['twitter_card_type'] ?? '', array( 'summary', 'summary_large_image' ), true ) ? $input['twitter_card_type'] : 'summary_large_image';
		$out['twitter_site']      = sanitize_text_field( $input['twitter_site'] ?? '' );
		$out['facebook_url']      = esc_url_raw( $input['facebook_url'] ?? '' );
		$out['twitter_url']       = esc_url_raw( $input['twitter_url'] ?? '' );
		$out['linkedin_url']      = esc_url_raw( $input['linkedin_url'] ?? '' );
		$out['instagram_url']     = esc_url_raw( $input['instagram_url'] ?? '' );
		$out['youtube_url']       = esc_url_raw( $input['youtube_url'] ?? '' );
		$out['pinterest_url']     = esc_url_raw( $input['pinterest_url'] ?? '' );
		$out['og_default_image']  = esc_url_raw( $input['og_default_image'] ?? '' );
		$out['fb_app_id']         = sanitize_text_field( $input['fb_app_id'] ?? '' );
		$out['org_name']          = sanitize_text_field( $input['org_name'] ?? '' );
		$out['org_logo']          = esc_url_raw( $input['org_logo'] ?? '' );
		$out['person_name']       = sanitize_text_field( $input['person_name'] ?? '' );
		return $out;
	}

	public function sanitize_sitemap( $input ) {
		$out = array();
		$out['enabled']           = isset( $input['enabled'] ) ? 1 : 0;
		$out['include_post']      = isset( $input['include_post'] ) ? 1 : 0;
		$out['include_page']      = isset( $input['include_page'] ) ? 1 : 0;
		$out['include_product']   = isset( $input['include_product'] ) ? 1 : 0;
		$out['include_category']  = isset( $input['include_category'] ) ? 1 : 0;
		$out['include_post_tag']  = isset( $input['include_post_tag'] ) ? 1 : 0;
		$out['include_images']    = isset( $input['include_images'] ) ? 1 : 0;
		$out['posts_per_page']    = absint( $input['posts_per_page'] ?? 1000 );
		if ( $out['posts_per_page'] < 1 || $out['posts_per_page'] > 50000 ) {
			$out['posts_per_page'] = 1000;
		}
		$out['ping_google']       = isset( $input['ping_google'] ) ? 1 : 0;
		$out['ping_bing']         = isset( $input['ping_bing'] ) ? 1 : 0;
		$out['exclude_ids']       = sanitize_text_field( $input['exclude_ids'] ?? '' );
		$out['exclude_noindex']   = isset( $input['exclude_noindex'] ) ? 1 : 0;
		add_action( 'shutdown', 'flush_rewrite_rules' );
		return $out;
	}

	public function sanitize_breadcrumbs( $input ) {
		$out = array();
		$out['enabled']       = isset( $input['enabled'] ) ? 1 : 0;
		$out['separator']     = wp_kses_post( $input['separator'] ?? ' &rsaquo; ' );
		$out['home_label']    = sanitize_text_field( $input['home_label'] ?? 'Home' );
		$out['show_current']  = isset( $input['show_current'] ) ? 1 : 0;
		$out['bold_last']     = isset( $input['bold_last'] ) ? 1 : 0;
		$out['prefix']        = sanitize_text_field( $input['prefix'] ?? '' );
		$out['schema_enabled'] = isset( $input['schema_enabled'] ) ? 1 : 0;
		return $out;
	}

	public function sanitize_woocommerce( $input ) {
		$out = array();
		$out['product_schema']     = isset( $input['product_schema'] ) ? 1 : 0;
		$out['include_price']      = isset( $input['include_price'] ) ? 1 : 0;
		$out['include_stock']      = isset( $input['include_stock'] ) ? 1 : 0;
		$out['include_ratings']    = isset( $input['include_ratings'] ) ? 1 : 0;
		$out['og_product_gallery'] = isset( $input['og_product_gallery'] ) ? 1 : 0;
		$out['hide_cart_checkout'] = isset( $input['hide_cart_checkout'] ) ? 1 : 0;
		$out['hide_filter_pages']  = isset( $input['hide_filter_pages'] ) ? 1 : 0;
		$out['primary_category']   = isset( $input['primary_category'] ) ? 1 : 0;
		$out['breadcrumb_replace'] = isset( $input['breadcrumb_replace'] ) ? 1 : 0;
		return $out;
	}

	// ──────────────────────────────────────────
	// PAGE CALLBACKS
	// ──────────────────────────────────────────

	public function page_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once RP_SEO_PLUGIN_DIR . 'admin/views/settings-general.php';
	}

	public function page_social() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once RP_SEO_PLUGIN_DIR . 'admin/views/settings-social.php';
	}

	public function page_sitemap() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once RP_SEO_PLUGIN_DIR . 'admin/views/settings-sitemap.php';
	}

	public function page_breadcrumbs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once RP_SEO_PLUGIN_DIR . 'admin/views/settings-breadcrumbs.php';
	}

	public function page_redirects() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once RP_SEO_PLUGIN_DIR . 'admin/views/settings-redirects.php';
	}

	public function page_woocommerce() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once RP_SEO_PLUGIN_DIR . 'admin/views/settings-woocommerce.php';
	}

	public function plugin_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=rankpilot-seo' ) . '">' . __( 'Settings', 'rankpilot-seo' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function maybe_show_flush_notice() {
		if ( isset( $_GET['settings-updated'] ) && isset( $_GET['page'] ) && strpos( sanitize_key( $_GET['page'] ), 'rankpilot-seo' ) === 0 ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'RankPilot SEO settings saved.', 'rankpilot-seo' ) . '</p></div>';
		}
	}
}
