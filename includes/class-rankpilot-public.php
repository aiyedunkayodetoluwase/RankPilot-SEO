<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all frontend <head> meta tag output.
 */
class RankPilot_Public {

	public function __construct() {
		// Title tag
		add_filter( 'pre_get_document_title', array( $this, 'filter_title' ), 20 );
		add_filter( 'wp_title',               array( $this, 'filter_wp_title' ), 20, 3 );

		// Head meta
		add_action( 'wp_head', array( $this, 'output_head_meta' ), 1 );

		// Remove WordPress generator tag if enabled
		$general = get_option( 'rp_seo_general', array() );
		if ( ! empty( $general['remove_wp_generator'] ) ) {
			remove_action( 'wp_head', 'wp_generator' );
		}

		// Remove canonical already added by WP core (we manage ours)
		remove_action( 'wp_head', 'rel_canonical' );
	}

	// ──────────────────────────────────────────
	// TITLE FILTERS
	// ──────────────────────────────────────────

	public function filter_title( $title ) {
		$general  = get_option( 'rp_seo_general', array() );
		$sep      = isset( $general['separator'] ) ? html_entity_decode( $general['separator'] ) : '–';
		$sitename = isset( $general['site_name'] ) ? $general['site_name'] : get_bloginfo( 'name' );

		if ( is_singular() ) {
			$post_id  = get_queried_object_id();
			$custom   = get_post_meta( $post_id, '_rp_seo_title', true );
			if ( $custom ) {
				return $custom;
			}
			$post_type = get_post_type( $post_id );
			$tpl_key   = 'post' === $post_type ? 'post_title_tpl' : ( 'product' === $post_type ? 'product_title_tpl' : 'page_title_tpl' );
			$tpl       = isset( $general[ $tpl_key ] ) ? $general[ $tpl_key ] : '%%title%% %%sep%% %%sitename%%';
			return RankPilot_Plugin::resolve_title_template( $tpl, get_queried_object() );
		}

		if ( is_front_page() || is_home() ) {
			$homepage_title = isset( $general['homepage_title'] ) ? $general['homepage_title'] : '';
			return $homepage_title ?: get_bloginfo( 'name' ) . ' ' . $sep . ' ' . get_bloginfo( 'description' );
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			$tpl  = isset( $general['archive_title_tpl'] ) ? $general['archive_title_tpl'] : '%%term_title%% Archives %%sep%% %%sitename%%';
			return RankPilot_Plugin::resolve_title_template( $tpl );
		}

		if ( is_archive() ) {
			return get_the_archive_title() . ' ' . $sep . ' ' . $sitename;
		}

		if ( is_search() ) {
			/* translators: %s: search query */
			return sprintf( __( 'Search: %s', 'rankpilot-seo' ), get_search_query() ) . ' ' . $sep . ' ' . $sitename;
		}

		if ( is_404() ) {
			return __( 'Page Not Found', 'rankpilot-seo' ) . ' ' . $sep . ' ' . $sitename;
		}

		return $title;
	}

	public function filter_wp_title( $title, $sep, $seplocation ) {
		return $this->filter_title( $title );
	}

	// ──────────────────────────────────────────
	// HEAD META OUTPUT
	// ──────────────────────────────────────────

	public function output_head_meta() {
		// Theme support check — only output title tag if theme declares it
		if ( ! current_theme_supports( 'title-tag' ) ) {
			$title = $this->filter_title( '' );
			echo '<title>' . esc_html( $title ) . '</title>' . "\n";
		}

		echo "\n<!-- RankPilot SEO " . esc_html( RP_SEO_VERSION ) . " -->\n";

		$this->output_robots();
		$this->output_description();
		$this->output_canonical();
		$this->output_opengraph();
		$this->output_twitter_card();

		echo "<!-- / RankPilot SEO -->\n\n";
	}

