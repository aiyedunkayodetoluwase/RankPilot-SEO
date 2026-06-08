<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-seo-settings">
	<div class="rp-seo-header">
		<h1><span class="rp-logo">&#9650;</span> <?php esc_html_e( 'RankPilot SEO — WooCommerce SEO', 'rankpilot-seo' ); ?></h1>
	</div>

	<?php if ( ! class_exists( 'WooCommerce' ) ) : ?>
	<div class="notice notice-warning"><p><?php esc_html_e( 'WooCommerce is not installed or active. These settings have no effect.', 'rankpilot-seo' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'rp_seo_woocommerce_group' ); ?>
		<?php $woo = get_option( 'rp_seo_woocommerce', array() ); ?>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Product Schema', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Product Schema', 'rankpilot-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="rp_seo_woocommerce[product_schema]" value="1" <?php checked( $woo['product_schema'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Output Product JSON-LD schema', 'rankpilot-seo' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Schema Fields', 'rankpilot-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="rp_seo_woocommerce[include_price]" value="1" <?php checked( $woo['include_price'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Include price and availability', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_woocommerce[include_stock]" value="1" <?php checked( $woo['include_stock'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Include stock status', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_woocommerce[include_ratings]" value="1" <?php checked( $woo['include_ratings'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Include ratings & review count', 'rankpilot-seo' ); ?></label>
					</td>
				</tr>
			</table>
		</div>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Open Graph', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Product Gallery', 'rankpilot-seo' ); ?></th>
					<td><label><input type="checkbox" name="rp_seo_woocommerce[og_product_gallery]" value="1" <?php checked( $woo['og_product_gallery'] ?? 0, 1 ); ?>> <?php esc_html_e( 'Use product gallery as OG image fallback', 'rankpilot-seo' ); ?></label></td>
				</tr>
			</table>
		</div>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Sitemap', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Excluded Pages', 'rankpilot-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="rp_seo_woocommerce[hide_cart_checkout]" value="1" <?php checked( $woo['hide_cart_checkout'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Exclude cart, checkout, and my account from sitemap', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_woocommerce[hide_filter_pages]" value="1" <?php checked( $woo['hide_filter_pages'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Exclude filtered/layered navigation pages from sitemap', 'rankpilot-seo' ); ?></label>
					</td>
				</tr>
			</table>
		</div>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Breadcrumbs', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Replace WooCommerce Breadcrumbs', 'rankpilot-seo' ); ?></th>
					<td><label><input type="checkbox" name="rp_seo_woocommerce[breadcrumb_replace]" value="1" <?php checked( $woo['breadcrumb_replace'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Replace WooCommerce default breadcrumbs with RankPilot breadcrumbs', 'rankpilot-seo' ); ?></label></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Primary Category', 'rankpilot-seo' ); ?></th>
					<td><label><input type="checkbox" name="rp_seo_woocommerce[primary_category]" value="1" <?php checked( $woo['primary_category'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Enable primary category selection for breadcrumbs', 'rankpilot-seo' ); ?></label></td>
				</tr>
			</table>
		</div>

		<?php submit_button( __( 'Save Settings', 'rankpilot-seo' ) ); ?>
	</form>
</div>
