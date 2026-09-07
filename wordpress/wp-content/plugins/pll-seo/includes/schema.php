<?php
/**
 * Schema.org JSON-LD — 1:1 PHP port of lib/jsonld.ts, emitted in the same
 * page contexts the Next.js app used.
 *
 * @package pll-seo
 */

defined( 'ABSPATH' ) || exit;

/**
 * MedicalBusiness + WebSite graph — every page (Next mounts it in app/layout.tsx).
 *
 * @return array
 */
function pll_seo_site_graph() {
	$org_id  = PLL_SEO_ORIGIN . '/#organization';
	$site_id = PLL_SEO_ORIGIN . '/#website';
	return array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			array(
				'@type'              => 'MedicalBusiness',
				'@id'                => $org_id,
				'name'               => 'Premier Limb Lengthening Institute',
				'legalName'          => 'Premier Limb Lengthening',
				'url'                => PLL_SEO_ORIGIN,
				'telephone'          => '+1-951-620-5663',
				// Floor is the lowest tier in data/pricing.php (Bilateral Femur
				// with PRECICE 2). It previously read "$95,500" (the PRECICE Max
				// femur tier) with an en dash, which both broke the brand copy
				// rule inside machine-readable output and declared a sitewide
				// price floor $20,000 above the advertised "From" price.
				'priceRange'         => '$75,500 to $195,000',
				'medicalSpecialty'   => array( 'Orthopedic', 'Trauma' ),
				'address'            => array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => '400 N. Mountain Ave. Suite 305',
					'addressLocality' => 'Upland',
					'addressRegion'   => 'CA',
					'postalCode'      => '91786',
					'addressCountry'  => 'US',
				),
				// Entity disambiguation for surgeon-name queries (cannibalization
				// audit A6, owner-approved 2026-08-27). "dr basmajian" and its
				// variants split ~50/50 between the homepage and /dr-basmajian/
				// because both carry the name prominently and neither declared
				// which one is the canonical Person. Referencing the Physician
				// by @id (emitted in full on /dr-basmajian/) resolves the graph
				// to one entity without a title fight on the homepage.
				'founder'            => array( '@id' => PLL_SEO_ORIGIN . '/dr-basmajian#physician' ),
				'employee'           => array( '@id' => PLL_SEO_ORIGIN . '/dr-basmajian#physician' ),
				'parentOrganization' => array(
					'@type' => 'Organization',
					'name'  => 'Premier Orthopaedic & Trauma Specialists',
				),
			),
			array(
				'@type'      => 'WebSite',
				'@id'        => $site_id,
				'url'        => PLL_SEO_ORIGIN,
				'name'       => 'Premier Limb Lengthening Institute',
				'publisher'  => array( '@id' => $org_id ),
				'inLanguage' => 'en-US',
			),
		),
	);
}

/**
 * BreadcrumbList.
 *
 * @param array<int, array{name: string, url: string}> $items Crumbs (url = path with trailing slash).
 * @return array
 */
function pll_seo_breadcrumb( $items ) {
	$elements = array();
	foreach ( $items as $i => $item ) {
		$elements[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $item['name'],
			'item'     => PLL_SEO_ORIGIN . $item['url'],
		);
	}
	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $elements,
	);
}

/**
 * Article schema for a post or your-surgery sub-page.
 *
 * @param WP_Post $post Post.
 * @return array
 */
function pll_seo_article( $post ) {
	$path     = wp_parse_url( get_permalink( $post ), PHP_URL_PATH );
	$thumb_id = get_post_thumbnail_id( $post );
	$image    = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'full' ) : null;
	$title    = (string) get_post_meta( $post->ID, '_pll_seo_title', true );
	if ( ! $title ) {
		$title = get_the_title( $post );
	}

	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'Article',
		'headline'         => $title,
		'description'      => (string) get_post_meta( $post->ID, '_pll_seo_description', true ),
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => PLL_SEO_ORIGIN . $path,
		),
		'author'           => array(
			'@type' => 'Person',
			'name'  => 'Dr. Hrayr Basmajian',
			'@id'   => PLL_SEO_ORIGIN . '/dr-basmajian#physician',
		),
		'publisher'        => array( '@id' => PLL_SEO_ORIGIN . '/#organization' ),
		'inLanguage'       => 'en-US',
	);

	if ( $image ) {
		$schema['image'] = $image;
	}
	// Dates only for blog posts (the dateless service-subs omit them, matching Next).
	if ( 'post' === $post->post_type ) {
		$date                    = get_post_time( 'c', false, $post, true );
		$schema['datePublished'] = $date;
		$schema['dateModified']  = $date;
	}
	return $schema;
}

/**
 * FAQPage from a list of question/answer pairs.
 *
 * Post-deprecation compliant (audit Critical 3): one FAQPage per URL, all Q/A
 * in a single mainEntity array, no promotional language in answer fields. The
 * $qas passed in must match the visible on-page text verbatim (homepage FAQ
 * comes from pll_seo_faqs(), PAA pages come from pll_seo_paa()).
 *
 * Both fields are run through wptexturize() before they are published. The
 * visible copy lives in post_content, which the_content texturizes on every
 * request, so a straight apostrophe in the data file rendered as a curly one
 * on the page while the schema kept the straight one. That divergence was live
 * on /is-leg-lengthening-off-limits-for-athletes/, the only PAA page whose
 * answers contain an apostrophe. Texturizing here fixes it in the correct
 * direction: the rendered page is the published artifact and the schema must
 * describe it, so the data files stay authored in plain ASCII and survive the
 * next copy edit. wptexturize() self-guards on the run_wptexturize filter, so
 * a site that turns texturization off keeps both sides in lockstep anyway.
 *
 * @param array<int, array{q: string, a: string}> $qas Question/answer pairs.
 * @return array
 */