	private function output_robots() {
		$general  = get_option( 'rp_seo_general', array() );
		$directives = array();

		if ( is_singular() ) {
			$post_id   = get_queried_object_id();
			$noindex   = get_post_meta( $post_id, '_rp_seo_noindex', true ) ?: 'index';
			$nofollow  = get_post_meta( $post_id, '_rp_seo_nofollow', true ) ?: 'follow';
			$noarchive = (int) get_post_meta( $post_id, '_rp_seo_noarchive', true );
			$nosnippet = (int) get_post_meta( $post_id, '_rp_seo_nosnippet', true );

			$directives[] = sanitize_key( $noindex );
			$directives[] = sanitize_key( $nofollow );
			if ( $noarchive ) {
				$directives[] = 'noarchive';
			}
			if ( $nosnippet ) {
				$directives[] = 'nosnippet';
			}
		} elseif (
			( is_archive() && ! empty( $general['noindex_archives'] ) ) ||
			( is_search() && ! empty( $general['noindex_search'] ) ) ||
			( is_404() && ! empty( $general['noindex_404'] ) )
		) {
			$directives = array( 'noindex', 'follow' );
		} else {
			$directives = array( 'index', 'follow' );
		}

		$robots = apply_filters( 'rp_seo_robots_directives', $directives );
		echo '<meta name="robots" content="' . esc_attr( implode( ', ', $robots ) ) . '">' . "\n";
	}

	private function output_description() {
		$desc = $this->get_description();
		if ( $desc ) {
			echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
	}

	private function get_description() {
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			$desc    = get_post_meta( $post_id, '_rp_seo_description', true );
			if ( $desc ) {
				return $desc;
			}
			$post = get_post( $post_id );
			if ( $post->post_excerpt ) {
				return wp_strip_all_tags( $post->post_excerpt );
			}
			return wp_trim_words( wp_strip_all_tags( $post->post_content ), 25, '...' );
		}

		if ( is_front_page() || is_home() ) {
			$general = get_option( 'rp_seo_general', array() );
			return isset( $general['homepage_desc'] ) ? $general['homepage_desc'] : get_bloginfo( 'description' );
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			return $term ? wp_strip_all_tags( $term->description ) : '';
		}

		return '';
	}

	private function output_canonical() {
		$canonical = '';

		if ( is_singular() ) {
			$post_id   = get_queried_object_id();
			$canonical = get_post_meta( $post_id, '_rp_seo_canonical', true );
			if ( ! $canonical ) {
				$canonical = get_permalink( $post_id );
			}
		} elseif ( is_front_page() ) {
			$canonical = home_url( '/' );
		} elseif ( is_home() ) {
			$canonical = get_permalink( get_option( 'page_for_posts' ) );
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$term      = get_queried_object();
			$canonical = $term ? get_term_link( $term ) : '';
		} elseif ( is_archive() ) {
			$canonical = get_the_archive_link();
		}

		if ( get_query_var( 'paged' ) > 1 ) {
			$canonical = trailingslashit( $canonical ) . 'page/' . get_query_var( 'paged' ) . '/';
		}

		if ( $canonical && ! is_wp_error( $canonical ) ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
		}
	}

	private function output_opengraph() {
		$social = get_option( 'rp_seo_social', array() );
		if ( empty( $social['og_enabled'] ) ) {
			return;
		}

		$title   = '';
		$desc    = $this->get_description();
		$image   = '';
		$url     = '';
		$type    = 'website';

		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			$post    = get_post( $post_id );

			$title = get_post_meta( $post_id, '_rp_seo_og_title', true )
				?: get_post_meta( $post_id, '_rp_seo_title', true )
				?: get_the_title( $post_id );
			$desc  = get_post_meta( $post_id, '_rp_seo_og_description', true ) ?: $desc;
			$image = get_post_meta( $post_id, '_rp_seo_og_image', true );

			if ( ! $image ) {
				$thumb_id = get_post_thumbnail_id( $post_id );
				if ( $thumb_id ) {
					$thumb = wp_get_attachment_image_src( $thumb_id, 'large' );
					$image = $thumb ? $thumb[0] : '';
				}
			}

			$url  = get_permalink( $post_id );
			$type = ( 'post' === $post->post_type || 'product' === $post->post_type ) ? 'article' : 'website';
		} else {
			$general = get_option( 'rp_seo_general', array() );
			$title   = isset( $general['homepage_title'] ) && $general['homepage_title']
				? $general['homepage_title']
				: get_bloginfo( 'name' );
			$url     = home_url( '/' );
		}

