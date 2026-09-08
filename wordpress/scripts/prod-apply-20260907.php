<?php
/**
 * One-off production migration, 2026-09-07.
 *
 * Applies ONLY the owner-approved changes to the live site:
 *   - cannibalization audit A2, A5, A6 (link half), A7
 *   - the 2026-09-07 pricing copy revisions (PRECICE MAX naming, headings,
 *     removal of the advertised financing terms)
 *
 * Deliberately standalone rather than deploying content/setup.php, which on
 * this server is from 2026-07-28 and is ~620 lines behind the repo. Running
 * that would also apply every other seeding change accumulated since then
 * (marketing-sprint drafts and more), none of which is in scope here.
 *
 * Safe to re-run: every step checks for its own result first.
 *
 * Usage:
 *   wp eval-file prod-apply-20260907.php --path=/var/www/premierlimblengthening.com
 *
 * @package pll-seo
 */

if ( ! function_exists( 'pll_seo_resolve_path_id' ) ) {
	fwrite( STDERR, "pll-seo plugin not loaded, aborting.\n" );
	exit( 1 );
}

// Declared global explicitly. `wp eval-file` executes this file inside a
// function scope, so a plain top-level assignment creates a LOCAL, while
// pll_step()'s `global` statement binds the real global. The two then diverge
// and the summary reports zeroes no matter what happened, which also silences
// the "needs attention" guard at the bottom. Found the hard way on the
// 2026-09-07 production run: 24 APPLIED lines, summary said 0.
global $pll_applied, $pll_skipped, $pll_missing;
$pll_applied = 0;
$pll_skipped = 0;
$pll_missing = 0;

/**
 * Report one step.
 *
 * @param string $state APPLIED|already|MISSING.
 * @param string $msg   Description.
 * @return void
 */
function pll_step( $state, $msg ) {
	global $pll_applied, $pll_skipped, $pll_missing;
	if ( 'APPLIED' === $state ) {
		$pll_applied++;
	} elseif ( 'MISSING' === $state ) {
		$pll_missing++;
	} else {
		$pll_skipped++;
	}
	printf( "  %-8s %s\n", $state, $msg );
}

/**
 * Replace a string inside one page's stored content.
 *
 * @param string $path Root-relative path.
 * @param string $from Text to find.
 * @param string $to   Replacement.
 * @param string $label Human label.
 * @return void
 */
function pll_replace_in( $path, $from, $to, $label ) {
	$post_id = pll_seo_resolve_path_id( $path );
	if ( ! $post_id ) {
		pll_step( 'MISSING', "$path not found ($label)" );
		return;
	}
	$post    = get_post( $post_id );
	$content = $post->post_content;

	if ( false !== strpos( $content, $from ) ) {
		$content = str_replace( $from, $to, $content );
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => wp_slash( $content ),
			)
		);
		pll_step( 'APPLIED', "$path — $label" );
		return;
	}
	if ( false !== strpos( $content, $to ) ) {
		pll_step( 'already', "$path — $label" );
		return;
	}
	pll_step( 'MISSING', "$path — $label (neither old nor new text present)" );
}

/**
 * Wrap an existing phrase in a link, once.
 *
 * @param string $path   Host page.
 * @param string $find   Verbatim context fragment.
 * @param string $anchor Sub-phrase to wrap.
 * @param string $href   Destination path.
 * @return void
 */
function pll_link_in( $path, $find, $anchor, $href ) {
	$post_id = pll_seo_resolve_path_id( $path );
	if ( ! $post_id ) {
		pll_step( 'MISSING', "$path not found (link to $href)" );
		return;
	}
	$post    = get_post( $post_id );
	$content = $post->post_content;

	if ( false !== strpos( $content, 'href="' . $href . '"' ) ) {
		pll_step( 'already', "$path -> $href" );
		return;
	}
	$pos = strpos( $content, $find );
	if ( false === $pos ) {
		pll_step( 'MISSING', "$path -> $href (phrase not found)" );
		return;
	}
	$linked  = str_replace( $anchor, '<a href="' . $href . '">' . $anchor . '</a>', $find );
	$content = substr( $content, 0, $pos ) . $linked . substr( $content, $pos + strlen( $find ) );
	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => wp_slash( $content ),
		)
	);
	pll_step( 'APPLIED', "$path -> $href (\"$anchor\")" );
}

/**
 * Add a "see also" line near the top of a page.
 *
 * @param string $path   Host page.
 * @param string $intro  Lead-in text.
 * @param string $href   Destination path.
 * @param string $anchor Anchor text.
 * @return void
 */