function pll_seo_faqpage_from( $qas ) {
	$entities = array();
	foreach ( $qas as $faq ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => wptexturize( $faq['q'] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wptexturize( $faq['a'] ),
			),
		);
	}
	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);
}

/**
 * FAQPage from the homepage FAQ entries.
 *
 * @return array
 */
function pll_seo_faq_schema() {
	return pll_seo_faqpage_from( pll_seo_faqs() );
}

/**
 * Collapse rendered block HTML into the published text represented by schema.
 *
 * @param string $html Rendered block HTML.
 * @return string
 */
function pll_seo_schema_visible_text( $html ) {
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	return trim( (string) preg_replace( '/\s+/', ' ', $text ) );
}

/**
 * Recursively collect only visible PLL FAQ items from ordinary block content.
 *
 * The question belongs to the FAQ item attribute. Its answer is rendered from
 * the item's real inner blocks, then reduced to the text patients can read.
 * Empty or malformed rows do not earn schema entries.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed Gutenberg blocks.
 * @return array<int, array{q: string, a: string}>
 */
function pll_seo_visible_faqs_from_blocks( $blocks ) {
	$faqs = array();
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}
		if ( 'pll/faq-item' === ( $block['blockName'] ?? '' ) ) {
			$question    = pll_seo_schema_visible_text( (string) ( $block['attrs']['question'] ?? '' ) );
			$answer_html = '';
			foreach ( $block['innerBlocks'] ?? array() as $inner_block ) {
				$answer_html .= render_block( $inner_block );
			}
			$answer = pll_seo_schema_visible_text( $answer_html );
			if ( '' !== $question && '' !== $answer ) {
				$faqs[] = array(
					'q' => $question,
					'a' => $answer,
				);
			}
			continue;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$faqs = array_merge( $faqs, pll_seo_visible_faqs_from_blocks( $block['innerBlocks'] ) );
		}
	}
	return $faqs;
}

/**
 * Existing page-level FAQ sources remain the single owner of their schema.
 *
 * @param string $path Root-relative page path.
 * @return bool
 */
function pll_seo_has_hard_coded_faq_owner( $path ) {
	if ( pll_seo_paa( $path ) ) {
		return true;
	}
	return in_array(
		$path,
		array(
			'/',
			'/limb-lengthening-pricing-options/',
			'/evaluate-your-surgeon/',
		),
		true
	);
}

/**
 * Build FAQPage schema from visible blocks for an indexable, ordinary page.
 *
 * @param WP_Post $post Current page.
 * @param string  $path Root-relative page path.
 * @return array|null
 */
function pll_seo_visible_faq_schema_for_page( $post, $path ) {
	if ( ! $post instanceof WP_Post || pll_seo_is_noindex( $post->ID ) || pll_seo_has_hard_coded_faq_owner( $path ) ) {
		return null;
	}
	$faqs = pll_seo_visible_faqs_from_blocks( parse_blocks( $post->post_content ) );
	return $faqs ? pll_seo_faqpage_from( $faqs ) : null;
}

/**
 * Physician (dr-basmajian page).
 *
 * @return array
 */
function pll_seo_physician() {
	return array(
		'@context'         => 'https://schema.org',
		'@type'            => 'Physician',
		'@id'              => PLL_SEO_ORIGIN . '/dr-basmajian#physician',
		'name'             => 'Dr. Hrayr Basmajian',
		'medicalSpecialty' => array( 'Orthopedic', 'Trauma' ),
		'image'            => PLL_SEO_ORIGIN . '/dr-picture.jpg',
		'affiliation'      => array(
			array(
				'@type' => 'Hospital',
				'name'  => 'Pomona Valley Hospital Medical Center',
			),
		),
		'worksFor'         => array( '@id' => PLL_SEO_ORIGIN . '/#organization' ),
	);
}

/**
 * MedicalProcedure (your-surgery overview).
 *
 * @return array
 */
function pll_seo_procedure() {
	return array(
		'@context'      => 'https://schema.org',
		'@type'         => 'MedicalProcedure',
		'name'          => 'Limb Lengthening Surgery',
		'procedureType' => 'https://schema.org/SurgicalProcedure',
		'bodyLocation'  => array( 'Femur', 'Tibia' ),
		'howPerformed'  => 'Distraction osteogenesis via the Precice internal nail system: the bone is cut and gradually pulled apart while new bone regenerates in the gap.',
		'preparation'   => 'Pre-operative consultation, imaging, and health assessment.',
		'followup'      => 'On-site therapy in Upland, CA. Surgical follow-up visits. Active lengthening over 3-4 months, full recovery in 6-12 months.',
	);
}

/**
 * Physician node used as performedBy on the MedicalProcedure schemas, linked
 * by @id to the Physician entity emitted on /dr-basmajian/ (EEAT: makes the
 * surgeon-to-procedure relationship machine-readable).
 *
 * @return array
 */
