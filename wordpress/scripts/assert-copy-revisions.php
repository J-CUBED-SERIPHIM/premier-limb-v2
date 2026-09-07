<?php
/**
 * Assertions for the owner copy revisions of 2026-09-07: PRECICE MAX naming on
 * the top-tier packages, and removal of the advertised financing terms.
 *
 * Runs inside Playground after the full boot (theme + plugins + WXR + seed).
 * The driver re-requires setup.php first, so every assertion here is also an
 * idempotency test: a second pass must not double-apply or re-strip anything.
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
 * Stored block markup for a path.
 *
 * @param string $path Root-relative path.
 * @return string
 */
function pll_content_at( $path ) {
	$post_id = pll_seo_resolve_path_id( $path );
	if ( ! $post_id ) {
		return '';
	}
	$post = get_post( $post_id );
	return $post instanceof WP_Post ? $post->post_content : '';
}

$pll_home    = pll_content_at( '/' );
$pll_pricing = pll_content_at( '/limb-lengthening-pricing-options/' );
$pll_ins     = pll_content_at( '/is-limb-lengthening-covered-by-insurance/' );

pll_assert( '' !== $pll_home, 'homepage resolved' );
pll_assert( '' !== $pll_pricing, 'pricing page resolved' );
pll_assert( '' !== $pll_ins, 'insurance article resolved' );

$pll_report[] = '';
$pll_report[] = '── PRECICE MAX naming on the top-tier packages ──';

// Homepage: two 2-nail packages and one 4-nail package, all PRECICE Max.
pll_assert(
	2 === substr_count( $pll_home, '2 PRECICE MAX internal nail implants' ),
	'home: both 2-nail bullets say PRECICE MAX (got ' . substr_count( $pll_home, '2 PRECICE MAX internal nail implants' ) . ')'
);
pll_assert(
	1 === substr_count( $pll_home, '4 PRECICE MAX internal nail implants' ),
	'home: the 4-nail bullet says PRECICE MAX'
);
pll_assert(
	0 === substr_count( $pll_home, '<span>2 PRECICE internal nail implants</span>' )
		&& 0 === substr_count( $pll_home, '<span>4 PRECICE internal nail implants</span>' ),
	'home: no bare "PRECICE" implant bullets left'
);

// Pricing page, MAX group.
pll_assert(
	2 === substr_count( $pll_pricing, '2 PRECICE MAX internal nail implants' ),
	'pricing: both MAX 2-nail bullets renamed (got ' . substr_count( $pll_pricing, '2 PRECICE MAX internal nail implants' ) . ')'
);
pll_assert(
	1 === substr_count( $pll_pricing, '4 PRECICE MAX internal nail implants' ),
	'pricing: the $195,000 package says 4 PRECICE MAX internal nail implants'
);
pll_assert(
	0 === substr_count( $pll_pricing, 'PRECICE 4th Generation' ),
	'pricing: the "4th Generation" wording is gone'
);

// The PRECICE 2 group must be untouched: it is a different device.
pll_assert(
	2 === substr_count( $pll_pricing, '2 PRECICE 2 internal nail implants' )
		&& 1 === substr_count( $pll_pricing, '4 PRECICE 2 internal nail implants' ),
	'pricing: the PRECICE 2 tiers are unchanged'
);

$pll_report[] = '';
$pll_report[] = '── Headings ──';

pll_assert(
	false === strpos( $pll_pricing, 'priced like a partner' ),
	'pricing: "priced like a partner" removed from the h1'
);
pll_assert(
	false !== strpos( $pll_pricing, 'Limb Lengthening <em class="italic text-spine">Cost.</em>' ),
	'pricing: h1 reads "Limb Lengthening Cost." with the spine accent intact'
);
pll_assert(
	false === strpos( $pll_pricing, 'One transparent price' ),
	'pricing: "One transparent price" replaced'
);
pll_assert(
	false !== strpos( $pll_pricing, 'Three procedures. <em class="italic text-spine">PRECICE MAX cost.</em>' ),
	'pricing: the MAX group heading names the device'
);
// The PRECICE 2 group keeps its own heading, so the two groups stay distinct.
pll_assert(
	false !== strpos( $pll_pricing, 'PRECICE 2 pricing.' ),
	'pricing: the PRECICE 2 group heading is untouched'
);

$pll_report[] = '';
$pll_report[] = '── Financing claims removed ──';

// These are advertised credit terms. Anywhere they survive is a live claim.
foreach ( array(
	'$1,200'             => 'the indicative monthly figure',
	'/mo'                => 'the per-month suffix',
	'Indicative monthly' => 'the "Indicative monthly" label',
	'With approved credit' => 'the "With approved credit" label',
	'0&nbsp;%'           => 'the 0% intro tile',
) as $pll_needle => $pll_label ) {
	pll_assert(
		false === strpos( $pll_pricing, $pll_needle ),
		'pricing: ' . $pll_label . ' is gone'
	);
}
pll_assert(
	false === strpos( $pll_home, '$1,200' ) && false === strpos( $pll_home, 'As low as' ),
	'home: no payment figure in the financing line'
);
pll_assert(
	false === strpos( $pll_ins, 'as low as $1,200' ),
	'insurance article: the visible answer no longer quotes a monthly payment'
);

// The visible copy and the FAQ schema are published from two different places
// (setup.php seeds one, pll_seo_paa feeds the other). If they disagree, the
// page says one thing and the search result says another.
$pll_paa = function_exists( 'pll_seo_paa' ) ? pll_seo_paa( '/is-limb-lengthening-covered-by-insurance/' ) : array();
$pll_paa_json = wp_json_encode( $pll_paa );
pll_assert(
	false === strpos( (string) $pll_paa_json, '1,200' ),
	'insurance article: the FAQ schema source carries no payment figure either'
);

// Removing the stat tiles must not have left unbalanced block markup, which
// shows in the editor as "This block contains unexpected or invalid content".
pll_assert(
	substr_count( $pll_pricing, '<!-- wp:group' ) === substr_count( $pll_pricing, '<!-- /wp:group -->' ),
	'pricing: wp:group open/close comments are balanced after the tile removal'
);

// The financing section itself survives, only its claims went.
pll_assert(
	false !== strpos( $pll_pricing, '"anchor":"financing"' ) || false !== strpos( $pll_pricing, 'id="financing"' ),
	'pricing: the #financing anchor still exists (the footer links to it)'
);
pll_assert(
	false !== strpos( $pll_pricing, 'carecredit.com' ),
	'pricing: the CareCredit apply link is still present'
);

$pll_report[] = '';
$pll_report[] = $pll_failures
	? "{$pll_failures} FAILURE(S)"
	: 'ALL ASSERTIONS PASSED';

file_put_contents( '/verify-scripts/.copy-revisions-result.txt', implode( "\n", $pll_report ) . "\n" );