function pll_seealso_in( $path, $intro, $href, $anchor ) {
	$post_id = pll_seo_resolve_path_id( $path );
	if ( ! $post_id ) {
		pll_step( 'MISSING', "$path not found (see-also to $href)" );
		return;
	}
	$post    = get_post( $post_id );
	$content = $post->post_content;

	if ( false !== strpos( $content, 'href="' . $href . '"' ) ) {
		pll_step( 'already', "$path see-also -> $href" );
		return;
	}
	$block = '<!-- wp:paragraph {"className":"pll-related-links text-[14px] leading-[1.6] text-ink-soft"} -->' . "\n"
		. '<p class="pll-related-links text-[14px] leading-[1.6] text-ink-soft">'
		. esc_html( $intro ) . ' <a href="' . $href . '">' . esc_html( $anchor ) . '</a>.</p>' . "\n"
		. '<!-- /wp:paragraph -->';

	$marker = '<!-- /wp:paragraph -->';
	$pos    = strpos( $content, $marker );
	if ( false !== $pos ) {
		$at      = $pos + strlen( $marker );
		$content = substr( $content, 0, $at ) . "\n\n" . $block . substr( $content, $at );
	} else {
		$content = $block . "\n\n" . $content;
	}
	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => wp_slash( $content ),
		)
	);
	pll_step( 'APPLIED', "$path see-also -> $href (\"$anchor\")" );
}

/**
 * Remove a wp:group by exact className, balancing nested groups.
 *
 * @param string $content   Block markup.
 * @param string $classname Exact className value.
 * @return string
 */
function pll_strip_group( $content, $classname ) {
	$needle = '"className":"' . $classname . '"';
	$at     = strpos( $content, $needle );
	if ( false === $at ) {
		return $content;
	}
	$start = strrpos( substr( $content, 0, $at ), '<!-- wp:group' );
	if ( false === $start ) {
		return $content;
	}
	$depth  = 0;
	$cursor = $start;
	$len    = strlen( $content );
	while ( $cursor < $len ) {
		$open  = strpos( $content, '<!-- wp:group', $cursor );
		$close = strpos( $content, '<!-- /wp:group -->', $cursor );
		if ( false === $close ) {
			return $content;
		}
		if ( false !== $open && $open < $close ) {
			++$depth;
			$cursor = $open + strlen( '<!-- wp:group' );
			continue;
		}
		--$depth;
		$cursor = $close + strlen( '<!-- /wp:group -->' );
		if ( 0 === $depth ) {
			return rtrim( substr( $content, 0, $start ) ) . "\n" . ltrim( substr( $content, $cursor ) );
		}
	}
	return $content;
}

echo "\n=== 1. Pricing copy: PRECICE MAX naming ===\n";

pll_replace_in( '/', '<span>2 PRECICE internal nail implants</span>', '<span>2 PRECICE MAX internal nail implants</span>', 'home 2-nail bullets' );
pll_replace_in( '/', '<span>4 PRECICE internal nail implants</span>', '<span>4 PRECICE MAX internal nail implants</span>', 'home 4-nail bullet' );
pll_replace_in( '/limb-lengthening-pricing-options/', '<span>2 PRECICE 4th Generation internal nail implants</span>', '<span>2 PRECICE MAX internal nail implants</span>', 'pricing 2-nail bullets' );
pll_replace_in( '/limb-lengthening-pricing-options/', '<span>4 PRECICE internal nail implants</span>', '<span>4 PRECICE MAX internal nail implants</span>', 'pricing $195,000 bullet' );

echo "\n=== 2. Pricing headings ===\n";

pll_replace_in( '/limb-lengthening-pricing-options/', 'Limb Lengthening Cost, <em class="italic text-spine">priced like a partner.</em>', 'Limb Lengthening <em class="italic text-spine">Cost.</em>', 'h1 drops "priced like a partner"' );
pll_replace_in( '/limb-lengthening-pricing-options/', 'Three procedures. <em class="italic text-spine">One transparent price.</em>', 'Three procedures. <em class="italic text-spine">PRECICE MAX cost.</em>', 'h2 names PRECICE MAX' );

echo "\n=== 3. Financing claims removed ===\n";

pll_replace_in( '/', 'Financing available through CareCredit. As low as $1,200/month with approved credit.', 'Financing options are available. We will walk you through them during your consultation.', 'home financing line' );
pll_replace_in( '/is-limb-lengthening-covered-by-insurance/', 'Premier Limb Lengthening offers financing through SoFi and CareCredit, with monthly payments starting as low as $1,200 for qualified applicants.', 'Premier Limb Lengthening works with financing partners including SoFi and CareCredit, and terms depend on the applicant and the lender.', 'insurance article visible answer' );

