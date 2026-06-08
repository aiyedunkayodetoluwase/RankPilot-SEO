<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-seo-settings">
	<div class="rp-seo-header">
		<h1><span class="rp-logo">&#9650;</span> <?php esc_html_e( 'RankPilot SEO — General Settings', 'rankpilot-seo' ); ?></h1>
		<p class="rp-seo-version"><?php printf( esc_html__( 'Version %s', 'rankpilot-seo' ), esc_html( RP_SEO_VERSION ) ); ?></p>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'rp_seo_general_group' ); ?>
		<?php $g = get_option( 'rp_seo_general', array() ); ?>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Site Identity', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="rp_site_name"><?php esc_html_e( 'Site Name', 'rankpilot-seo' ); ?></label></th>
					<td>
						<input type="text" id="rp_site_name" name="rp_seo_general[site_name]"
							value="<?php echo esc_attr( $g['site_name'] ?? get_bloginfo( 'name' ) ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Used in title templates as %%sitename%%.', 'rankpilot-seo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="rp_separator"><?php esc_html_e( 'Title Separator', 'rankpilot-seo' ); ?></label></th>
					<td>
						<?php
						$current_sep = $g['separator'] ?? '&#8211;';
						$separators  = array(
							'&#8211;' => '&#8211; (en dash)',
							'&#8212;' => '&#8212; (em dash)',
							'&#124;'  => '| (pipe)',
							'&#183;'  => '· (middle dot)',
							'-'       => '- (hyphen)',
							'~'       => '~ (tilde)',
							'&raquo;' => '» (double angle)',
						);
						echo '<select id="rp_separator" name="rp_seo_general[separator]">';
						foreach ( $separators as $val => $label ) {
							printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $current_sep, $val, false ), esc_html( html_entity_decode( $label ) ) );
						}
						echo '</select>';
						?>
						<p class="description"><?php esc_html_e( 'Separator between page title and site name. Used as %%sep%% in templates.', 'rankpilot-seo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Schema Type', 'rankpilot-seo' ); ?></label></th>
					<td>
						<label><input type="radio" name="rp_seo_general[schema_type]" value="Organization" <?php checked( $g['schema_type'] ?? 'Organization', 'Organization' ); ?>> <?php esc_html_e( 'Organization', 'rankpilot-seo' ); ?></label>
						&nbsp;&nbsp;
						<label><input type="radio" name="rp_seo_general[schema_type]" value="Person" <?php checked( $g['schema_type'] ?? '', 'Person' ); ?>> <?php esc_html_e( 'Person (personal blog/portfolio)', 'rankpilot-seo' ); ?></label>
					</td>
				</tr>
			</table>
		</div>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Homepage', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="rp_homepage_title"><?php esc_html_e( 'Homepage SEO Title', 'rankpilot-seo' ); ?></label></th>
					<td>
						<input type="text" id="rp_homepage_title" name="rp_seo_general[homepage_title]"
							value="<?php echo esc_attr( $g['homepage_title'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th><label for="rp_homepage_desc"><?php esc_html_e( 'Homepage Meta Description', 'rankpilot-seo' ); ?></label></th>
					<td>
						<textarea id="rp_homepage_desc" name="rp_seo_general[homepage_desc]" rows="3" class="large-text"><?php echo esc_textarea( $g['homepage_desc'] ?? '' ); ?></textarea>
					</td>
				</tr>
			</table>
		</div>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Title Templates', 'rankpilot-seo' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Available tokens: %%title%%, %%sitename%%, %%sep%%, %%term_title%%, %%page%%', 'rankpilot-seo' ); ?></p>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Posts', 'rankpilot-seo' ); ?></th>
					<td><input type="text" name="rp_seo_general[post_title_tpl]" value="<?php echo esc_attr( $g['post_title_tpl'] ?? '%%title%% %%sep%% %%sitename%%' ); ?>" class="large-text"></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Pages', 'rankpilot-seo' ); ?></th>
					<td><input type="text" name="rp_seo_general[page_title_tpl]" value="<?php echo esc_attr( $g['page_title_tpl'] ?? '%%title%% %%sep%% %%sitename%%' ); ?>" class="large-text"></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Products', 'rankpilot-seo' ); ?></th>
					<td><input type="text" name="rp_seo_general[product_title_tpl]" value="<?php echo esc_attr( $g['product_title_tpl'] ?? '%%title%% %%sep%% %%sitename%%' ); ?>" class="large-text"></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Archives / Taxonomies', 'rankpilot-seo' ); ?></th>
					<td><input type="text" name="rp_seo_general[archive_title_tpl]" value="<?php echo esc_attr( $g['archive_title_tpl'] ?? '%%term_title%% Archives %%sep%% %%sitename%%' ); ?>" class="large-text"></td>
				</tr>
			</table>
		</div>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Robots & Indexing', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'No-index Settings', 'rankpilot-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="rp_seo_general[noindex_archives]" value="1" <?php checked( $g['noindex_archives'] ?? 0, 1 ); ?>> <?php esc_html_e( 'Noindex date archives', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_general[noindex_search]" value="1" <?php checked( $g['noindex_search'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Noindex search result pages', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_general[noindex_404]" value="1" <?php checked( $g['noindex_404'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Noindex 404 pages', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_general[noindex_attachment]" value="1" <?php checked( $g['noindex_attachment'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Noindex attachment pages', 'rankpilot-seo' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Clean Head', 'rankpilot-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="rp_seo_general[remove_wp_generator]" value="1" <?php checked( $g['remove_wp_generator'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Remove WordPress version meta tag', 'rankpilot-seo' ); ?></label>
					</td>
				</tr>
			</table>
		</div>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'AI Content Generation', 'rankpilot-seo' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Connect your Anthropic API key to enable AI-powered title and description generation directly in the editor.', 'rankpilot-seo' ); ?></p>
			<table class="form-table">
				<tr>
					<th><label for="rp_ai_key"><?php esc_html_e( 'Anthropic API Key', 'rankpilot-seo' ); ?></label></th>
					<td>
						<input type="password" id="rp_ai_key" name="rp_seo_general[ai_api_key]"
							value="<?php echo esc_attr( $g['ai_api_key'] ?? '' ); ?>" class="regular-text"
							autocomplete="off" placeholder="sk-ant-...">
						<p class="description"><?php esc_html_e( 'Used only for AI title/description generation from the post editor. Never shared.', 'rankpilot-seo' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button( __( 'Save Settings', 'rankpilot-seo' ) ); ?>
	</form>
</div>
