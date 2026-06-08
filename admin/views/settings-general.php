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
			<p class="description">
				<?php esc_html_e( 'Enables AI-powered SEO title and meta description generation in the post editor. Choose a free or paid AI provider below.', 'rankpilot-seo' ); ?>
			</p>
			<?php
			$ai_provider = $g['ai_provider'] ?? 'groq';
			$ai_model    = $g['ai_model'] ?? '';
			$ai_key      = $g['ai_api_key'] ?? '';
			$ollama_url  = $g['ollama_url'] ?? 'http://localhost:11434';

			$providers = array(
				'none'        => __( '— Disabled (use rule-based fallback) —', 'rankpilot-seo' ),
				'groq'        => __( 'Groq (FREE — llama-3.3-70b — 14,400 req/day)', 'rankpilot-seo' ),
				'gemini'      => __( 'Google Gemini (FREE — gemini-1.5-flash — 1M tokens/day)', 'rankpilot-seo' ),
				'huggingface' => __( 'Hugging Face (FREE — no key required for public models)', 'rankpilot-seo' ),
				'ollama'      => __( 'Ollama (FREE — runs locally on your server)', 'rankpilot-seo' ),
				'anthropic'   => __( 'Anthropic Claude (Paid)', 'rankpilot-seo' ),
				'openai'      => __( 'OpenAI (Paid)', 'rankpilot-seo' ),
			);

			$provider_info = array(
				'groq'        => array(
					'get_key'    => 'https://console.groq.com',
					'models'     => 'llama-3.3-70b-versatile, llama3-8b-8192, mixtral-8x7b-32768',
					'default'    => 'llama-3.3-70b-versatile',
					'needs_key'  => true,
					'key_hint'   => 'gsk_...',
					'needs_ollama_url' => false,
				),
				'gemini'      => array(
					'get_key'    => 'https://aistudio.google.com/app/apikey',
					'models'     => 'gemini-1.5-flash, gemini-1.5-pro, gemini-2.0-flash-exp',
					'default'    => 'gemini-1.5-flash',
					'needs_key'  => true,
					'key_hint'   => 'AIza...',
					'needs_ollama_url' => false,
				),
				'huggingface' => array(
					'get_key'    => 'https://huggingface.co/settings/tokens',
					'models'     => 'mistralai/Mistral-7B-Instruct-v0.3, meta-llama/Meta-Llama-3-8B-Instruct',
					'default'    => 'mistralai/Mistral-7B-Instruct-v0.3',
					'needs_key'  => false,
					'key_hint'   => 'hf_... (optional — increases rate limits)',
					'needs_ollama_url' => false,
				),
				'ollama'      => array(
					'get_key'    => 'https://ollama.com',
					'models'     => 'llama3.2, llama3.1, mistral, phi3, qwen2.5',
					'default'    => 'llama3.2',
					'needs_key'  => false,
					'key_hint'   => '',
					'needs_ollama_url' => true,
				),
				'anthropic'   => array(
					'get_key'    => 'https://console.anthropic.com',
					'models'     => 'claude-haiku-4-5-20251001, claude-3-5-haiku-20241022',
					'default'    => 'claude-haiku-4-5-20251001',
					'needs_key'  => true,
					'key_hint'   => 'sk-ant-...',
					'needs_ollama_url' => false,
				),
				'openai'      => array(
					'get_key'    => 'https://platform.openai.com/api-keys',
					'models'     => 'gpt-4o-mini, gpt-4o, gpt-3.5-turbo',
					'default'    => 'gpt-4o-mini',
					'needs_key'  => true,
					'key_hint'   => 'sk-...',
					'needs_ollama_url' => false,
				),
				'none'        => array( 'needs_key' => false, 'needs_ollama_url' => false, 'models' => '', 'default' => '' ),
			);
			?>
			<table class="form-table">
				<tr>
					<th><label for="rp_ai_provider"><?php esc_html_e( 'AI Provider', 'rankpilot-seo' ); ?></label></th>
					<td>
						<select id="rp_ai_provider" name="rp_seo_general[ai_provider]" onchange="rpSeoToggleAiFields(this.value)">
							<?php foreach ( $providers as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $ai_provider, $val ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description" id="rp_ai_provider_hint"></p>
					</td>
				</tr>
				<tr id="rp_ai_model_row">
					<th><label for="rp_ai_model"><?php esc_html_e( 'Model', 'rankpilot-seo' ); ?></label></th>
					<td>
						<input type="text" id="rp_ai_model" name="rp_seo_general[ai_model]"
							value="<?php echo esc_attr( $ai_model ); ?>" class="regular-text" placeholder="leave blank for default">
						<p class="description" id="rp_ai_model_hint"><?php esc_html_e( 'Leave blank to use the recommended default model for the selected provider.', 'rankpilot-seo' ); ?></p>
					</td>
				</tr>
				<tr id="rp_ai_key_row">
					<th><label for="rp_ai_key"><?php esc_html_e( 'API Key', 'rankpilot-seo' ); ?></label></th>
					<td>
						<input type="password" id="rp_ai_key" name="rp_seo_general[ai_api_key]"
							value="<?php echo esc_attr( $ai_key ); ?>" class="regular-text"
							autocomplete="off" id="rp_ai_key_input">
						<p class="description" id="rp_ai_getkey_hint"></p>
						<p class="description"><?php esc_html_e( 'Your API key is stored in WordPress and never exposed publicly.', 'rankpilot-seo' ); ?></p>
					</td>
				</tr>
				<tr id="rp_ollama_url_row" style="display:none">
					<th><label for="rp_ollama_url"><?php esc_html_e( 'Ollama Server URL', 'rankpilot-seo' ); ?></label></th>
					<td>
						<input type="url" id="rp_ollama_url" name="rp_seo_general[ollama_url]"
							value="<?php echo esc_attr( $ollama_url ); ?>" class="regular-text" placeholder="http://localhost:11434">
						<p class="description"><?php esc_html_e( 'URL to your Ollama instance. Default is localhost — use a full URL if Ollama is on a different server.', 'rankpilot-seo' ); ?></p>
					</td>
				</tr>
				<tr id="rp_ai_test_row">
					<th><?php esc_html_e( 'Test Connection', 'rankpilot-seo' ); ?></th>
					<td>
						<button type="button" id="rp_ai_test_btn" class="button button-secondary"
							onclick="rpSeoTestAi()"><?php esc_html_e( 'Test AI Connection', 'rankpilot-seo' ); ?></button>
						<span id="rp_ai_test_result" style="margin-left:10px;font-style:italic;"></span>
						<p class="description"><?php esc_html_e( 'Sends a quick test prompt to verify your configuration works. Save settings first.', 'rankpilot-seo' ); ?></p>
					</td>
				</tr>
			</table>

			<script>
			var rpSeoProviderInfo = <?php echo wp_json_encode( $provider_info ); ?>;

			function rpSeoToggleAiFields( provider ) {
				var info       = rpSeoProviderInfo[ provider ] || {};
				var needsKey   = info.needs_key;
				var needsOllama = info.needs_ollama_url;
				var models     = info.models || '';
				var getKey     = info.get_key || '';

				// Key row
				document.getElementById('rp_ai_key_row').style.display    = ( needsKey || (!needsOllama && provider !== 'none') ) ? '' : 'none';
				document.getElementById('rp_ai_key_input').placeholder     = info.key_hint || '';
				document.getElementById('rp_ai_getkey_hint').innerHTML     = getKey
					? '<?php esc_html_e( 'Get a free API key at:', 'rankpilot-seo' ); ?> <a href="' + getKey + '" target="_blank" rel="noopener">' + getKey + '</a>'
					: '';

				// Ollama URL row
				document.getElementById('rp_ollama_url_row').style.display = needsOllama ? '' : 'none';

				// Model hint
				document.getElementById('rp_ai_model_hint').textContent = models
					? '<?php esc_html_e( 'Available models:', 'rankpilot-seo' ); ?> ' + models
					: '';
				document.getElementById('rp_ai_model_row').style.display = provider !== 'none' ? '' : 'none';
				document.getElementById('rp_ai_test_row').style.display  = provider !== 'none' ? '' : 'none';

				// Provider hint
				var hintEl = document.getElementById('rp_ai_provider_hint');
				if ( provider === 'huggingface' ) {
					hintEl.textContent = '<?php esc_html_e( 'No API key required for public models. Add a key to increase rate limits.', 'rankpilot-seo' ); ?>';
				} else if ( provider === 'ollama' ) {
					hintEl.textContent = '<?php esc_html_e( 'Ollama must be running on your WordPress server. Completely free and unlimited.', 'rankpilot-seo' ); ?>';
				} else {
					hintEl.textContent = '';
				}
			}

			function rpSeoTestAi() {
				var btn    = document.getElementById('rp_ai_test_btn');
				var result = document.getElementById('rp_ai_test_result');
				btn.disabled = true;
				result.textContent = '<?php esc_html_e( 'Testing…', 'rankpilot-seo' ); ?>';

				fetch( '<?php echo esc_url( rest_url( 'rankpilot/v1/ai-test' ) ); ?>', {
					method  : 'POST',
					headers : {
						'Content-Type' : 'application/json',
						'X-WP-Nonce'   : '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>',
					},
					body : JSON.stringify({}),
				} )
				.then( function(r) { return r.json(); } )
				.then( function(data) {
					if ( data.success ) {
						result.style.color   = '#00a32a';
						result.textContent   = '✓ <?php esc_html_e( 'Connected! Sample:', 'rankpilot-seo' ); ?> ' + data.sample;
					} else {
						result.style.color   = '#d63638';
						result.textContent   = '✗ ' + ( data.error || '<?php esc_html_e( 'Connection failed', 'rankpilot-seo' ); ?>' );
					}
				} )
				.catch( function(e) {
					result.style.color   = '#d63638';
					result.textContent   = '✗ ' + e.message;
				} )
				.finally( function() { btn.disabled = false; } );
			}

			// Init on page load
			document.addEventListener('DOMContentLoaded', function() {
				rpSeoToggleAiFields( document.getElementById('rp_ai_provider').value );
			});
			</script>
		</div>

		<?php submit_button( __( 'Save Settings', 'rankpilot-seo' ) ); ?>
	</form>
</div>
