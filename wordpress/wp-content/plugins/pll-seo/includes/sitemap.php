<?php
/**
 * Sitemap: core wp-sitemap.xml, trimmed to what the site actually publishes,
 * with 301 aliases for the legacy sitemap URLs.
 *
 * @package pll-seo
 */

defined( 'ABSPATH' ) || exit;

// Only the category taxonomy (no tags/post formats in this content model).
add_filter(
	'wp_sitemaps_taxonomies',
	function ( $taxonomies ) {
		return array_intersect_key( $taxonomies, array( 'category' => true ) );
	}
);

// No users sitemap: the two legacy author archives were retired (owner
// decision 2026-07-28 — those contributors no longer exist, only
// Dr. Basmajian). The archive URLs 301 to /blog/ (redirects.php), so they
// must not be advertised here.
add_filter(
	'wp_sitemaps_add_provider',
	function ( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);

// Never advertise a URL that 301s. A consolidation ticket (audit A4) retires a
// duplicate post by mapping its path in redirects.php while the post itself
// stays in the database for WXR re-import fidelity, so it would otherwise keep
// appearing in wp-sitemap.xml as a published post and keep inviting the crawl
// that the 301 exists to stop. Driven off the map itself, so any future
// retirement excludes itself with no second edit here.
add_filter(
	'wp_sitemaps_posts_query_args',
	function ( $args ) {
		if ( ! function_exists( 'pll_seo_redirect_map' ) || ! function_exists( 'pll_seo_resolve_path_id' ) ) {
			return $args;
		}
		$exclude = array();
		foreach ( array_keys( pll_seo_redirect_map() ) as $path ) {
			$post_id = pll_seo_resolve_path_id( trailingslashit( $path ) );
			if ( $post_id ) {
				$exclude[] = $post_id;
			}
		}
		if ( $exclude ) {
			$args['post__not_in'] = array_merge(
				isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(),
				$exclude
			);
		}
		return $args;
	}
);

// Legacy sitemap URLs → core sitemap (covers the Next-era /sitemap.xml and
// the original Yoast /sitemap_index.xml from old Search Console submissions).
add_action(
	'template_redirect',
	function () {
		$path = wp_parse_url( add_query_arg( array() ), PHP_URL_PATH );
		if ( in_array( untrailingslashit( (string) $path ), array( '/sitemap.xml', '/sitemap_index.xml' ), true ) ) {
			wp_safe_redirect( home_url( '/wp-sitemap.xml' ), 301 );
			exit;
		}
	},
	1
);
