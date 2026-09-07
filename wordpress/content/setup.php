<?php
/**
 * PLL site configuration + content seeding. IDEMPOTENT — safe to re-run.
 *
 * What it does:
 *   1. Core options (title, tagline, timezone, permalinks, comments off)
 *   2. Sideloads bundled media (content/media/*) into the media library
 *   3. Composes the marketing pages from the theme's registered block
 *      patterns (single source of truth: wp-content/themes/pll-editorial/patterns/)
 *   4. Reading settings: static front page "Home", posts page "Blog"
 *   5. Flushes rewrite rules
 *
 * How to run:
 *   - Local dev: executed automatically by the Playground blueprint (runPHP)
 *   - HIPAA Vault: `wp eval-file wp-content/../setup.php` (or place beside
 *     wp-config and run `wp eval-file setup.php`) AFTER activating the
 *     pll-editorial theme and importing content/pll-content.wxr
 *
 * Define PLL_SEED_FORCE=true to overwrite existing page content from the
 * current pattern files (used when iterating on patterns in dev).
 *
 * @package pll
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'pll_media_url' ) ) {
	// Theme not active — seeding depends on the pll-editorial theme.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::error( 'Activate the pll-editorial theme before running setup.php.' );
	}
	return;
}

/**
 * 1) Core options.
 */
update_option( 'blogname', 'Premier Limb Lengthening' );
update_option( 'blogdescription', 'Cosmetic limb lengthening, founded by Dr. Hrayr Basmajian' );
update_option( 'timezone_string', 'America/Los_Angeles' );
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'default_comment_status', 'closed' );
update_option( 'default_ping_status', 'closed' );

/**
 * 1b) Remove WordPress default sample content — it would leak into the blog
 * index, category counts, and the CollectionPage JSON-LD.
 */
function pll_remove_default_content() {
	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello ) {
		wp_delete_post( $hello->ID, true );
	}
	$sample = get_page_by_path( 'sample-page', OBJECT, 'page' );
	if ( $sample ) {
		wp_delete_post( $sample->ID, true );
	}
}
pll_remove_default_content();

/**
 * 2) Media sideloading.
 */
function pll_seed_media() {
	$dir = defined( 'PLL_SEED_MEDIA_DIR' ) ? PLL_SEED_MEDIA_DIR : __DIR__ . '/media';
	if ( ! is_dir( $dir ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	foreach ( glob( $dir . '/*' ) as $file ) {
		if ( ! is_file( $file ) ) {
			continue;
		}
		$name = basename( $file );
		if ( pll_media_url( $name ) ) {
			continue; // Already imported.
		}

		$tmp = wp_tempnam( $name );
		if ( ! $tmp || ! copy( $file, $tmp ) ) {
			continue;
		}

		$attachment_id = media_handle_sideload(
			array(
				'name'     => $name,
				'tmp_name' => $tmp,
			),
			0
		);

		// pll_media_url caches negative lookups for the request — clear it.
		wp_cache_delete( 'pll_media_' . md5( $name ), 'pll-editorial' );

		if ( is_wp_error( $attachment_id ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'pll seed: media sideload failed for ' . $name . ': ' . $attachment_id->get_error_message() );
		}
	}
}
pll_seed_media();

/**
 * 2b) Verbatim-path uploads + featured images.
 *
 * The content/media-uploads/ folder holds assets whose URL paths must survive the
 * migration byte-for-byte: blog hero images at their original
 * /wp-content/uploads/YYYY/MM/<file> paths (zero-redirect for anything
 * indexed in Google Images) and the your-surgery diagrams under a stable
 * pll/ namespace referenced by the generated post content.
 */
function pll_seed_uploads() {
	$src = defined( 'PLL_SEED_MEDIA_DIR' ) ? dirname( PLL_SEED_MEDIA_DIR ) . '/media-uploads' : __DIR__ . '/media-uploads';
	if ( ! is_dir( $src ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';

	$uploads  = wp_get_upload_dir();
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $src, FilesystemIterator::SKIP_DOTS ) );

	foreach ( $iterator as $file ) {
		if ( ! $file->isFile() ) {
			continue;
		}
		$rel    = str_replace( '\\', '/', ltrim( substr( $file->getPathname(), strlen( $src ) ), '/\\' ) );
		$target = trailingslashit( $uploads['basedir'] ) . $rel;

		if ( ! file_exists( $target ) ) {
			wp_mkdir_p( dirname( $target ) );
			copy( $file->getPathname(), $target );
		}

		if ( pll_attachment_id_by_relpath( $rel ) ) {
			continue;
		}

		$filetype      = wp_check_filetype( basename( $rel ) );
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => sanitize_text_field( pathinfo( $rel, PATHINFO_FILENAME ) ),
				'post_status'    => 'inherit',
				'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'application/octet-stream',
			),
			$target
		);
		if ( ! is_wp_error( $attachment_id ) && $attachment_id ) {
			update_post_meta( $attachment_id, '_wp_attached_file', $rel );
			$meta = wp_generate_attachment_metadata( $attachment_id, $target );
			if ( $meta ) {
				wp_update_attachment_metadata( $attachment_id, $meta );
			}
		}
	}
}

/**
 * Find an attachment by its uploads-relative path.
 *
 * @param string $rel e.g. '2025/07/Am-I-Too-Old.jpeg' or 'pll/your-surgery/x.webp'.
 * @return int Attachment ID or 0.
 */
function pll_attachment_id_by_relpath( $rel ) {
	global $wpdb;
	$id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
			$rel
		)
	);
	return $id ? (int) $id : 0;
}

/**
 * Wire featured images + alt text from media-manifest.json (slug → file/alt).
 */
function pll_seed_featured_images() {
	$manifest_path = ( defined( 'PLL_SEED_MEDIA_DIR' ) ? dirname( PLL_SEED_MEDIA_DIR ) : __DIR__ ) . '/media-manifest.json';
	if ( ! file_exists( $manifest_path ) ) {
		return;
	}
	// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- bundled local seed file, not a remote resource.
	$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
	if ( empty( $manifest['featured'] ) ) {
		return;
	}

	foreach ( $manifest['featured'] as $slug => $info ) {
		$post = get_page_by_path( $slug, OBJECT, 'post' );
		if ( ! $post ) {
			continue;
		}
		$attachment_id = pll_attachment_id_by_relpath( $info['file'] );
		if ( ! $attachment_id ) {
			continue;
		}
		set_post_thumbnail( $post->ID, $attachment_id );
		if ( ! empty( $info['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $info['alt'] ) );
		}
	}
}

pll_seed_uploads();
pll_seed_featured_images();

/**
 * 3) Marketing pages composed from theme patterns.
 */

/**
 * Compose post_content from an ordered list of registered pattern slugs.
 *
 * @param string[] $pattern_slugs Pattern slugs.
 * @return string Block markup.
 */
function pll_compose_patterns( $pattern_slugs ) {
	$registry = WP_Block_Patterns_Registry::get_instance();
	$content  = '';
	foreach ( $pattern_slugs as $slug ) {
		$pattern = $registry->get_registered( $slug );
		if ( $pattern && ! empty( $pattern['content'] ) ) {
			$content .= "\n" . trim( $pattern['content'] ) . "\n";
		} else {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'pll seed: pattern not registered: ' . $slug );
		}
	}
	return trim( $content );
}

/**
 * Create or (when PLL_SEED_FORCE) refresh a page.
 *
 * @param string $slug     Page slug.
 * @param string $title    Page title.
 * @param string $content  Block markup.
 * @param array  $extra    Extra wp_insert_post fields (post_parent, meta_input…).
 * @return int Page ID (0 on failure).
 */
function pll_seed_page( $slug, $title, $content, $extra = array() ) {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	$force    = defined( 'PLL_SEED_FORCE' ) && PLL_SEED_FORCE;

	if ( $existing && ! $force ) {
		return (int) $existing->ID;
	}

	$postarr = array_merge(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $title,
			'post_content' => $content,
		),
		$extra
	);

	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
	}

	$page_id = wp_insert_post( wp_slash( $postarr ), true );
	if ( is_wp_error( $page_id ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'pll seed: page failed for ' . $slug . ': ' . $page_id->get_error_message() );
		return 0;
	}
	return (int) $page_id;
}

