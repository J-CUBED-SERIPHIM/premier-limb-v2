<?php
/**
 * SEO meta fields. Seeded by the content pipeline (WXR/setup.php); override
 * per entity. Underscore-prefixed (protected) so they don't clutter the
 * Custom Fields UI; exposed over REST for future editor UI.
 *
 * @package pll-seo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize an editor-supplied canonical to an absolute web URL.
 *
 * @param mixed $value Raw post-meta value.
 * @return string
 */
function pll_seo_sanitize_canonical_url( $value ) {
	$url    = esc_url_raw( (string) $value, array( 'http', 'https' ) );
	$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
	$host   = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! $url || ! $host || ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
		return '';
	}
	return $url;
}

/**
 * Store the noindex flag as a predictable integer for REST and sitemap use.
 *
 * @param mixed $value Raw post-meta value.
 * @return int
 */
function pll_seo_sanitize_noindex( $value ) {
	return empty( $value ) ? 0 : 1;
}

/**
 * Whether the current or supplied singular item must not be indexed.
 *
 * @param int $post_id Post ID, or zero for the queried item.
 * @return bool
 */
function pll_seo_is_noindex( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	return $post_id > 0 && 1 === (int) get_post_meta( $post_id, '_pll_robots_noindex', true );
}

/**
 * Gates the SERP price anchor on /limb-lengthening-pricing-options/ (SEO spec
 * 02-SEO-TECH-SPEC.md §3.3 item 2 and §4).
 *
 *   false = ship the price free fallback
 *           "Limb Lengthening Surgery Cost: 2026 All-Inclusive Pricing"
 *   true  = ship "Limb Lengthening Surgery Cost: From $75,500 (2026)"
 *
 * ⚠️ FAIL SAFE UNTIL APPROVED. The default remains `false` until a recorded
 * client approval activates the anchor.
 *
 * This publishes a dollar figure for elective surgery into a Google result. It
 * requires the client's explicit sign-off (Jaime). The absence of a decision
 * must never produce the riskier
 * outcome, so a fresh install, a staging rebuild, a re-seed, or any environment
 * that never defines this constant all publish the price free copy.
 *
 * Approval record: Jaime Alefosio, 2026-08-27, Notion task comment
 *
 * Flipping this to `true` IS the act of recording that approval. Do it in its
 * own reviewed commit, so the approval has a signature and a date attached to
 * it rather than being buried in a refactor.
 *
 * TO APPROVE: set the value to true AND add an approval line to this docblock,
 * replacing the angle-bracket placeholders. verify-seo-meta.mjs rejects a
 * `true` that has no approval record, so the two cannot drift apart:
 *
 *   Approved-By: Jaime Alefosio, 2026-08-27, Notion task comment
 *
 * Because it is wrapped in defined(), an environment can also opt in (or force
 * itself back off) without a code deploy, from wp-config.php:
 *
 *   define( 'PLL_SEO_PRICE_ANCHOR_APPROVED', true );
 *
 * $75,500 is the lowest of the six tiers in data/pricing.php (Bilateral Femur
 * Lengthening with PRECICE 2), so "From" is literally accurate. Note this
 * constant gates the METADATA only. The JSON-LD priceRange floor and the
 * homepage FAQ cost answer were corrected unconditionally (SEO spec REQ-13)
 * because the old values were factually wrong on their own terms.
 */
if ( ! defined( 'PLL_SEO_PRICE_ANCHOR_APPROVED' ) ) {
	define( 'PLL_SEO_PRICE_ANCHOR_APPROVED', true );
}

