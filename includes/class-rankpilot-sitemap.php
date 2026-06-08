<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RankPilot_Sitemap {

	public function __construct() {
		add_action( 'init',            array( $this, 'add_rewrite_rules' ) );
		add_action( 'template_redirect', array( $this, 'handle_sitemap_request' ) );
		add_action( 'publish_post',    array( $this, 'ping_search_engines' ) );
		add_action( 'publish_page',    array( $this, 'ping_search_engines' ) );
		add_filter( 'redirect_canonical', array( $this, 'prevent_sitemap_redirect' ), 10, 2 );

		// Add link in <head>
		add_action( 'wp_head', array( $this, 'output_sitemap_link' ) );
	}

	public function add_rewrite_rules() {
		$options = get_option( 'rp_seo_sitemap', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}
		add_rewrite_rule( '^sitemap_index\.xml$',    'index.php?rp_seo_sitemap=index',    'top' );
		add_rewrite_rule( '^sitemap-posts\.xml$',    'index.php?rp_seo_sitemap=posts',    'top' );
		add_rewrite_rule( '^sitemap-pages\.xml$',    'index.php?rp_seo_sitemap=pages',    'top' );
		add_rewrite_rule( '^sitemap-products\.xml$', 'index.php?rp_seo_sitemap=products', 'top' );
		add_rewrite_rule( '^sitemap-terms\.xml$',    'index.php?rp_seo_sitemap=terms',    'top' );
		add_rewrite_rule( '^sitemap\.xml$',          'index.php?rp_seo_sitemap=index',    'top' );
		add_rewrite_tag( '%rp_seo_sitemap%', '([^&]+)' );
	}

	public function prevent_sitemap_redirect( $redirect_url, $requested_url ) {
		if ( get_query_var( 'rp_seo_sitemap' ) ) {
			return false;
		}
		return $redirect_url;
	}

	public function handle_sitemap_request() {
		$sitemap = get_query_var( 'rp_seo_sitemap' );
		if ( ! $sitemap ) {
			return;
		}

		$options = get_option( 'rp_seo_sitemap', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: application/xml; charset=UTF-8' );

		switch ( $sitemap ) {
			case 'index':
				$this->render_index();
				break;
			case 'posts':
				$this->render_post_type_sitemap( 'post' );
				break;
			case 'pages':
				$this->render_post_type_sitemap( 'page' );
				break;
			case 'products':
				if ( class_exists( 'WooCommerce' ) ) {
					$this->render_post_type_sitemap( 'product' );
				}
				break;
			case 'terms':
				$this->render_terms_sitemap();
				break;
		}

		exit;
	}

	private function render_index() {
		$options  = get_option( 'rp_seo_sitemap', array() );
		$sitemaps = array();

		if ( ! empty( $options['include_post'] ) ) {
			$sitemaps[] = array( 'url' => home_url( '/sitemap-posts.xml' ), 'lastmod' => $this->get_latest_post_date( 'post' ) );
		}
		if ( ! empty( $options['include_page'] ) ) {
			$sitemaps[] = array( 'url' => home_url( '/sitemap-pages.xml' ), 'lastmod' => $this->get_latest_post_date( 'page' ) );
		}
		if ( ! empty( $options['include_product'] ) && class_exists( 'WooCommerce' ) ) {
			$sitemaps[] = array( 'url' => home_url( '/sitemap-products.xml' ), 'lastmod' => $this->get_latest_post_date( 'product' ) );
		}
		if ( ! empty( $options['include_category'] ) || ! empty( $options['include_post_tag'] ) ) {
			$sitemaps[] = array( 'url' => home_url( '/sitemap-terms.xml' ), 'lastmod' => gmdate( 'Y-m-d' ) );
		}

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( RP_SEO_PLUGIN_URL . 'public/sitemap.xsl' ) . '"?>' . "\n";
		echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $sitemaps as $sm ) {
			echo "\t<sitemap>\n";
			echo "\t\t<loc>" . esc_url( $sm['url'] ) . "</loc>\n";
			if ( $sm['lastmod'] ) {
				echo "\t\t<lastmod>" . esc_html( $sm['lastmod'] ) . "</lastmod>\n";
			}
			echo "\t</sitemap>\n";
		}

		echo '</sitemapindex>';
	}

	private function render_post_type_sitemap( $post_type ) {
		$options      = get_option( 'rp_seo_sitemap', array() );
		$per_page     = isset( $options['posts_per_page'] ) ? absint( $options['posts_per_page'] ) : 1000;
		$exclude_ids  = $this->get_exclude_ids( $options );

		$args = array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => $per_page,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'post__not_in'           => $exclude_ids,
			'meta_query'             => array(),
		);

		// Exclude noindex posts
		if ( ! empty( $options['exclude_noindex'] ) ) {
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array( 'key' => '_rp_seo_noindex', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_rp_seo_noindex', 'value' => 'noindex', 'compare' => '!=' ),
			);
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array( 'key' => '_rp_seo_exclude_sitemap', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_rp_seo_exclude_sitemap', 'value' => '1', 'compare' => '!=' ),
			);
		}

		$query = new WP_Query( $args );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';

		if ( ! empty( $options['include_images'] ) ) {
			echo ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
		}

		echo '>' . "\n";

		// Homepage in posts sitemap
		if ( 'post' === $post_type ) {
			echo "\t<url>\n";
			echo "\t\t<loc>" . esc_url( home_url( '/' ) ) . "</loc>\n";
			echo "\t\t<changefreq>daily</changefreq>\n";
			echo "\t\t<priority>1.0</priority>\n";
			echo "\t</url>\n";
		}

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id   = get_the_ID();
				$permalink = get_permalink( $post_id );
				$modified  = get_the_modified_date( 'Y-m-d' );

				echo "\t<url>\n";
				echo "\t\t<loc>" . esc_url( $permalink ) . "</loc>\n";
				echo "\t\t<lastmod>" . esc_html( $modified ) . "</lastmod>\n";
				echo "\t\t<changefreq>" . esc_html( $this->get_changefreq( $post_type ) ) . "</changefreq>\n";
				echo "\t\t<priority>" . esc_html( $this->get_priority( $post_type ) ) . "</priority>\n";

				// Images
				if ( ! empty( $options['include_images'] ) ) {
					$images = $this->get_post_images( $post_id );
					foreach ( $images as $img ) {
						echo "\t\t<image:image>\n";
						echo "\t\t\t<image:loc>" . esc_url( $img['url'] ) . "</image:loc>\n";
						if ( $img['title'] ) {
							echo "\t\t\t<image:title>" . esc_html( $img['title'] ) . "</image:title>\n";
						}
						echo "\t\t</image:image>\n";
					}
				}

				echo "\t</url>\n";
			}
			wp_reset_postdata();
		}

		echo '</urlset>';
	}

	private function render_terms_sitemap() {
		$options    = get_option( 'rp_seo_sitemap', array() );
		$taxonomies = array();

		if ( ! empty( $options['include_category'] ) ) {
			$taxonomies[] = 'category';
		}
		if ( ! empty( $options['include_post_tag'] ) ) {
			$taxonomies[] = 'post_tag';
		}
		if ( class_exists( 'WooCommerce' ) ) {
			$taxonomies[] = 'product_cat';
			$taxonomies[] = 'product_tag';
		}

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $taxonomies as $tax ) {
			$terms = get_terms( array(
				'taxonomy'   => $tax,
				'hide_empty' => true,
			) );
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$url = get_term_link( $term );
				if ( is_wp_error( $url ) ) {
					continue;
				}
				echo "\t<url>\n";
				echo "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
				echo "\t\t<changefreq>weekly</changefreq>\n";
				echo "\t\t<priority>0.6</priority>\n";
				echo "\t</url>\n";
			}
		}

		echo '</urlset>';
	}

	private function get_post_images( $post_id ) {
		$images = array();
		$thumb  = get_post_thumbnail_id( $post_id );
		if ( $thumb ) {
			$src = wp_get_attachment_image_src( $thumb, 'full' );
			if ( $src ) {
				$images[] = array(
					'url'   => $src[0],
					'title' => get_the_title( $thumb ),
				);
			}
		}

		// Attached images
		$attached = get_posts( array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_parent'    => $post_id,
			'posts_per_page' => 5,
			'post__not_in'   => array( $thumb ),
			'fields'         => 'ids',
		) );
		foreach ( $attached as $att_id ) {
			$src = wp_get_attachment_image_src( $att_id, 'full' );
			if ( $src ) {
				$images[] = array(
					'url'   => $src[0],
					'title' => get_the_title( $att_id ),
				);
			}
		}

		return $images;
	}

	private function get_latest_post_date( $post_type ) {
		$posts = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'fields'         => 'ids',
		) );
		if ( ! empty( $posts ) ) {
			return get_the_modified_date( 'Y-m-d', $posts[0] );
		}
		return gmdate( 'Y-m-d' );
	}

	private function get_exclude_ids( $options ) {
		$ids = array();
		if ( ! empty( $options['exclude_ids'] ) ) {
			$raw = explode( ',', $options['exclude_ids'] );
			foreach ( $raw as $id ) {
				$ids[] = absint( trim( $id ) );
			}
		}
		return array_filter( $ids );
	}

	private function get_changefreq( $post_type ) {
		$map = array(
			'post'    => 'weekly',
			'page'    => 'monthly',
			'product' => 'weekly',
		);
		return isset( $map[ $post_type ] ) ? $map[ $post_type ] : 'monthly';
	}

	private function get_priority( $post_type ) {
		$map = array(
			'post'    => '0.7',
			'page'    => '0.8',
			'product' => '0.7',
		);
		return isset( $map[ $post_type ] ) ? $map[ $post_type ] : '0.5';
	}

	public function ping_search_engines( $post_id ) {
		$options = get_option( 'rp_seo_sitemap', array() );
		$sitemap_url = home_url( '/sitemap.xml' );

		if ( ! empty( $options['ping_google'] ) ) {
			wp_remote_get( 'https://www.google.com/ping?sitemap=' . rawurlencode( $sitemap_url ), array( 'timeout' => 5, 'blocking' => false ) );
		}
		if ( ! empty( $options['ping_bing'] ) ) {
			wp_remote_get( 'https://www.bing.com/ping?sitemap=' . rawurlencode( $sitemap_url ), array( 'timeout' => 5, 'blocking' => false ) );
		}
	}

	public function output_sitemap_link() {
		$options = get_option( 'rp_seo_sitemap', array() );
		if ( ! empty( $options['enabled'] ) ) {
			echo '<link rel="sitemap" type="application/xml" title="Sitemap" href="' . esc_url( home_url( '/sitemap.xml' ) ) . '">' . "\n";
		}
	}
}