function pll_seo_performed_by() {
	return array(
		'@type'            => 'Physician',
		'@id'             => PLL_SEO_ORIGIN . '/dr-basmajian#physician',
		'name'            => 'Dr. Hrayr Basmajian',
		'medicalSpecialty' => 'Orthopedic Trauma Surgery',
		'url'             => PLL_SEO_ORIGIN . '/dr-basmajian/',
	);
}

/**
 * Medical-review sign-off dates keyed by page path. THE single source of truth,
 * and it lives here rather than in content/setup.php because it has to be
 * readable on a front-end request: one map drives two outputs, the seeded
 * "Medically reviewed by" byline (pll_seed_clinical_additions() reads this
 * function) and the MedicalWebPage + reviewedBy JSON-LD below.
 *
 * A path appears here ONLY after Dr. Basmajian has reviewed that page
 * (wordpress/docs/MEDICAL_REVIEW_LOG.md). Absent path = no byline and no
 * MedicalWebPage. This is the "attribution must be true" gate from
 * PLL_Medical_Review_Policy_and_Log.docx and it guarantees a placeholder date
 * can never reach production. To publish a byline: add the path below with a
 * real "Month Year" value, then re-seed (docs/MIGRATION.md §6b).
 *
 * @return array<string, string> Path => "Month Year".
 */
function pll_seo_review_dates() {
	return array(
		// Signed off by Dr. Basmajian, July 2026 (PLL_Pillar_Pages_Clinical_Review.docx).
		'/height-surgery/'          => 'July 2026',
		'/leg-lengthening-surgery/' => 'July 2026',
		// Held pending review — uncomment with a real date once signed off:
		// '/your-surgery/' => 'Month Year',
		// '/your-surgery/how-much-taller-can-i-get-with-limb-lengthening/' => 'Month Year',
		// '/limb-lengthening-what-you-gain-what-you-risk/' => 'Month Year',
		// '/is-leg-lengthening-off-limits-for-athletes/' => 'Month Year',
		// '/limb-lengthening-pain-the-truth/' => 'Month Year',
	);
}

/**
 * Normalise a display review date to a schema.org Date. The visible byline
 * keeps the human string ("July 2026"); schema.org wants ISO 8601, so a
 * month-precision sign-off resolves to the first of that month.
 *
 * Accepts "July 2026", "Jul 2026", "2026-07", and "2026-07-01".
 *
 * @param string $month_year Display review date.
 * @return string ISO 8601 date, or '' when unparseable.
 */
function pll_seo_review_date_iso( $month_year ) {
	$raw = trim( (string) $month_year );
	if ( '' === $raw ) {
		return '';
	}
	// Already ISO: pass through, defaulting a bare year-month to the 1st.
	if ( preg_match( '/^(\d{4})-(\d{2})(?:-(\d{2}))?$/', $raw, $matches ) ) {
		return $matches[1] . '-' . $matches[2] . '-' . ( empty( $matches[3] ) ? '01' : $matches[3] );
	}
	$timestamp = strtotime( '1 ' . $raw . ' 00:00:00 UTC' );
	return false === $timestamp ? '' : gmdate( 'Y-m-d', $timestamp );
}

/**
 * MedicalWebPage for a page carrying a documented clinical review. Rides
 * alongside the page's own entity schema (the pillars keep their
 * MedicalProcedure) and makes the review attribution machine-readable.
 *
 * reviewedBy reuses pll_seo_performed_by(), whose @id is identical to the
 * Physician emitted on /dr-basmajian/, so the graph resolves to one entity.
 *
 * @param string $path       Page path, with trailing slash.
 * @param string $month_year Review date as shown in the visible byline.
 * @return array
 */
function pll_seo_medical_webpage( $path, $month_year ) {
	$schema = array(
		'@context'     => 'https://schema.org',
		'@type'        => 'MedicalWebPage',
		'url'          => PLL_SEO_ORIGIN . $path,
		'lastReviewed' => pll_seo_review_date_iso( $month_year ),
		'reviewedBy'   => pll_seo_performed_by(),
		'isPartOf'     => array( '@id' => PLL_SEO_ORIGIN . '/#website' ),
		'publisher'    => array( '@id' => PLL_SEO_ORIGIN . '/#organization' ),
	);
	if ( '' === $schema['lastReviewed'] ) {
		unset( $schema['lastReviewed'] );
	}
	return $schema;
}

/**
 * MedicalClinic node (availableService target) for the homepage procedure.
 *
 * @return array
 */
function pll_seo_available_clinic() {
	return array(
		'@type'     => 'MedicalClinic',
		'name'      => 'Premier Limb Lengthening',
		'url'       => PLL_SEO_ORIGIN,
		'telephone' => '(951) 620-5663',
		'address'   => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => 'Upland',
			'addressRegion'   => 'CA',
			'addressCountry'  => 'US',
		),
	);
}

/**
 * Homepage MedicalProcedure — the cosmetic limb lengthening entity with the
 * consumer-language alternateName stack (height surgery, leg lengthening, etc.)
 * that anchors semantic SEO across the site. Supplements the homepage FAQPage.
 *
 * @return array
 */