$pll_pricing_id = pll_seo_resolve_path_id( '/limb-lengthening-pricing-options/' );
if ( $pll_pricing_id ) {
	$pll_post = get_post( $pll_pricing_id );
	$pll_new  = pll_strip_group( $pll_post->post_content, 'mt-10 grid grid-cols-2 gap-0 border-t border-white/25 max-w-[460px]' );
	if ( $pll_new !== $pll_post->post_content ) {
		if ( substr_count( $pll_new, '<!-- wp:group' ) !== substr_count( $pll_new, '<!-- /wp:group -->' ) ) {
			pll_step( 'MISSING', 'financing tiles NOT removed (would leave unbalanced blocks)' );
		} else {
			wp_update_post(
				array(
					'ID'           => $pll_pricing_id,
					'post_content' => wp_slash( $pll_new ),
				)
			);
			pll_step( 'APPLIED', 'pricing — $1,200/mo and 0% intro tiles removed' );
		}
	} else {
		pll_step( 'already', 'pricing — financing tiles already gone' );
	}
}

echo "\n=== 4. Cannibalization A2: head-term anchors -> /your-surgery/ ===\n";

pll_link_in( '/limb-lengthening-what-you-gain-what-you-risk/', 'In fact, limb lengthening surgery can open up new worlds', 'limb lengthening surgery', '/your-surgery/' );
pll_link_in( '/are-you-a-good-candidate-for-limb-lengthening/', 'prior to limb lengthening surgery to reduce the risk', 'limb lengthening surgery', '/your-surgery/' );
pll_link_in( '/is-limb-lengthening-covered-by-insurance/', 'if limb lengthening surgery is deemed medically necessary', 'limb lengthening surgery', '/your-surgery/' );
pll_link_in( '/rewriting-the-body-norm-stigmas-around-limb-lengthening/', 'the decision to undergo limb lengthening surgery', 'limb lengthening surgery', '/your-surgery/' );
pll_link_in( '/your-surgery/can-i-bend-my-lengthening-nail/', 'In trauma and modern limb lengthening surgery, an intramedullary nail', 'limb lengthening surgery', '/your-surgery/' );

echo "\n=== 5. Cannibalization A6: name anchors -> /dr-basmajian/ ===\n";

pll_link_in( '/your-surgery/can-i-bend-my-lengthening-nail/', 'a trauma background, like Dr. Basmajian, can help', 'Dr. Basmajian', '/dr-basmajian/' );
pll_link_in( '/your-surgery/how-much-taller-can-i-get-with-limb-lengthening/', 'Dr. Basmajian will work with each of our patients', 'Dr. Basmajian', '/dr-basmajian/' );
pll_link_in( '/your-surgery/limb-lengthening-expectations/', 'During your consultation, Dr. Basmajian will review your medical history', 'Dr. Basmajian', '/dr-basmajian/' );
pll_link_in( '/your-surgery/external-internal-lengthening/', 'Dr. Basmajian has used both technologies', 'Dr. Basmajian', '/dr-basmajian/' );
pll_link_in( '/your-surgery/is-there-an-age-limit-for-limb-lengthening/', 'should be discussed with Dr. Basmajian and the family', 'Dr. Basmajian', '/dr-basmajian/' );

echo "\n=== 6. Cannibalization A7: sports pair ===\n";

pll_link_in( '/is-leg-lengthening-off-limits-for-athletes/', 'reintroduces running, jumping, cutting movements', 'running, jumping, cutting movements', '/your-surgery/exercise-after-limb-lengthening/' );

echo "\n=== 7. Cannibalization A5: age pair ===\n";

pll_seealso_in( '/your-surgery/is-there-an-age-limit-for-limb-lengthening/', 'For what changes when patients are older, see', '/am-i-too-old-for-limb-lengthening/', 'limb lengthening for patients over 40' );
pll_seealso_in( '/am-i-too-old-for-limb-lengthening/', 'For the eligibility rules by age, see', '/your-surgery/is-there-an-age-limit-for-limb-lengthening/', 'age limit for limb lengthening' );

$pll_age_id = pll_seo_resolve_path_id( '/am-i-too-old-for-limb-lengthening/' );
if ( $pll_age_id ) {
	$pll_cur = (string) get_post_meta( $pll_age_id, '_pll_seo_title', true );
	$pll_new_title = 'Limb Lengthening in Your 40s and 50s · Are You Too Old?';
	if ( $pll_cur === $pll_new_title ) {
		pll_step( 'already', 'age article title' );
	} elseif ( 'Am I Too Old for Limb Lengthening?' === $pll_cur ) {
		update_post_meta( $pll_age_id, '_pll_seo_title', $pll_new_title );
		pll_step( 'APPLIED', 'age article retitled' );
	} else {
		pll_step( 'MISSING', 'age article title is "' . $pll_cur . '", left alone (an editor owns it)' );
	}
}

printf( "\n=== SUMMARY: %d applied, %d already done, %d needs attention ===\n", $pll_applied, $pll_skipped, $pll_missing );
if ( $pll_missing > 0 ) {
	echo "Review the MISSING lines above before treating this as complete.\n";
}
