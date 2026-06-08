<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs JSON-LD structured data (Schema.org).
 */
class RankPilot_Schema {

	public function __construct() {
		add_action( 'wp_head', array( $this, 'output_schema' ), 90 );
	}

	public function output_schema() {
		$graphs = array();

		// WebSite with SearchAction (always)
		$graphs[] = $this->get_website_schema();

		// Organization / Person
		$graphs[] = $this->get_identity_schema();

		// Page-specific
		if ( is_singular() ) {
			$graphs[] = $this->get_singular_schema();
			if ( class_exists( 'WooCommerce' ) && is_product() ) {
				$product_schema = $this->get_product_schema();
				if ( $product_schema ) {
					$graphs[] = $product_schema;
				}
			}
		} elseif ( is_archive() || is_category() || is_tag() || is_tax() ) {
			$graphs[] = $this->get_collection_schema();
		}

		// BreadcrumbList
		$breadcrumb_schema = $this->get_breadcrumb_schema();
		if ( $breadcrumb_schema ) {
			$graphs[] = $breadcrumb_schema;
		}

		$graphs = array_filter( $graphs );

		if ( empty( $graphs ) ) {
			return;
		}

		$output = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graphs,
		);

		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		echo "\n</script>\n";
	}

	private function get_website_schema() {
		$social   = get_option( 'rp_seo_social', array() );
		$org_name = isset( $social['org_name'] ) && $social['org_name'] ? $social['org_name'] : get_bloginfo( 'name' );

		return array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'url'             => home_url( '/' ),
			'name'            => $org_name,
			'description'     => get_bloginfo( 'description' ),
			'potentialAction' => array(
				array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => home_url( '/?s={search_term_string}' ),
					),
					'query-input' => 'required name=search_term_string',
				),
			),
			'inLanguage'      => get_locale(),
		);
	}

	private function get_identity_schema() {
		$social      = get_option( 'rp_seo_social', array() );
		$general     = get_option( 'rp_seo_general', array() );
		$schema_type = isset( $general['schema_type'] ) ? $general['schema_type'] : 'Organization';

		$org_name = isset( $social['org_name'] ) && $social['org_name'] ? $social['org_name'] : get_bloginfo( 'name' );
		$org_logo = isset( $social['org_logo'] ) ? $social['org_logo'] : '';

		$same_as = array_filter( array(
			isset( $social['facebook_url'] )  ? $social['facebook_url']  : '',
			isset( $social['twitter_url'] )   ? $social['twitter_url']   : '',
			isset( $social['linkedin_url'] )  ? $social['linkedin_url']  : '',
			isset( $social['instagram_url'] ) ? $social['instagram_url'] : '',
			isset( $social['youtube_url'] )   ? $social['youtube_url']   : '',
			isset( $social['pinterest_url'] ) ? $social['pinterest_url'] : '',
		) );

		$schema = array(
			'@type'  => 'Organization' === $schema_type ? 'Organization' : 'Person',
			'@id'    => home_url( '/#identity' ),
			'name'   => $org_name,
			'url'    => home_url( '/' ),
			'sameAs' => array_values( $same_as ),
		);

		if ( $org_logo ) {
			$schema['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $org_logo,
			);
		}

		return $schema;
	}

	private function get_singular_schema() {
		$post_id      = get_queried_object_id();
		$post         = get_post( $post_id );
		$custom_type  = get_post_meta( $post_id, '_rp_seo_schema_type', true );
		$article_type = get_post_meta( $post_id, '_rp_seo_schema_article_type', true ) ?: 'Article';
		$general      = get_option( 'rp_seo_general', array() );

		// Determine schema type
		if ( $custom_type ) {
			$schema_type = $custom_type;
		} elseif ( 'page' === $post->post_type ) {
			$schema_type = 'WebPage';
		} else {
			$schema_type = $article_type;
		}

		$title = get_post_meta( $post_id, '_rp_seo_title', true ) ?: get_the_title( $post_id );
		$desc  = get_post_meta( $post_id, '_rp_seo_description', true );
		if ( ! $desc ) {
			$desc = $post->post_excerpt
				? wp_strip_all_tags( $post->post_excerpt )
				: wp_trim_words( wp_strip_all_tags( $post->post_content ), 25, '...' );
		}

		$schema = array(
			'@type'            => $schema_type,
			'@id'              => get_permalink( $post_id ) . '#' . strtolower( $schema_type ),
			'url'              => get_permalink( $post_id ),
			'name'             => $title,
			'isPartOf'         => array( '@id' => home_url( '/#website' ) ),
			'datePublished'    => get_the_date( 'c', $post_id ),
			'dateModified'     => get_the_modified_date( 'c', $post_id ),
			'inLanguage'       => get_locale(),
		);

		if ( $desc ) {
			$schema['description'] = $desc;
		}

		// Featured image
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( $thumb_id ) {
			$thumb = wp_get_attachment_image_src( $thumb_id, 'large' );
			if ( $thumb ) {
				$img_meta = wp_get_attachment_metadata( $thumb_id );
				$schema['image'] = array(
					'@type'   => 'ImageObject',
					'url'     => $thumb[0],
					'width'   => $img_meta ? $img_meta['width'] : $thumb[1],
					'height'  => $img_meta ? $img_meta['height'] : $thumb[2],
				);
			}
		}

		// Author (for articles)
		if ( in_array( $schema_type, array( 'Article', 'BlogPosting', 'NewsArticle', 'TechArticle' ), true ) ) {
			$author_id   = (int) $post->post_author;
			$schema['author'] = array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', $author_id ),
				'url'   => get_author_posts_url( $author_id ),
			);
			$schema['publisher'] = array( '@id' => home_url( '/#identity' ) );
		}

		return $schema;
	}

	private function get_product_schema() {
		if ( ! class_exists( 'WC_Product' ) ) {
			return null;
		}

		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product ) {
			return null;
		}

		$woo     = get_option( 'rp_seo_woocommerce', array() );
		$post_id = get_queried_object_id();
		$title   = get_post_meta( $post_id, '_rp_seo_title', true ) ?: $product->get_name();
		$desc    = get_post_meta( $post_id, '_rp_seo_description', true ) ?: wp_strip_all_tags( $product->get_short_description() );

		$schema = array(
			'@type'       => 'Product',
			'@id'         => get_permalink( $post_id ) . '#product',
			'name'        => $title,
			'url'         => get_permalink( $post_id ),
			'description' => $desc,
			'sku'         => $product->get_sku() ?: '',
		);

		// Image
		$thumb_id = $product->get_image_id();
		if ( $thumb_id ) {
			$img = wp_get_attachment_image_src( $thumb_id, 'large' );
			if ( $img ) {
				$schema['image'] = $img[0];
			}
		}

		// Offers
		if ( ! empty( $woo['include_price'] ) ) {
			$offer = array(
				'@type'         => 'Offer',
				'url'           => get_permalink( $post_id ),
				'priceCurrency' => get_woocommerce_currency(),
				'price'         => $product->get_price(),
				'priceValidUntil' => gmdate( 'Y-12-31' ),
			);

			if ( ! empty( $woo['include_stock'] ) ) {
				$offer['availability'] = $product->is_in_stock()
					? 'https://schema.org/InStock'
					: 'https://schema.org/OutOfStock';
				$offer['itemCondition'] = 'https://schema.org/NewCondition';
			}

			$schema['offers'] = $offer;
		}

		// Reviews / ratings
		if ( ! empty( $woo['include_ratings'] ) && $product->get_rating_count() > 0 ) {
			$schema['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'reviewCount' => $product->get_review_count(),
				'bestRating'  => '5',
				'worstRating' => '1',
			);
		}

		// Brand (if product has a brand attribute)
		$brand = $product->get_attribute( 'pa_brand' );
		if ( ! $brand ) {
			$brand = $product->get_attribute( 'brand' );
		}
		if ( $brand ) {
			$schema['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brand,
			);
		}

		return $schema;
	}

	private function get_collection_schema() {
		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			return array(
				'@type'       => 'CollectionPage',
				'@id'         => get_term_link( $term ) . '#webpage',
				'url'         => get_term_link( $term ),
				'name'        => $term->name,
				'description' => wp_strip_all_tags( $term->description ),
				'isPartOf'    => array( '@id' => home_url( '/#website' ) ),
				'inLanguage'  => get_locale(),
			);
		}
		return null;
	}

	private function get_breadcrumb_schema() {
		$bc_options = get_option( 'rp_seo_breadcrumbs', array() );
		if ( empty( $bc_options['schema_enabled'] ) ) {
			return null;
		}
		if ( is_front_page() ) {
			return null;
		}

		require_once RP_SEO_PLUGIN_DIR . 'includes/class-rankpilot-breadcrumbs.php';
		$breadcrumbs = RankPilot_Breadcrumbs::get_breadcrumbs();
		if ( empty( $breadcrumbs ) ) {
			return null;
		}

		$items = array();
		foreach ( $breadcrumbs as $i => $crumb ) {
			$item = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $crumb['name'],
			);
			if ( ! empty( $crumb['url'] ) ) {
				$item['item'] = $crumb['url'];
			}
			$items[] = $item;
		}

		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => get_the_permalink() . '#breadcrumb',
			'itemListElement' => $items,
		);
	}
}