$pll_home_id = pll_seed_page(
	'home',
	'Home',
	// Order per homepage handoff v2: the surgeon is the product — Bio sits
	// directly after the hero continuation; Candidate before Concierge.
	pll_compose_patterns(
		array(
			'pll/home-hero',
			'pll/home-article',
			'pll/home-bio',
			'pll/home-pillars',
			'pll/home-process',
			'pll/home-candidate',
			'pll/home-concierge',
			'pll/home-results',
			'pll/home-pricing',
			'pll/home-testimonials',
			'pll/home-faq',
			'pll/final-cta',
		)
	)
);

// Posts page — content is ignored by WP (the home.html template renders the loop).
// A short editor-only note keeps the block editor from opening the empty-page
// "Choose a pattern" modal and tells editors where the real layout lives.
$pll_blog_id = pll_seed_page(
	'blog',
	'Blog',
	"<!-- wp:paragraph -->\n<p>This page lists the latest articles automatically. Nothing written here appears on the site. To change the listing layout, edit the Blog Home template under Appearance \xE2\x86\x92 Editor \xE2\x86\x92 Templates.</p>\n<!-- /wp:paragraph -->"
);

// Marketing pages — composed from theme patterns (single source of truth).
pll_seed_page(
	'about',
	'About',
	pll_compose_patterns( array( 'pll/about-page', 'pll/final-cta' ) )
);

pll_seed_page(
	'dr-basmajian',
	'Dr. Basmajian',
	pll_compose_patterns(
		array(
			'pll/profile-hero',
			'pll/profile-intro',
			'pll/profile-credentials',
			'pll/profile-training',
			'pll/profile-memberships',
			'pll/profile-research',
			'pll/final-cta',
		)
	)
);

// No FinalCta on consult (the page goes straight to the footer in Next).
pll_seed_page( 'consult', 'Contact', pll_compose_patterns( array( 'pll/consult-page' ) ) );

// Booking is a separate flow from contact (owner request 2026-07): the
// embedded GHL calendar lives here, and the site-wide "Schedule Consultation"
// button (pll_site_info cta_url) points at this page. No FinalCta either —
// the page IS the conversion.
pll_seed_page( 'book-a-consultation', 'Book a Consultation', pll_compose_patterns( array( 'pll/booking-page' ) ) );

// Section order per app/limb-lengthening-pricing-options/page.tsx:
// hero → plans → add-ons → included/excluded → financing. No FinalCta.
pll_seed_page(
	'limb-lengthening-pricing-options',
	'Pricing',
	pll_compose_patterns(
		array(
			'pll/pricing-hero',
			'pll/pricing-plans',
			'pll/pricing-addons',
			'pll/pricing-included',
			'pll/pricing-financing',
		)
	)
);

// SEO pillar pages (PLL_SEO_Audit_Report.docx). Pattern-composed like the
// other marketing pages. They own the consumer head terms "height surgery"
// and "leg lengthening surgery" and are reached through the internal-link
// map (homepage, pricing, your-surgery, how-much-taller) — intentionally NOT
// added to the primary nav to avoid diluting it and to keep the hub-and-spoke
// relationship with /your-surgery/ free of cannibalization.
pll_seed_page(
	'height-surgery',
	'Height Surgery',
	pll_compose_patterns( array( 'pll/height-surgery', 'pll/final-cta' ) )
);
pll_seed_page(
	'leg-lengthening-surgery',
	'Leg Lengthening Surgery',
	pll_compose_patterns( array( 'pll/leg-lengthening-surgery', 'pll/final-cta' ) )
);

// Evaluate Your Surgeon — the interactive surgeon-evaluation guide + scoring
// tool. Composed from a single full-page pattern (its own final CTA lives in
// the Why-Premier section, so no shared pll/final-cta is appended). Reached
// from the primary nav + footer Resources.
pll_seed_page(
	'evaluate-your-surgeon',
	'Evaluate Your Surgeon',
	pll_compose_patterns( array( 'pll/evaluate-page' ) )
);

// Local authoring demonstration only. It resolves an ordinary (unsynced)
// pattern into a normal page, so duplicating or editing either page never
// changes the other. It is deliberately opt-in: normal production and HIPAA
// setup must never publish a duplicate of an indexed source page.
function pll_seed_seo_pattern_demo() {
	$pll_seo_pattern_demo_content = "<!-- Source: How Much Taller Can I Get With Limb Lengthening? -->\n"
		. pll_compose_patterns( array( 'pll/seo-landing-page' ) );
	return pll_seed_page(
		'seo-pattern-demo',
		'SEO Pattern Demo: How Much Taller Can I Get With Limb Lengthening?',
		$pll_seo_pattern_demo_content,
		array(
			'meta_input' => array(
				'_pll_seo_title'       => 'How Much Taller Can I Get With Limb Lengthening?',
				'_pll_seo_description' => 'How much height limb lengthening can safely add, why the range is limited, and how femur and tibia procedures differ.',
				'_pll_canonical_url'   => '',
				'_pll_robots_noindex'  => 1,
			),
		)
	);
}

function pll_seed_seo_image_led_demo() {
	$pll_seo_image_led_demo_content = "<!-- Source: How Much Taller Can I Get With Limb Lengthening? -->\n"
		. pll_compose_patterns( array( 'pll/seo-image-led-landing-page' ) );
	return pll_seed_page(
		'seo-image-led-demo',
		'SEO Image-Led Demo: How Much Taller Can I Get With Limb Lengthening?',
		$pll_seo_image_led_demo_content,
		array(
			'meta_input' => array(
				'_pll_seo_title'       => 'How Much Taller Can You Get? Up to 6 Inches',
				'_pll_seo_description' => 'Most patients gain 2 to 6 inches depending on whether the femur, the tibia, or both are lengthened. See the realistic range for each segment.',
				'_pll_canonical_url'   => '',
				'_pll_robots_noindex'  => 1,
			),
		)
	);
}

if ( ( defined( 'PLL_SEO_PAGES_SEED_DEMO' ) && PLL_SEO_PAGES_SEED_DEMO === true ) || wp_get_environment_type() === 'local' ) {
	pll_seed_seo_pattern_demo();
	pll_seed_seo_image_led_demo();
}

