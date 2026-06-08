<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles automatic plugin updates from GitHub releases.
 *
 * Checks https://api.github.com/repos/{user}/{repo}/releases/latest
 * and injects update data into WordPress's native update transient.
 * When WordPress downloads the update it gets a correctly named zip.
 */
class RankPilot_Updater {

	private $plugin_basename;
	private $plugin_slug;
	private $github_user;
	private $github_repo;
	private $current_version;
	private $api_url;
	private $transient_key;
	private $check_action; // wp action key for "check for updates" click

	public function __construct( $plugin_basename, $github_user, $github_repo, $current_version ) {
		$this->plugin_basename  = $plugin_basename;
		$this->plugin_slug      = dirname( $plugin_basename );
		$this->github_user      = $github_user;
		$this->github_repo      = $github_repo;
		$this->current_version  = $current_version;
		$this->api_url          = "https://api.github.com/repos/{$github_user}/{$github_repo}/releases/latest";
		$this->transient_key    = 'rp_seo_github_release';
		$this->check_action     = 'rp_seo_check_update';

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update_info' ) );
		add_filter( 'plugins_api',                           array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection',             array( $this, 'fix_source_dir' ), 10, 4 );
		add_action( 'upgrader_process_complete',             array( $this, 'clear_cache' ), 10, 2 );

		// "Check for Updates" link on the Plugins page
		add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'add_check_link' ) );
		add_action( 'admin_init', array( $this, 'handle_check_request' ) );
		add_action( 'admin_notices', array( $this, 'show_update_notice' ) );
	}

	// ──────────────────────────────────────────
	// "Check for Updates" link in plugin row
	// ──────────────────────────────────────────

	public function add_check_link( $links ) {
		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action'  => $this->check_action,
					'plugin'  => rawurlencode( $this->plugin_basename ),
				),
				admin_url( 'plugins.php' )
			),
			$this->check_action
		);

		$links['check_update'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Check for Updates', 'rankpilot-seo' ) . '</a>';
		return $links;
	}

	// ──────────────────────────────────────────
	// Handle the click — clear cache, re-fetch, redirect back with result
	// ──────────────────────────────────────────

	public function handle_check_request() {
		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== $this->check_action ) {
			return;
		}
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'rankpilot-seo' ) );
		}
		check_admin_referer( $this->check_action );

		// Clear cached release so get_release() hits GitHub fresh
		delete_transient( $this->transient_key );

		// Also clear WordPress's own plugin update transient so the UI refreshes
		delete_site_transient( 'update_plugins' );

		// Fetch the latest release now
		$release = $this->get_release();
		$status  = 'no_update';

		if ( $release ) {
			$remote_version = ltrim( $release->tag_name, 'v' );
			if ( version_compare( $this->current_version, $remote_version, '<' ) ) {
				$status = 'update_available';

				// Force WordPress to know about the update immediately
				$transient = get_site_transient( 'update_plugins' );
				if ( ! is_object( $transient ) ) {
					$transient = new stdClass();
				}
				if ( empty( $transient->response ) ) {
					$transient->response = array();
				}
				$transient->response[ $this->plugin_basename ] = (object) array(
					'id'           => $this->plugin_basename,
					'slug'         => $this->plugin_slug,
					'plugin'       => $this->plugin_basename,
					'new_version'  => $remote_version,
					'url'          => "https://github.com/{$this->github_user}/{$this->github_repo}",
					'package'      => $this->get_download_url( $release ),
					'icons'        => array(),
					'banners'      => array(),
					'banners_rtl'  => array(),
					'requires'     => RP_SEO_MIN_WP,
					'tested'       => '6.7',
					'requires_php' => RP_SEO_MIN_PHP,
				);
				set_site_transient( 'update_plugins', $transient );
			} elseif ( version_compare( $this->current_version, $remote_version, '=' ) ) {
				$status = 'up_to_date';
			}
		} else {
			$status = 'check_failed';
		}

		// Redirect back to Plugins page with the result as a query arg
		wp_safe_redirect( add_query_arg(
			array(
				'rp_seo_update_status'  => $status,
				'rp_seo_remote_version' => isset( $remote_version ) ? rawurlencode( $remote_version ) : '',
			),
			admin_url( 'plugins.php' )
		) );
		exit;
	}

	// ──────────────────────────────────────────
	// Show admin notice after the redirect
	// ──────────────────────────────────────────

	public function show_update_notice() {
		if ( ! isset( $_GET['rp_seo_update_status'] ) ) {
			return;
		}
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$status         = sanitize_key( $_GET['rp_seo_update_status'] );
		$remote_version = isset( $_GET['rp_seo_remote_version'] ) ? sanitize_text_field( rawurldecode( $_GET['rp_seo_remote_version'] ) ) : '';

		switch ( $status ) {

			case 'update_available':
				$update_url = wp_nonce_url(
					admin_url( 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $this->plugin_basename ) ),
					'upgrade-plugin_' . $this->plugin_basename
				);
				printf(
					'<div class="notice notice-warning is-dismissible"><p><strong>RankPilot SEO:</strong> %s &mdash; <a href="%s"><strong>%s</strong></a></p></div>',
					sprintf(
						/* translators: %1$s current version, %2$s new version */
						esc_html__( 'Update available! You have v%1$s installed; v%2$s is ready on GitHub.', 'rankpilot-seo' ),
						esc_html( $this->current_version ),
						esc_html( $remote_version )
					),
					esc_url( $update_url ),
					esc_html__( 'Update Now', 'rankpilot-seo' )
				);
				break;

			case 'up_to_date':
				printf(
					'<div class="notice notice-success is-dismissible"><p><strong>RankPilot SEO:</strong> %s</p></div>',
					sprintf(
						esc_html__( 'You are already running the latest version (v%s).', 'rankpilot-seo' ),
						esc_html( $this->current_version )
					)
				);
				break;

			case 'check_failed':
				echo '<div class="notice notice-error is-dismissible"><p><strong>RankPilot SEO:</strong> ' . esc_html__( 'Could not reach GitHub to check for updates. Please try again later.', 'rankpilot-seo' ) . '</p></div>';
				break;
		}
	}

	// ──────────────────────────────────────────
	// Fetch latest release from GitHub API
	// ──────────────────────────────────────────

	private function get_release() {
		$cached = get_transient( $this->transient_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get( $this->api_url, array(
			'timeout' => 10,
			'headers' => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'RankPilot-SEO-Updater/' . $this->current_version,
			),
		) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$release = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $release->tag_name ) ) {
			return false;
		}

		// Cache for 6 hours
		set_transient( $this->transient_key, $release, 6 * HOUR_IN_SECONDS );
		return $release;
	}

	/**
	 * Find the plugin zip asset in the release.
	 * Prefers an asset named rankpilot-seo.zip; falls back to zipball_url.
	 */
	private function get_download_url( $release ) {
		if ( ! empty( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( isset( $asset->browser_download_url ) && str_ends_with( $asset->name, '.zip' ) ) {
					return $asset->browser_download_url;
				}
			}
		}
		return $release->zipball_url ?? '';
	}

	// ──────────────────────────────────────────
	// Inject update into WordPress transient
	// ──────────────────────────────────────────

	public function inject_update_info( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $transient;
		}

		$remote_version = ltrim( $release->tag_name, 'v' );

		if ( version_compare( $this->current_version, $remote_version, '<' ) ) {
			$transient->response[ $this->plugin_basename ] = (object) array(
				'id'            => $this->plugin_basename,
				'slug'          => $this->plugin_slug,
				'plugin'        => $this->plugin_basename,
				'new_version'   => $remote_version,
				'url'           => "https://github.com/{$this->github_user}/{$this->github_repo}",
				'package'       => $this->get_download_url( $release ),
				'icons'         => array(),
				'banners'       => array(),
				'banners_rtl'   => array(),
				'requires'      => RP_SEO_MIN_WP,
				'tested'        => '6.7',
				'requires_php'  => RP_SEO_MIN_PHP,
			);
		}

		return $transient;
	}

	// ──────────────────────────────────────────
	// Provide plugin info for "View details" popup
	// ──────────────────────────────────────────

	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $result;
		}

		$remote_version = ltrim( $release->tag_name, 'v' );
		$changelog      = isset( $release->body ) ? nl2br( esc_html( $release->body ) ) : '';

		return (object) array(
			'name'          => 'RankPilot SEO',
			'slug'          => $this->plugin_slug,
			'version'       => $remote_version,
			'author'        => '<a href="https://github.com/' . esc_attr( $this->github_user ) . '">RankPilot</a>',
			'homepage'      => "https://github.com/{$this->github_user}/{$this->github_repo}",
			'download_link' => $this->get_download_url( $release ),
			'requires'      => RP_SEO_MIN_WP,
			'tested'        => '6.7',
			'requires_php'  => RP_SEO_MIN_PHP,
			'last_updated'  => $release->published_at ?? '',
			'sections'      => array(
				'description' => '<p>Full-featured SEO plugin. See <a href="https://github.com/' . esc_attr( $this->github_user ) . '/' . esc_attr( $this->github_repo ) . '">GitHub</a> for details.</p>',
				'changelog'   => $changelog ?: '<p>See <a href="https://github.com/' . esc_attr( $this->github_user ) . '/' . esc_attr( $this->github_repo ) . '/releases">releases page</a>.</p>',
			),
			'banners'       => array(),
		);
	}

	// ──────────────────────────────────────────
	// Fix folder name after download
	// ──────────────────────────────────────────

	public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $source;
		}

		$correct_dir = trailingslashit( dirname( $source ) ) . $this->plugin_slug . '/';

		if ( $source === $correct_dir ) {
			return $source;
		}

		if ( $wp_filesystem->move( $source, $correct_dir ) ) {
			return $correct_dir;
		}

		return new WP_Error( 'rp_seo_rename_failed', 'Could not rename plugin folder during update.' );
	}

	// ──────────────────────────────────────────
	// Clear cache after update
	// ──────────────────────────────────────────

	public function clear_cache( $upgrader, $options ) {
		if (
			'update' === $options['action'] &&
			'plugin' === $options['type'] &&
			! empty( $options['plugins'] ) &&
			in_array( $this->plugin_basename, $options['plugins'], true )
		) {
			delete_transient( $this->transient_key );
		}
	}
}