function pll_seo_home_procedure() {
	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'MedicalProcedure',
		'name'            => 'Cosmetic Limb Lengthening Surgery',
		'alternateName'   => array( 'Height Surgery', 'Height Increase Surgery', 'Leg Lengthening Surgery', 'Stature Lengthening Surgery', 'Height Lengthening Surgery', 'Cosmetic Height Surgery' ),
		'procedureType'   => 'https://schema.org/SurgicalProcedure',
		'bodyLocation'    => array( 'Femur', 'Tibia' ),
		'description'     => 'Cosmetic limb lengthening surgery permanently increases height by 2 to 6 inches using internal telescopic nail technology. Performed by Dr. Hrayr Basmajian at Premier Limb Lengthening in Upland, California.',
		'howPerformed'    => 'A controlled osteotomy is performed on the femur or tibia. An internal telescopic nail (PRECICE Max or PRECICE 2) is inserted into the marrow canal. The nail is gradually extended using an external magnetic controller at approximately 1 millimeter per day, stimulating new bone growth through distraction osteogenesis.',
		'preparation'     => 'Comprehensive pre-operative evaluation including physical examination, full-length standing X-rays, blood work, and medical clearance. Patients must be skeletally mature with closed growth plates.',
		'followup'        => 'Regular follow-up imaging to monitor bone consolidation. Physical therapy throughout lengthening and consolidation phases. Nail removal at 12 to 18 months post-surgery.',
		'status'          => 'https://schema.org/EventScheduled',
		'performedBy'     => pll_seo_performed_by(),
		'availableService' => pll_seo_available_clinic(),
	);
}

/**
 * Pricing-page MedicalProcedures — the six priced packages (three surgery
 * types, each in PRECICE Max and PRECICE 2 versions, mirroring the visible
 * plan cards). Emitted alongside the ItemList so the priced procedures are
 * also modeled as MedicalProcedure with Offer.
 *
 * @return array A @graph of six MedicalProcedure nodes.
 */
function pll_seo_pricing_procedures() {
	$url      = PLL_SEO_ORIGIN . '/limb-lengthening-pricing-options/';
	$by       = array( '@id' => PLL_SEO_ORIGIN . '/dr-basmajian#physician' );
	$packages = array(
		array(
			'name'          => 'Bilateral Femur Lengthening with PRECICE Max',
			'alternateName' => array( 'Femur Height Surgery', 'Thigh Bone Lengthening' ),
			'bodyLocation'  => 'Femur',
			'description'   => 'Bilateral femur lengthening using the PRECICE Max internal nail. Typical height gain of 2 to 3.5 inches.',
			'price'         => '95500',
			'nail'          => 'PRECICE Max',
		),
		array(
			'name'          => 'Bilateral Tibia Lengthening with PRECICE Max',
			'alternateName' => array( 'Tibia Height Surgery', 'Shin Bone Lengthening' ),
			'bodyLocation'  => 'Tibia',
			'description'   => 'Bilateral tibia lengthening using the PRECICE Max internal nail. Typical height gain of 2 to 3 inches.',
			'price'         => '105500',
			'nail'          => 'PRECICE Max',
		),
		array(
			'name'          => 'Combined Bilateral Femur and Tibia Lengthening with PRECICE Max',
			'alternateName' => array( 'Combined Limb Lengthening', 'Full Leg Lengthening' ),
			'bodyLocation'  => array( 'Femur', 'Tibia' ),
			'description'   => 'Bilateral femur and tibia lengthening using PRECICE Max internal nails, performed as a combined surgery or staged as two separate procedures. Combined height gain of 4 to 6 inches.',
			'price'         => '195000',
			'nail'          => 'PRECICE Max',
		),
		array(
			'name'         => 'Bilateral Femur Lengthening with PRECICE 2',
			'bodyLocation' => 'Femur',
			'description'  => 'Bilateral femur lengthening using the PRECICE 2 internal nail. Typical height gain of 2 to 3.5 inches.',
			'price'        => '75500',
			'nail'         => 'PRECICE 2',
		),
		array(
			'name'         => 'Bilateral Tibia Lengthening with PRECICE 2',
			'bodyLocation' => 'Tibia',
			'description'  => 'Bilateral tibia lengthening using the PRECICE 2 internal nail. Typical height gain of 2 to 3 inches.',
			'price'        => '85500',
			'nail'         => 'PRECICE 2',
		),
		array(
			'name'         => 'Combined Bilateral Femur and Tibia Lengthening with PRECICE 2',
			'bodyLocation' => array( 'Femur', 'Tibia' ),
			'description'  => 'Bilateral femur and tibia lengthening using PRECICE 2 internal nails, performed as a combined surgery or staged as two separate procedures. Combined height gain of 4 to 6 inches.',
			// Second hardcoded copy of the same tier as data/pricing.php. Both
			// read 175000 while the pricing page's visible card reads $150,000.
			// $150,000 is correct (owner, 2026-08-17). Keep the two in step.
			'price'        => '150000',
			'nail'         => 'PRECICE 2',
		),
	);

	$graph = array();
	foreach ( $packages as $package ) {
		$node = array(
			'@type'         => 'MedicalProcedure',
			'name'          => $package['name'],
			'procedureType' => 'https://schema.org/SurgicalProcedure',
			'bodyLocation'  => $package['bodyLocation'],
			'description'   => $package['description'],
			'performedBy'   => $by,
			'offers'        => array(
				'@type'         => 'Offer',
				'price'         => $package['price'],
				'priceCurrency' => 'USD',
				'description'   => 'All-in pricing includes surgery, ' . $package['nail'] . ' implants, anesthesia, hospitalization, physical therapy, follow-up imaging, and nail removal.',
				'url'           => $url,
			),
		);
		if ( ! empty( $package['alternateName'] ) ) {
			$node['alternateName'] = $package['alternateName'];
		}
		$graph[] = $node;
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);
}

