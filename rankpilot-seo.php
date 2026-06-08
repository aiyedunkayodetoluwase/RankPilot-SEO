<?php
/**
 * Plugin Name:       RankPilot SEO
 * Plugin URI:        https://rankpilot.io/
 * Description:       Full-featured SEO plugin: meta optimization, XML sitemaps, breadcrumbs, structured data/schema, redirect manager, social previews, readability analysis, and WooCommerce SEO.
 * Version:           1.0.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            RankPilot
 * Author URI:        https://rankpilot.io/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rankpilot-seo
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RP_SEO_VERSION',       '1.0.5' );
define( 'RP_SEO_PLUGIN_DIR',    plugin_dir_path( __FILE__ ) );
define( 'RP_SEO_PLUGIN_URL',    plugin_dir_url( __FILE__ ) );
define( 'RP_SEO_PLUGIN_BASE',   plugin_basename( __FILE__ ) );
define( 'RP_SEO_MIN_WP',        '6.0' );
define( 'RP_SEO_MIN_PHP',       '7.4' );

// ──────────────────────────────────────────────
// ACTIVATION
// ──────────────────────────────────────────────
register_activation_hook( __FILE__, 'rp_seo_activate' );
function rp_seo_activate() {
	require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-redirects.php';
	RankPilot_Redirects::create_table();

	add_option( 'rp_seo_version', RP_SEO_VERSION );

	add_option( 'rp_seo_general', array(
		'separator'            => '&#8211;',
		'site_name'            => get_bloginfo( 'name' ),
		'homepage_title'       => '',
		'homepage_desc'        => '',
		'post_title_tpl'       => '%%title%% %%sep%% %%sitename%%',
		'page_title_tpl'       => '%%title%% %%sep%% %%sitename%%',
		'product_title_tpl'    => '%%title%% %%sep%% %%sitename%%',
		'archive_title_tpl'    => '%%term_title%% Archives %%sep%% %%sitename%%',
		'noindex_archives'     => 0,
		'noindex_search'       => 1,
		'noindex_404'          => 1,
		'noindex_attachment'   => 1,
		'remove_wp_generator'  => 1,
		'clean_permalink'      => 1,
		'schema_type'          => 'Organization',
	) );

	add_option( 'rp_seo_social', array(
		'og_enabled'           => 1,
		'twitter_enabled'      => 1,
		'twitter_card_type'    => 'summary_large_image',
		'twitter_site'         => '',
		'facebook_url'         => '',
		'twitter_url'          => '',
		'linkedin_url'         => '',
		'instagram_url'        => '',
		'youtube_url'          => '',
		'pinterest_url'        => '',
		'og_default_image'     => '',
		'fb_app_id'            => '',
		'org_name'             => get_bloginfo( 'name' ),
		'org_logo'             => '',
		'person_name'          => '',
	) );

	add_option( 'rp_seo_sitemap', array(
		'enabled'              => 1,
		'include_post'         => 1,
		'include_page'         => 1,
		'include_product'      => 1,
		'include_category'     => 1,
		'include_post_tag'     => 1,
		'include_images'       => 1,
		'posts_per_page'       => 1000,
		'ping_google'          => 1,
		'ping_bing'            => 1,
		'exclude_ids'          => '',
		'exclude_noindex'      => 1,
	) );

	add_option( 'rp_seo_breadcrumbs', array(
		'enabled'              => 1,
		'separator'            => ' &rsaquo; ',
		'home_label'           => 'Home',
		'show_current'         => 1,
		'bold_last'            => 1,
		'prefix'               => '',
		'schema_enabled'       => 1,
	) );

	add_option( 'rp_seo_woocommerce', array(
		'product_schema'       => 1,
		'include_price'        => 1,
		'include_stock'        => 1,
		'include_ratings'      => 1,
		'og_product_gallery'   => 0,
		'hide_cart_checkout'   => 1,
		'hide_filter_pages'    => 1,
		'primary_category'     => 1,
		'breadcrumb_replace'   => 1,
	) );

	flush_rewrite_rules();
}

// ──────────────────────────────────────────────
// DEACTIVATION
// ──────────────────────────────────────────────
register_deactivation_hook( __FILE__, 'rp_seo_deactivate' );
function rp_seo_deactivate() {
	flush_rewrite_rules();
}

// ──────────────────────────────────────────────
// AUTO-UPDATER (GitHub releases)
// ──────────────────────────────────────────────
require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-updater.php';
new RankPilot_Updater(
	RP_SEO_PLUGIN_BASE,
	'aiyedunkayodetoluwase',
	'RankPilot-SEO',
	RP_SEO_VERSION
);

// ──────────────────────────────────────────────
// BOOTSTRAP
// ──────────────────────────────────────────────
require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-plugin.php';
RankPilot_Plugin::get_instance();