/**
 * 3a-bis) Marketing sprint pages — SEEDED AS DRAFTS, NEVER PUBLISHED.
 *
 * Four build-ready pages from the J Cubed marketing sprint (Notion "PLL
 * Marketing Sprint — 12-Page Content Build"). The marketing team still owes
 * several rounds of edits and the pages are gated on Dr. B, Legal, and Jaime
 * sign-offs, so every one of them lands unpublished.
 *
 * Three independent guards keep them off the public site:
 *   1. post_status = draft, so they are not publicly routable and core leaves
 *      them out of the sitemap.
 *   2. _pll_robots_noindex = 1, so even an accidental publish stays noindex.
 *   3. Visible "Fill required" asides in the body, so nobody can publish one
 *      without first seeing what a human still owes.
 *
 * Publishing is a deliberate act: flip the status in the editor, resolve and
 * delete every fill note, then clear _pll_robots_noindex. Do not add these to
 * nav or the sitemap until that happens.
 *
 * Note the seeding contract: pll_seed_page() only writes when the page does
 * not already exist (absent PLL_SEED_FORCE), so a re-run never clobbers the
 * marketing team's edits. Editing the pattern file after the first seed will
 * NOT change a seeded page.
 *
 * @return void
 */
function pll_marketing_sprint_body( $slug ) {
	$path = __DIR__ . '/pages/' . $slug . '.html';
	if ( ! is_readable( $path ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'pll seed: marketing page body missing: ' . $path );
		return '';
	}

	$info = function_exists( 'pll_site_info' ) ? pll_site_info() : array();

	// The pattern's consult section hardcodes a phone. These bodies carry a
	// token instead so the number always comes from theme settings, which is
	// how the shared Final CTA does it.
	return strtr(
		(string) file_get_contents( $path ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		array(
			'{{PHONE}}'      => (string) ( $info['phone'] ?? '' ),
			'{{PHONE_HREF}}' => (string) ( $info['phone_href'] ?? '' ),
		)
	);
}

function pll_seed_marketing_sprint_drafts() {
	$draft_meta = array(
		'_pll_canonical_url'  => '',
		'_pll_robots_noindex' => 1,
	);

	pll_seed_page(
		'limb-lengthening-california',
		'Limb Lengthening Surgery in California',
		pll_marketing_sprint_body( 'limb-lengthening-california' ),
		array(
			'post_status' => 'draft',
			'meta_input'  => array_merge(
				$draft_meta,
				array(
					'_pll_seo_title'       => 'Limb Lengthening Surgery in California | Dr. Basmajian',
					'_pll_seo_description' => 'Limb lengthening surgery in California with board-certified Dr. Basmajian in Upland, near Los Angeles. Serving LA, Orange County, San Diego and beyond.',
				)
			),
		)
	);

	pll_seed_page(
		'international-patients',
		'International Patients',
		pll_marketing_sprint_body( 'international-patients' ),
		array(
			'post_status' => 'draft',
			'meta_input'  => array_merge(
				$draft_meta,
				array(
					'_pll_seo_title'       => 'Limb Lengthening for International Patients in the USA',
					'_pll_seo_description' => 'Limb lengthening for international patients traveling to the United States. How remote consultation, surgery, the in-country stay, and telehealth follow-up work.',
				)
			),
		)
	);

	// The only one of these built from the IMAGE-LED pattern rather than
	// pll/seo-landing-page: a hyperlocal map-pack anchor earns a photo-forward
	// treatment, and it keeps a second worked example of that pattern alive.
	pll_seed_page(
		'limb-lengthening-upland',
		'Limb Lengthening in Upland, CA',
		pll_marketing_sprint_body( 'limb-lengthening-upland' ),
		array(
			'post_status' => 'draft',
			'meta_input'  => array_merge(
				$draft_meta,
				array(
					'_pll_seo_title'       => 'Limb Lengthening in Upland, CA · Premier Limb Lengthening',
					'_pll_seo_description' => 'Limb lengthening surgery in Upland, CA. Premier Limb Lengthening performs the internal PRECICE nail procedure in the Inland Empire, near Ontario International Airport.',
				)
			),
		)
	);

	// Regional sub-hub of the California geo cluster. UP to the state hub, DOWN
	// to the city spokes (Upland now, Los Angeles and Orange County when built).
	pll_seed_page(
		'limb-lengthening-southern-california',
		'Limb Lengthening in Southern California',
		pll_marketing_sprint_body( 'limb-lengthening-southern-california' ),
		array(
			'post_status' => 'draft',
			'meta_input'  => array_merge(
				$draft_meta,
				array(
					'_pll_seo_title'       => 'Limb Lengthening in Southern California | Premier Limb Lengthening',
					'_pll_seo_description' => 'Southern California limb lengthening based in Upland, serving Los Angeles, Orange County, the Inland Empire, and San Diego. Board-certified orthopedic surgeon.',
				)
			),
		)
	);

	// "Near me" intent page. No build-ready Notion draft exists for it, and the
	// Content Registry currently assigns the near-me terms to the California hub
	// and the Upland page, so its top fill note asks Jaime to rule on
	// cannibalization before it can publish. Draft + noindex like the others.
	pll_seed_page(
		'limb-lengthening-near-me',
		'Limb Lengthening Near Me',
		pll_marketing_sprint_body( 'limb-lengthening-near-me' ),
		array(
			'post_status' => 'draft',
			'meta_input'  => array_merge(
				$draft_meta,
				array(
					'_pll_seo_title'       => 'Limb Lengthening Near Me · Premier Limb Lengthening, Upland CA',
					'_pll_seo_description' => 'Searching for limb lengthening near me or height surgery near me? Premier Limb Lengthening is based in Upland, CA, serving Los Angeles, Orange County, the Inland Empire, and San Diego.',
				)
			),
		)
	);

	pll_seed_page(
		'traveling-for-limb-lengthening',
		'Traveling for Limb Lengthening',
		pll_marketing_sprint_body( 'traveling-for-limb-lengthening' ),
		array(
			'post_status' => 'draft',
			'meta_input'  => array_merge(
				$draft_meta,
				array(
					'_pll_seo_title'       => 'Traveling for Limb Lengthening: The Trip, the Stay, and Getting Home',
					'_pll_seo_description' => 'Traveling for limb lengthening surgery, step by step. Fly into Ontario airport (ONT), stay near the Upland, California clinic, then head home for remote follow-up.',
				)
			),
		)
	);

	// Lives under the Your Surgery hub, so it needs that page as its parent to
	// resolve at /your-surgery/daily-life-during-limb-lengthening-recovery/.
	// The hub arrives via the WXR import, so resolve it rather than assume an ID.
	$your_surgery = get_page_by_path( 'your-surgery', OBJECT, 'page' );
	$daily_extra  = array(
		'post_status' => 'draft',
		'meta_input'  => array_merge(
			$draft_meta,
			array(
				'_pll_seo_title'       => 'Daily Life During Limb Lengthening Recovery: Stairs, Bathrooms, and Help at Home',
				'_pll_seo_description' => 'The practical side of limb lengthening recovery: getting up and down stairs, on and off the toilet, showering, dressing, what to buy before surgery, and whether you need help at home.',
			)
		),
	);
	if ( $your_surgery instanceof WP_Post ) {
		$daily_extra['post_parent'] = $your_surgery->ID;
	} else {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'pll seed: your-surgery parent missing, daily-life draft seeded at the site root' );
	}

	// A child page's path includes its parent, so pll_seed_page()'s bare-slug
	// get_page_by_path() lookup can never find this one and would insert a
	// fresh duplicate on every re-run. Resolve it on the full path here and
	// hand the ID over, so a re-seed updates in place like every other page.
	$daily_slug     = 'daily-life-during-limb-lengthening-recovery';
	$daily_path     = $your_surgery instanceof WP_Post ? 'your-surgery/' . $daily_slug : $daily_slug;
	$existing_daily = get_page_by_path( $daily_path, OBJECT, 'page' );

	if ( $existing_daily instanceof WP_Post ) {
		if ( ! ( defined( 'PLL_SEED_FORCE' ) && PLL_SEED_FORCE ) ) {
			return;
		}
		$daily_extra['ID'] = $existing_daily->ID;
	}

	pll_seed_page(
		$daily_slug,
		'Daily Life During Limb Lengthening Recovery',
		pll_marketing_sprint_body( 'daily-life-during-limb-lengthening-recovery' ),
		$daily_extra
	);
}
pll_seed_marketing_sprint_drafts();

