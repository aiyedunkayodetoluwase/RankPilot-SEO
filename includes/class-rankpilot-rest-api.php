<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RankPilot_REST_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		// AI title/description generation
		register_rest_route( 'rankpilot/v1', '/generate', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'generate_content' ),
			'permission_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
			'args' => array(
				'post_id' => array(
					'required'          => true,
					'validate_callback' => function( $param ) { return is_numeric( $param ) && $param > 0; },
					'sanitize_callback' => 'absint',
				),
				'type' => array(
					'required'          => true,
					'validate_callback' => function( $param ) { return in_array( $param, array( 'title', 'description' ), true ); },
					'sanitize_callback' => 'sanitize_key',
				),
				'focus_keyword' => array(
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		// Analysis endpoint
		register_rest_route( 'rankpilot/v1', '/analyze', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'analyze_content' ),
			'permission_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
			'args' => array(
				'post_id' => array(
					'required'          => true,
					'validate_callback' => function( $param ) { return is_numeric( $param ) && $param > 0; },
					'sanitize_callback' => 'absint',
				),
				'focus_keyword' => array(
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'seo_title' => array(
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'meta_description' => array(
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
			),
		) );

		// Settings endpoint
		register_rest_route( 'rankpilot/v1', '/settings/(?P<group>[a-z_]+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => function() { return current_user_can( 'manage_options' ); },
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_settings' ),
				'permission_callback' => function() { return current_user_can( 'manage_options' ); },
			),
		) );

		// Test AI connection endpoint
		register_rest_route( 'rankpilot/v1', '/ai-test', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'test_ai_connection' ),
			'permission_callback' => function() { return current_user_can( 'manage_options' ); },
		) );
	}

	// ──────────────────────────────────────────
	// GENERATE CONTENT
	// ──────────────────────────────────────────

	public function generate_content( $request ) {
		$post_id       = $request->get_param( 'post_id' );
		$type          = $request->get_param( 'type' );
		$focus_keyword = $request->get_param( 'focus_keyword' );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'unauthorized', 'Unauthorized', array( 'status' => 403 ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'not_found', 'Post not found', array( 'status' => 404 ) );
		}

		$general  = get_option( 'rp_seo_general', array() );
		$provider = isset( $general['ai_provider'] ) ? $general['ai_provider'] : 'none';
		$api_key  = isset( $general['ai_api_key'] ) ? $general['ai_api_key'] : '';

		$prompt = $this->build_prompt( $post, $type, $focus_keyword );

		// Try configured provider
		$result = $this->call_provider( $provider, $api_key, $prompt, $general );

		if ( ! is_wp_error( $result ) && $result ) {
			$text = $this->enforce_length( $result, $type );

			// If description is still too short after enforcement, retry once with an even stricter prompt
			if ( 'description' === $type && mb_strlen( $text ) < 70 ) {
				$retry_prompt = $this->build_retry_prompt( $post, $focus_keyword, $text );
				$retry        = $this->call_provider( $provider, $api_key, $retry_prompt, $general );
				if ( ! is_wp_error( $retry ) && $retry ) {
					$text = $this->enforce_length( $retry, $type );
				}
			}

			// If still too short, fall back to rule-based but blend it
			if ( 'description' === $type && mb_strlen( $text ) < 70 ) {
				$text = $this->generate_fallback( $post, $type, $focus_keyword );
			}

			return rest_ensure_response( array(
				'generated' => $text,
				'provider'  => $provider,
			) );
		}

		// Fallback: rule-based generation
		$generated = $this->generate_fallback( $post, $type, $focus_keyword );
		return rest_ensure_response( array(
			'generated' => $generated,
			'provider'  => 'fallback',
			'notice'    => is_wp_error( $result )
				? $result->get_error_message()
				: __( 'No AI provider configured. Using basic rule-based generation.', 'rankpilot-seo' ),
		) );
	}

	// ──────────────────────────────────────────
	// PROMPT BUILDER
	// ──────────────────────────────────────────

	private function build_prompt( $post, $type, $focus_keyword ) {
		$content = wp_strip_all_tags( $post->post_content );
		$content = substr( $content, 0, 2000 );
		$title   = wp_strip_all_tags( $post->post_title );
		$kw      = $focus_keyword ? $focus_keyword : '';

		if ( 'title' === $type ) {
			$kw_line = $kw ? "Focus keyword to include: \"{$kw}\"" : '';
			return <<<PROMPT
You are an expert SEO copywriter. Write ONE compelling SEO title.

Page title: {$title}
{$kw_line}

STRICT RULES (you MUST follow all of them):
1. Between 50 and 60 characters total (count every character including spaces).
2. Include the focus keyword naturally near the beginning.
3. Be specific, benefit-driven, and click-worthy.
4. Output ONLY the title text — no quotes, no explanation, no extra lines.

PROMPT;
		}

		$kw_line = $kw ? "Focus keyword to include: \"{$kw}\"" : '';
		return <<<PROMPT
You are an expert SEO copywriter. Write ONE compelling meta description.

Page title: {$title}
Page content: {$content}
{$kw_line}

STRICT RULES (you MUST follow ALL of them — this is critical):
1. The description MUST be between 130 and 155 characters total (count every character including spaces and punctuation).
2. Include the focus keyword naturally in the first half of the description.
3. Describe the benefit or value the reader gets from the page.
4. End with a clear, action-oriented phrase (e.g. "Shop now", "Learn more", "Discover how").
5. Do NOT mention character counts. Do NOT add quotes. Do NOT explain yourself.
6. Output ONLY the description text on a single line.

PROMPT;
	}

	private function build_retry_prompt( $post, $focus_keyword, $previous_attempt ) {
		$title   = wp_strip_all_tags( $post->post_title );
		$content = wp_strip_all_tags( $post->post_content );
		$content = substr( $content, 0, 1000 );
		$kw      = $focus_keyword ? $focus_keyword : '';
		$prev_len = mb_strlen( $previous_attempt );

		return <<<PROMPT
Your previous attempt was only {$prev_len} characters — that is too short.

Write a NEW meta description for this page. You MUST write between 130 and 155 characters.

Page title: {$title}
Page content: {$content}
Focus keyword: {$kw}

COUNT CAREFULLY. A good 140-character description looks like this example (140 chars):
"Discover the best WordPress SEO plugin with sitemaps, schema, and redirects built in. Optimize every page and boost your rankings today."

Now write one of similar length for this page. Include "{$kw}". End with an action phrase.
Output ONLY the description. No quotes. No explanation.

PROMPT;
	}

	/**
	 * Enforce length constraints on AI output.
	 * For descriptions: if too short, append a benefit phrase; if too long, trim at last word boundary.
	 */
	private function enforce_length( $text, $type ) {
		$text = trim( $text );
		// Strip surrounding quotes the AI sometimes adds
		$text = trim( $text, '"\'""''' );
		$text = trim( $text );

		if ( 'title' === $type ) {
			if ( mb_strlen( $text ) > 60 ) {
				$text = mb_substr( $text, 0, 57 ) . '…';
			}
			return $text;
		}

		// Description: enforce 120–158
		if ( mb_strlen( $text ) > 158 ) {
			// Trim to last space before 155
			$trimmed = mb_substr( $text, 0, 155 );
			$last    = mb_strrpos( $trimmed, ' ' );
			$text    = $last ? mb_substr( $trimmed, 0, $last ) . '…' : $trimmed . '…';
		}

		return $text;
	}

	// ──────────────────────────────────────────
	// PROVIDER DISPATCHER
	// ──────────────────────────────────────────

	private function call_provider( $provider, $api_key, $prompt, $general ) {
		switch ( $provider ) {
			case 'groq':
				return $this->call_groq( $api_key, $prompt, $general );

			case 'gemini':
				return $this->call_gemini( $api_key, $prompt, $general );

			case 'huggingface':
				return $this->call_huggingface( $api_key, $prompt, $general );

			case 'ollama':
				return $this->call_ollama( $prompt, $general );

			case 'anthropic':
				return $this->call_anthropic( $api_key, $prompt, $general );

			case 'openai':
				return $this->call_openai( $api_key, $prompt, $general );

			default:
				return new WP_Error( 'no_provider', __( 'No AI provider selected.', 'rankpilot-seo' ) );
		}
	}

	// ──────────────────────────────────────────
	// GROQ  (Free — https://console.groq.com)
	// Models: llama-3.3-70b-versatile, llama3-8b-8192, mixtral-8x7b-32768
	// ──────────────────────────────────────────

	private function call_groq( $api_key, $prompt, $general ) {
		if ( ! $api_key ) {
			return new WP_Error( 'no_key', __( 'Groq API key not configured.', 'rankpilot-seo' ) );
		}

		$model = isset( $general['ai_model'] ) && $general['ai_model'] ? $general['ai_model'] : 'llama-3.3-70b-versatile';

		$response = wp_remote_post( 'https://api.groq.com/openai/v1/chat/completions', array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body' => wp_json_encode( array(
				'model'       => $model,
				'max_tokens'  => 200,
				'temperature' => 0.7,
				'messages'    => array(
					array( 'role' => 'system', 'content' => 'You are an expert SEO copywriter. Always follow the exact character limits specified.' ),
					array( 'role' => 'user',   'content' => $prompt ),
				),
			) ),
		) );

		return $this->parse_openai_response( $response );
	}

	// ──────────────────────────────────────────
	// GOOGLE GEMINI  (Free — https://aistudio.google.com)
	// Model: gemini-1.5-flash (free tier: 15 RPM, 1M tokens/day)
	// ──────────────────────────────────────────

	private function call_gemini( $api_key, $prompt, $general ) {
		if ( ! $api_key ) {
			return new WP_Error( 'no_key', __( 'Google Gemini API key not configured.', 'rankpilot-seo' ) );
		}

		$model = isset( $general['ai_model'] ) && $general['ai_model'] ? $general['ai_model'] : 'gemini-1.5-flash';
		$url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

		$response = wp_remote_post( $url, array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array(
				'contents'         => array(
					array( 'parts' => array( array( 'text' => $prompt ) ) ),
				),
				'generationConfig' => array(
					'maxOutputTokens' => 200,
					'temperature'     => 0.7,
				),
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$msg = isset( $body['error']['message'] ) ? $body['error']['message'] : "Gemini API error ({$code})";
			return new WP_Error( 'gemini_error', $msg );
		}

		$text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
		return $text ? trim( $text ) : new WP_Error( 'empty_response', 'Gemini returned an empty response.' );
	}

	// ──────────────────────────────────────────
	// HUGGING FACE  (Free — https://huggingface.co)
	// No API key required for public inference endpoint.
	// ──────────────────────────────────────────

	private function call_huggingface( $api_key, $prompt, $general ) {
		$model   = isset( $general['ai_model'] ) && $general['ai_model'] ? $general['ai_model'] : 'mistralai/Mistral-7B-Instruct-v0.3';
		$hf_url  = "https://api-inference.huggingface.co/models/{$model}";

		$headers = array( 'Content-Type' => 'application/json' );
		if ( $api_key ) {
			$headers['Authorization'] = 'Bearer ' . $api_key;
		}

		$response = wp_remote_post( $hf_url, array(
			'timeout' => 60,
			'headers' => $headers,
			'body'    => wp_json_encode( array(
				'inputs'     => "<s>[INST] {$prompt} [/INST]",
				'parameters' => array(
					'max_new_tokens' => 200,
					'temperature'    => 0.7,
					'return_full_text' => false,
				),
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 503 === $code ) {
			return new WP_Error( 'model_loading', __( 'Model is loading, please try again in 20 seconds.', 'rankpilot-seo' ) );
		}

		if ( 200 !== $code ) {
			$msg = isset( $body['error'] ) ? $body['error'] : "Hugging Face API error ({$code})";
			return new WP_Error( 'hf_error', $msg );
		}

		$text = $body[0]['generated_text'] ?? '';
		return $text ? trim( $text ) : new WP_Error( 'empty_response', 'Hugging Face returned an empty response.' );
	}

	// ──────────────────────────────────────────
	// OLLAMA  (Free — https://ollama.com, runs locally on your server)
	// Requires Ollama installed on the WordPress server.
	// ──────────────────────────────────────────

	private function call_ollama( $prompt, $general ) {
		$base_url = isset( $general['ollama_url'] ) && $general['ollama_url']
			? trailingslashit( $general['ollama_url'] )
			: 'http://localhost:11434/';
		$model    = isset( $general['ai_model'] ) && $general['ai_model'] ? $general['ai_model'] : 'llama3.2';

		$response = wp_remote_post( $base_url . 'api/generate', array(
			'timeout' => 120,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array(
				'model'  => $model,
				'prompt' => $prompt,
				'stream' => false,
				'options' => array(
					'num_predict' => 200,
					'temperature' => 0.7,
				),
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ollama_error', __( 'Could not connect to Ollama. Is it running on the server?', 'rankpilot-seo' ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$msg = isset( $body['error'] ) ? $body['error'] : "Ollama error ({$code})";
			return new WP_Error( 'ollama_error', $msg );
		}

		$text = $body['response'] ?? '';
		return $text ? trim( $text ) : new WP_Error( 'empty_response', 'Ollama returned an empty response.' );
	}

	// ──────────────────────────────────────────
	// ANTHROPIC CLAUDE  (Paid — https://console.anthropic.com)
	// ──────────────────────────────────────────

	private function call_anthropic( $api_key, $prompt, $general ) {
		if ( ! $api_key ) {
			return new WP_Error( 'no_key', __( 'Anthropic API key not configured.', 'rankpilot-seo' ) );
		}

		$model = isset( $general['ai_model'] ) && $general['ai_model'] ? $general['ai_model'] : 'claude-haiku-4-5-20251001';

		$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
			'timeout' => 30,
			'headers' => array(
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
				'content-type'      => 'application/json',
			),
			'body' => wp_json_encode( array(
				'model'      => $model,
				'max_tokens' => 200,
				'messages'   => array(
					array( 'role' => 'user', 'content' => $prompt ),
				),
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$msg = isset( $body['error']['message'] ) ? $body['error']['message'] : "Anthropic API error ({$code})";
			return new WP_Error( 'anthropic_error', $msg );
		}

		$text = $body['content'][0]['text'] ?? '';
		return $text ? trim( $text ) : new WP_Error( 'empty_response', 'Claude returned an empty response.' );
	}

	// ──────────────────────────────────────────
	// OPENAI  (Paid — https://platform.openai.com)
	// ──────────────────────────────────────────

	private function call_openai( $api_key, $prompt, $general ) {
		if ( ! $api_key ) {
			return new WP_Error( 'no_key', __( 'OpenAI API key not configured.', 'rankpilot-seo' ) );
		}

		$model = isset( $general['ai_model'] ) && $general['ai_model'] ? $general['ai_model'] : 'gpt-4o-mini';

		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body' => wp_json_encode( array(
				'model'       => $model,
				'max_tokens'  => 200,
				'temperature' => 0.7,
				'messages'    => array(
					array( 'role' => 'system', 'content' => 'You are an expert SEO copywriter.' ),
					array( 'role' => 'user',   'content' => $prompt ),
				),
			) ),
		) );

		return $this->parse_openai_response( $response );
	}

	// ──────────────────────────────────────────
	// SHARED: OpenAI-compatible response parser (used by Groq + OpenAI)
	// ──────────────────────────────────────────

	private function parse_openai_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$msg = isset( $body['error']['message'] ) ? $body['error']['message'] : "API error ({$code})";
			return new WP_Error( 'api_error', $msg );
		}

		$text = $body['choices'][0]['message']['content'] ?? '';
		return $text ? trim( $text ) : new WP_Error( 'empty_response', 'API returned empty content.' );
	}

	// ──────────────────────────────────────────
	// TEST CONNECTION
	// ──────────────────────────────────────────

	public function test_ai_connection( $request ) {
		$general  = get_option( 'rp_seo_general', array() );
		$provider = isset( $general['ai_provider'] ) ? $general['ai_provider'] : 'none';
		$api_key  = isset( $general['ai_api_key'] ) ? $general['ai_api_key'] : '';

		$test_prompt = 'Write a 5-word SEO title for a bakery. Output only the title.';
		$result      = $this->call_provider( $provider, $api_key, $test_prompt, $general );

		if ( is_wp_error( $result ) ) {
			return rest_ensure_response( array(
				'success'  => false,
				'provider' => $provider,
				'error'    => $result->get_error_message(),
			) );
		}

		return rest_ensure_response( array(
			'success'  => true,
			'provider' => $provider,
			'sample'   => $result,
		) );
	}

	// ──────────────────────────────────────────
	// RULE-BASED FALLBACK
	// ──────────────────────────────────────────

	private function generate_fallback( $post, $type, $focus_keyword ) {
		$general  = get_option( 'rp_seo_general', array() );
		$sitename = isset( $general['site_name'] ) ? $general['site_name'] : get_bloginfo( 'name' );
		$sep      = ' – ';

		if ( 'title' === $type ) {
			$title = get_the_title( $post->ID );
			if ( $focus_keyword && stripos( $title, $focus_keyword ) === false ) {
				$title = $focus_keyword . $sep . $sitename;
			} else {
				$title = $title . $sep . $sitename;
			}
			return substr( $title, 0, 60 );
		}

		$content = wp_strip_all_tags( $post->post_content );
		if ( $focus_keyword ) {
			$pos = stripos( $content, $focus_keyword );
			if ( $pos !== false ) {
				$start = max( 0, $pos - 50 );
				$desc  = substr( $content, $start, 155 );
			} else {
				$desc = substr( $content, 0, 155 );
			}
		} else {
			$desc = substr( $content, 0, 155 );
		}
		return trim( $desc ) . '…';
	}

	// ──────────────────────────────────────────
	// SEO ANALYSIS
	// ──────────────────────────────────────────

	public function analyze_content( $request ) {
		$post_id       = $request->get_param( 'post_id' );
		$focus_keyword = $request->get_param( 'focus_keyword' );
		$seo_title     = $request->get_param( 'seo_title' );
		$meta_desc     = $request->get_param( 'meta_description' );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'unauthorized', 'Unauthorized', array( 'status' => 403 ) );
		}

		$post    = get_post( $post_id );
		$content = wp_strip_all_tags( $post ? $post->post_content : '' );
		$title   = $seo_title ?: get_the_title( $post_id );
		$desc    = $meta_desc;
		$slug    = $post ? $post->post_name : '';

		$checks   = array();
		$score    = 0;
		$possible = 0;

		if ( ! $focus_keyword ) {
			$checks[] = array( 'type' => 'warning', 'msg' => __( 'No focus keyword set.', 'rankpilot-seo' ) );
			return rest_ensure_response( array( 'checks' => $checks, 'score' => 0, 'grade' => 'na' ) );
		}

		$kw_lower    = strtolower( $focus_keyword );
		$title_lower = strtolower( $title );
		$desc_lower  = strtolower( $desc );
		$slug_lower  = strtolower( $slug );
		$cont_lower  = strtolower( $content );

		// 1. Keyword in title
		$possible++;
		if ( strpos( $title_lower, $kw_lower ) !== false ) {
			$checks[] = array( 'type' => 'good', 'msg' => __( 'Focus keyword found in SEO title.', 'rankpilot-seo' ) );
			$score++;
		} else {
			$checks[] = array( 'type' => 'bad', 'msg' => __( 'Focus keyword not found in SEO title.', 'rankpilot-seo' ) );
		}

		// 2. Title length
		$possible++;
		$title_len = mb_strlen( $title );
		if ( $title_len >= 30 && $title_len <= 60 ) {
			$checks[] = array( 'type' => 'good', 'msg' => sprintf( __( 'SEO title length is good (%d characters).', 'rankpilot-seo' ), $title_len ) );
			$score++;
		} elseif ( $title_len > 60 ) {
			$checks[] = array( 'type' => 'warning', 'msg' => sprintf( __( 'SEO title is too long (%d/60 characters).', 'rankpilot-seo' ), $title_len ) );
		} else {
			$checks[] = array( 'type' => 'warning', 'msg' => sprintf( __( 'SEO title is too short (%d characters). Aim for 30–60.', 'rankpilot-seo' ), $title_len ) );
		}

		// 3. Keyword in description
		if ( $desc ) {
			$possible++;
			if ( strpos( $desc_lower, $kw_lower ) !== false ) {
				$checks[] = array( 'type' => 'good', 'msg' => __( 'Focus keyword found in meta description.', 'rankpilot-seo' ) );
				$score++;
			} else {
				$checks[] = array( 'type' => 'bad', 'msg' => __( 'Focus keyword not found in meta description.', 'rankpilot-seo' ) );
			}
		}

		// 4. Description length
		if ( $desc ) {
			$possible++;
			$desc_len = mb_strlen( $desc );
			if ( $desc_len >= 70 && $desc_len <= 158 ) {
				$checks[] = array( 'type' => 'good', 'msg' => sprintf( __( 'Meta description length is good (%d characters).', 'rankpilot-seo' ), $desc_len ) );
				$score++;
			} elseif ( $desc_len > 158 ) {
				$checks[] = array( 'type' => 'warning', 'msg' => sprintf( __( 'Meta description too long (%d/158 characters).', 'rankpilot-seo' ), $desc_len ) );
			} else {
				$checks[] = array( 'type' => 'warning', 'msg' => sprintf( __( 'Meta description too short (%d characters). Aim for 70–158.', 'rankpilot-seo' ), $desc_len ) );
			}
		} else {
			$checks[] = array( 'type' => 'warning', 'msg' => __( 'No meta description set.', 'rankpilot-seo' ) );
		}

		// 5. Keyword in URL slug
		if ( $slug ) {
			$possible++;
			$slug_check = str_replace( array( '-', '_' ), ' ', $slug_lower );
			if ( strpos( $slug_check, $kw_lower ) !== false ) {
				$checks[] = array( 'type' => 'good', 'msg' => __( 'Focus keyword found in URL slug.', 'rankpilot-seo' ) );
				$score++;
			} else {
				$checks[] = array( 'type' => 'info', 'msg' => __( 'Focus keyword not found in URL slug.', 'rankpilot-seo' ) );
			}
		}

		// 6. Keyword density in content
		if ( $content ) {
			$possible++;
			$word_count = str_word_count( $content );
			$kw_count   = substr_count( $cont_lower, $kw_lower );
			$density    = $word_count > 0 ? ( $kw_count / $word_count ) * 100 : 0;

			if ( $kw_count === 0 ) {
				$checks[] = array( 'type' => 'bad', 'msg' => __( 'Focus keyword not found in content.', 'rankpilot-seo' ) );
			} elseif ( $density >= 0.5 && $density <= 2.5 ) {
				$checks[] = array( 'type' => 'good', 'msg' => sprintf( __( 'Keyword density is good (%.1f%%, %d occurrence(s)).', 'rankpilot-seo' ), $density, $kw_count ) );
				$score++;
			} elseif ( $density > 2.5 ) {
				$checks[] = array( 'type' => 'warning', 'msg' => sprintf( __( 'Keyword density too high (%.1f%%). Avoid keyword stuffing.', 'rankpilot-seo' ), $density ) );
			} else {
				$checks[] = array( 'type' => 'info', 'msg' => sprintf( __( 'Keyword appears only %d time(s). Consider using it more.', 'rankpilot-seo' ), $kw_count ) );
			}
		}

		// 7. Content word count
		if ( $content ) {
			$possible++;
			$word_count = str_word_count( $content );
			if ( $word_count >= 300 ) {
				$checks[] = array( 'type' => 'good', 'msg' => sprintf( __( 'Content is %d words. Good length.', 'rankpilot-seo' ), $word_count ) );
				$score++;
			} else {
				$checks[] = array( 'type' => 'warning', 'msg' => sprintf( __( 'Content is only %d words. Consider writing at least 300 words.', 'rankpilot-seo' ), $word_count ) );
			}
		}

		// 8. Featured image
		$possible++;
		if ( has_post_thumbnail( $post_id ) ) {
			$checks[] = array( 'type' => 'good', 'msg' => __( 'Featured image is set.', 'rankpilot-seo' ) );
			$score++;
		} else {
			$checks[] = array( 'type' => 'warning', 'msg' => __( 'No featured image. Images improve CTR in search results.', 'rankpilot-seo' ) );
		}

		// 9. Internal links
		$possible++;
		$link_count = substr_count( $content, '<a ' );
		if ( $link_count > 0 ) {
			$checks[] = array( 'type' => 'good', 'msg' => sprintf( __( 'Content contains %d link(s).', 'rankpilot-seo' ), $link_count ) );
			$score++;
		} else {
			$checks[] = array( 'type' => 'info', 'msg' => __( 'No links found. Internal links help distribute authority.', 'rankpilot-seo' ) );
		}

		// WooCommerce & other plugin checks
		$checks = apply_filters( 'rp_seo_analysis_checks', $checks, $post_id );

		$grade = 'poor';
		if ( $possible > 0 ) {
			$pct = ( $score / $possible ) * 100;
			if ( $pct >= 70 ) {
				$grade = 'good';
			} elseif ( $pct >= 40 ) {
				$grade = 'ok';
			}
		}

		return rest_ensure_response( array(
			'checks'   => $checks,
			'score'    => $score,
			'possible' => $possible,
			'grade'    => $grade,
		) );
	}

	// ──────────────────────────────────────────
	// SETTINGS ENDPOINTS
	// ──────────────────────────────────────────

	public function get_settings( $request ) {
		$group   = sanitize_key( $request->get_param( 'group' ) );
		$allowed = array( 'rp_seo_general', 'rp_seo_social', 'rp_seo_sitemap', 'rp_seo_breadcrumbs', 'rp_seo_woocommerce' );
		if ( ! in_array( $group, $allowed, true ) ) {
			return new WP_Error( 'invalid_group', 'Invalid settings group', array( 'status' => 400 ) );
		}
		return rest_ensure_response( get_option( $group, array() ) );
	}

	public function save_settings( $request ) {
		$group   = sanitize_key( $request->get_param( 'group' ) );
		$allowed = array( 'rp_seo_general', 'rp_seo_social', 'rp_seo_sitemap', 'rp_seo_breadcrumbs', 'rp_seo_woocommerce' );
		if ( ! in_array( $group, $allowed, true ) ) {
			return new WP_Error( 'invalid_group', 'Invalid settings group', array( 'status' => 400 ) );
		}
		$data = $request->get_json_params();
		if ( is_array( $data ) ) {
			update_option( $group, $data );
		}
		return rest_ensure_response( array( 'updated' => true ) );
	}
}