/**
 * Pillar-page MedicalProcedure for /height-surgery/. Differentiated from the
 * /your-surgery/ hub procedure by owning the consumer head terms (height
 * surgery, height increase surgery) rather than the clinical umbrella term.
 *
 * @return array
 */
function pll_seo_height_procedure() {
	return array(
		'@context'      => 'https://schema.org',
		'@type'         => 'MedicalProcedure',
		'name'          => 'Height Surgery',
		'alternateName' => array( 'Height Increase Surgery', 'Cosmetic Height Surgery', 'Stature Lengthening Surgery' ),
		'procedureType' => 'https://schema.org/SurgicalProcedure',
		'bodyLocation'  => array( 'Femur', 'Tibia' ),
		'description'   => 'Height surgery permanently increases height by lengthening the femur, the tibia, or both using an internal telescopic nail. Most patients gain 2 to 3.5 inches from a single femur procedure, up to 4 to 6 inches when the tibia is lengthened in a combined procedure.',
		'howPerformed'  => 'A controlled osteotomy is performed and an internal telescopic nail is inserted into the marrow canal. The nail is extended approximately 1 millimeter per day with an external controller, growing new bone through distraction osteogenesis over several months.',
		'preparation'   => 'Consultation, full-length standing X-rays confirming closed growth plates, blood work, and medical clearance.',
		'followup'      => 'Physical therapy throughout lengthening and consolidation, follow-up imaging, and nail removal 12 to 18 months after surgery.',
		'performedBy'   => pll_seo_performed_by(),
	);
}

/**
 * Pillar-page MedicalProcedure for /leg-lengthening-surgery/. Owns the
 * consumer head terms leg lengthening surgery / leg extension surgery (never
 * the clinical umbrella "limb lengthening surgery", which belongs to
 * /your-surgery/) to keep the hub-and-spoke relationship free of cannibalization.
 *
 * @return array
 */
function pll_seo_leg_procedure() {
	return array(
		'@context'      => 'https://schema.org',
		'@type'         => 'MedicalProcedure',
		'name'          => 'Leg Lengthening Surgery',
		'alternateName' => array( 'Leg Extension Surgery', 'Cosmetic Leg Lengthening' ),
		'procedureType' => 'https://schema.org/SurgicalProcedure',
		'bodyLocation'  => array( 'Femur', 'Tibia' ),
		'description'   => 'Leg lengthening surgery increases the length of the thigh bone, the shin bone, or both using an internal nail. A single femur procedure typically adds 2 to 3.5 inches, and a combined femur and tibia approach can add 4 to 6 inches total, as a combined surgery or staged as two separate surgeries.',
		'howPerformed'  => 'The surgeon performs a controlled osteotomy and inserts an internal telescopic nail (PRECICE Max or PRECICE 2). The nail is extended about 1 millimeter per day with an external controller while the body regenerates bone in the gap through distraction osteogenesis.',
		'preparation'   => 'Physical exam, full-length standing X-rays, and blood work, with the target length agreed between surgeon and patient.',
		'followup'      => 'Physical therapy throughout the lengthening and consolidation phases, staged follow-up imaging, and eventual nail removal.',
		'performedBy'   => pll_seo_performed_by(),
	);
}

/**
 * Evaluate-Your-Surgeon FAQ (12 Q/A). Must match the visible on-page FAQ text
 * verbatim (the pll/evaluate-page pattern), per the post-deprecation FAQPage
 * rule. If the on-page FAQ is edited, update this list to keep schema in sync.
 *
 * @return array<int, array{q: string, a: string}>
 */