		if ( ! $image ) {
			$image = isset( $social['og_default_image'] ) ? $social['og_default_image'] : '';
		}

		$sitename = isset( $social['org_name'] ) && $social['org_name'] ? $social['org_name'] : get_bloginfo( 'name' );

		if ( ! empty( $social['fb_app_id'] ) ) {
			echo '<meta property="fb:app_id" content="' . esc_attr( $social['fb_app_id'] ) . '">' . "\n";
		}
		echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '">' . "\n";
		echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		if ( $desc ) {
			echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		if ( $url ) {
			echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		}
		echo '<meta property="og:site_name" content="' . esc_attr( $sitename ) . '">' . "\n";
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
			$img_post_id = attachment_url_to_postid( $image );
			if ( $img_post_id ) {
				$meta = wp_get_attachment_metadata( $img_post_id );
				if ( $meta && isset( $meta['width'], $meta['height'] ) ) {
					echo '<meta property="og:image:width" content="' . absint( $meta['width'] ) . '">' . "\n";
					echo '<meta property="og:image:height" content="' . absint( $meta['height'] ) . '">' . "\n";
				}
			}
		}

		if ( 'article' === $type && is_singular() ) {
			$post_id = get_queried_object_id();
			$post    = get_post( $post_id );
			echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . "\n";
			echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c', $post_id ) ) . '">' . "\n";
			$author = get_the_author_meta( 'display_name', (int) $post->post_author );
			echo '<meta property="article:author" content="' . esc_attr( $author ) . '">' . "\n";

			$categories = get_the_category( $post_id );
			if ( $categories ) {
				echo '<meta property="article:section" content="' . esc_attr( $categories[0]->name ) . '">' . "\n";
			}

			$tags = get_the_tags( $post_id );
			if ( $tags ) {
				foreach ( $tags as $tag ) {
					echo '<meta property="article:tag" content="' . esc_attr( $tag->name ) . '">' . "\n";
				}
			}
		}
	}

	private function output_twitter_card() {
		$social = get_option( 'rp_seo_social', array() );
		if ( empty( $social['twitter_enabled'] ) ) {
			return;
		}

		$card  = isset( $social['twitter_card_type'] ) ? $social['twitter_card_type'] : 'summary_large_image';
		$site  = isset( $social['twitter_site'] ) ? $social['twitter_site'] : '';

		$title = '';
		$desc  = $this->get_description();
		$image = '';

		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			$title   = get_post_meta( $post_id, '_rp_seo_twitter_title', true )
				?: get_post_meta( $post_id, '_rp_seo_og_title', true )
				?: get_post_meta( $post_id, '_rp_seo_title', true )
				?: get_the_title( $post_id );
			$desc    = get_post_meta( $post_id, '_rp_seo_twitter_description', true )
				?: get_post_meta( $post_id, '_rp_seo_og_description', true )
				?: $desc;
			$image   = get_post_meta( $post_id, '_rp_seo_twitter_image', true )
				?: get_post_meta( $post_id, '_rp_seo_og_image', true );

			if ( ! $image ) {
				$thumb_id = get_post_thumbnail_id( $post_id );
				if ( $thumb_id ) {
					$thumb = wp_get_attachment_image_src( $thumb_id, 'large' );
					$image = $thumb ? $thumb[0] : '';
				}
			}
		} else {
			$title = get_bloginfo( 'name' );
		}

		if ( ! $image ) {
			$image = isset( $social['og_default_image'] ) ? $social['og_default_image'] : '';
		}

		echo '<meta name="twitter:card" content="' . esc_attr( $card ) . '">' . "\n";
		if ( $site ) {
			$site = ltrim( $site, '@' );
			echo '<meta name="twitter:site" content="@' . esc_attr( $site ) . '">' . "\n";
		}
		if ( $title ) {
			echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		}
		if ( $desc ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		if ( $image ) {
			echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
		}
	}
}