/**
 * 3b) Authors — historical post ownership only (archives RETIRED).
 *
 * The WXR importer creates users keyed by login; the public nicename must
 * differ from the login (blunts login enumeration) and match the legacy
 * archive slugs. ccatandella authored 4 posts (per the scraped author
 * archive); edusenbury authored the rest. cjpeters had zero posts live and
 * is intentionally absent (the legacy archive already 404'd).
 *
 * RETIRED 2026-07-28 (owner decision: those contributors no longer exist,
 * only Dr. Basmajian): the /author/<nicename>/ archives 301 to /blog/
 * (pll-seo redirects.php), the users sitemap is removed (pll-seo
 * sitemap.php), and every public byline renders "Dr. Hrayr Basmajian"
 * (theme inc/cards.php). The accounts are still seeded so WXR re-imports
 * keep mapping post ownership deterministically — they just never surface.
 */
function pll_seed_authors() {
	$ccatandella_posts = array(
		'the-science-behind-bone-regeneration-and-limb-lengthening',
		'bone-health-and-nutrition-before-and-after-limb-lengthening',
		'limb-lengthening-what-you-gain-what-you-risk',
		'rewriting-the-body-norm-stigmas-around-limb-lengthening',
	);

	$authors = array(
		array(
			'login'    => 'pll-ccatandella',
			'nicename' => 'ccatandella',
			'display'  => 'C. Catandella',
			'email'    => 'ccatandella@premierlimblengthening.com',
		),
		array(
			'login'    => 'pll-edusenbury',
			'nicename' => 'edusenbury',
			'display'  => 'E. Dusenbury',
			'email'    => 'edusenbury@premierlimblengthening.com',
		),
	);

	$ids = array();
	foreach ( $authors as $author ) {
		$user = get_user_by( 'login', $author['login'] );
		if ( ! $user ) {
			$user_id = wp_insert_user(
				array(
					'user_login'   => $author['login'],
					'user_pass'    => wp_generate_password( 32 ),
					'user_email'   => $author['email'],
					'display_name' => $author['display'],
					'role'         => 'author',
				)
			);
			if ( is_wp_error( $user_id ) ) {
				continue;
			}
		} else {
			$user_id = $user->ID;
		}
		wp_update_user(
			array(
				'ID'            => $user_id,
				'user_nicename' => $author['nicename'],
				'display_name'  => $author['display'],
			)
		);
		$ids[ $author['login'] ] = (int) $user_id;
	}

	if ( empty( $ids ) ) {
		return;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
		)
	);
	foreach ( $posts as $post ) {
		$target = in_array( $post->post_name, $ccatandella_posts, true )
			? ( $ids['pll-ccatandella'] ?? 0 )
			: ( $ids['pll-edusenbury'] ?? 0 );
		if ( $target && (int) $post->post_author !== $target ) {
			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_author' => $target,
				)
			);
		}
	}
}
pll_seed_authors();

/**
 * 3b) Undo importer URL rewriting inside the verbatim legal copy.
 *
 * WordPress Playground's WXR importer rewrites the source site URL to the
 * local instance URL during import — including bare-domain TEXT such as
 * "…use of the website at premierlimblengthening.com…" in the Terms of
 * Service, which became "127.0.0.1:9400/". The legal documents are verbatim
 * counsel-bound copy; restore the literal domain. Guarded so a production
 * host whose domain already IS premierlimblengthening.com never runs the
 * replacement (str_replace of "domain/" would strip slashes from real links).
 */
function pll_restore_legal_domain_text() {
	$host  = wp_parse_url( home_url(), PHP_URL_HOST );
	$port  = wp_parse_url( home_url(), PHP_URL_PORT );
	$local = $port ? $host . ':' . $port : $host;
	if ( 'premierlimblengthening.com' === $local ) {
		return;
	}
	foreach ( array( 'privacy', 'terms', 'accessibility' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( ! $page instanceof WP_Post ) {
			continue;
		}
		$content = str_replace(
			array( $local . '/', $local ),
			'premierlimblengthening.com',
			$page->post_content
		);
		if ( $content !== $page->post_content ) {
			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_content' => $content,
				)
			);
		}
	}
}
pll_restore_legal_domain_text();

/**
 * 3d) Clinical content additions (schema_updates package).
 *
 * Injects the visible "Patients Also Ask" accordion and the "Medically
 * reviewed by" byline into the relevant pages AFTER the WXR import and the
 * pattern-composed pages exist. Idempotent (guarded by content markers), so it
 * survives re-import and PLL_SEED_FORCE without duplicating.
 *
 * The PAA questions and answers are pulled from the pll-seo plugin's canonical
 * data (pll_seo_paa), the SAME source the FAQPage JSON-LD is built from, so the
 * visible copy and the structured data are generated from one source and always
 * match verbatim (SEO audit Critical 3). Content edits require re-seeding with
 * PLL_SEED_FORCE (the marker guard intentionally skips already-injected pages).
 */

/**
 * The medical-review sign-off map lives in the pll-seo plugin
 * (pll_seo_review_dates(), includes/schema.php), NOT here. It is the single
 * source for both outputs: the byline seeded below, and the MedicalWebPage +
 * reviewedBy JSON-LD emitted on the front end. This file runs at seed time
 * only, so a map kept here could never feed the schema. Add a signed-off path
 * to the plugin function, then re-seed.
 */

/**
 * "Medically reviewed by" byline block markup for a given review month.
 *
 * @param string $month_year e.g. "July 2026".
 * @return string Block markup.
 */
function pll_render_review_byline( $month_year ) {
	return '<!-- wp:paragraph {"className":"pll-medical-review mt-2 mb-8 font-mono uppercase tracking-[0.14em] text-[11px] text-muted"} -->' . "\n"
		. '<p class="pll-medical-review mt-2 mb-8 font-mono uppercase tracking-[0.14em] text-[11px] text-muted">Medically reviewed by <a href="/dr-basmajian/">Dr. Hrayr Basmajian</a>, dual fellowship-trained orthopedic trauma surgeon. Last reviewed: ' . esc_html( $month_year ) . '.</p>' . "\n"
		. '<!-- /wp:paragraph -->';
}

