<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Delete plugin options
delete_option( 'rp_seo_version' );
delete_option( 'rp_seo_general' );
delete_option( 'rp_seo_social' );
delete_option( 'rp_seo_sitemap' );
delete_option( 'rp_seo_breadcrumbs' );
delete_option( 'rp_seo_woocommerce' );

// Multisite
delete_site_option( 'rp_seo_version' );
delete_site_option( 'rp_seo_general' );
delete_site_option( 'rp_seo_social' );
delete_site_option( 'rp_seo_sitemap' );
delete_site_option( 'rp_seo_breadcrumbs' );
delete_site_option( 'rp_seo_woocommerce' );

// Drop redirects table
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rp_seo_redirects" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Delete all post meta created by the plugin
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_rp_seo_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Clear caches
wp_cache_flush();