function pll_seo_evaluate_faqs() {
	return array(
		array(
			'q' => 'What questions should I ask a limb lengthening surgeon?',
			'a' => "Ask about fellowship training, total lengthening procedure volume, which nail system and generation they use, whether they accept revision cases, and their written complication management protocol. Then evaluate responsiveness, thoroughness, and post-op support during the consultation itself. The evaluation tool on this page places every question next to the criterion it informs, with space to record each surgeon's answers, and the full question list can be printed from the page for paper use.",
		),
		array(
			'q' => 'How do I choose a limb lengthening surgeon?',
			'a' => 'Compare surgeons on the same criteria rather than on impressions. Score each surgeon on clinical qualifications, including fellowship training, procedure volume, device expertise, revision capability, and complication management, and on patient experience factors, including communication, thoroughness, and post-op support. The surgeon who scores well across both categories is the stronger choice.',
		),
		array(
			'q' => 'What credentials should a limb lengthening surgeon have?',
			'a' => 'Board certification in orthopedic surgery is the baseline. Fellowship training in orthopedic trauma, deformity correction, or limb reconstruction indicates the subspecialty depth to manage complications and revisions. Hospital affiliation matters because it determines where you go if something requires emergency care.',
		),
		array(
			'q' => 'How many limb lengthening procedures should a surgeon have performed?',
			'a' => 'There is no single threshold, but a surgeon should share their numbers without hesitation. Volume across cosmetic, trauma, and revision cases matters more than cosmetic volume alone, because complication management skill comes from breadth of practice. A surgeon who declines to discuss volume is a signal in itself.',
		),
		array(
			'q' => 'Why does revision surgery capability matter when choosing a surgeon?',
			'a' => 'A surgeon who accepts and corrects failed lengthenings from other practices has demonstrated the reconstruction skill to manage the hardest cases. If your own procedure develops a complication, you want to already be in the hands of a surgeon who fixes these problems, rather than one who refers them out.',
		),
		array(
			'q' => 'Should I choose a limb lengthening surgeon based on price?',
			'a' => 'Price transparency matters. Price alone does not. A fully itemized quote that includes the implant, hospitalization, anesthesia, therapy, and follow-up care lets you compare offers accurately. A low headline price with unlisted costs is a common source of unexpected expense, and the cost of correcting a failed procedure can exceed the original surgery.',
		),
		array(
			'q' => 'What are red flags when choosing a limb lengthening surgeon?',
			'a' => 'Common red flags include refusing to share procedure volume, dismissing complication questions, having no written recovery protocol, declining all revision cases, guaranteeing outcomes, and pressure to book quickly. Limb lengthening is a months-long commitment, and a credible surgeon treats the decision with corresponding weight.',
		),
		array(
			'q' => 'Which lengthening device should the surgeon use?',
			'a' => "For cosmetic lengthening, the current standard is a fully internal magnetic nail such as the PRECICE system, which lengthens inside the bone with no external frame or pins through the skin. Ask which system and generation the surgeon implants, why they selected it, and how they speak to the device category's full history, including the recalled Stryde nail. A surgeon who cannot explain their own hardware has not earned your confidence.",
		),
		// De-cannibalization: the "cost", "recovery timeline", and "how much
		// taller" FAQs are intentionally NOT emitted in this page's FAQPage
		// schema — those FAQ rich-result entries belong to the pricing page,
		// the your-surgery / how-much-taller sub-page, and the homepage. They
		// remain VISIBLE in the pll/evaluate-page pattern; only the JSON-LD is
		// trimmed (a schema subset of visible FAQs is allowed). See
		// docs/EVALUATE_SEO_AUDIT.md.
		array(
			'q' => 'Does this tool send my scores to Premier Limb Lengthening?',
			'a' => 'No. All scoring data stays in your browser. Premier Limb Lengthening receives information only if you actively choose to email your results to yourself or opt into educational content, and in those cases it receives only your email address and the results you chose to send.',
		),
	);
}

/**
 * MedicalWebPage for /evaluate-your-surgeon/ (reviewed by Dr. Basmajian).
 * Port of medicalWebPageSchema() in app/evaluate-your-surgeon/page.tsx.
 *
 * @return array
 */
function pll_seo_evaluate_medical_webpage() {
	$url = PLL_SEO_ORIGIN . '/evaluate-your-surgeon/';
	return array(
		'@context'    => 'https://schema.org',
		'@type'       => 'MedicalWebPage',
		'name'        => 'How to Evaluate a Limb Lengthening Surgeon',
		'url'         => $url,
		'description' => 'A structured 10-criterion framework for evaluating limb lengthening surgeons across clinical qualifications and patient experience factors, with an interactive scoring tool, per-criterion answer notes, and a printable question list.',
		'about'       => array(
			'@type'         => 'MedicalProcedure',
			'name'          => 'Limb Lengthening Surgery',
			'procedureType' => 'Surgical',
			'bodyLocation'  => 'Femur, Tibia',
		),
		'reviewedBy'  => array(
			'@type'               => 'Physician',
			// Same @id as pll_seo_physician() / pll_seo_performed_by(), so this
			// richer literal resolves to the one Physician entity in the graph.
			'@id'                 => PLL_SEO_ORIGIN . '/dr-basmajian#physician',
			'name'                => 'Hrayr Basmajian, MD, MS',
			'medicalSpecialty'    => 'Orthopedic Surgery',
			'jobTitle'            => array( 'Founder, Premier Limb Lengthening', 'Director, Orthopedic Trauma, PVHMC' ),
			'hospitalAffiliation' => 'Pomona Valley Hospital Medical Center',
			'alumniOf'            => array( 'USC/LAC Medical Center', 'Sonoran Orthopaedic Trauma Surgeons, Scottsdale', 'Hannover Medical School' ),
			'knowsLanguage'       => array( 'en', 'hy', 'es' ),
			'url'                 => PLL_SEO_ORIGIN . '/dr-basmajian/',
			'sameAs'              => array( PLL_SEO_ORIGIN . '/dr-basmajian/' ),
		),
		'publisher'   => array(
			'@type'     => 'MedicalOrganization',
			'name'      => 'Premier Limb Lengthening',
			'url'       => PLL_SEO_ORIGIN . '/',
			'telephone' => '+1-951-620-5663',
			'address'   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => '400 N. Mountain Ave. Suite 305',
				'addressLocality' => 'Upland',
				'addressRegion'   => 'CA',
				'postalCode'      => '91786',
				'addressCountry'  => 'US',
			),
		),
	);
}

/**
 * HowTo for /evaluate-your-surgeon/ — the five-step evaluation process.
 * Port of howToSchema() in app/evaluate-your-surgeon/page.tsx.
 *
 * @return array
 */
