<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-seo-settings">
	<div class="rp-seo-header">
		<h1><span class="rp-logo">&#9650;</span> <?php esc_html_e( 'RankPilot SEO — Social & Open Graph', 'rankpilot-seo' ); ?></h1>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'rp_seo_social_group' ); ?>
		<?php $s = get_option( 'rp_seo_social', array() ); ?>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Site Identity for Schema', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="rp_org_name"><?php esc_html_e( 'Organization / Person Name', 'rankpilot-seo' ); ?></label></th>
					<td><input type="text" id="rp_org_name" name="rp_seo_social[org_name]" value="<?php echo esc_attr( $s['org_name'] ?? get_bloginfo( 'name' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="rp_org_logo"><?php esc_html_e( 'Logo URL', 'rankpilot-seo' ); ?></label></th>
					<td>
						<div class="rp-media-field">
							<input type="text" id="rp_org_logo" name="rp_seo_social[org_logo]" value="<?php echo esc_attr( $s['org_logo'] ?? '' ); ?>" class="large-text">
							<button type="button" class="button rp-media-btn" data-target="rp_org_logo"><?php esc_html_e( 'Select', 'rankpilot-seo' ); ?></button>
						</div>
						<?php if ( ! empty( $s['org_logo'] ) ) : ?><img src="<?php echo esc_url( $s['org_logo'] ); ?>" style="max-height:60px;margin-top:4px"><?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><label for="rp_og_default_image"><?php esc_html_e( 'Default OG Image', 'rankpilot-seo' ); ?></label></th>
					<td>
						<div class="rp-media-field">
							<input type="text" id="rp_og_default_image" name="rp_seo_social[og_default_image]" value="<?php echo esc_attr( $s['og_default_image'] ?? '' ); ?>" class="large-text">
							<button type="button" class="button rp-media-btn" data-target="rp_og_default_image"><?php esc_html_e( 'Select', 'rankpilot-seo' ); ?></button>
						</div>
						<p class="description"><?php esc_html_e( 'Fallback image used when a post has no featured image. Recommended: 1200×630px.', 'rankpilot-seo' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Open Graph (Facebook)', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Enable Open Graph', 'rankpilot-seo' ); ?></th>
					<td><label><input type="checkbox" name="rp_seo_social[og_enabled]" value="1" <?php checked( $s['og_enabled'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Output og: meta tags', 'rankpilot-seo' ); ?></label></td>
				</tr>
				<tr>
					<th><label for="rp_fb_app_id"><?php esc_html_e( 'Facebook App ID', 'rankpilot-seo' ); ?></label></th>
					<td><input type="text" id="rp_fb_app_id" name="rp_seo_social[fb_app_id]" value="<?php echo esc_attr( $s['fb_app_id'] ?? '' ); ?>" class="regular-text" placeholder="123456789"></td>
				</tr>
				<tr>
					<th><label for="rp_facebook_url"><?php esc_html_e( 'Facebook Page URL', 'rankpilot-seo' ); ?></label></th>
					<td><input type="url" id="rp_facebook_url" name="rp_seo_social[facebook_url]" value="<?php echo esc_attr( $s['facebook_url'] ?? '' ); ?>" class="regular-text" placeholder="https://facebook.com/yourpage"></td>
				</tr>
			</table>
		</div>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Twitter / X Card', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Enable Twitter Cards', 'rankpilot-seo' ); ?></th>
					<td><label><input type="checkbox" name="rp_seo_social[twitter_enabled]" value="1" <?php checked( $s['twitter_enabled'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Output twitter: meta tags', 'rankpilot-seo' ); ?></label></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Default Card Type', 'rankpilot-seo' ); ?></th>
					<td>
						<select name="rp_seo_social[twitter_card_type]">
							<option value="summary_large_image" <?php selected( $s['twitter_card_type'] ?? '', 'summary_large_image' ); ?>><?php esc_html_e( 'Summary with large image', 'rankpilot-seo' ); ?></option>
							<option value="summary" <?php selected( $s['twitter_card_type'] ?? '', 'summary' ); ?>><?php esc_html_e( 'Summary', 'rankpilot-seo' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="rp_twitter_site"><?php esc_html_e( 'Twitter @username', 'rankpilot-seo' ); ?></label></th>
					<td><input type="text" id="rp_twitter_site" name="rp_seo_social[twitter_site]" value="<?php echo esc_attr( $s['twitter_site'] ?? '' ); ?>" class="regular-text" placeholder="@yourhandle"></td>
				</tr>
			</table>
		</div>

		<div class="rp-seo-card">
			<h2><?php esc_html_e( 'Social Profiles (for Schema sameAs)', 'rankpilot-seo' ); ?></h2>
			<table class="form-table">
				<?php
				$profiles = array(
					'twitter_url'   => array( 'Twitter / X', 'https://twitter.com/yourhandle' ),
					'linkedin_url'  => array( 'LinkedIn', 'https://linkedin.com/company/yourcompany' ),
					'instagram_url' => array( 'Instagram', 'https://instagram.com/yourhandle' ),
					'youtube_url'   => array( 'YouTube', 'https://youtube.com/yourchannel' ),
					'pinterest_url' => array( 'Pinterest', 'https://pinterest.com/yourprofile' ),
				);
				foreach ( $profiles as $key => $info ) :
				?>
				<tr>
					<th><label for="rp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $info[0] ); ?></label></th>
					<td><input type="url" id="rp_<?php echo esc_attr( $key ); ?>" name="rp_seo_social[<?php echo esc_attr( $key ); ?>]"
						value="<?php echo esc_attr( $s[ $key ] ?? '' ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $info[1] ); ?>"></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<?php submit_button( __( 'Save Settings', 'rankpilot-seo' ) ); ?>
	</form>
</div>
