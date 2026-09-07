<?php
/**
 * Assertions for the keyword-cannibalization remediation (audit 2026-08-05,
 * owner-approved in artifact comments 2026-08-27).
 *
 * Runs inside Playground after the full boot (theme + plugins + WXR + seed).
 * Reports through the bidirectional scripts/ mount because run-blueprint does
 * not pipe runPHP stdout back to the host.
 *
 * The driver re-requires setup.php before this file, so every link assertion
 * below is also an idempotency test: a second seed pass must not double-inject
 * an anchor or stack a second "see also" line onto a page.
 *
 * @package pll-editorial
 */

$pll_report   = array();
$pll_failures = 0;

/**
 * Record one assertion.
 *
 * @param bool   $condition Condition under test.
 * @param string $message   Human-readable description.
 * @return void
 */
function pll_assert( $condition, $message ) {
	global $pll_report, $pll_failures;
	if ( $condition ) {
		$pll_report[] = 'PASS ' . $message;
		return;
	}
	$pll_failures++;
	$pll_report[] = 'FAIL ' . $message;
}

/**
 * Fetch a page's stored block markup by root-relative path.
 *
 * @param string $path Root-relative path.
 * @return string Post content, or '' when the path resolves to nothing.
 */
function pll_content_at( $path ) {
	$post_id = pll_seo_resolve_path_id( $path );
	if ( ! $post_id ) {
		return '';
	}
	$post = get_post( $post_id );
	return $post instanceof WP_Post ? $post->post_content : '';
}

/**
 * Count occurrences of a link to $href in a page's content.
 *
 * @param string $path Root-relative path of the host page.
 * @param string $href Destination path.
 * @return int
 */
function pll_link_count( $path, $href ) {
	return substr_count( pll_content_at( $path ), 'href="' . $href . '"' );
}

$pll_report[] = '── A2 · /your-surgery/ owns the "limb lengthening surgery" head term ──';

// The audit's head-term cluster had no designated landing page. These are the
// exact-phrase inbound anchors that answer "which page?" for the crawler.
$pll_a2_hosts = array(
	'/limb-lengthening-what-you-gain-what-you-risk/',
	'/are-you-a-good-candidate-for-limb-lengthening/',
	'/is-limb-lengthening-covered-by-insurance/',
	'/rewriting-the-body-norm-stigmas-around-limb-lengthening/',
	'/your-surgery/can-i-bend-my-lengthening-nail/',
);
foreach ( $pll_a2_hosts as $pll_host ) {
	$pll_n = pll_link_count( $pll_host, '/your-surgery/' );
	pll_assert( 1 === $pll_n, "{$pll_host} links to the hub exactly once (got {$pll_n})" );
	pll_assert(
		false !== strpos( pll_content_at( $pll_host ), '>limb lengthening surgery</a>' ),
		"{$pll_host} uses the exact head-term anchor"
	);
}

// The hub must NOT annex the terms the newer pillars own, or A2 rebuilds the
// split it exists to close. This is the narrowing applied to the audit text.
pll_assert(
	0 === pll_link_count( '/your-surgery/', '/your-surgery/' ),
	'the hub does not link to itself'
);
pll_assert(
	1 === pll_link_count( '/your-surgery/', '/leg-lengthening-surgery/' ),
	'the hub still defers the "leg lengthening surgery" term to its pillar'
);

$pll_report[] = '';
$pll_report[] = '── A5 · the age pair is differentiated, not consolidated ──';

$pll_age_hub  = '/your-surgery/is-there-an-age-limit-for-limb-lengthening/';
$pll_age_blog = '/am-i-too-old-for-limb-lengthening/';

pll_assert(
	1 === pll_link_count( $pll_age_hub, $pll_age_blog ),
	'age hub links to the older-patient article exactly once'
);
pll_assert(
	1 === pll_link_count( $pll_age_blog, $pll_age_hub ),
	'older-patient article links back to the age hub exactly once'
);
pll_assert(
	false !== strpos( pll_content_at( $pll_age_hub ), 'limb lengthening for patients over 40' ),
	'age hub uses the older-patient intent anchor'
);
pll_assert(
	false !== strpos( pll_content_at( $pll_age_blog ), 'age limit for limb lengthening' ),
	'older-patient article uses the eligibility intent anchor'
);

// Both pages must survive as separate URLs: differentiation, not a merge.
pll_assert( (bool) pll_seo_resolve_path_id( $pll_age_hub ), 'age hub still exists' );
pll_assert( (bool) pll_seo_resolve_path_id( $pll_age_blog ), 'older-patient article still exists' );

// The retitle has to reach real post meta. Editing the map alone would not.
$pll_age_title = (string) get_post_meta( pll_seo_resolve_path_id( $pll_age_blog ), '_pll_seo_title', true );
pll_assert(
	'Limb Lengthening in Your 40s and 50s · Are You Too Old?' === $pll_age_title,
	'older-patient article carries the retitled SEO title in post meta (got "' . $pll_age_title . '")'
);
pll_assert(
	false === stripos( pll_content_at( $pll_age_blog ), 'age limit' )
		|| false !== strpos( pll_content_at( $pll_age_blog ), 'age limit for limb lengthening' ),
	'older-patient article uses "age limit" only as the cross-link anchor'
);

$pll_report[] = '';
$pll_report[] = '── A6 · one canonical Person entity for surgeon-name queries ──';

$pll_graph = wp_json_encode( pll_seo_site_graph() );
pll_assert(
	false !== strpos( $pll_graph, '"founder"' ),
	'site graph declares a founder'
);
pll_assert(
	false !== strpos( $pll_graph, '/dr-basmajian#physician' ),
	'the founder resolves by @id to the Physician node'
);

