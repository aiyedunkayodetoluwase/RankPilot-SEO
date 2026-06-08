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
	}

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
		$ai_key   = isset( $general['ai_api_key'] ) ? $general['ai_api_key'] : '';

		// Use Claude API if configured
		if ( $ai_key ) {
			$result = $this->call_ai_api( $ai_key, $post, $type, $focus_keyword );
			if ( ! is_wp_error( $result ) ) {
				return rest_ensure_response( array( 'generated' => $result ) );
			}
		}

		// Fallback: rule-based generation
		$generated = $this->generate_fallback( $post, $type, $focus_keyword );
		return rest_ensure_response( array( 'generated' => $generated, 'notice' => __( 'AI API not configured. Generated using basic rules.', 'rankpilot-seo' ) ) );
	}

	private function call_ai_api( $api_key, $post, $type, $focus_keyword ) {
		$content = wp_strip_all_tags( $post->post_content );
		$content = substr( $content, 0, 2000 );

		if ( 'title' === $type ) {
			$prompt = sprintf(
				"Write a compelling SEO title for a webpage. The page is about: %s. Focus keyword: %s. Requirements: max 60 characters, include the focus keyword naturally, be specific and engaging. Output only the title, no quotes.",
				wp_strip_all_tags( $post->post_title ),
				$focus_keyword
			);
		} else {
			$prompt = sprintf(
				"Write a compelling meta description for this page. Title: %s. Content excerpt: %s. Focus keyword: %s. Requirements: 120-158 characters, include the focus keyword, entice clicks from search results. Output only the description, no quotes.",
				wp_strip_all_tags( $post->post_title ),
				$content,
				$focus_keyword
			);
		}

		$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
			'timeout' => 30,
			'headers' => array(
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
				'content-type'      => 'application/json',
			),
			'body' => wp_json_encode( array(
				'model'      => 'claude-haiku-4-5-20251001',
				'max_tokens' => 200,
				'messages'   => array(
					array( 'role' => 'user', 'content' => $prompt ),
				),
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $body['content'][0]['text'] ) ) {
			return trim( $body['content'][0]['text'] );
		}

		return new WP_Error( 'api_error', 'AI API error' );
	}

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

		// description
		$content = wp_strip_all_tags( $post->post_content );
		if ( $focus_keyword ) {
			$pos  = stripos( $content, $focus_keyword );
			if ( $pos !== false ) {
				$start  = max( 0, $pos - 50 );
				$desc   = substr( $content, $start, 155 );
			} else {
				$desc = substr( $content, 0, 155 );
			}
		} else {
			$desc = substr( $content, 0, 155 );
		}
		return trim( $desc ) . '…';
	}

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

		// 5. Keyword in URL
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

		// 6. Keyword density
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
				$checks[] = array( 'type' => 'warning', 'msg' => sprintf( __( 'Keyword density is too high (%.1f%%). Avoid keyword stuffing.', 'rankpilot-seo' ), $density ) );
			} else {
				$checks[] = array( 'type' => 'info', 'msg' => sprintf( __( 'Keyword appears only %d time(s). Consider using it more.', 'rankpilot-seo' ), $kw_count ) );
			}
		}

		// 7. Content length
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
			$checks[] = array( 'type' => 'warning', 'msg' => __( 'No featured image set. Images improve CTR in search results.', 'rankpilot-seo' ) );
		}

		// 9. Internal links
		$possible++;
		$link_count = substr_count( $content, '<a ' );
		if ( $link_count > 0 ) {
			$checks[] = array( 'type' => 'good', 'msg' => sprintf( __( 'Content contains %d link(s).', 'rankpilot-seo' ), $link_count ) );
			$score++;
		} else {
			$checks[] = array( 'type' => 'info', 'msg' => __( 'No links found in content. Adding internal links helps SEO.', 'rankpilot-seo' ) );
		}

		// WooCommerce extra checks
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

	public function get_settings( $request ) {
		$group = sanitize_key( $request->get_param( 'group' ) );
		$allowed = array( 'rp_seo_general', 'rp_seo_social', 'rp_seo_sitemap', 'rp_seo_breadcrumbs', 'rp_seo_woocommerce' );
		if ( ! in_array( $group, $allowed, true ) ) {
			return new WP_Error( 'invalid_group', 'Invalid settings group', array( 'status' => 400 ) );
		}
		return rest_ensure_response( get_option( $group, array() ) );
	}

	public function save_settings( $request ) {
		$group = sanitize_key( $request->get_param( 'group' ) );
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
