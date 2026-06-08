<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-seo-settings">
	<div class="rp-seo-header">
		<h1><span class="rp-logo">&#9650;</span> <?php esc_html_e( 'RankPilot SEO — Redirect Manager', 'rankpilot-seo' ); ?></h1>
	</div>

	<div class="rp-seo-card">
		<h2><?php esc_html_e( 'Add / Edit Redirect', 'rankpilot-seo' ); ?></h2>
		<div id="rp-redirect-form" class="rp-redirect-form">
			<input type="hidden" id="rp-redirect-id" value="">
			<table class="form-table">
				<tr>
					<th><label for="rp-redirect-source"><?php esc_html_e( 'From (old URL path)', 'rankpilot-seo' ); ?></label></th>
					<td>
						<input type="text" id="rp-redirect-source" class="regular-text" placeholder="/old-page-slug">
						<p class="description"><?php esc_html_e( 'Relative path, e.g. /old-post-name', 'rankpilot-seo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="rp-redirect-target"><?php esc_html_e( 'To (new URL)', 'rankpilot-seo' ); ?></label></th>
					<td>
						<input type="text" id="rp-redirect-target" class="regular-text" placeholder="https://yoursite.com/new-page or /new-page">
					</td>
				</tr>
				<tr>
					<th><label for="rp-redirect-type"><?php esc_html_e( 'Redirect Type', 'rankpilot-seo' ); ?></label></th>
					<td>
						<select id="rp-redirect-type">
							<option value="301">301 — Permanent</option>
							<option value="302">302 — Temporary</option>
							<option value="307">307 — Temporary (preserve method)</option>
						</select>
					</td>
				</tr>
			</table>
			<div class="rp-redirect-actions">
				<button type="button" class="button button-primary" id="rp-save-redirect"><?php esc_html_e( 'Save Redirect', 'rankpilot-seo' ); ?></button>
				<button type="button" class="button" id="rp-clear-form"><?php esc_html_e( 'Clear', 'rankpilot-seo' ); ?></button>
				<span id="rp-redirect-status" class="rp-status-msg"></span>
			</div>
		</div>
	</div>

	<div class="rp-seo-card">
		<h2><?php esc_html_e( 'Existing Redirects', 'rankpilot-seo' ); ?></h2>
		<div class="rp-redirect-search">
			<input type="text" id="rp-search-redirects" placeholder="<?php esc_attr_e( 'Search redirects…', 'rankpilot-seo' ); ?>" class="regular-text">
			<button type="button" class="button" id="rp-do-search"><?php esc_html_e( 'Search', 'rankpilot-seo' ); ?></button>
		</div>
		<table class="widefat striped" id="rp-redirects-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'From', 'rankpilot-seo' ); ?></th>
					<th><?php esc_html_e( 'To', 'rankpilot-seo' ); ?></th>
					<th><?php esc_html_e( 'Type', 'rankpilot-seo' ); ?></th>
					<th><?php esc_html_e( 'Hits', 'rankpilot-seo' ); ?></th>
					<th><?php esc_html_e( 'Created', 'rankpilot-seo' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'rankpilot-seo' ); ?></th>
				</tr>
			</thead>
			<tbody id="rp-redirects-list">
				<tr><td colspan="6"><?php esc_html_e( 'Loading…', 'rankpilot-seo' ); ?></td></tr>
			</tbody>
		</table>
		<div id="rp-redirects-pagination" class="rp-pagination"></div>
	</div>
</div>