function pll_seo_evaluate_howto() {
	return array(
		'@context'    => 'https://schema.org',
		'@type'       => 'HowTo',
		'name'        => 'How to Evaluate a Limb Lengthening Surgeon',
		'description' => 'A structured process for comparing limb lengthening surgeons using 10 scored criteria across clinical qualifications and patient experience, with consultation questions and answer notes built into each criterion.',
		'step'        => array(
			array(
				'@type'    => 'HowToStep',
				'position' => 1,
				'name'     => 'Add each surgeon you are considering',
				'text'     => 'Enter each surgeon\'s name in the evaluation tool. Each surgeon gets an independent scorecard containing the full question list, space to record their answers, and a 1 to 5 score for every criterion. A printable version of the question list is available for paper use.',
			),
			array(
				'@type'    => 'HowToStep',
				'position' => 2,
				'name'     => 'Ask the questions and record each answer',
				'text'     => 'Every criterion card contains the questions to ask, or what to notice for observational criteria. Record each surgeon\'s answer in the notes field on that card, during the consultation or after.',
			),
			array(
				'@type'    => 'HowToStep',
				'position' => 3,
				'name'     => 'Score clinical criteria from your research',
				'text'     => 'Score fellowship training, procedure volume, device expertise, revision capability, and complication management using published information and the answers you recorded.',
			),
			array(
				'@type'    => 'HowToStep',
				'position' => 4,
				'name'     => 'Score patient experience criteria after each consultation',
				'text'     => 'Score bedside manner, communication responsiveness, thoroughness, comfort level, and post-op support clarity once you have met the surgeon and team.',
			),
			array(
				'@type'    => 'HowToStep',
				'position' => 5,
				'name'     => 'Compare surgeons side by side',
				'text'     => 'Review the summary dashboard, compare section subtotals and total scores, and identify what still needs evaluation before deciding.',
			),
		),
	);
}

/**
 * Pricing ItemList.
 *
 * @return array
 */
function pll_seo_pricing_schema() {
	$elements = array();
	foreach ( pll_seo_pricing_tiers() as $i => $tier ) {
		$elements[] = array(
			'@type'    => 'Service',
			'position' => $i + 1,
			'name'     => $tier['name'],
			'offers'   => array(
				'@type'         => 'Offer',
				'priceCurrency' => 'USD',
				'price'         => preg_replace( '/[^0-9]/', '', $tier['price'] ),
				'availability'  => 'https://schema.org/InStock',
			),
			'provider' => array( '@id' => PLL_SEO_ORIGIN . '/#organization' ),
		);
	}
	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => 'Limb Lengthening Procedure Pricing',
		'itemListElement' => $elements,
	);
}

/**
 * CollectionPage for the blog index / category archives.
 *
 * @param string    $name  Collection name.
 * @param WP_Post[] $posts Posts, newest first.
 * @return array
 */
function pll_seo_collection( $name, $posts ) {
	$elements = array();
	foreach ( $posts as $i => $post ) {
		$title = (string) get_post_meta( $post->ID, '_pll_seo_title', true );
		if ( ! $title ) {
			$title = get_the_title( $post );
		}
		$elements[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'url'      => PLL_SEO_ORIGIN . wp_parse_url( get_permalink( $post ), PHP_URL_PATH ),
			'name'     => $title,
		);
	}
	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'CollectionPage',
		'name'       => $name,
		'mainEntity' => array(
			'@type'           => 'ItemList',
			'itemListElement' => $elements,
		),
	);
}

/**
 * Schemas for the current view, mirroring the JsonLd usage per route.
 *
 * @return array[]
 */
