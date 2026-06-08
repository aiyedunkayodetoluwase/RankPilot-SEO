<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RankPilot_WooCommerce {

	public function __construct() {
		$options = get_option( 'rp_seo_woocommerce', array() );

		// Remove WooCommerce's own breadcrumbs if we're replacing them
		if ( ! empty( $options['breadcrumb_replace'] ) ) {
			add_filter( 'woocommerce_breadcrumb_defaults', '__return_empty_array' );
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
			add_action( 'woocommerce_before_main_content', array( $this, 'replace_woo_breadcrumb' ), 20 );
		}

		// Filter OG image to use product gallery images
		if ( ! empty( $options['og_product_gallery'] ) ) {
			add_filter( 'rp_seo_og_image', array( $this, 'og_product_gallery_image' ) );
		}

		// Exclude WooCommerce utility pages from sitemap
		if ( ! empty( $options['hide_cart_checkout'] ) ) {
			add_filter( 'rp_seo_sitemap_exclude_ids', array( $this, 'exclude_woo_pages' ) );
		}

		// Add WooCommerce-specific SEO checks to meta box analysis
		add_filter( 'rp_seo_analysis_checks', array( $this, 'add_woo_analysis_checks' ), 10, 2 );

		// Primary category support for product categories
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_primary_category_field' ) );
	}

	public function replace_woo_breadcrumb() {
		rankpilot_breadcrumb();
	}

	public function og_product_gallery_image( $image ) {
		if ( ! is_product() ) {
			return $image;
		}
		if ( $image ) {
			return $image;
		}
		$product     = wc_get_product( get_queried_object_id() );
		$gallery_ids = $product ? $product->get_gallery_image_ids() : array();
		if ( ! empty( $gallery_ids ) ) {
			$src = wp_get_attachment_image_src( $gallery_ids[0], 'large' );
			return $src ? $src[0] : $image;
		}
		return $image;
	}

	public function exclude_woo_pages( $ids ) {
		$pages_to_exclude = array(
			wc_get_page_id( 'cart' ),
			wc_get_page_id( 'checkout' ),
			wc_get_page_id( 'myaccount' ),
		);
		return array_merge( $ids, array_filter( $pages_to_exclude ) );
	}

	public function add_woo_analysis_checks( $checks, $post_id ) {
		if ( 'product' !== get_post_type( $post_id ) ) {
			return $checks;
		}

		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			return $checks;
		}

		// Check short description
		if ( ! $product->get_short_description() ) {
			$checks[] = array(
				'type'    => 'warning',
				'message' => __( 'Product has no short description. Adding one improves SEO and conversion.', 'rankpilot-seo' ),
			);
		} else {
			$checks[] = array(
				'type'    => 'good',
				'message' => __( 'Product has a short description.', 'rankpilot-seo' ),
			);
		}

		// Check gallery images
		$gallery = $product->get_gallery_image_ids();
		if ( empty( $gallery ) ) {
			$checks[] = array(
				'type'    => 'info',
				'message' => __( 'No product gallery images. Gallery images can improve click-through rate.', 'rankpilot-seo' ),
			);
		}

		// Check SKU
		if ( ! $product->get_sku() ) {
			$checks[] = array(
				'type'    => 'info',
				'message' => __( 'Product has no SKU. Adding a SKU improves schema markup.', 'rankpilot-seo' ),
			);
		}

		// Check product image alt text
		$image_id = $product->get_image_id();
		if ( $image_id ) {
			$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			if ( ! $alt ) {
				$checks[] = array(
					'type'    => 'warning',
					'message' => __( 'Product image has no alt text. Alt text is important for image SEO.', 'rankpilot-seo' ),
				);
			} else {
				$checks[] = array(
					'type'    => 'good',
					'message' => __( 'Product image has alt text.', 'rankpilot-seo' ),
				);
			}
		}

		return $checks;
	}

	public function add_primary_category_field() {
		global $post;
		$primary = get_post_meta( $post->ID, '_rp_seo_primary_product_cat', true );
		$terms   = get_the_terms( $post->ID, 'product_cat' );

		echo '<div class="options_group">';
		echo '<p class="form-field"><label>' . esc_html__( 'Primary Category (RankPilot SEO)', 'rankpilot-seo' ) . '</label>';
		echo '<select name="rp_seo_primary_product_cat">';
		echo '<option value="">' . esc_html__( '— Auto —', 'rankpilot-seo' ) . '</option>';
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				printf(
					'<option value="%d"%s>%s</option>',
					absint( $term->term_id ),
					selected( $primary, $term->term_id, false ),
					esc_html( $term->name )
				);
			}
		}
		echo '</select></p>';
		echo '</div>';
	}
}
