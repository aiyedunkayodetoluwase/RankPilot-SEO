<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-seo-settings">
	<div class="rp-seo-header">
		<h1><span class="rp-logo">&#9650;</span> <?php esc_html_e( 'RankPilot SEO — XML Sitemaps', 'rankpilot-seo' ); ?></h1>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'rp_seo_sitemap_group' ); ?>
		<?php $sm = get_option( 'rp_seo_sitemap', array() ); ?>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Sitemap Settings', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Enable Sitemaps', 'rankpilot-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="rp_seo_sitemap[enabled]" value="1" <?php checked( $sm['enabled'] ?? 1, 1 ); ?>>
						<?php esc_html_e( 'Enable XML sitemap generation', 'rankpilot-seo' ); ?></label>
						<?php if ( ! empty( $sm['enabled'] ) ) : ?>
						<p><a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank"><?php echo esc_html( home_url( '/sitemap.xml' ) ); ?></a></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Include in Sitemap', 'rankpilot-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="rp_seo_sitemap[include_post]" value="1" <?php checked( $sm['include_post'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Posts', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_sitemap[include_page]" value="1" <?php checked( $sm['include_page'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Pages', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_sitemap[include_product]" value="1" <?php checked( $sm['include_product'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Products (WooCommerce)', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_sitemap[include_category]" value="1" <?php checked( $sm['include_category'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Categories', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_sitemap[include_post_tag]" value="1" <?php checked( $sm['include_post_tag'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Tags', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_sitemap[include_images]" value="1" <?php checked( $sm['include_images'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Include image tags', 'rankpilot-seo' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><label for="rp_posts_per_page"><?php esc_html_e( 'Posts Per Sitemap', 'rankpilot-seo' ); ?></label></th>
					<td>
						<input type="number" id="rp_posts_per_page" name="rp_seo_sitemap[posts_per_page]" value="<?php echo absint( $sm['posts_per_page'] ?? 1000 ); ?>" min="1" max="50000" class="small-text">
						<p class="description"><?php esc_html_e( 'Recommended: 1000. Max: 50000.', 'rankpilot-seo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Exclusions', 'rankpilot-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="rp_seo_sitemap[exclude_noindex]" value="1" <?php checked( $sm['exclude_noindex'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Exclude noindexed posts', 'rankpilot-seo' ); ?></label>
						<br><br>
						<label for="rp_exclude_ids"><?php esc_html_e( 'Exclude specific post IDs (comma-separated):', 'rankpilot-seo' ); ?></label>
						<input type="text" id="rp_exclude_ids" name="rp_seo_sitemap[exclude_ids]" value="<?php echo esc_attr( $sm['exclude_ids'] ?? '' ); ?>" class="regular-text" placeholder="1, 5, 42">
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Ping Search Engines', 'rankpilot-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="rp_seo_sitemap[ping_google]" value="1" <?php checked( $sm['ping_google'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Ping Google when content is published', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_sitemap[ping_bing]" value="1" <?php checked( $sm['ping_bing'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Ping Bing when content is published', 'rankpilot-seo' ); ?></label>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button( __( 'Save Settings', 'rankpilot-seo' ) ); ?>
	</form>
</div>