function pll_seo_schemas_for_view() {
	$schemas = array( pll_seo_site_graph() );

	// Noindex items remain usable pages, but publish no page-specific schema,
	// including FAQPage. The organization graph remains site-wide.
	if ( is_singular() && pll_seo_is_noindex() ) {
		return $schemas;
	}

	if ( is_front_page() ) {
		$schemas[] = pll_seo_faq_schema();
		$schemas[] = pll_seo_home_procedure();
		return $schemas;
	}

	if ( is_home() ) {
		$posts     = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$schemas[] = pll_seo_collection( 'Limb Lengthening Blog', $posts );
		$schemas[] = pll_seo_breadcrumb(
			array(
				array(
					'name' => 'Home',
					'url'  => '/',
				),
				array(
					'name' => 'Blog',
					'url'  => '/blog/',
				),
			)
		);
		return $schemas;
	}

	if ( is_category() ) {
		$term      = get_queried_object();
		$posts     = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
				'category__in'   => array( $term->term_id ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$label     = $term->name;
		$schemas[] = pll_seo_collection( $label . ' · Limb Lengthening Articles', $posts );
		$schemas[] = pll_seo_breadcrumb(
			array(
				array(
					'name' => 'Home',
					'url'  => '/',
				),
				array(
					'name' => 'Blog',
					'url'  => '/blog/',
				),
				array(
					'name' => $label,
					'url'  => '/category/' . $term->slug . '/',
				),
			)
		);
		return $schemas;
	}

	if ( is_singular( 'post' ) ) {
		$post   = get_post( get_queried_object_id() );
		$cat    = pll_seo_primary_category_for( $post );
		$crumbs = array(
			array(
				'name' => 'Home',
				'url'  => '/',
			),
			array(
				'name' => 'Blog',
				'url'  => '/blog/',
			),
		);
		if ( $cat ) {
			$crumbs[] = array(
				'name' => $cat->name,
				'url'  => '/category/' . $cat->slug . '/',
			);
		}
		$title     = (string) get_post_meta( $post->ID, '_pll_seo_title', true );
		$crumbs[]  = array(
			'name' => $title ? $title : get_the_title( $post ),
			'url'  => '/' . $post->post_name . '/',
		);
		$schemas[] = pll_seo_article( $post );
		$schemas[] = pll_seo_breadcrumb( $crumbs );
		// FAQPage for the clinical articles that carry a "Patients Also Ask" block.
		$post_qas = pll_seo_paa( '/' . $post->post_name . '/' );
		if ( $post_qas ) {
			$schemas[] = pll_seo_faqpage_from( $post_qas );
		}
		return $schemas;
	}

	if ( is_page() ) {
		$post = get_post( get_queried_object_id() );
		$path = pll_seo_current_path();

		// MedicalWebPage rides ALONGSIDE the per-page entity below, never
		// instead of it: the branches that follow are one if/elseif chain, so
		// an added branch would silently suppress the pillar pages'
		// MedicalProcedure. Emitted only for paths with a documented sign-off
		// in pll_seo_review_dates(), the same gate as the visible byline.
		$review_dates = pll_seo_review_dates();
		if ( isset( $review_dates[ $path ] ) ) {
			$schemas[] = pll_seo_medical_webpage( $path, $review_dates[ $path ] );
		}

		if ( '/dr-basmajian/' === $path ) {
			$schemas[] = pll_seo_physician();
		} elseif ( '/your-surgery/' === $path ) {
			$schemas[] = pll_seo_procedure();
		} elseif ( '/height-surgery/' === $path ) {
			// Pillars carry MedicalProcedure plus the MedicalWebPage emitted
			// above (no visible Q/A, so no FAQPage).
			$schemas[] = pll_seo_height_procedure();
		} elseif ( '/leg-lengthening-surgery/' === $path ) {
			$schemas[] = pll_seo_leg_procedure();
		} elseif ( '/limb-lengthening-pricing-options/' === $path ) {
			$schemas[] = pll_seo_pricing_schema();
			$schemas[] = pll_seo_pricing_procedures();
			// Guarded like the article and sub-page branches: an unguarded call
			// publishes an empty FAQPage (mainEntity: []) the moment the pricing
			// key goes missing from data/paa.php.
			$pricing_qas = pll_seo_paa( $path );
			if ( $pricing_qas ) {
				$schemas[] = pll_seo_faqpage_from( $pricing_qas );
			}
		} elseif ( '/evaluate-your-surgeon/' === $path ) {
			// Interactive surgeon-evaluation guide: MedicalWebPage (reviewed by
			// Dr. Basmajian) + HowTo (5-step process) + FAQPage. Port of the
			// JsonLd usage in app/evaluate-your-surgeon/page.tsx.
			$schemas[] = pll_seo_evaluate_medical_webpage();
			$schemas[] = pll_seo_evaluate_howto();
			$schemas[] = pll_seo_faqpage_from( pll_seo_evaluate_faqs() );
		} elseif ( $post->post_parent ) {
			$parent = get_post( $post->post_parent );
			if ( $parent && 'your-surgery' === $parent->post_name ) {
				$schemas[] = pll_seo_article( $post );
				$schemas[] = pll_seo_breadcrumb(
					array(
						array(
							'name' => 'Home',
							'url'  => '/',
						),
						array(
							'name' => 'Your Surgery',
							'url'  => '/your-surgery/',
						),
						array(
							'name' => get_the_title( $post ),
							'url'  => $path,
						),
					)
				);
				// FAQPage for the your-surgery sub-page that carries a PAA block.
				$sub_qas = pll_seo_paa( $path );
				if ( $sub_qas ) {
					$schemas[] = pll_seo_faqpage_from( $sub_qas );
				}
				$visible_faq_schema = pll_seo_visible_faq_schema_for_page( $post, $path );
				if ( $visible_faq_schema ) {
					$schemas[] = $visible_faq_schema;
				}
				return $schemas;
			}
		} elseif ( in_array( $path, array( '/privacy/', '/terms/', '/accessibility/' ), true ) ) {
			$schemas[] = array(
				'@context'   => 'https://schema.org',
				'@type'      => 'WebPage',
				'name'       => get_the_title( $post ),
				'url'        => PLL_SEO_ORIGIN . $path,
				'inLanguage' => 'en-US',
				'isPartOf'   => array( '@id' => PLL_SEO_ORIGIN . '/#website' ),
				'publisher'  => array( '@id' => PLL_SEO_ORIGIN . '/#organization' ),
			);
		}

		$visible_faq_schema = pll_seo_visible_faq_schema_for_page( $post, $path );
		if ( $visible_faq_schema ) {
			$schemas[] = $visible_faq_schema;
		}

		$schemas[] = pll_seo_breadcrumb(
			array(
				array(
					'name' => 'Home',
					'url'  => '/',
				),
				array(
					'name' => get_the_title( $post ),
					'url'  => $path,
				),
			)
		);
		return $schemas;
	}

	return $schemas;
}

/**
 * First category of a post (the content model assigns one).
 *
 * @param WP_Post $post Post.
 * @return WP_Term|null
 */
function pll_seo_primary_category_for( $post ) {
	$terms = get_the_category( $post->ID );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

add_action(
	'wp_head',
	function () {
		if ( is_404() || is_search() ) {
			// Only the site graph on utility views.
			$schemas = array( pll_seo_site_graph() );
		} else {
			$schemas = pll_seo_schemas_for_view();
		}
		foreach ( $schemas as $schema ) {
			echo '<script type="application/ld+json">' .
				wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) .
				'</script>' . "\n";
		}
	},
	5
);