/**
 * Build the visible "Patients Also Ask" section (pll/faq accordion) from a
 * question/answer set. Only HTML-structural characters are encoded in the
 * answer so the rendered text matches the schema string verbatim.
 *
 * @param array<int, array{q: string, a: string}> $qas Question/answer pairs.
 * @return string Block markup ('' when empty).
 */
function pll_render_paa_section( $qas ) {
	if ( empty( $qas ) ) {
		return '';
	}
	$items = '';
	foreach ( $qas as $qa ) {
		$attr   = wp_json_encode( array( 'question' => $qa['q'] ) );
		$answer = str_replace( array( '&', '<', '>' ), array( '&amp;', '&lt;', '&gt;' ), $qa['a'] );
		$items .= '<!-- wp:pll/faq-item ' . $attr . ' -->' . "\n"
			. '<!-- wp:paragraph -->' . "\n" . '<p>' . $answer . '</p>' . "\n" . '<!-- /wp:paragraph -->' . "\n"
			. '<!-- /wp:pll/faq-item -->' . "\n";
	}
	return '<!-- wp:group {"tagName":"section","layout":{"type":"default"},"anchor":"patients-also-ask","className":"bg-paper-off py-16 lg:py-24 border-t border-rule"} -->' . "\n"
		. '<section class="wp-block-group bg-paper-off py-16 lg:py-24 border-t border-rule" id="patients-also-ask">' . "\n"
		. '<!-- wp:group {"layout":{"type":"default"},"className":"mx-auto max-w-wrap px-6 lg:px-12"} -->' . "\n"
		. '<div class="wp-block-group mx-auto max-w-wrap px-6 lg:px-12">' . "\n"
		. '<!-- wp:group {"tagName":"header","layout":{"type":"default"},"className":"pb-6 mb-8 border-b border-ink"} -->' . "\n"
		. '<header class="wp-block-group pb-6 mb-8 border-b border-ink">' . "\n"
		. '<!-- wp:paragraph {"className":"eyebrow mb-3"} -->' . "\n" . '<p class="eyebrow mb-3">Common Questions</p>' . "\n" . '<!-- /wp:paragraph -->' . "\n"
		. '<!-- wp:heading {"level":2,"className":"mt-2 font-serif font-normal tracking-[-0.02em] text-ink leading-[1.0] text-[clamp(30px,4.4vw,56px)]"} -->' . "\n"
		. '<h2 class="wp-block-heading mt-2 font-serif font-normal tracking-[-0.02em] text-ink leading-[1.0] text-[clamp(30px,4.4vw,56px)]">Patients also <em class="italic text-spine">ask.</em></h2>' . "\n"
		. '<!-- /wp:heading -->' . "\n"
		. '</header>' . "\n" . '<!-- /wp:group -->' . "\n"
		. '<!-- wp:pll/faq -->' . "\n" . $items . '<!-- /wp:pll/faq -->' . "\n"
		. '</div>' . "\n" . '<!-- /wp:group -->' . "\n"
		. '</section>' . "\n" . '<!-- /wp:group -->';
}

/**
 * Inject PAA blocks and (when signed off) the review byline into the pages
 * named in pll_seo_paa_all() / pll_seo_review_dates().
 */
function pll_seed_clinical_additions() {
	// The PAA copy, the FAQPage schema, and the review-date map all come from
	// pll-seo; if the plugin is inactive we inject nothing (visible copy and
	// schema stay in lockstep).
	if ( ! function_exists( 'pll_seo_paa_all' ) || ! function_exists( 'pll_seo_resolve_path_id' ) || ! function_exists( 'pll_seo_review_dates' ) ) {
		return;
	}

	$paa_all      = pll_seo_paa_all();
	$review_dates = pll_seo_review_dates();
	$paths        = array_unique( array_merge( array_keys( $paa_all ), array_keys( $review_dates ) ) );

	foreach ( $paths as $path ) {
		$post_id = pll_seo_resolve_path_id( $path );
		if ( ! $post_id ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'pll seed: clinical additions could not resolve ' . $path );
			continue;
		}
		$post    = get_post( $post_id );
		$content = $post->post_content;
		$changed = false;

		// (a) Medically-reviewed byline — only for signed-off paths, once.
		if ( isset( $review_dates[ $path ] ) && false === strpos( $content, 'pll-medical-review' ) ) {
			$byline = pll_render_review_byline( $review_dates[ $path ] );
			// Directly under the H1. Pillars carry the H1 in post_content, so
			// insert after the first heading close; WXR pages render the H1 from
			// the template, so there is no H1 in content and we prepend.
			$marker = '<!-- /wp:heading -->';
			$pos    = strpos( $content, $marker );
			if ( false !== strpos( $content, '"level":1' ) && false !== $pos ) {
				$at      = $pos + strlen( $marker );
				$content = substr( $content, 0, $at ) . "\n\n" . $byline . substr( $content, $at );
			} else {
				$content = $byline . "\n\n" . $content;
			}
			$changed = true;
		}

		// (b) Patients Also Ask accordion — above the footer / final CTA.
		if ( isset( $paa_all[ $path ] ) && false === strpos( $content, 'patients-also-ask' ) ) {
			$paa    = pll_render_paa_section( $paa_all[ $path ] );
			$needle = '<!-- wp:pattern {"slug":"pll/final-cta"} /-->';
			if ( false !== strpos( $content, $needle ) ) {
				$content = str_replace( $needle, $paa . "\n\n" . $needle, $content );
			} else {
				$content = rtrim( $content ) . "\n\n" . $paa;
			}
			$changed = true;
		}

		if ( $changed ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_slash( $content ),
				)
			);
		}
	}
}
pll_seed_clinical_additions();

/**
 * 3e) Internal-linking map (PLL_SEO_Audit_Report.docx, "Links INTO the new
 * pillars"). Adds a near-the-top "see also" line to the WXR-generated pages so
 * they pass their existing ranking signal to the better-matched pillars,
 * WITHOUT editing scraped_content (shared with the Next.js parity source) or
 * hand-editing the generated WXR. The homepage and pricing links live in their
 * patterns (home-article.php, pricing-plans.php). Idempotent via the
 * pll-related-links marker.
 *
 * @return array<string, array{intro: string, links: array<int, array{0: string, 1: string}>}>
 */
