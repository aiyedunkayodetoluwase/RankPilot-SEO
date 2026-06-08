<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RankPilot_Breadcrumbs {

	public function __construct() {
		add_shortcode( 'rankpilot_breadcrumbs', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	public function enqueue_styles() {
		$options = get_option( 'rp_seo_breadcrumbs', array() );
		if ( ! empty( $options['enabled'] ) ) {
			wp_enqueue_style(
				'rp-seo-breadcrumbs',
				RP_SEO_PLUGIN_URL . 'public/css/breadcrumbs.css',
				array(),
				RP_SEO_VERSION
			);
		}
	}

	/**
	 * Shortcode handler: [rankpilot_breadcrumbs]
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'separator' => '',
			'home'      => '',
		), $atts, 'rankpilot_breadcrumbs' );
		ob_start();
		$this->render( $atts );
		return ob_get_clean();
	}

	/**
	 * Template function for use in themes.
	 */
	public static function display( $args = array() ) {
		$instance = new self();
		$instance->render( $args );
	}

	/**
	 * Render the breadcrumb HTML.
	 */
	public function render( $args = array() ) {
		$options = get_option( 'rp_seo_breadcrumbs', array() );
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		$breadcrumbs = self::get_breadcrumbs();
		if ( empty( $breadcrumbs ) ) {
			return;
		}

		$sep       = isset( $args['separator'] ) && $args['separator'] ? $args['separator'] : ( isset( $options['separator'] ) ? $options['separator'] : ' &rsaquo; ' );
		$bold_last = ! empty( $options['bold_last'] );
		$prefix    = isset( $options['prefix'] ) ? $options['prefix'] : '';
		$total     = count( $breadcrumbs );

		echo '<nav class="rp-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'rankpilot-seo' ) . '">';
		echo '<ol class="rp-breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">';

		if ( $prefix ) {
			echo '<li class="rp-breadcrumbs__prefix">' . esc_html( $prefix ) . '</li>';
		}

		foreach ( $breadcrumbs as $i => $crumb ) {
			$is_last = ( $i === $total - 1 );
			$pos     = $i + 1;

			echo '<li class="rp-breadcrumbs__item' . ( $is_last ? ' rp-breadcrumbs__item--current' : '' ) . '" '
				. 'itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';

			if ( ! $is_last && ! empty( $crumb['url'] ) ) {
				echo '<a href="' . esc_url( $crumb['url'] ) . '" itemprop="item">';
				echo '<span itemprop="name">' . esc_html( $crumb['name'] ) . '</span>';
				echo '</a>';
			} else {
				$tag = ( $bold_last && $is_last ) ? 'strong' : 'span';
				echo '<' . esc_attr( $tag ) . ' itemprop="name" aria-current="page">' . esc_html( $crumb['name'] ) . '</' . esc_attr( $tag ) . '>';
			}

			echo '<meta itemprop="position" content="' . absint( $pos ) . '">';

			if ( ! $is_last ) {
				echo '<span class="rp-breadcrumbs__sep" aria-hidden="true">' . wp_kses_post( $sep ) . '</span>';
			}

			echo '</li>';
		}

		echo '</ol>';
		echo '</nav>';
	}

	/**
	 * Build the breadcrumb trail array.
	 *
	 * @return array Array of ['name', 'url'] pairs.
	 */
	public static function get_breadcrumbs() {
		$options     = get_option( 'rp_seo_breadcrumbs', array() );
		$home_label  = isset( $options['home_label'] ) ? $options['home_label'] : __( 'Home', 'rankpilot-seo' );
		$show_current = ! isset( $options['show_current'] ) || $options['show_current'];

		$breadcrumbs = array();
		$breadcrumbs[] = array( 'name' => $home_label, 'url' => home_url( '/' ) );

		if ( is_front_page() ) {
			return $breadcrumbs;
		}

		if ( is_home() ) {
			$breadcrumbs[] = array( 'name' => __( 'Blog', 'rankpilot-seo' ), 'url' => '' );
			return $breadcrumbs;
		}

		if ( is_singular() ) {
			$post    = get_queried_object();
			$pt      = $post->post_type;

			// WooCommerce product
			if ( 'product' === $pt && class_exists( 'WooCommerce' ) ) {
				$breadcrumbs[] = array( 'name' => __( 'Shop', 'rankpilot-seo' ), 'url' => get_permalink( wc_get_page_id( 'shop' ) ) );
				$terms = get_the_terms( $post->ID, 'product_cat' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$primary = self::get_primary_term( $terms, 'product_cat' );
					if ( $primary ) {
						$ancestors = get_ancestors( $primary->term_id, 'product_cat', 'taxonomy' );
						foreach ( array_reverse( $ancestors ) as $anc_id ) {
							$anc_term      = get_term( $anc_id, 'product_cat' );
							$breadcrumbs[] = array( 'name' => $anc_term->name, 'url' => get_term_link( $anc_term ) );
						}
						$breadcrumbs[] = array( 'name' => $primary->name, 'url' => get_term_link( $primary ) );
					}
				}
			}

			// Posts
			elseif ( 'post' === $pt ) {
				$page_for_posts = get_option( 'page_for_posts' );
				if ( $page_for_posts ) {
					$breadcrumbs[] = array( 'name' => get_the_title( $page_for_posts ), 'url' => get_permalink( $page_for_posts ) );
				}
				$cats = get_the_category( $post->ID );
				if ( $cats ) {
					$primary = self::get_primary_term( $cats, 'category' );
					if ( $primary ) {
						$ancestors = get_ancestors( $primary->term_id, 'category', 'taxonomy' );
						foreach ( array_reverse( $ancestors ) as $anc_id ) {
							$anc_term      = get_term( $anc_id, 'category' );
							$breadcrumbs[] = array( 'name' => $anc_term->name, 'url' => get_category_link( $anc_term ) );
						}
						$breadcrumbs[] = array( 'name' => $primary->name, 'url' => get_category_link( $primary ) );
					}
				}
			}

			// Pages — handle parent hierarchy
			elseif ( 'page' === $pt ) {
				if ( $post->post_parent ) {
					$ancestors = get_ancestors( $post->ID, 'page', 'post_type' );
					foreach ( array_reverse( $ancestors ) as $anc_id ) {
						$breadcrumbs[] = array( 'name' => get_the_title( $anc_id ), 'url' => get_permalink( $anc_id ) );
					}
				}
			}

			// Other CPTs
			else {
				$pt_obj = get_post_type_object( $pt );
				if ( $pt_obj && $pt_obj->has_archive ) {
					$breadcrumbs[] = array( 'name' => $pt_obj->labels->name, 'url' => get_post_type_archive_link( $pt ) );
				}
			}

			if ( $show_current ) {
				$bc_title = get_post_meta( $post->ID, '_rp_seo_breadcrumb_title', true );
				$breadcrumbs[] = array( 'name' => $bc_title ?: get_the_title( $post->ID ), 'url' => '' );
			}
		}

		elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( is_a( $term, 'WP_Term' ) ) {
				$ancestors = get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' );
				foreach ( array_reverse( $ancestors ) as $anc_id ) {
					$anc_term      = get_term( $anc_id, $term->taxonomy );
					$breadcrumbs[] = array( 'name' => $anc_term->name, 'url' => get_term_link( $anc_term ) );
				}
				if ( $show_current ) {
					$breadcrumbs[] = array( 'name' => $term->name, 'url' => '' );
				}
			}
		}

		elseif ( is_post_type_archive() ) {
			$pt_obj = get_queried_object();
			if ( $show_current && $pt_obj ) {
				$breadcrumbs[] = array( 'name' => $pt_obj->labels->name, 'url' => '' );
			}
		}

		elseif ( is_author() ) {
			$author = get_queried_object();
			if ( $show_current && $author ) {
				$breadcrumbs[] = array( 'name' => $author->display_name, 'url' => '' );
			}
		}

		elseif ( is_date() ) {
			if ( is_year() ) {
				if ( $show_current ) {
					$breadcrumbs[] = array( 'name' => get_the_date( 'Y' ), 'url' => '' );
				}
			} elseif ( is_month() ) {
				$breadcrumbs[] = array( 'name' => get_the_date( 'Y' ), 'url' => get_year_link( get_the_date( 'Y' ) ) );
				if ( $show_current ) {
					$breadcrumbs[] = array( 'name' => get_the_date( 'F' ), 'url' => '' );
				}
			} elseif ( is_day() ) {
				$breadcrumbs[] = array( 'name' => get_the_date( 'Y' ), 'url' => get_year_link( get_the_date( 'Y' ) ) );
				$breadcrumbs[] = array( 'name' => get_the_date( 'F' ), 'url' => get_month_link( get_the_date( 'Y' ), get_the_date( 'm' ) ) );
				if ( $show_current ) {
					$breadcrumbs[] = array( 'name' => get_the_date( 'j' ), 'url' => '' );
				}
			}
		}

		elseif ( is_search() ) {
			if ( $show_current ) {
				/* translators: %s: search query */
				$breadcrumbs[] = array( 'name' => sprintf( __( 'Search: %s', 'rankpilot-seo' ), get_search_query() ), 'url' => '' );
			}
		}

		elseif ( is_404() ) {
			if ( $show_current ) {
				$breadcrumbs[] = array( 'name' => __( 'Page Not Found', 'rankpilot-seo' ), 'url' => '' );
			}
		}

		return apply_filters( 'rp_seo_breadcrumbs', $breadcrumbs );
	}

	/**
	 * Get the primary term from a list (prefers one set manually, otherwise alphabetical).
	 */
	private static function get_primary_term( $terms, $taxonomy ) {
		if ( empty( $terms ) ) {
			return null;
		}
		usort( $terms, function( $a, $b ) {
			return $a->term_id - $b->term_id;
		} );
		return $terms[0];
	}
}

/**
 * Template function for themes.
 */
function rankpilot_breadcrumb( $args = array() ) {
	RankPilot_Breadcrumbs::display( $args );
}