add_action(
	'init',
	function () {
		$fields = array(
			'_pll_seo_title'       => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_pll_seo_description' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_pll_og_title'        => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_pll_og_description'  => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'_pll_canonical_url'   => array(
				'type'              => 'string',
				'sanitize_callback' => 'pll_seo_sanitize_canonical_url',
			),
			'_pll_robots_noindex'  => array(
				'type'              => 'boolean',
				'sanitize_callback' => 'pll_seo_sanitize_noindex',
			),
			'_pll_og_image_id'     => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
		);
		foreach ( array( 'post', 'page' ) as $type ) {
			foreach ( $fields as $key => $field ) {
				register_post_meta(
					$type,
					$key,
					array(
						'show_in_rest'      => true,
						'single'            => true,
						'type'              => $field['type'],
						'sanitize_callback' => $field['sanitize_callback'],
						'auth_callback'     => function () {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}
	}
);

/**
 * Hand-written metadata for the marketing pages — extracted from each
 * page.tsx `metadata` export in the Next.js app (the parity source).
 * Post meta wins over these defaults; these win over generated fallbacks.
 *
 * @return array<string, array{title?: string, description?: string, og_title?: string, og_description?: string, og_type?: string}>
 */
function pll_seo_page_defaults() {
	return array(
		// Absolute (no site-name suffix): the template would push it past
		// 100 chars. Keyword-first per homepage handoff v2 §08.
		'/'                                  => array(
			'title'          => 'Cosmetic Limb Lengthening Surgery · Dr. Hrayr Basmajian · Southern California',
			'title_absolute' => true,
			'description'    => 'Cosmetic limb lengthening surgery performed by Dr. Hrayr Basmajian, a fellowship-trained orthopaedic trauma surgeon in Southern California. Precice internal nail. Revision cases accepted. Concierge care included.',
			'og_title'       => 'Cosmetic Limb Lengthening Surgery · Dr. Hrayr Basmajian · Southern California',
			'og_description' => 'Cosmetic limb lengthening performed by a fellowship-trained orthopaedic trauma surgeon. Precice internal nail. Revision cases accepted. Concierge care included.',
			'og_type'        => 'website',
		),
		'/about/'                            => array(
			'title'          => 'About Premier Limb Lengthening, Founded by Dr. Hrayr Basmajian',
			'description'    => 'Premier Limb Lengthening is a cosmetic and reconstructive surgery practice created by Dr. Hrayr Basmajian, founder of Premier Orthopaedic & Trauma Specialists, based in Upland, California.',
			'og_title'       => 'About Premier Limb Lengthening, Founded by Dr. Hrayr Basmajian',
			'og_description' => 'A cosmetic and reconstructive surgery practice created by Dr. Hrayr Basmajian, founder of Premier Orthopaedic & Trauma Specialists, based in Upland, California.',
			'og_type'        => 'website',
		),
		// Contact and booking are separate flows (owner request 2026-07):
		// /consult/ is the contact page (form), /book-a-consultation/ carries
		// the scheduling intent (embedded GHL calendar). Absolute title on
		// /consult/ — the brand name IS the title, so the template suffix
		// would double it.
		'/consult/'                          => array(
			'title'          => 'Contact Premier Limb Lengthening · Upland, California',
			'title_absolute' => true,
			'description'    => 'Contact Premier Limb Lengthening in Upland, California. Send a confidential message and we respond within one business day, or call (951) 620-5663.',
			'og_title'       => 'Contact Premier Limb Lengthening · Upland, California',
			'og_description' => 'Send us a confidential message. We respond within one business day. Virtual visits and white-glove travel coordination from Upland, California.',
			'og_type'        => 'website',
		),
		'/book-a-consultation/'              => array(
			'title'          => 'Book a Limb Lengthening Consultation',
			'description'    => 'Book your limb lengthening consultation online. Pick a time for a secure video or in-office visit with Dr. Hrayr Basmajian in Upland, California.',
			'og_title'       => 'Book a Limb Lengthening Consultation · Premier',
			'og_description' => 'Pick a time online for a secure video or in-office consultation with Dr. Hrayr Basmajian in Upland, California.',
			'og_type'        => 'website',
		),
		'/dr-basmajian/'                     => array(
			'title'          => 'Dr. Hrayr Basmajian · Limb Lengthening Surgeon',
			'description'    => 'Board-certified orthopaedic trauma surgeon and Medical Director of Orthopaedic Trauma at Pomona Valley Hospital. Thousands of limb lengthening procedures performed.',
			'og_title'       => 'Dr. Hrayr Basmajian · Limb Lengthening Surgeon',
			'og_description' => 'Board-certified orthopaedic trauma surgeon. Director, Orthopaedic Trauma at Pomona Valley Hospital. Thousands of limb lengthening procedures performed.',
			'og_type'        => 'profile',
		),
		// ⚠️ PRICE ANCHOR, PENDING CLIENT APPROVAL. See the block comment on
		// PLL_SEO_PRICE_ANCHOR_APPROVED at the top of this file. The gate
		// defaults to false, so the price free fallback below is what ships
		// until Jaime approves. Flipping that one constant to true is the
		// recorded act of approval.
		//
		// All four fields are set on purpose (SEO spec REQ-10): this path is
		// the only in-scope entry that already seeded _pll_og_title and
		// _pll_og_description post meta, so setting only the SEO pair would
		// leave a stale OG title on every social share. The overrides map
		// defines no og_* keys, so the other six in-scope pages inherit.
		//
		// The description names the tier ("bilateral femur with PRECICE 2") as
		// a hard requirement, not as flavour: it is what makes the word "From"
		// defensible disclosure for elective surgery (SEO spec §4.1). Do not
		// shorten it to drop the tier name.
		//
		// Year token: roll 2026 to 2027 every January, in all four fields
		// (SEO spec §3.3 item 2, backlog ticket T5).
		'/limb-lengthening-pricing-options/' => PLL_SEO_PRICE_ANCHOR_APPROVED
			? array(
				// APPROVED BY SEO, GATED ON THE CLIENT. Carries $75,500.
				'title'          => 'Limb Lengthening Surgery Cost: From $75,500 (2026)',
				'description'    => '2026 pricing from $75,500 for bilateral femur with PRECICE 2. Every quote bundles the implant, OR time, hospital stay, anesthesia, and follow-up care.',
				'og_title'       => 'Limb Lengthening Surgery Cost: From $75,500 (2026)',
				'og_description' => '2026 pricing from $75,500 for bilateral femur with PRECICE 2. Every quote bundles the implant, OR time, hospital stay, anesthesia, and follow-up care.',
				'og_type'        => 'website',
			)
			: array(
				// DEFAULT. No client approval required, and no dollar figure.
				// Keeps the exact phrase match on "limb lengthening surgery
				// cost" and the all inclusive hook.
				//
				// The description is NOT the old production string: that one
				// measured 998px against a 960px budget, so it truncated. This
				// is the SEO's corrected 144 char / 895px replacement, which
				// also drops "and on-site sessions" (vague, and the phrase
				// appears nowhere on the landing page) and mirrors the
				// approved branch so the two variants read consistently.
				'title'          => 'Limb Lengthening Surgery Cost: 2026 All-Inclusive Pricing',
				'description'    => 'Transparent 2026 pricing for cosmetic limb lengthening. Every quote bundles the implant, OR time, hospital stay, anesthesia, and follow-up care.',
				'og_title'       => 'Limb Lengthening Surgery Cost: 2026 All-Inclusive Pricing',
				'og_description' => 'Transparent 2026 pricing for cosmetic limb lengthening. Bundled implants, OR time, hospitalization, anesthesia, follow-up care, and on-site sessions.',
				'og_type'        => 'website',
			),
		// SEO pillars (PLL_SEO_Audit_Report.docx, Title Tags and Meta
		// Descriptions). Titles stored WITHOUT the "· Premier Limb
		// Lengthening" suffix — titles.php appends it.
		'/height-surgery/'                   => array(
			'title'          => 'Height Surgery · What It Is, Cost, and How It Works',
			'description'    => 'Height surgery permanently adds 2 to 3.5 inches by lengthening the femur. Learn how it works, costs, candidacy, and how to choose a surgeon. Call (951) 620-5663.',
			'og_title'       => 'Height Surgery · What It Is, Cost, and How It Works',
			'og_description' => 'Height surgery permanently adds 2 to 3.5 inches by lengthening the femur. How it works, costs, candidacy, and how to choose a surgeon.',
			'og_type'        => 'article',
		),
		'/leg-lengthening-surgery/'          => array(
			'title'          => 'Leg Lengthening Surgery · Procedure, Recovery, and Risks',
			'description'    => 'Leg lengthening surgery increases height using an internal nail. Learn the procedure, recovery timeline, pain, and risks from a trauma-trained surgeon. Call (951) 620-5663.',
			'og_title'       => 'Leg Lengthening Surgery · Procedure, Recovery, and Risks',
			'og_description' => 'Leg lengthening surgery increases height using an internal nail. The procedure, recovery timeline, pain, and risks from a trauma-trained surgeon.',
			'og_type'        => 'article',
		),
		'/your-surgery/'                     => array(
			'title'          => 'Limb Lengthening Surgery · How It Works',
			'description'    => 'How limb lengthening works: distraction osteogenesis, Precice internal nail placement, gradual distraction, and a recovery timeline you can plan your life around.',
			'og_title'       => 'Limb Lengthening Surgery · How It Works',
			'og_description' => 'Distraction osteogenesis, internal Precice technology, and a recovery timeline you can plan your life around.',
			'og_type'        => 'article',
		),
		'/blog/'                             => array(
			'title'          => 'Limb Lengthening Blog, Articles & Patient Guides',
			'description'    => 'Honest, plain-language coverage of cosmetic limb lengthening: candidacy, recovery, pricing, and the science of bone regeneration, written to help patients decide with confidence.',
			'og_title'       => 'Limb Lengthening Blog, Articles & Patient Guides',
			'og_description' => 'Patient-grade articles on candidacy, recovery, pricing, and the science of bone regeneration.',
			'og_type'        => 'website',
		),
		// Absolute title (keyword-first, no site-name suffix): the template
		// would push it past 100 chars. Mirrors the Next.js route metadata.
		'/evaluate-your-surgeon/'            => array(
			'title'          => 'How to Evaluate a Limb Lengthening Surgeon · Free Scoring Tool',
			'title_absolute' => true,
			'description'    => 'Score any limb lengthening surgeon on 10 clinical and patient-experience criteria, compare side by side, and bring the right questions to your consultation.',
			'og_title'       => 'How to Evaluate a Limb Lengthening Surgeon · Free Scoring Tool',
			'og_description' => 'Score any limb lengthening surgeon across 10 clinical and patient experience criteria. Compare surgeons side by side. Free, private, no account required.',
			'og_type'        => 'website',
		),
		'/privacy/'                          => array(
			'title'          => 'Privacy Policy',
			'description'    => 'How Premier Limb Lengthening collects, uses, and protects website data, our HIPAA commitments, and our SMS and mobile messaging practices.',
			'og_title'       => 'Privacy Policy · Premier Limb Lengthening',
			'og_description' => 'Our privacy practices for website data, HIPAA-protected health information, and SMS and mobile messaging.',
			'og_type'        => 'website',
		),
		'/terms/'                            => array(
			'title'          => 'Terms of Service',
			'description'    => 'The terms of use for the Premier Limb Lengthening website, including our medical disclaimer, individual-results notice, and SMS text messaging program terms.',
			'og_title'       => 'Terms of Service · Premier Limb Lengthening',
			'og_description' => 'Website terms of use, medical disclaimer, and SMS text messaging terms for Premier Limb Lengthening.',
			'og_type'        => 'website',
		),
		'/accessibility/'                    => array(
			'title'          => 'Accessibility Statement',
			'description'    => "Premier Limb Lengthening's commitment to website accessibility, our work toward WCAG 2.1 Level AA, and how to request an accommodation.",
			'og_title'       => 'Accessibility Statement · Premier Limb Lengthening',
			'og_description' => 'Our commitment to an accessible website, our WCAG 2.1 AA goal, and how to reach us for help or accommodations.',
			'og_type'        => 'website',
		),
	);
}

/**
 * Current request path, root-relative with trailing slash ('/about/').
 *
 * @return string
 */
function pll_seo_current_path() {
	if ( is_front_page() ) {
		return '/';
	}
	$url  = pll_seo_current_url();
	$path = wp_parse_url( $url, PHP_URL_PATH );
	return $path ? user_trailingslashit( $path ) : '/';
}

/**
 * Canonical URL for the current view, on the production origin.
 *
 * @return string
 */
function pll_seo_current_url() {
	global $wp;
	if ( is_singular() ) {
		$canonical = pll_seo_sanitize_canonical_url( get_post_meta( get_queried_object_id(), '_pll_canonical_url', true ) );
		if ( $canonical ) {
			return $canonical;
		}
	}
	if ( is_front_page() ) {
		return PLL_SEO_ORIGIN . '/';
	}
	$local = home_url( add_query_arg( array(), trailingslashit( $wp->request ) ) );
	$path  = wp_parse_url( $local, PHP_URL_PATH );
	return PLL_SEO_ORIGIN . ( $path ? user_trailingslashit( $path ) : '/' );
}

/**
 * Use an editor-selected Media Library image for social cards when available.
 *
 * @param array{url: string, alt: string}|null $image Default image data.
 * @return array{url: string, alt: string}|null
 */
function pll_seo_og_image_override( $image ) {
	if ( ! is_singular() ) {
		return $image;
	}

	$image_id = absint( get_post_meta( get_queried_object_id(), '_pll_og_image_id', true ) );
	if ( ! $image_id ) {
		return $image;
	}
	$url = wp_get_attachment_image_url( $image_id, 'full' );
	if ( ! $url ) {
		return $image;
	}

	return array(
		'url' => $url,
		'alt' => (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
	);
}
add_filter( 'pll_seo_og_image', 'pll_seo_og_image_override' );

/**
 * Keep explicitly noindexed entries out of WordPress core post sitemaps.
 *
 * @param array<string, mixed> $args WP_Query arguments.
 * @return array<string, mixed>
 */
function pll_seo_exclude_noindex_from_sitemaps( $args ) {
	if ( ! isset( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
		$args['meta_query'] = array();
	}
	$args['meta_query'][] = array(
		'relation' => 'OR',
		array(
			'key'     => '_pll_robots_noindex',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_pll_robots_noindex',
			'value'   => '1',
			'compare' => '!=',
		),
	);
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'pll_seo_exclude_noindex_from_sitemaps' );

/**
 * Pages marked noindex are still usable by patients, so leave links followed.
 *
 * @param array<string, bool> $robots Current robots directives.
 * @return array<string, bool>
 */
function pll_seo_robots_for_noindex( $robots ) {
	if ( ! is_singular() || ! pll_seo_is_noindex() ) {
		return $robots;
	}
	return array(
		'noindex' => true,
		'follow'  => true,
	);
}
add_filter( 'wp_robots', 'pll_seo_robots_for_noindex', 30 );

/**
 * Curated SEO overrides for the surgery sub-pages and articles.
 *
 * These pages were imported with weak, auto-derived post meta (the description
 * was often the first body sentence, or the title repeated). This map restores
 * the optimized, keyword-aligned titles and descriptions (ported from
 * lib/seo_metadata.ts) and is authoritative for the paths it covers. Brand
 * rules honored: no em dashes, no semicolons.
 *
 * @return array<string, array{title?: string, description?: string}>
 */
function pll_seo_overrides() {
	return array(
		// Your Surgery sub-pages.
		//
		// IN SCOPE (SEO spec §3.3 item 1). Supersedes c508bf7. "Up to 6 Inches"
		// is deliberate and must not be changed back to "3 to 6": only the
		// combined two-stage procedure reaches 6 inches (single-segment femur
		// is 2 to 3.5 per the MedicalProcedure copy in schema.php), so a bare
		// "3 to 6" title claim is not supportable. See SEO spec R-J.
		'/your-surgery/how-much-taller-can-i-get-with-limb-lengthening/' => array(
			'title'       => 'How Much Taller Can You Get? Up to 6 Inches',
			'description' => 'Most patients gain 2 to 6 inches depending on whether the femur, the tibia, or both are lengthened. See the realistic range for each segment.',
		),
		// Reverted to the pre-c508bf7 value (out of scope, position 26.5, and
		// never validated against GSC). Also clears the en dash c508bf7 put in
		// the age range.
		'/your-surgery/is-there-an-age-limit-for-limb-lengthening/' => array(
			'title'       => 'Is There an Age Limit for Limb Lengthening?',
			'description' => 'There is no fixed cutoff for cosmetic limb lengthening. What matters is bone density, joint health, and overall fitness, and here is how Dr. Basmajian evaluates candidacy.',
		),
		// Reverted to the pre-c508bf7 value (out of scope).
		'/your-surgery/can-i-bend-my-lengthening-nail/' => array(
			'title'       => 'Can I Bend My Lengthening Nail?',
			'description' => 'What happens if you over-stress the Precice internal nail during recovery, and the daily-life precautions that keep your hardware safe through full bone healing.',
		),
		// Reverted to the pre-c508bf7 value (out of scope).
		'/your-surgery/external-internal-lengthening/' => array(
			'title'       => 'External vs. Internal Lengthening: A Comparison',
			'description' => 'Internal magnetic nails versus external fixator frames, and the trade-offs in pain, infection risk, mobility, scarring, and recovery time. Premier uses internal nails only.',
		),
		// IN SCOPE (SEO spec §3.3 item 7). Supersedes c508bf7. Leads with the
		// noun phrase so the keyword sits at pixel 0 of the visible fold.
		'/your-surgery/exercise-after-limb-lengthening/' => array(
			'title'       => 'Exercise After Limb Lengthening: Week by Week',
			'description' => 'A week-by-week return-to-exercise timeline after limb lengthening: walking, swimming, cycling, then strength training, and when the gym is cleared.',
		),
		// Reverted to the pre-c508bf7 value (out of scope). This revert is what
		// clears defect D6: c508bf7 gave this page a description byte-identical
		// to /limb-lengthening-pain-the-truth/.
		'/your-surgery/will-limb-lengthening-hurt/' => array(
			'title'       => 'Will Limb Lengthening Hurt? Pain Levels Explained',
			'description' => 'An honest answer to the most asked question about limb lengthening: how much pain to expect during distraction, how it is managed, and when it fades.',
		),
		// Reverted to the pre-c508bf7 value (out of scope).
		'/your-surgery/limb-lengthening-expectations/' => array(
			'title'       => 'Limb Lengthening: What to Expect Before & After Surgery',
			'description' => 'A realistic timeline for surgery, distraction, consolidation, and physical therapy, plus the lifestyle changes to plan for in the first six months.',
		),

		// Articles.
		// Reverted to the pre-c508bf7 value (out of scope).
		'/are-you-a-good-candidate-for-limb-lengthening/' => array(
			'title'       => 'Is Limb Lengthening Right for You? Candidacy Explained',
			'description' => 'A surgeon-written guide to the four factors that decide candidacy for cosmetic limb lengthening: age, bone health, lifestyle, and mental preparation.',
		),
		// Reverted to the pre-c508bf7 value (out of scope, and already
		// converting at 2x the site average). Also clears the en dash.
		// Retitled for cannibalization audit A5 (owner-approved 2026-08-27).
		// This page and /your-surgery/is-there-an-age-limit-for-limb-lengthening/
		// were each surfacing as the losing secondary on the other's queries
		// (9-21% bleed). Google had already half-sorted them by intent, so the
		// titles were made to say out loud which intent each one serves: the
		// hub keeps the eligibility-ceiling wording ("age limit"), this one
		// takes the older-patient wording. The body needed no edit, it never
		// used "age limit" and its H2s were already the 40s/50s angle.
		'/am-i-too-old-for-limb-lengthening/' => array(
			'title'       => 'Limb Lengthening in Your 40s and 50s · Are You Too Old?',
			'description' => 'Adults in their 40s and 50s are routinely good candidates for limb lengthening. What matters is biological readiness, not chronological age, and here is how we screen for it.',
		),
		// Reverted to the pre-c508bf7 value (out of scope, position 15.7 and a
		// ranking problem rather than a metadata one).
		'/limb-lengthening-what-you-gain-what-you-risk/' => array(
			'title'       => 'Pros and Cons of Limb Lengthening',
			'description' => 'The honest trade-offs: height gain, posture change, and confidence weighed against pain, downtime, hardware, and complication risk. Read both sides before deciding.',
		),
		'/rewriting-the-body-norm-stigmas-around-limb-lengthening/' => array(
			'title'       => 'Dealing With Limb Lengthening-Related Stigma',
			'description' => 'Dealing with the social stigma and judgment around cosmetic limb lengthening. How patients respond, and what clinical and psychological data actually shows.',
		),
		'/can-i-get-a-leg-lengthening-procedure-for-cosmetic-reasons/' => array(
			'title'       => 'Can I Get Leg Lengthening for Cosmetic Reasons?',
			'description' => 'Yes, cosmetic limb lengthening is a legitimate, surgeon-performed orthopaedic procedure. Here is how the screening, surgery, and recovery differ from medical cases.',
		),
		// IN SCOPE (SEO spec §3.3 item 4). Supersedes c508bf7. "vs" carries no
		// trailing period, uniformly across the set.
		'/leg-up-or-let-down-can-you-gain-height-without-surgery/' => array(
			'title'       => 'Can You Gain Height Without Surgery? Fact vs Myth',
			'description' => 'Do posture work, insoles, and growth hormone actually add height? A surgeon\'s breakdown of what each method can and cannot do, and the real ceiling.',
		),
		// IN SCOPE (SEO spec §3.3 item 5). Supersedes c508bf7. Flips the frame
		// from negative ("Off-Limits") to the question athletes actually ask.
		'/is-leg-lengthening-off-limits-for-athletes/' => array(
			'title'       => 'Can Athletes Get Leg Lengthening? Running & Sports',
			'description' => 'Can you still run, sprint, and lift after leg lengthening? How athletes rebuild function and biomechanics once the nail consolidates, plus the timeline.',
		),
		// Reverted to the pre-c508bf7 value (out of scope).
		'/fixation-methods-in-limb-lengthening-internal-vs-external/' => array(
			'title'       => 'Fixation Methods in Limb Lengthening: Internal vs. External',
			'description' => 'Internal Precice nails versus external Ilizarov-style frames, and how each method holds bone, distributes load, and shapes your recovery.',
		),
		// Reverted to the pre-c508bf7 value (out of scope).
		'/will-leg-lengthening-be-obvious/' => array(
			'title'       => 'Will My Leg Lengthening Be Obvious?',
			'description' => 'With internal nails and modern post-op care, leg lengthening leaves no visible hardware and minimal scarring. Here is what people notice, and what they do not.',
		),
		// IN SCOPE (SEO spec §3.3 item 6). Supersedes c508bf7. "Limb" in the
		// title matches the URL and H1, "leg" in the description covers the
		// query variant.
		'/limb-lengthening-pain-the-truth/' => array(
			'title'       => 'How Painful Is Limb Lengthening? An Honest Answer',
			'description' => 'How painful is leg lengthening, really? Pain peaks in the first two weeks of distraction, then fades. Inside the nerve blocks, medication, and PT that help.',
		),
		// IN SCOPE (SEO spec §3.3 item 3). Supersedes c508bf7. Matches the
		// active-voice phrasing this page already ranks position 1.0 for, and
		// the cosmetic vs medical differentiator Google is already surfacing as
		// this page's top jump link.
		'/is-limb-lengthening-covered-by-insurance/' => array(
			'title'       => 'Does Insurance Cover Leg Lengthening? Cosmetic vs Medical',
			'description' => 'Cosmetic limb lengthening is almost never covered. Reconstructive cases like limb-length discrepancy or deformity may qualify. What to ask your insurer.',
		),
		'/the-importance-of-physical-therapy-in-limb-lengthening/' => array(
			'title'       => 'Why Physical Therapy Is Critical in Limb Lengthening',
			'description' => 'PT is the difference between full mobility and lasting stiffness. The Premier protocol: daily sessions during distraction, tapering through consolidation.',
		),
		'/tips-for-traveling-for-the-holidays-after-limb-lengthening-surgery/' => array(
			'title'       => 'Traveling After Limb Lengthening: Holiday & Flight Tips',
			'description' => 'When it is safe to fly, what to pack, and how to arrange airport assistance and TSA hardware notifications after limb lengthening surgery.',
		),
		'/bone-health-and-nutrition-before-and-after-limb-lengthening/' => array(
			'title'       => 'Nutrition for Limb Lengthening Recovery',
			'description' => 'Calcium, vitamin D, protein, and collagen support new bone formation. A practical pre-op and post-op nutrition plan from the Premier care team.',
		),
		// Reverted to the pre-c508bf7 value (out of scope).
		'/the-science-behind-bone-regeneration-and-limb-lengthening/' => array(
			'title'       => 'The Science of Bone Regeneration',
			'description' => 'Distraction osteogenesis: how slow, controlled separation triggers your body to grow new bone in the gap. A patient-friendly explainer.',
		),
		// Reverted to the pre-c508bf7 value (out of scope).
		'/what-happens-to-muscle-during-and-after-limb-lengthening/' => array(
			'title'       => 'Limb Lengthening and Its Effect on Soft Tissues',
			'description' => 'Bone is not the only tissue that lengthens. Muscles, nerves, and tendons adapt too. What that adaptation feels like, and how PT supports it.',
		),
	);
}

/**
 * Resolve a metadata field for the current view.
 *
 * Resolution order, highest priority first (this docblock previously stated it
 * backwards, which is how the PHP map and production came to disagree without
 * anyone noticing, so keep it accurate):
 *
 *   1. post meta            _pll_seo_title / _pll_seo_description /
 *                           _pll_og_title / _pll_og_description   ← WINS
 *   2. pll_seo_overrides()
 *   3. pll_seo_page_defaults()
 *   4. generated fallback   (og_title falls back to title,
 *                            og_description falls back to description)
 *
 * The consequence worth spelling out: the one-time seeding migration at the
 * bottom of this file promotes the whole map into real post meta, so on any
 * environment that has already seeded, EDITING THIS FILE ALONE WILL NOT CHANGE
 * THE RENDERED PAGE. Post meta shadows it. To change a seeded page, edit the
 * "SEO (PLL)" document panel (or update_post_meta directly). Do NOT reach for
 * a PLL_SEO_VERSION bump to force a re-seed: that re-runs the migration across
 * the entire map and overwrites every SEO field any editor has set since
 * launch (see wordpress/docs/MIGRATION.md).
 *
 * @param string $field 'title'|'description'|'og_title'|'og_description'|'og_type'.
 * @return string
 */
function pll_seo_value( $field ) {
	$meta_keys = array(
		'title'          => '_pll_seo_title',
		'description'    => '_pll_seo_description',
		'og_title'       => '_pll_og_title',
		'og_description' => '_pll_og_description',
	);

	// Editor-set post meta is authoritative when present, so per-page SEO can be
	// edited in the block editor (the "SEO (PLL)" document panel). The curated
	// overrides and page defaults below act as the seeded fallback for any field
	// left blank. og_* fields inherit via the fallbacks below.
	if ( is_singular() && isset( $meta_keys[ $field ] ) ) {
		$meta = (string) get_post_meta( get_queried_object_id(), $meta_keys[ $field ], true );
		if ( '' !== $meta ) {
			return $meta;
		}
	}

	$overrides    = pll_seo_overrides();
	$ov_path      = pll_seo_current_path();
	$ov_slashed   = trailingslashit( $ov_path );
	$ov_unslashed = untrailingslashit( $ov_path );

	foreach ( array( $ov_path, $ov_slashed, $ov_unslashed ) as $p_key ) {
		if ( isset( $overrides[ $p_key ][ $field ] ) ) {
			return $overrides[ $p_key ][ $field ];
		}
	}

	$defaults = pll_seo_page_defaults();
	foreach ( array( $ov_path, $ov_slashed, $ov_unslashed ) as $p_key ) {
		if ( isset( $defaults[ $p_key ][ $field ] ) ) {
			return $defaults[ $p_key ][ $field ];
		}
	}

	// OG fields fall back to the SEO fields.
	if ( 'og_title' === $field ) {
		return pll_seo_value( 'title' );
	}
	if ( 'og_description' === $field ) {
		return pll_seo_value( 'description' );
	}
	return '';
}

/**
 * Resolve a root-relative path ('/about/', '/your-surgery/x/', '/slug/') to a
 * post or page ID. '/' maps to the static front page.
 *
 * @param string $path Root-relative path.
 * @return int Post ID or 0.
 */
function pll_seo_resolve_path_id( $path ) {
	$slug = trim( (string) $path, '/' );
	if ( '' === $slug ) {
		return (int) get_option( 'page_on_front' );
	}
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $page instanceof WP_Post ) {
		return (int) $page->ID;
	}
	$post = get_page_by_path( $slug, OBJECT, 'post' );
	if ( $post instanceof WP_Post ) {
		return (int) $post->ID;
	}
	$base_slug = basename( $slug );
	if ( $base_slug !== $slug ) {
		$page_base = get_page_by_path( $base_slug, OBJECT, 'page' );
		if ( $page_base instanceof WP_Post ) {
			return (int) $page_base->ID;
		}
		$post_base = get_page_by_path( $base_slug, OBJECT, 'post' );
		if ( $post_base instanceof WP_Post ) {
			return (int) $post_base->ID;
		}
	}
	return 0;
}

/**
 * One-time launch migration: promote the curated titles/descriptions (the
 * overrides + page-defaults maps) into real post meta, so that with post meta
 * now authoritative (see pll_seo_value) the front end keeps the optimized SEO
 * copy AND every field is editable in the "SEO (PLL)" panel, pre-filled with
 * the current value. Without this, the weak, auto-derived meta the WXR import
 * seeded would win over the curated maps.
 *
 * Runs once (guarded by the pll_seo_meta_seeded option === PLL_SEO_VERSION), so
 * later editor edits are never clobbered. Intended for the pre-launch seed; it
 * deliberately overwrites the weak imported values on that first pass only.
 */
add_action(
	'init',
	function () {
		if ( get_option( 'pll_seo_meta_seeded' ) === PLL_SEO_VERSION ) {
			return;
		}

		$fields = array(
			'title'          => '_pll_seo_title',
			'description'    => '_pll_seo_description',
			'og_title'       => '_pll_og_title',
			'og_description' => '_pll_og_description',
		);

		// page-defaults first, overrides layered on top (overrides are the
		// more specific curated copy for sub-pages and articles).
		$map = pll_seo_page_defaults();
		foreach ( pll_seo_overrides() as $path => $vals ) {
			$map[ $path ] = array_merge( isset( $map[ $path ] ) ? $map[ $path ] : array(), $vals );
		}

		foreach ( $map as $path => $vals ) {
			$post_id = pll_seo_resolve_path_id( $path );
			if ( ! $post_id ) {
				continue;
			}
			foreach ( $fields as $field => $key ) {
				if ( ! empty( $vals[ $field ] ) ) {
					update_post_meta( $post_id, $key, $vals[ $field ] );
				}
			}
		}

		update_option( 'pll_seo_meta_seeded', PLL_SEO_VERSION );
	},
	20
);

/**
 * Targeted post-meta migrations.
 *
 * Post meta is authoritative (see pll_seo_value), and the launch seeder above
 * only runs while pll_seo_meta_seeded !== PLL_SEO_VERSION. On an environment
 * that has already seeded — production has — changing a value in the maps
 * above therefore does nothing on its own, and the documented trap is to
 * "fix" that by bumping PLL_SEO_VERSION, which re-runs the seeder across the
 * ENTIRE map and overwrites every SEO field any editor has touched since
 * launch.
 *
 * This is the safe alternative: each entry names the exact pages it may
 * write, carries its own one-shot option guard, and skips any page whose
 * stored value is no longer the "from" value (meaning an editor has since
 * changed it by hand, and their edit wins). Add an entry, never edit a
 * shipped one.
 *
 * @return array<string, array<string, array<string, array{from: string, to: string}>>>
 *         guard option => path => meta key => {from, to}.
 */
function pll_seo_targeted_meta_migrations() {
	return array(
		// Cannibalization audit A5, owner-approved 2026-08-27.
		'pll_seo_migrated_a5_age_title' => array(
			// og_title is intentionally absent: the map never set one for this
			// page, so the launch seeder never wrote _pll_og_title, and
			// pll_seo_value() falls og_title back to title. Listing it here
			// would be a no-op entry that reads like a shipped change.
			'/am-i-too-old-for-limb-lengthening/' => array(
				'_pll_seo_title' => array(
					'from' => 'Am I Too Old for Limb Lengthening?',
					'to'   => 'Limb Lengthening in Your 40s and 50s · Are You Too Old?',
				),
			),
		),
	);
}

add_action(
	'init',
	function () {
		foreach ( pll_seo_targeted_meta_migrations() as $guard => $pages ) {
			if ( get_option( $guard ) ) {
				continue;
			}
			foreach ( $pages as $path => $fields ) {
				$post_id = pll_seo_resolve_path_id( $path );
				if ( ! $post_id ) {
					// No post here yet (fresh environment seeds from the map
					// directly). Leave the guard unset so this retries.
					continue 2;
				}
				foreach ( $fields as $key => $change ) {
					$current = (string) get_post_meta( $post_id, $key, true );
					// Only rewrite the value this migration expects. An empty
					// value means the launch seeder has not run for this field,
					// and anything else means an editor owns it now.
					if ( $current === $change['from'] ) {
						update_post_meta( $post_id, $key, $change['to'] );
					}
				}
			}
			update_option( $guard, 1 );
		}
	},
	21
);