function pll_related_links() {
	return array(
		'/your-surgery/' => array(
			'intro' => 'For the consumer-language overview of this procedure, see',
			'links' => array(
				array( '/leg-lengthening-surgery/', 'leg lengthening surgery' ),
			),
		),
		'/your-surgery/how-much-taller-can-i-get-with-limb-lengthening/' => array(
			'intro' => 'For the full procedure guides, see',
			'links' => array(
				array( '/height-surgery/', 'height surgery' ),
				array( '/leg-lengthening-surgery/', 'leg lengthening surgery' ),
			),
		),

		// A5 (cannibalization audit, owner-approved 2026-08-27). Google has
		// already half-sorted the age pair on its own: the hub page wins "age
		// limit" queries at #3-9 and the blog wins "too old / old age" at
		// #10.5. Each still surfaces as a losing secondary on the other's
		// queries, capping both, so the fix is differentiation rather than the
		// consolidation used on the pain and fixation pairs. These two lines
		// are what makes the split explicit to a crawler: intent-matched
		// anchor text pointing each page at the other's actual subject.
		// Custom anchor wording is why this lives here and not in
		// pll_cannibalization_links() — that seeder can only wrap a phrase
		// that already exists in the copy.
		'/your-surgery/is-there-an-age-limit-for-limb-lengthening/' => array(
			'intro' => 'For what changes when patients are older, see',
			'links' => array(
				array( '/am-i-too-old-for-limb-lengthening/', 'limb lengthening for patients over 40' ),
			),
		),
		'/am-i-too-old-for-limb-lengthening/' => array(
			'intro' => 'For the eligibility rules by age, see',
			'links' => array(
				array( '/your-surgery/is-there-an-age-limit-for-limb-lengthening/', 'age limit for limb lengthening' ),
			),
		),
	);
}

/**
 * Render the "see also" links paragraph. Hrefs are trusted developer constants.
 *
 * @param array $spec One pll_related_links() entry.
 * @return string Block markup.
 */
function pll_render_related_links( $spec ) {
	$anchors = array();
	foreach ( $spec['links'] as $link ) {
		$anchors[] = '<a href="' . $link[0] . '">' . esc_html( $link[1] ) . '</a>';
	}
	if ( count( $anchors ) > 1 ) {
		$last   = array_pop( $anchors );
		$joined = implode( ', ', $anchors ) . ' and ' . $last;
	} else {
		$joined = $anchors[0];
	}
	$text = esc_html( $spec['intro'] ) . ' ' . $joined . '.';
	return '<!-- wp:paragraph {"className":"pll-related-links text-[14px] leading-[1.6] text-ink-soft"} -->' . "\n"
		. '<p class="pll-related-links text-[14px] leading-[1.6] text-ink-soft">' . $text . '</p>' . "\n"
		. '<!-- /wp:paragraph -->';
}

/**
 * Inject the internal-link "see also" line near the top of the WXR pages.
 */
function pll_seed_internal_links() {
	if ( ! function_exists( 'pll_seo_resolve_path_id' ) ) {
		return;
	}
	foreach ( pll_related_links() as $path => $spec ) {
		$post_id = pll_seo_resolve_path_id( $path );
		if ( ! $post_id ) {
			continue;
		}
		$post    = get_post( $post_id );
		$content = $post->post_content;
		if ( false !== strpos( $content, 'pll-related-links' ) ) {
			continue;
		}
		$block  = pll_render_related_links( $spec );
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
	}
}
pll_seed_internal_links();

/**
 * 3f) Contextual internal links to the SEO pillars (Tier 1 of the
 * internal-linking expansion audit). Wraps an existing in-sentence phrase on
 * each topically-relevant page in a link to the matching pillar, funneling
 * authority up with varied, natural anchor text. Anchored on a longer verbatim
 * fragment so the link lands in the intended sentence and never nests inside an
 * existing link. Idempotent (skips a page that already links to that pillar),
 * so it survives re-import and PLL_SEED_FORCE. scraped_content and the WXR stay
 * untouched.
 *
 * @return array<string, array<int, array{0: string, 1: string, 2: string}>>
 *         path => list of [ context fragment, anchor sub-phrase, pillar path ].
 */
function pll_contextual_links() {
	$h = '/height-surgery/';
	$l = '/leg-lengthening-surgery/';
	return array(
		// -> /height-surgery/
		'/leg-up-or-let-down-can-you-gain-height-without-surgery/' => array(
			array( 'provide permanent, measurable gains in height', 'permanent, measurable gains in height', $h ),
		),
		'/can-i-get-a-leg-lengthening-procedure-for-cosmetic-reasons/' => array(
			array( 'simply gain a few extra inches of height', 'gain a few extra inches of height', $h ),
		),
		'/your-surgery/is-there-an-age-limit-for-limb-lengthening/' => array(
			array( 'having surgery to become taller', 'surgery to become taller', $h ),
		),
		// -> /leg-lengthening-surgery/
		'/fixation-methods-in-limb-lengthening-internal-vs-external/' => array(
			array( 'your recovery timeline', 'recovery timeline', $l ),
		),
		'/limb-lengthening-pain-the-truth/' => array(
			array( 'slowly separated, millimeter by millimeter', 'slowly separated, millimeter by millimeter', $l ),
		),
		'/your-surgery/external-internal-lengthening/' => array(
			array( 'our only option for leg lengthening', 'leg lengthening', $l ),
		),
		'/your-surgery/limb-lengthening-expectations/' => array(
			array( 'to guide the lengthening process', 'the lengthening process', $l ),
		),
		'/will-leg-lengthening-be-obvious/' => array(
			array( 'Leg lengthening procedures have come a long way', 'Leg lengthening procedures', $l ),
		),
		'/is-leg-lengthening-off-limits-for-athletes/' => array(
			array( 'recovery following a limb lengthening surgery', 'recovery following a limb lengthening surgery', $l ),
		),
		'/limb-lengthening-what-you-gain-what-you-risk/' => array(
			array( 'The limb lengthening procedure typically involves', 'limb lengthening procedure', $l ),
		),
	);
}

/**
 * Orphan-remediation contextual links (internal_links_audit.md, 2026-07-14).
 *
 * The audit found 9 blog posts with zero contextual inbound links (in-degree
 * 0 excluding the blog loop). Each entry below gives one orphan its first
 * inbound link from a topically related page, by wrapping a phrase that
 * already exists in the host copy — the visible text never changes. Same
 * shape and mechanics as pll_contextual_links() above. The 10th audit link
 * (pricing → insurance article) lives in the pricing-included pattern, not
 * here, because that page is pattern-composed.
 *
 * @return array<string, array<int, array{0: string, 1: string, 2: string}>>
 */
function pll_orphan_links() {
	return array(
		'/your-surgery/exercise-after-limb-lengthening/' => array(
			array( 'you will likely not see any difference in your athletic performance', 'your athletic performance', '/is-leg-lengthening-off-limits-for-athletes/' ),
		),
		'/the-importance-of-physical-therapy-in-limb-lengthening/' => array(
			array( 'As the bone grows longer, all the muscles, tendons, nerves, and blood vessels must stretch and remodel.', 'muscles, tendons, nerves, and blood vessels must stretch and remodel', '/what-happens-to-muscle-during-and-after-limb-lengthening/' ),
			array( 'Techniques for sleeping, sitting, and traveling that protect the limb', 'sleeping, sitting, and traveling', '/tips-for-traveling-for-the-holidays-after-limb-lengthening-surgery/' ),
		),
		'/your-surgery/' => array(
			array( 'or those who wish to change their height for cosmetic reasons can benefit from a procedure', 'can benefit from a procedure', '/are-you-a-good-candidate-for-limb-lengthening/' ),
		),
		'/bone-health-and-nutrition-before-and-after-limb-lengthening/' => array(
			array( 'During distraction osteogenesis, your body has to grow brand-new bone', 'distraction osteogenesis', '/the-science-behind-bone-regeneration-and-limb-lengthening/' ),
		),
		'/can-i-get-a-leg-lengthening-procedure-for-cosmetic-reasons/' => array(
			array( 'many of us have looked in the mirror and wondered what it would be like to be just a bit taller', 'what it would be like to be just a bit taller', '/leg-up-or-let-down-can-you-gain-height-without-surgery/' ),
			array( 'Gaining those extra inches can have a real impact on how you carry yourself, both physically and mentally.', 'how you carry yourself, both physically and mentally', '/rewriting-the-body-norm-stigmas-around-limb-lengthening/' ),
		),
		'/your-surgery/how-much-taller-can-i-get-with-limb-lengthening/' => array(
			array( 'This means we do not want the legs to look awkward after the procedure and lengthening.', 'look awkward after the procedure', '/will-leg-lengthening-be-obvious/' ),
		),
	);
}

