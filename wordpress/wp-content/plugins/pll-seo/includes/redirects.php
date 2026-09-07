<?php
/**
 * Legacy URL redirects. The URL strategy is zero-redirect for every built
 * surface; this map only covers legacy URLs with no destination page.
 *
 * @package pll-seo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gates the A4 fixation consolidation (cannibalization audit, owner-approved
 * 2026-08-27).
 *
 *   false = /fixation-methods-in-limb-lengthening-internal-vs-external/ keeps
 *           serving its own content
 *   true  = that URL 301s to /your-surgery/external-internal-lengthening/
 *
 * ⚠️ FAIL SAFE UNTIL THE CONTENT MERGE LANDS. A4 is two halves and they must
 * ship in this order:
 *
 *   1. Port the blog's unique prose (its "The Role of Fixation", "External
 *      Fixation: Visible but Versatile", "Internal Fixation: A Stealthier
 *      Approach", "Recovery and Lifestyle Considerations" and "Making the
 *      Right Choice" sections, ~1,100 words) into the hub page, which today
 *      covers the same topic in ~840 words organized by comparison attribute
 *      rather than by method.
 *   2. THEN flip this constant.
 *
 * Flipping it first is a regression, not a partial win. The blog page is the
 * one that currently RANKS (85% share at #8.9 on "internal vs external
 * fixation"); the hub page sits at #26. Redirecting the winner into a thinner
 * page before the prose moves throws away the content that earned the
 * position, which is the opposite of consolidation.
 *
 * The merge is clinical content on a page that Dr. Basmajian has NOT signed
 * off (see docs/MEDICAL_REVIEW_LOG.md — only /height-surgery/ and
 * /leg-lengthening-surgery/ are reviewed), so it needs an author and a review,
 * not a code change.
 *
 * TO ENABLE: land the merge, then set this to true in its own reviewed commit
 * and add the record here, replacing the placeholders:
 *
 *   Merged-By: <name>, <date>, <where the merged copy was reviewed>
 *
 * An environment can also opt in without a deploy, from wp-config.php:
 *
 *   define( 'PLL_SEO_A4_FIXATION_MERGED', true );
 */
if ( ! defined( 'PLL_SEO_A4_FIXATION_MERGED' ) ) {
	define( 'PLL_SEO_A4_FIXATION_MERGED', false );
}

/**
 * Path (no trailing slash) → target path. 301s.
 *
 * /video/will-i-be-a-better-athlete had no body content on the legacy site
 * (only a related-articles widget), so it redirects to the topically exact
 * article rather than being rebuilt. (The Next.js app interim-302'd it to
 * /blog; a topical 301 transfers more equity.)
 *
 * /your-visit is a legacy "Your Visit" URL from the pre-rebuild site with no
 * destination in the current IA. GSC still indexes it as an orphan (~position
 * 49, no clicks); 301 it to the surgical-journey hub so the residual equity
 * consolidates instead of decaying on a soft 404.
 *
 * /fixation-methods-in-limb-lengthening-internal-vs-external is a RETIRED
 * post, not a missing one (cannibalization audit A4, owner-approved
 * 2026-08-27). It duplicated /your-surgery/external-internal-lengthening/
 * with a near-identical title and won the generic query at #8.9 while the hub
 * page, the one wired into the patient journey, sat at #26. Its unique prose
 * is merged into the hub page and the URL 301s there. Because the post still
 * exists in the database (kept so the WXR re-import stays clean), this entry
 * only works via the unconditional map check below, not the 404 fallback.
 *
 * Never chain: every value must be a final destination, not another key.
 *
 * @return array<string, string>
 */
function pll_seo_redirect_map() {
	$map = array(
		'/video/will-i-be-a-better-athlete' => '/is-leg-lengthening-off-limits-for-athletes/',
		'/your-visit'                       => '/your-surgery/',
	);

	if ( PLL_SEO_A4_FIXATION_MERGED ) {
		$map['/fixation-methods-in-limb-lengthening-internal-vs-external'] = '/your-surgery/external-internal-lengthening/';
	}

	return apply_filters( 'pll_seo_redirects', $map );
}

/**
 * Author archives → /blog/. The two legacy author archives were retired
 * (owner decision 2026-07-28 — those contributors no longer exist, only
 * Dr. Basmajian). The user accounts stay (they record historical post
 * ownership and keep WXR re-imports mapping cleanly) but their public
 * archive URLs 301 to the blog hub so the residual legacy equity
 * consolidates instead of presenting a retired byline. The users sitemap is
 * removed in sitemap.php, so no advertised URL redirects.
 */
add_action(
	'template_redirect',
	function () {
		if ( is_author() ) {
			wp_safe_redirect( home_url( '/blog/' ), 301 );
			exit;
		}
	},
	1
);

/**
 * Apply the legacy/retired URL map.
 *
 * Checked on every front-end request, not only 404s. The 404-guarded version
 * this replaces could redirect a legacy URL that had no post behind it, but it
 * silently did nothing for a RETIRED post — one that still resolves 200 and so
 * never reaches is_404(). Consolidation tickets (audit A4) need exactly that
 * case, otherwise the duplicate keeps ranking and the 301 is a no-op.
 *
 * Safe because a map key is by definition a URL that must not serve content:
 * the two legacy entries have no post at all, and a retired entry is retired
 * on purpose. Guarded against redirect loops by skipping any request whose
 * path already equals its own destination.
 */
add_action(
	'template_redirect',
	function () {
		if ( is_admin() || is_robots() || is_feed() ) {
			return;
		}
		$path = untrailingslashit( (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH ) );
		if ( '' === $path ) {
			return;
		}
		$map = pll_seo_redirect_map();
		if ( ! isset( $map[ $path ] ) ) {
			return;
		}
		$target = $map[ $path ];
		if ( untrailingslashit( $target ) === $path ) {
			return;
		}
		wp_safe_redirect( home_url( $target ), 301 );
		exit;
	},
	5
);
