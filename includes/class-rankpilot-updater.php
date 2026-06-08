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

	public function __construct( $plugin_basename, $github_user, $github_repo, $current_version ) {
		$this->plugin_basename  = $plugin_basename;                         // rankpilot-seo/rankpilot-seo.php
		$this->plugin_slug      = dirname( $plugin_basename );              // rankpilot-seo
		$this->github_user      = $github_user;                             // aiyedunkayodetoluwase
		$this->github_repo      = $github_repo;                             // RankPilot-SEO
		$this->current_version  = $current_version;                         // 1.0.0
		$this->api_url          = "https://api.github.com/repos/{$github_user}/{$github_repo}/releases/latest";
		$this->transient_key    = 'rp_seo_github_release';

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update_info' ) );
		add_filter( 'plugins_api',                           array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection',             array( $this, 'fix_source_dir' ), 10, 4 );
		add_action( 'upgrader_process_complete',             array( $this, 'clear_cache' ), 10, 2 );
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
		// GitHub auto-generated source zip (folder name will need fixing)
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

		// Strip leading 'v' from tag (v1.2.0 → 1.2.0)
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
			'name'              => 'RankPilot SEO',
			'slug'              => $this->plugin_slug,
			'version'           => $remote_version,
			'author'            => '<a href="https://github.com/' . esc_attr( $this->github_user ) . '">RankPilot</a>',
			'homepage'          => "https://github.com/{$this->github_user}/{$this->github_repo}",
			'download_link'     => $this->get_download_url( $release ),
			'requires'          => RP_SEO_MIN_WP,
			'tested'            => '6.7',
			'requires_php'      => RP_SEO_MIN_PHP,
			'last_updated'      => $release->published_at ?? '',
			'sections'          => array(
				'description' => '<p>Full-featured SEO plugin. See <a href="https://github.com/' . esc_attr( $this->github_user ) . '/' . esc_attr( $this->github_repo ) . '">GitHub</a> for details.</p>',
				'changelog'   => $changelog ?: '<p>See <a href="https://github.com/' . esc_attr( $this->github_user ) . '/' . esc_attr( $this->github_repo ) . '/releases">releases page</a>.</p>',
			),
			'banners'           => array(),
		);
	}

	// ──────────────────────────────────────────
	// Fix folder name after download
	// GitHub zips come as {repo}-{version}/ but WP expects rankpilot-seo/
	// ──────────────────────────────────────────

	public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $source;
		}

		// The extracted folder name from GitHub (e.g. aiyedunkayodetoluwase-RankPilot-SEO-abc1234/)
		$basename    = basename( $source );
		$correct_dir = trailingslashit( dirname( $source ) ) . $this->plugin_slug . '/';

		if ( $source === $correct_dir ) {
			return $source; // Already correct
		}

		if ( $wp_filesystem->move( $source, $correct_dir ) ) {
			return $correct_dir;
		}

		return new WP_Error( 'rp_seo_rename_failed', 'Could not rename plugin folder during update.' );
	}

	// ──────────────────────────────────────────
	// Clear cache after update so next check is fresh
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