/**
 * 3g) Cannibalization-remediation contextual links (Keyword Cannibalization
 * Audit, 2026-08-05; owner-approved in artifact comments 2026-08-27).
 *
 * Same mechanics and idempotence as pll_contextual_links() above: wrap a
 * phrase that already exists in the host copy, so the visible text never
 * changes and only the link graph moves. Grouped by ticket:
 *
 * A2 — "limb lengthening surgery" had no designated landing page, so Google
 * rotated four URLs and none reached page one (~3,500 impressions, 9 clicks
 * in six months). /your-surgery/ already carries the matching title and the
 * MedicalProcedure schema; what it lacked was exact-phrase inbound anchors.
 * Scope deliberately NARROWER than the audit text: the audit predates the
 * /leg-lengthening-surgery/ and /height-surgery/ pillars, which now own the
 * "leg lengthening surgery" and height families (see pll_contextual_links).
 * Sending those terms to the hub as well would rebuild the same split this
 * ticket exists to close, so the hub is anchored ONLY on the "limb
 * lengthening surgery" wording.
 *
 * A6 — name queries split ~50/50 between the homepage and /dr-basmajian/
 * with zero clicks, because both carry the name and neither declared the
 * canonical Person. The Physician node and the founder reference handle the
 * machine-readable half (pll-seo/includes/schema.php); these anchors are the
 * link-graph half, first in-body mention per page pointed at the profile.
 *
 * A7 — the sports pair is nearly healthy (athletes article owns "play sports
 * after", exercise page owns "can you run after") but each still bleeds
 * 23-39% onto the other's queries. The exercise page already links back to
 * the athletes article (pll_orphan_links), so only the outbound half is new,
 * anchored on running language to match the exercise page's query family.
 *
 * @return array<string, array<int, array{0: string, 1: string, 2: string}>>
 */
function pll_cannibalization_links() {
	$hub     = '/your-surgery/';
	$surgeon = '/dr-basmajian/';

	return array(
		// A2 -> /your-surgery/ as the "limb lengthening surgery" head-term page.
		'/limb-lengthening-what-you-gain-what-you-risk/' => array(
			array( 'In fact, limb lengthening surgery can open up new worlds', 'limb lengthening surgery', $hub ),
		),
		'/are-you-a-good-candidate-for-limb-lengthening/' => array(
			array( 'prior to limb lengthening surgery to reduce the risk', 'limb lengthening surgery', $hub ),
		),
		'/is-limb-lengthening-covered-by-insurance/' => array(
			array( 'if limb lengthening surgery is deemed medically necessary', 'limb lengthening surgery', $hub ),
		),
		'/rewriting-the-body-norm-stigmas-around-limb-lengthening/' => array(
			array( 'the decision to undergo limb lengthening surgery', 'limb lengthening surgery', $hub ),
		),
		'/your-surgery/can-i-bend-my-lengthening-nail/' => array(
			array( 'In trauma and modern limb lengthening surgery, an intramedullary nail', 'limb lengthening surgery', $hub ),
			// A6 name anchor on the same page (different href, so both apply).
			array( 'a trauma background, like Dr. Basmajian, can help', 'Dr. Basmajian', $surgeon ),
		),

		// A6 -> /dr-basmajian/ as the canonical Person entity.
		'/your-surgery/how-much-taller-can-i-get-with-limb-lengthening/' => array(
			array( 'Dr. Basmajian will work with each of our patients', 'Dr. Basmajian', $surgeon ),
		),
		'/your-surgery/limb-lengthening-expectations/' => array(
			array( 'During your consultation, Dr. Basmajian will review your medical history', 'Dr. Basmajian', $surgeon ),
		),
		'/your-surgery/external-internal-lengthening/' => array(
			array( 'Dr. Basmajian has used both technologies', 'Dr. Basmajian', $surgeon ),
		),
		'/your-surgery/is-there-an-age-limit-for-limb-lengthening/' => array(
			array( 'should be discussed with Dr. Basmajian and the family', 'Dr. Basmajian', $surgeon ),
		),

		// A7 -> the exercise page owns the "can you run after" family.
		'/is-leg-lengthening-off-limits-for-athletes/' => array(
			array( 'reintroduces running, jumping, cutting movements', 'running, jumping, cutting movements', '/your-surgery/exercise-after-limb-lengthening/' ),
		),
	);
}

/**
 * 3g-bis) Owner copy revisions, 2026-09-07.
 *
 * The marketing pages are COMPOSED FROM PATTERNS AT SEED TIME, so their copy
 * lives in post_content and editing a pattern file changes nothing on a page
 * that already exists. PLL_SEED_FORCE would recompose them, and would also
 * destroy every owner edit made in the block editor since launch, so it is not
 * an option (MIGRATION.md). This applies the owner's changes as targeted
 * find/replace against the stored content instead.
 *
 * Idempotent by construction: once "from" has been replaced it is no longer
 * present, so a re-run is a no-op. A revision whose "from" AND "to" are both
 * missing means the page has drifted from what we expect, which is logged
 * rather than silently ignored.
 *
 * Two groups of change:
 *
 * 1. PRECICE MAX naming. The three top-tier packages are PRECICE Max but the
 *    bullets said either a bare "PRECICE" or "PRECICE 4th Generation", which
 *    read as a different device from the "PRECICE 2" tiers below them. The
 *    owner asked for the Max name on the implant lines and in the section
 *    heading. The PRECICE 2 tiers are deliberately untouched.
 *
 * 2. Financing claims. "$1,200/month" and the "0% intro" tile are removed
 *    everywhere, pending approved CareCredit material. These are advertised
 *    credit terms, so they do not go back without written sign-off. Note the
 *    insurance article's answer is published twice, as visible copy (seeded
 *    here) and as FAQPage JSON-LD (runtime, from pll_seo_paa), so both halves
 *    have to change or the schema and the page disagree.
 *
 * @return array<string, array<int, array{from: string, to: string}>>
 */
