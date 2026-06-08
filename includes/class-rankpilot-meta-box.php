<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RankPilot_Meta_Box {

	public function __construct() {
		add_action( 'add_meta_boxes',        array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post',             array( $this, 'save_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_meta_boxes() {
		$post_types = RankPilot_Plugin::get_seo_post_types();
		foreach ( $post_types as $pt ) {
			add_meta_box(
				'rp_seo_meta_box',
				__( 'RankPilot SEO', 'rankpilot-seo' ),
				array( $this, 'render_meta_box' ),
				$pt,
				'normal',
				'high'
			);
		}
	}

	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_style(
			'rp-seo-meta-box',
			RP_SEO_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			RP_SEO_VERSION
		);
		wp_enqueue_script(
			'rp-seo-meta-box',
			RP_SEO_PLUGIN_URL . 'admin/js/meta-box.js',
			array( 'jquery' ),
			RP_SEO_VERSION,
			true
		);
		wp_localize_script( 'rp-seo-meta-box', 'rpSeoMetaBox', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'rp_seo_meta_box_nonce' ),
			'restUrl'   => rest_url( 'rankpilot/v1/' ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'i18n'      => array(
				'noKeyword'       => __( 'No focus keyword set.', 'rankpilot-seo' ),
				'good'            => __( 'Good', 'rankpilot-seo' ),
				'ok'              => __( 'OK', 'rankpilot-seo' ),
				'poor'            => __( 'Poor', 'rankpilot-seo' ),
				'titleTooLong'    => __( 'Title is too long (over 60 chars).', 'rankpilot-seo' ),
				'titleTooShort'   => __( 'Title is too short (under 30 chars).', 'rankpilot-seo' ),
				'descTooLong'     => __( 'Meta description is too long (over 158 chars).', 'rankpilot-seo' ),
				'descTooShort'    => __( 'Meta description is too short (under 70 chars).', 'rankpilot-seo' ),
				'kwInTitle'       => __( 'Focus keyword found in SEO title.', 'rankpilot-seo' ),
				'kwNotInTitle'    => __( 'Focus keyword not found in SEO title.', 'rankpilot-seo' ),
				'kwInDesc'        => __( 'Focus keyword found in meta description.', 'rankpilot-seo' ),
				'kwNotInDesc'     => __( 'Focus keyword not found in meta description.', 'rankpilot-seo' ),
				'kwInSlug'        => __( 'Focus keyword found in URL slug.', 'rankpilot-seo' ),
				'kwNotInSlug'     => __( 'Focus keyword not found in URL slug.', 'rankpilot-seo' ),
				'generating'      => __( 'Generating with AI...', 'rankpilot-seo' ),
				'generateError'   => __( 'AI generation failed. Please try again.', 'rankpilot-seo' ),
			),
		) );
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'rp_seo_save_meta', 'rp_seo_nonce' );

		$meta = $this->get_post_meta( $post->ID );
		$general = get_option( 'rp_seo_general', array() );
		$sep      = isset( $general['separator'] ) ? $general['separator'] : '&#8211;';
		$sitename = isset( $general['site_name'] ) ? $general['site_name'] : get_bloginfo( 'name' );
		$slug     = $post->post_name ? $post->post_name : sanitize_title( $post->post_title );
		?>
		<div class="rp-seo-box" id="rp-seo-meta-box">

			<!-- TABS -->
			<div class="rp-seo-tabs">
				<button type="button" class="rp-seo-tab active" data-tab="general">
					<span class="dashicons dashicons-chart-line"></span> <?php esc_html_e( 'SEO', 'rankpilot-seo' ); ?>
				</button>
				<button type="button" class="rp-seo-tab" data-tab="readability">
					<span class="dashicons dashicons-text-page"></span> <?php esc_html_e( 'Readability', 'rankpilot-seo' ); ?>
				</button>
				<button type="button" class="rp-seo-tab" data-tab="social">
					<span class="dashicons dashicons-share"></span> <?php esc_html_e( 'Social', 'rankpilot-seo' ); ?>
				</button>
				<button type="button" class="rp-seo-tab" data-tab="schema">
					<span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'Schema', 'rankpilot-seo' ); ?>
				</button>
				<button type="button" class="rp-seo-tab" data-tab="advanced">
					<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Advanced', 'rankpilot-seo' ); ?>
				</button>
			</div>

			<!-- ── SEO TAB ── -->
			<div class="rp-seo-tab-content active" id="rp-tab-general">

				<!-- Score badge -->
				<div class="rp-seo-score-wrap">
					<div class="rp-seo-score-circle" id="rp-score-badge">
						<span class="rp-score-dot rp-score-na"></span>
						<span class="rp-score-label"><?php esc_html_e( 'Not analyzed', 'rankpilot-seo' ); ?></span>
					</div>
				</div>

				<!-- Snippet preview -->
				<div class="rp-seo-section">
					<h4><?php esc_html_e( 'Search Engine Preview', 'rankpilot-seo' ); ?></h4>
					<div class="rp-snippet-preview">
						<div class="rp-snippet-url" id="rp-preview-url"><?php echo esc_url( get_permalink( $post->ID ) ?: get_home_url() . '/' . esc_attr( $slug ) ); ?></div>
						<div class="rp-snippet-title" id="rp-preview-title"><?php echo esc_html( $meta['title'] ?: get_the_title( $post->ID ) . ' ' . html_entity_decode( $sep ) . ' ' . $sitename ); ?></div>
						<div class="rp-snippet-desc" id="rp-preview-desc"><?php echo esc_html( $meta['description'] ?: wp_trim_words( $post->post_content, 25 ) ); ?></div>
					</div>
					<div class="rp-snippet-tabs">
						<button type="button" class="rp-snippet-mode active" data-mode="desktop"><?php esc_html_e( 'Desktop', 'rankpilot-seo' ); ?></button>
						<button type="button" class="rp-snippet-mode" data-mode="mobile"><?php esc_html_e( 'Mobile', 'rankpilot-seo' ); ?></button>
					</div>
				</div>

				<!-- Focus keyword -->
				<div class="rp-seo-section">
					<label for="rp_seo_focus_keyword"><strong><?php esc_html_e( 'Focus Keyword', 'rankpilot-seo' ); ?></strong></label>
					<input type="text" id="rp_seo_focus_keyword" name="rp_seo_focus_keyword"
						value="<?php echo esc_attr( $meta['focus_keyword'] ); ?>"
						class="widefat" placeholder="<?php esc_attr_e( 'e.g. wordpress seo plugin', 'rankpilot-seo' ); ?>">
					<p class="description"><?php esc_html_e( 'The phrase you want this page to rank for.', 'rankpilot-seo' ); ?></p>
				</div>

				<!-- Related keyphrases / synonyms -->
				<div class="rp-seo-section">
					<label for="rp_seo_synonyms"><strong><?php esc_html_e( 'Synonyms & Related Keyphrases', 'rankpilot-seo' ); ?></strong></label>
					<input type="text" id="rp_seo_synonyms" name="rp_seo_synonyms"
						value="<?php echo esc_attr( $meta['synonyms'] ); ?>"
						class="widefat" placeholder="<?php esc_attr_e( 'e.g. seo plugin, search engine optimization', 'rankpilot-seo' ); ?>">
					<p class="description"><?php esc_html_e( 'Comma-separated synonyms and related phrases to check in content.', 'rankpilot-seo' ); ?></p>
				</div>

				<!-- SEO Title -->
				<div class="rp-seo-section">
					<label for="rp_seo_title">
						<strong><?php esc_html_e( 'SEO Title', 'rankpilot-seo' ); ?></strong>
						<span class="rp-char-count" id="rp-title-count">0/60</span>
					</label>
					<div class="rp-input-with-ai">
						<input type="text" id="rp_seo_title" name="rp_seo_title"
							value="<?php echo esc_attr( $meta['title'] ); ?>"
							class="widefat" placeholder="<?php esc_attr_e( 'Leave empty to use default title template', 'rankpilot-seo' ); ?>">
						<button type="button" class="button rp-ai-btn" id="rp-generate-title" title="<?php esc_attr_e( 'Generate with AI', 'rankpilot-seo' ); ?>">
							&#9889; <?php esc_html_e( 'AI', 'rankpilot-seo' ); ?>
						</button>
					</div>
					<div class="rp-progress-bar"><div class="rp-progress-fill" id="rp-title-bar"></div></div>
					<p class="description"><?php esc_html_e( 'Recommended: 30–60 characters. Overrides the default title template.', 'rankpilot-seo' ); ?></p>
				</div>

				<!-- Meta Description -->
				<div class="rp-seo-section">
					<label for="rp_seo_description">
						<strong><?php esc_html_e( 'Meta Description', 'rankpilot-seo' ); ?></strong>
						<span class="rp-char-count" id="rp-desc-count">0/158</span>
					</label>
					<div class="rp-input-with-ai">
						<textarea id="rp_seo_description" name="rp_seo_description" rows="3"
							class="widefat" placeholder="<?php esc_attr_e( 'Write a compelling meta description…', 'rankpilot-seo' ); ?>"><?php echo esc_textarea( $meta['description'] ); ?></textarea>
						<button type="button" class="button rp-ai-btn" id="rp-generate-desc" title="<?php esc_attr_e( 'Generate with AI', 'rankpilot-seo' ); ?>">
							&#9889; <?php esc_html_e( 'AI', 'rankpilot-seo' ); ?>
						</button>
					</div>
					<div class="rp-progress-bar"><div class="rp-progress-fill" id="rp-desc-bar"></div></div>
					<p class="description"><?php esc_html_e( 'Recommended: 70–158 characters. Shown in search results.', 'rankpilot-seo' ); ?></p>
				</div>

				<!-- SEO Analysis -->
				<div class="rp-seo-section">
					<h4><?php esc_html_e( 'SEO Analysis', 'rankpilot-seo' ); ?></h4>
					<ul class="rp-analysis-list" id="rp-seo-analysis">
						<li class="rp-check rp-na"><span class="rp-dot"></span> <?php esc_html_e( 'Set a focus keyword to see analysis.', 'rankpilot-seo' ); ?></li>
					</ul>
				</div>
			</div>

			<!-- ── READABILITY TAB ── -->
			<div class="rp-seo-tab-content" id="rp-tab-readability">
				<div class="rp-seo-score-wrap">
					<div class="rp-seo-score-circle" id="rp-readability-badge">
						<span class="rp-score-dot rp-score-na"></span>
						<span class="rp-score-label"><?php esc_html_e( 'Not analyzed', 'rankpilot-seo' ); ?></span>
					</div>
				</div>
				<div class="rp-seo-section">
					<h4><?php esc_html_e( 'Readability Analysis', 'rankpilot-seo' ); ?></h4>
					<ul class="rp-analysis-list" id="rp-readability-analysis">
						<li class="rp-check rp-na"><span class="rp-dot"></span> <?php esc_html_e( 'Open the editor to analyze readability.', 'rankpilot-seo' ); ?></li>
					</ul>
				</div>
				<div class="rp-seo-section">
					<h4><?php esc_html_e( 'Flesch Reading Ease', 'rankpilot-seo' ); ?></h4>
					<div class="rp-flesch-score">
						<div class="rp-flesch-bar"><div class="rp-flesch-fill" id="rp-flesch-fill" style="width:0%"></div></div>
						<span id="rp-flesch-label"><?php esc_html_e( '—', 'rankpilot-seo' ); ?></span>
					</div>
				</div>
			</div>

			<!-- ── SOCIAL TAB ── -->
			<div class="rp-seo-tab-content" id="rp-tab-social">

				<!-- Facebook / Open Graph -->
				<div class="rp-seo-section">
					<h4><span class="dashicons dashicons-facebook"></span> <?php esc_html_e( 'Facebook / Open Graph', 'rankpilot-seo' ); ?></h4>
					<div class="rp-social-preview rp-fb-preview" id="rp-fb-preview">
						<div class="rp-social-img-wrap">
							<?php if ( $meta['og_image'] ) : ?>
								<img src="<?php echo esc_url( $meta['og_image'] ); ?>" alt="">
							<?php else : ?>
								<div class="rp-social-img-placeholder"><?php esc_html_e( 'No image', 'rankpilot-seo' ); ?></div>
							<?php endif; ?>
						</div>
						<div class="rp-social-text">
							<div class="rp-social-domain"><?php echo esc_html( parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
							<div class="rp-social-title" id="rp-fb-title"><?php echo esc_html( $meta['og_title'] ?: get_the_title( $post->ID ) ); ?></div>
							<div class="rp-social-desc" id="rp-fb-desc"><?php echo esc_html( $meta['og_description'] ?: wp_trim_words( $post->post_content, 20 ) ); ?></div>
						</div>
					</div>

					<label for="rp_seo_og_title"><strong><?php esc_html_e( 'Facebook Title', 'rankpilot-seo' ); ?></strong></label>
					<input type="text" id="rp_seo_og_title" name="rp_seo_og_title"
						value="<?php echo esc_attr( $meta['og_title'] ); ?>" class="widefat"
						placeholder="<?php esc_attr_e( 'Defaults to SEO title', 'rankpilot-seo' ); ?>">

					<label for="rp_seo_og_description"><strong><?php esc_html_e( 'Facebook Description', 'rankpilot-seo' ); ?></strong></label>
					<textarea id="rp_seo_og_description" name="rp_seo_og_description" rows="2"
						class="widefat" placeholder="<?php esc_attr_e( 'Defaults to meta description', 'rankpilot-seo' ); ?>"><?php echo esc_textarea( $meta['og_description'] ); ?></textarea>

					<label for="rp_seo_og_image"><strong><?php esc_html_e( 'Facebook Image', 'rankpilot-seo' ); ?></strong></label>
					<div class="rp-media-field">
						<input type="text" id="rp_seo_og_image" name="rp_seo_og_image"
							value="<?php echo esc_attr( $meta['og_image'] ); ?>" class="widefat"
							placeholder="<?php esc_attr_e( 'Image URL or select from media library', 'rankpilot-seo' ); ?>">
						<button type="button" class="button rp-media-btn" data-target="rp_seo_og_image">
							<?php esc_html_e( 'Select Image', 'rankpilot-seo' ); ?>
						</button>
					</div>
				</div>

				<!-- Twitter Card -->
				<div class="rp-seo-section">
					<h4><span class="dashicons dashicons-twitter"></span> <?php esc_html_e( 'Twitter / X Card', 'rankpilot-seo' ); ?></h4>

					<label for="rp_seo_twitter_title"><strong><?php esc_html_e( 'Twitter Title', 'rankpilot-seo' ); ?></strong></label>
					<input type="text" id="rp_seo_twitter_title" name="rp_seo_twitter_title"
						value="<?php echo esc_attr( $meta['twitter_title'] ); ?>" class="widefat"
						placeholder="<?php esc_attr_e( 'Defaults to Facebook title', 'rankpilot-seo' ); ?>">

					<label for="rp_seo_twitter_description"><strong><?php esc_html_e( 'Twitter Description', 'rankpilot-seo' ); ?></strong></label>
					<textarea id="rp_seo_twitter_description" name="rp_seo_twitter_description" rows="2"
						class="widefat" placeholder="<?php esc_attr_e( 'Defaults to Facebook description', 'rankpilot-seo' ); ?>"><?php echo esc_textarea( $meta['twitter_description'] ); ?></textarea>

					<label for="rp_seo_twitter_image"><strong><?php esc_html_e( 'Twitter Image', 'rankpilot-seo' ); ?></strong></label>
					<div class="rp-media-field">
						<input type="text" id="rp_seo_twitter_image" name="rp_seo_twitter_image"
							value="<?php echo esc_attr( $meta['twitter_image'] ); ?>" class="widefat"
							placeholder="<?php esc_attr_e( 'Defaults to Facebook image', 'rankpilot-seo' ); ?>">
						<button type="button" class="button rp-media-btn" data-target="rp_seo_twitter_image">
							<?php esc_html_e( 'Select Image', 'rankpilot-seo' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- ── SCHEMA TAB ── -->
			<div class="rp-seo-tab-content" id="rp-tab-schema">
				<div class="rp-seo-section">
					<h4><?php esc_html_e( 'Schema / Structured Data', 'rankpilot-seo' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Override the default schema type for this specific page.', 'rankpilot-seo' ); ?></p>

					<label for="rp_seo_schema_type"><strong><?php esc_html_e( 'Page Schema Type', 'rankpilot-seo' ); ?></strong></label>
					<select id="rp_seo_schema_type" name="rp_seo_schema_type" class="widefat">
						<?php
						$schema_types = array(
							''            => __( '— Default (auto-detect) —', 'rankpilot-seo' ),
							'Article'     => 'Article',
							'WebPage'     => 'WebPage',
							'BlogPosting' => 'BlogPosting',
							'NewsArticle' => 'NewsArticle',
							'FAQPage'     => 'FAQPage',
							'HowTo'       => 'HowTo',
							'Product'     => 'Product',
							'Event'       => 'Event',
							'Service'     => 'Service',
							'LocalBusiness' => 'LocalBusiness',
						);
						foreach ( $schema_types as $val => $label ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $val ),
								selected( $meta['schema_type'], $val, false ),
								esc_html( $label )
							);
						}
						?>
					</select>
				</div>

				<div class="rp-seo-section">
					<label for="rp_seo_schema_article_type"><strong><?php esc_html_e( 'Article Type', 'rankpilot-seo' ); ?></strong></label>
					<select id="rp_seo_schema_article_type" name="rp_seo_schema_article_type" class="widefat">
						<?php
						$article_types = array(
							'Article'     => 'Article',
							'BlogPosting' => 'Blog Posting',
							'NewsArticle' => 'News Article',
							'TechArticle' => 'Tech Article',
						);
						foreach ( $article_types as $val => $label ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $val ),
								selected( $meta['schema_article_type'], $val, false ),
								esc_html( $label )
							);
						}
						?>
					</select>
				</div>
			</div>

			<!-- ── ADVANCED TAB ── -->
			<div class="rp-seo-tab-content" id="rp-tab-advanced">
				<div class="rp-seo-section">
					<h4><?php esc_html_e( 'Robots & Indexing', 'rankpilot-seo' ); ?></h4>
					<table class="form-table rp-form-table">
						<tr>
							<th><label for="rp_seo_noindex"><?php esc_html_e( 'Robots index', 'rankpilot-seo' ); ?></label></th>
							<td>
								<select id="rp_seo_noindex" name="rp_seo_noindex">
									<option value="index" <?php selected( $meta['noindex'], 'index' ); ?>><?php esc_html_e( 'Index (default)', 'rankpilot-seo' ); ?></option>
									<option value="noindex" <?php selected( $meta['noindex'], 'noindex' ); ?>><?php esc_html_e( 'No index', 'rankpilot-seo' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="rp_seo_nofollow"><?php esc_html_e( 'Robots follow', 'rankpilot-seo' ); ?></label></th>
							<td>
								<select id="rp_seo_nofollow" name="rp_seo_nofollow">
									<option value="follow" <?php selected( $meta['nofollow'], 'follow' ); ?>><?php esc_html_e( 'Follow (default)', 'rankpilot-seo' ); ?></option>
									<option value="nofollow" <?php selected( $meta['nofollow'], 'nofollow' ); ?>><?php esc_html_e( 'No follow', 'rankpilot-seo' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="rp_seo_noarchive"><?php esc_html_e( 'Archive', 'rankpilot-seo' ); ?></label></th>
							<td>
								<label>
									<input type="checkbox" id="rp_seo_noarchive" name="rp_seo_noarchive" value="1"
										<?php checked( $meta['noarchive'], 1 ); ?>>
									<?php esc_html_e( 'noarchive — prevent caching', 'rankpilot-seo' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><label for="rp_seo_nosnippet"><?php esc_html_e( 'Snippet', 'rankpilot-seo' ); ?></label></th>
							<td>
								<label>
									<input type="checkbox" id="rp_seo_nosnippet" name="rp_seo_nosnippet" value="1"
										<?php checked( $meta['nosnippet'], 1 ); ?>>
									<?php esc_html_e( 'nosnippet — suppress description snippet', 'rankpilot-seo' ); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>

				<div class="rp-seo-section">
					<h4><?php esc_html_e( 'Canonical URL', 'rankpilot-seo' ); ?></h4>
					<input type="url" id="rp_seo_canonical" name="rp_seo_canonical"
						value="<?php echo esc_attr( $meta['canonical'] ); ?>"
						class="widefat" placeholder="<?php echo esc_attr( get_permalink( $post->ID ) ); ?>">
					<p class="description"><?php esc_html_e( 'Override the canonical URL. Leave empty to use the permalink.', 'rankpilot-seo' ); ?></p>
				</div>

				<div class="rp-seo-section">
					<h4><?php esc_html_e( 'Breadcrumb Title', 'rankpilot-seo' ); ?></h4>
					<input type="text" id="rp_seo_breadcrumb_title" name="rp_seo_breadcrumb_title"
						value="<?php echo esc_attr( $meta['breadcrumb_title'] ); ?>"
						class="widefat" placeholder="<?php echo esc_attr( get_the_title( $post->ID ) ); ?>">
					<p class="description"><?php esc_html_e( 'Override the title shown in the breadcrumb trail.', 'rankpilot-seo' ); ?></p>
				</div>

				<div class="rp-seo-section">
					<h4><?php esc_html_e( 'Exclude from Sitemap', 'rankpilot-seo' ); ?></h4>
					<label>
						<input type="checkbox" name="rp_seo_exclude_sitemap" value="1"
							<?php checked( $meta['exclude_sitemap'], 1 ); ?>>
						<?php esc_html_e( 'Do not include this post in the XML sitemap', 'rankpilot-seo' ); ?>
					</label>
				</div>
			</div>

		</div><!-- .rp-seo-box -->
		<?php
	}

	/**
	 * Get all post meta for a given post.
	 */
	private function get_post_meta( $post_id ) {
		return array(
			'title'               => get_post_meta( $post_id, '_rp_seo_title', true ),
			'description'         => get_post_meta( $post_id, '_rp_seo_description', true ),
			'focus_keyword'       => get_post_meta( $post_id, '_rp_seo_focus_keyword', true ),
			'synonyms'            => get_post_meta( $post_id, '_rp_seo_synonyms', true ),
			'noindex'             => get_post_meta( $post_id, '_rp_seo_noindex', true ) ?: 'index',
			'nofollow'            => get_post_meta( $post_id, '_rp_seo_nofollow', true ) ?: 'follow',
			'noarchive'           => (int) get_post_meta( $post_id, '_rp_seo_noarchive', true ),
			'nosnippet'           => (int) get_post_meta( $post_id, '_rp_seo_nosnippet', true ),
			'canonical'           => get_post_meta( $post_id, '_rp_seo_canonical', true ),
			'og_title'            => get_post_meta( $post_id, '_rp_seo_og_title', true ),
			'og_description'      => get_post_meta( $post_id, '_rp_seo_og_description', true ),
			'og_image'            => get_post_meta( $post_id, '_rp_seo_og_image', true ),
			'twitter_title'       => get_post_meta( $post_id, '_rp_seo_twitter_title', true ),
			'twitter_description' => get_post_meta( $post_id, '_rp_seo_twitter_description', true ),
			'twitter_image'       => get_post_meta( $post_id, '_rp_seo_twitter_image', true ),
			'schema_type'         => get_post_meta( $post_id, '_rp_seo_schema_type', true ),
			'schema_article_type' => get_post_meta( $post_id, '_rp_seo_schema_article_type', true ) ?: 'Article',
			'breadcrumb_title'    => get_post_meta( $post_id, '_rp_seo_breadcrumb_title', true ),
			'exclude_sitemap'     => (int) get_post_meta( $post_id, '_rp_seo_exclude_sitemap', true ),
		);
	}

	/**
	 * Save the meta box data.
	 */
	public function save_meta( $post_id, $post ) {
		// Autosave / revision guard
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Nonce check
		if ( ! isset( $_POST['rp_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rp_seo_nonce'] ), 'rp_seo_save_meta' ) ) {
			return;
		}

		// Capability check
		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type_obj->cap->edit_post, $post_id ) ) {
			return;
		}

		// Text fields
		$text_fields = array(
			'rp_seo_title'               => '_rp_seo_title',
			'rp_seo_description'         => '_rp_seo_description',
			'rp_seo_focus_keyword'       => '_rp_seo_focus_keyword',
			'rp_seo_synonyms'            => '_rp_seo_synonyms',
			'rp_seo_og_title'            => '_rp_seo_og_title',
			'rp_seo_og_description'      => '_rp_seo_og_description',
			'rp_seo_twitter_title'       => '_rp_seo_twitter_title',
			'rp_seo_twitter_description' => '_rp_seo_twitter_description',
			'rp_seo_breadcrumb_title'    => '_rp_seo_breadcrumb_title',
			'rp_seo_schema_article_type' => '_rp_seo_schema_article_type',
		);
		foreach ( $text_fields as $post_key => $meta_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) );
			}
		}

		// URL fields
		$url_fields = array(
			'rp_seo_canonical'     => '_rp_seo_canonical',
			'rp_seo_og_image'      => '_rp_seo_og_image',
			'rp_seo_twitter_image' => '_rp_seo_twitter_image',
		);
		foreach ( $url_fields as $post_key => $meta_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				update_post_meta( $post_id, $meta_key, esc_url_raw( wp_unslash( $_POST[ $post_key ] ) ) );
			}
		}

		// Select fields with allowed values
		$allowed_noindex   = array( 'index', 'noindex' );
		$allowed_nofollow  = array( 'follow', 'nofollow' );
		$allowed_schema    = array( '', 'Article', 'WebPage', 'BlogPosting', 'NewsArticle', 'FAQPage', 'HowTo', 'Product', 'Event', 'Service', 'LocalBusiness' );

		if ( isset( $_POST['rp_seo_noindex'] ) && in_array( $_POST['rp_seo_noindex'], $allowed_noindex, true ) ) {
			update_post_meta( $post_id, '_rp_seo_noindex', sanitize_key( $_POST['rp_seo_noindex'] ) );
		}
		if ( isset( $_POST['rp_seo_nofollow'] ) && in_array( $_POST['rp_seo_nofollow'], $allowed_nofollow, true ) ) {
			update_post_meta( $post_id, '_rp_seo_nofollow', sanitize_key( $_POST['rp_seo_nofollow'] ) );
		}
		if ( isset( $_POST['rp_seo_schema_type'] ) && in_array( $_POST['rp_seo_schema_type'], $allowed_schema, true ) ) {
			update_post_meta( $post_id, '_rp_seo_schema_type', sanitize_key( $_POST['rp_seo_schema_type'] ) );
		}

		// Checkboxes
		$checkbox_fields = array(
			'rp_seo_noarchive'      => '_rp_seo_noarchive',
			'rp_seo_nosnippet'      => '_rp_seo_nosnippet',
			'rp_seo_exclude_sitemap' => '_rp_seo_exclude_sitemap',
		);
		foreach ( $checkbox_fields as $post_key => $meta_key ) {
			update_post_meta( $post_id, $meta_key, isset( $_POST[ $post_key ] ) ? 1 : 0 );
		}
	}
}