$pll_a6_hosts = array(
	'/your-surgery/how-much-taller-can-i-get-with-limb-lengthening/',
	'/your-surgery/limb-lengthening-expectations/',
	'/your-surgery/external-internal-lengthening/',
	'/your-surgery/is-there-an-age-limit-for-limb-lengthening/',
	'/your-surgery/can-i-bend-my-lengthening-nail/',
);
foreach ( $pll_a6_hosts as $pll_host ) {
	$pll_n = pll_link_count( $pll_host, '/dr-basmajian/' );
	pll_assert( 1 === $pll_n, "{$pll_host} points its name mention at the profile once (got {$pll_n})" );
}

$pll_report[] = '';
$pll_report[] = '── A7 · the sports pair cross-links both ways ──';

pll_assert(
	1 === pll_link_count( '/is-leg-lengthening-off-limits-for-athletes/', '/your-surgery/exercise-after-limb-lengthening/' ),
	'athletes article links to the exercise page'
);
pll_assert(
	1 === pll_link_count( '/your-surgery/exercise-after-limb-lengthening/', '/is-leg-lengthening-off-limits-for-athletes/' ),
	'exercise page links back to the athletes article'
);

$pll_report[] = '';
$pll_report[] = '── A4 · the fixation consolidation, and its content-merge gate ──';

$pll_fixation = '/fixation-methods-in-limb-lengthening-internal-vs-external';
$pll_hub      = '/your-surgery/external-internal-lengthening/';
$pll_map      = pll_seo_redirect_map();
$pll_gate     = defined( 'PLL_SEO_A4_FIXATION_MERGED' ) && PLL_SEO_A4_FIXATION_MERGED;

$pll_report[] = '   gate PLL_SEO_A4_FIXATION_MERGED = ' . ( $pll_gate ? 'true' : 'false' );

// The gate is the whole point of A4's sequencing: the blog page is the one
// that ranks, so redirecting it into the thinner hub page BEFORE its prose
// moves there destroys the content that earned the position. These assertions
// make that unshippable rather than merely documented.
if ( ! $pll_gate ) {
	pll_assert(
		! isset( $pll_map[ $pll_fixation ] ),
		'gate closed: the fixation URL is NOT redirected yet'
	);
	pll_assert(
		'' !== pll_content_at( $pll_fixation . '/' ),
		'gate closed: the fixation article still serves its own content'
	);
} else {
	pll_assert(
		'/your-surgery/external-internal-lengthening/' === ( $pll_map[ $pll_fixation ] ?? '' ),
		'gate open: the fixation URL targets the hub comparison page'
	);

	// Flipping the gate without doing the merge is the exact mistake the gate
	// exists to prevent, so prove the prose actually arrived. These are the
	// blog's section headings, which the hub does not otherwise have.
	$pll_hub_content = pll_content_at( $pll_hub );
	foreach ( array( 'Role of Fixation', 'Visible but Versatile', 'Stealthier Approach' ) as $pll_marker ) {
		pll_assert(
			false !== stripos( $pll_hub_content, $pll_marker ),
			"gate open: hub page carries the merged \"{$pll_marker}\" material"
		);
	}
	pll_assert(
		str_word_count( wp_strip_all_tags( $pll_hub_content ) ) > 1400,
		'gate open: hub page grew past its pre-merge length'
	);
}

// Never chain: no value in the map may itself be a key. Holds in either state.
$pll_chained = array();
foreach ( $pll_map as $pll_from => $pll_to ) {
	if ( isset( $pll_map[ untrailingslashit( $pll_to ) ] ) ) {
		$pll_chained[] = $pll_from;
	}
}
pll_assert( empty( $pll_chained ), 'no redirect chains in the map' );

// The post stays in the database either way (WXR re-import fidelity), which is
// exactly why a 404-guarded redirect could never have retired it.
$pll_fixation_id = pll_seo_resolve_path_id( $pll_fixation . '/' );
pll_assert( (bool) $pll_fixation_id, 'the fixation post is present in the database' );

$pll_sitemap_args = apply_filters( 'wp_sitemaps_posts_query_args', array(), 'post' );
$pll_excluded     = in_array( $pll_fixation_id, (array) ( $pll_sitemap_args['post__not_in'] ?? array() ), true );

// Whatever a page still points at must agree with whether the URL serves.
$pll_still_linking = array();
foreach ( get_posts(
	array(
		'post_type'      => array( 'post', 'page' ),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	)
) as $pll_post ) {
	if ( (int) $pll_post->ID === (int) $pll_fixation_id ) {
		continue;
	}
	if ( false !== strpos( $pll_post->post_content, $pll_fixation ) ) {
		$pll_still_linking[] = $pll_post->post_name;
	}
}

if ( $pll_gate ) {
	pll_assert( $pll_excluded, 'gate open: the retired URL is excluded from wp-sitemap.xml' );
	pll_assert(
		empty( $pll_still_linking ),
		'gate open: nothing links to the retired URL (offenders: ' . ( implode( ', ', $pll_still_linking ) ?: 'none' ) . ')'
	);
} else {
	// A URL that still serves its own content must stay in the sitemap. Pulling
	// it early would deindex a page that is currently ranking.
	pll_assert( ! $pll_excluded, 'gate closed: the still-serving URL remains in wp-sitemap.xml' );
	$pll_report[] = '     (' . count( $pll_still_linking ) . ' page(s) still link to it, correct while it serves)';
}

$pll_report[] = '';
$pll_report[] = $pll_failures
	? "{$pll_failures} FAILURE(S)"
	: 'ALL ASSERTIONS PASSED';

file_put_contents( '/verify-scripts/.cannibalization-result.txt', implode( "\n", $pll_report ) . "\n" );
