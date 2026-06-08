<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-seo-settings">
	<div class="rp-seo-header">
		<h1><span class="rp-logo">&#9650;</span> <?php esc_html_e( 'RankPilot SEO — Breadcrumbs', 'rankpilot-seo' ); ?></h1>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'rp_seo_breadcrumbs_group' ); ?>
		<?php $bc = get_option( 'rp_seo_breadcrumbs', array() ); ?>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Breadcrumb Settings', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Enable Breadcrumbs', 'rankpilot-seo' ); ?></th>
					<td><label><input type="checkbox" name="rp_seo_breadcrumbs[enabled]" value="1" <?php checked( $bc['enabled'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Enable breadcrumb functionality', 'rankpilot-seo' ); ?></label></td>
				</tr>
				<tr>
					<th><label for="rp_bc_separator"><?php esc_html_e( 'Separator', 'rankpilot-seo' ); ?></label></th>
					<td>
						<input type="text" id="rp_bc_separator" name="rp_seo_breadcrumbs[separator]" value="<?php echo esc_attr( $bc['separator'] ?? ' &rsaquo; ' ); ?>" class="small-text">
						<p class="description"><?php esc_html_e( 'HTML allowed. Default: &rsaquo;', 'rankpilot-seo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="rp_bc_home"><?php esc_html_e( 'Home Label', 'rankpilot-seo' ); ?></label></th>
					<td><input type="text" id="rp_bc_home" name="rp_seo_breadcrumbs[home_label]" value="<?php echo esc_attr( $bc['home_label'] ?? 'Home' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Prefix', 'rankpilot-seo' ); ?></th>
					<td>
						<input type="text" name="rp_seo_breadcrumbs[prefix]" value="<?php echo esc_attr( $bc['prefix'] ?? '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. You are here:', 'rankpilot-seo' ); ?>">
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Options', 'rankpilot-seo' ); ?></th>
					<td>
						<label><input type="checkbox" name="rp_seo_breadcrumbs[show_current]" value="1" <?php checked( $bc['show_current'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Show current page in breadcrumb', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_breadcrumbs[bold_last]" value="1" <?php checked( $bc['bold_last'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Bold the last breadcrumb item', 'rankpilot-seo' ); ?></label><br>
						<label><input type="checkbox" name="rp_seo_breadcrumbs[schema_enabled]" value="1" <?php checked( $bc['schema_enabled'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Output BreadcrumbList JSON-LD schema', 'rankpilot-seo' ); ?></label>
					</td>
				</tr>
			</table>
		</div>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Usage', 'rankpilot-seo' ); ?></h2>
			<p><?php esc_html_e( 'Add breadcrumbs to your theme or page using any of these methods:', 'rankpilot-seo' ); ?></p>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Method', 'rankpilot-seo' ); ?></th><th><?php esc_html_e( 'Code', 'rankpilot-seo' ); ?></th></tr></thead>
				<tbody>
					<tr><td><?php esc_html_e( 'PHP Template Function', 'rankpilot-seo' ); ?></td><td><code>&lt;?php rankpilot_breadcrumb(); ?&gt;</code></td></tr>
					<tr><td><?php esc_html_e( 'Shortcode', 'rankpilot-seo' ); ?></td><td><code>[rankpilot_breadcrumbs]</code></td></tr>
					<tr><td><?php esc_html_e( 'With custom separator', 'rankpilot-seo' ); ?></td><td><code>[rankpilot_breadcrumbs separator=" / "]</code></td></tr>
				</tbody>
			</table>
		</div>

		<?php submit_button( __( 'Save Settings', 'rankpilot-seo' ) ); ?>
	</form>
</div>