function pll_copy_revisions() {
	return array(
		'/' => array(
			array(
				'from' => '<span>2 PRECICE internal nail implants</span>',
				'to'   => '<span>2 PRECICE MAX internal nail implants</span>',
			),
			array(
				'from' => '<span>4 PRECICE internal nail implants</span>',
				'to'   => '<span>4 PRECICE MAX internal nail implants</span>',
			),
			array(
				'from' => 'Financing available through CareCredit. As low as $1,200/month with approved credit.',
				'to'   => 'Financing options are available. We will walk you through them during your consultation.',
			),
		),
		'/limb-lengthening-pricing-options/' => array(
			array(
				'from' => 'Limb Lengthening Cost, <em class="italic text-spine">priced like a partner.</em>',
				'to'   => 'Limb Lengthening <em class="italic text-spine">Cost.</em>',
			),
			array(
				'from' => 'Three procedures. <em class="italic text-spine">One transparent price.</em>',
				'to'   => 'Three procedures. <em class="italic text-spine">PRECICE MAX cost.</em>',
			),
			array(
				'from' => '<span>2 PRECICE 4th Generation internal nail implants</span>',
				'to'   => '<span>2 PRECICE MAX internal nail implants</span>',
			),
			// Only the bare "PRECICE"; "4 PRECICE 2 internal nail implants" in
			// the PRECICE 2 group is a different string and stays as it is.
			array(
				'from' => '<span>4 PRECICE internal nail implants</span>',
				'to'   => '<span>4 PRECICE MAX internal nail implants</span>',
			),
		),
		'/is-limb-lengthening-covered-by-insurance/' => array(
			array(
				'from' => 'Premier Limb Lengthening offers financing through SoFi and CareCredit, with monthly payments starting as low as $1,200 for qualified applicants.',
				'to'   => 'Premier Limb Lengthening works with financing partners including SoFi and CareCredit, and terms depend on the applicant and the lender.',
			),
		),
	);
}

/**
 * Remove a top-level wp:group by its exact className, with its children.
 *
 * Used to pull the financing stat tiles ("$1,200/mo", "0% intro") out of the
 * stored pricing page. Walks the wp:group open/close comments to find the
 * matching close rather than assuming the first one, because the block wraps
 * two nested groups and a non-greedy match would cut it in the middle and
 * leave unbalanced block markup behind, which the editor then reports as
 * invalid content.
 *
 * @param string $content   Block markup.
 * @param string $classname Exact className attribute value.
 * @return string Markup with the group removed, unchanged if not found.
 */
function pll_remove_group_by_classname( $content, $classname ) {
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
			return $content; // Unbalanced, leave it alone.
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

/**
 * Apply the owner copy revisions to the stored pages.
 */
function pll_seed_copy_revisions() {
	if ( ! function_exists( 'pll_seo_resolve_path_id' ) ) {
		return;
	}

	foreach ( pll_copy_revisions() as $path => $revisions ) {
		$post_id = pll_seo_resolve_path_id( $path );
		if ( ! $post_id ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'pll seed: copy revision target missing: ' . $path );
			continue;
		}
		$post    = get_post( $post_id );
		$content = $post->post_content;
		$changed = false;

		foreach ( $revisions as $rev ) {
			if ( false !== strpos( $content, $rev['from'] ) ) {
				$content = str_replace( $rev['from'], $rev['to'], $content );
				$changed = true;
			} elseif ( false === strpos( $content, $rev['to'] ) ) {
				// Neither the old copy nor the new copy is present: the page
				// has been edited away from what this revision assumed.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'pll seed: copy revision stale (' . $path . '): ' . substr( $rev['from'], 0, 60 ) );
			}
		}

		if ( '/limb-lengthening-pricing-options/' === $path ) {
			$stripped = pll_remove_group_by_classname(
				$content,
				'mt-10 grid grid-cols-2 gap-0 border-t border-white/25 max-w-[460px]'
			);
			if ( $stripped !== $content ) {
				$content = $stripped;
				$changed = true;
			}
		}

		if ( $changed ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_slash( $content ),
				)
			);
		}
	}
}
pll_seed_copy_revisions();

/**
 * 3h) Repoint inbound links away from retired URLs.
 *
 * A consolidation ticket (cannibalization audit A4) retires a duplicate by
 * adding it to pll_seo_redirect_map(). The 301 keeps external links and
 * bookmarks working, but every INTERNAL link still aimed at the retired URL
 * would spend a redirect hop and keep telling Google the old URL is a real
 * destination, which is most of what the consolidation is trying to stop.
 *
 * Driven off the redirect map itself rather than a second hand-maintained
 * list, so retiring a URL in one place repoints the whole site. Rewrites both
 * the root-relative and the absolute (legacy WXR) forms of the href, and
 * matches on the trailing quote so a longer sibling slug can never be caught
 * by a prefix match. Idempotent by construction: after one pass the old href
 * is simply absent.
 */
function pll_seed_retired_link_repoint() {
	if ( ! function_exists( 'pll_seo_redirect_map' ) ) {
		return;
	}
	$map = pll_seo_redirect_map();
	if ( ! $map ) {
		return;
	}

	foreach ( get_posts(
		array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
		)
	) as $post ) {
		$content = $post->post_content;
		$before  = $content;

		foreach ( $map as $from => $to ) {
			$from = untrailingslashit( $from );
			// Never rewrite a page's links to itself: the retired post keeps
			// its own body, and self-links there are not the problem.
			if ( pll_seo_resolve_path_id( $from . '/' ) === (int) $post->ID ) {
				continue;
			}
			foreach ( array( $from, home_url( $from ) ) as $needle ) {
				foreach ( array( $needle . '/"', $needle . '"' ) as $old ) {
					$new     = ( $needle === $from ? $to : home_url( $to ) ) . '"';
					$content = str_replace( 'href="' . $old, 'href="' . $new, $content );
				}
			}
		}

		if ( $content !== $before ) {
			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => wp_slash( $content ),
				)
			);
		}
	}
}

/**
 * Apply the contextual pillar + orphan-remediation + cannibalization links.
 */
function pll_seed_contextual_links() {
	if ( ! function_exists( 'pll_seo_resolve_path_id' ) ) {
		return;
	}
	$map = array_merge_recursive( pll_contextual_links(), pll_orphan_links(), pll_cannibalization_links() );
	foreach ( $map as $path => $links ) {
		$post_id = pll_seo_resolve_path_id( $path );
		if ( ! $post_id ) {
			continue;
		}
		$post    = get_post( $post_id );
		$content = $post->post_content;
		$changed = false;

		foreach ( $links as $link ) {
			list( $find, $anchor, $href ) = $link;
			// Idempotent: skip if this pillar is already linked from the page.
			if ( false !== strpos( $content, 'href="' . $href . '"' ) ) {
				continue;
			}
			$pos = strpos( $content, $find );
			if ( false === $pos ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'pll seed: contextual link phrase not found (' . $path . '): ' . $find );
				continue;
			}
			$linked  = str_replace( $anchor, '<a href="' . $href . '">' . $anchor . '</a>', $find );
			$content = substr( $content, 0, $pos ) . $linked . substr( $content, $pos + strlen( $find ) );
			$changed = true;
		}

		if ( $changed ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_slash( $content ),
				)
			);
		}
	}
}
pll_seed_contextual_links();

// Last of the content passes: the seeders above may themselves add a link, and
// this one has to see the final state of every page.
pll_seed_retired_link_repoint();

/**
 * 4) Reading settings.
 */
if ( $pll_home_id ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $pll_home_id );
}
if ( $pll_blog_id ) {
	update_option( 'page_for_posts', $pll_blog_id );
}

/**
 * 5) Permalinks.
 */
flush_rewrite_rules();
