<?php
/**
 * "Patients Also Ask" (PAA) question/answer sets, keyed by page path.
 *
 * SINGLE SOURCE OF TRUTH for both (a) the FAQPage JSON-LD emitted by
 * includes/schema.php and (b) the visible pll/faq accordion blocks injected
 * by the theme's content/setup.php (pll_render_paa_blocks() reads this same
 * data). This structurally satisfies the SEO audit's hard rule that schema
 * text must match visible text verbatim — change the copy here and both the
 * markup and the schema update together.
 *
 * Content is verbatim from PLL_Content_Schema_Package.docx (answer-first,
 * mapped to real GSC queries). Brand rules honored: no em dashes, no
 * semicolons, no promotional adjectives inside answer text (post-deprecation
 * FAQPage compliance).
 *
 * @package pll-seo
 */

defined( 'ABSPATH' ) || exit;

/**
 * PAA question/answer sets keyed by root-relative path (trailing slash).
 *
 * @return array<string, array<int, array{q: string, a: string}>>
 */
function pll_seo_paa_all() {
	return array(
		'/your-surgery/how-much-taller-can-i-get-with-limb-lengthening/' => array(
			array(
				'q' => 'How many inches can limb lengthening surgery add?',
				'a' => 'A single femur procedure typically adds 2 to 3 inches. A second staged procedure on the tibia can add another 2 to 3 inches, for a combined total of 4 to 6 inches. Attempting to lengthen more than 3 inches in one bone segment increases complication risk and is not recommended by most experienced surgeons.',
			),
			array(
				'q' => 'Does limb lengthening surgery make you look disproportionate?',
				'a' => 'When performed within the recommended range of 2 to 3 inches per segment, the result looks natural. The muscles, tendons, and skin adapt gradually alongside the bone during the lengthening phase. Disproportionality becomes a concern only when patients pursue aggressive lengthening beyond clinically recommended limits.',
			),
			array(
				'q' => 'How much taller can surgery make you?',
				'a' => 'Most patients gain 2 to 3 inches from femur lengthening alone, which is the most commonly performed procedure. Combined femur and tibia procedures can achieve 4 to 6 inches total, staged over two separate surgeries approximately 12 months apart.',
			),
			array(
				'q' => 'Can you grow taller after your growth plates close?',
				'a' => 'Natural height growth stops after the growth plates close, typically in the late teens or early twenties. Limb lengthening surgery is the only medically established method for permanently increasing height in adults. The procedure works by creating new bone through distraction osteogenesis, which does not depend on growth plate activity.',
			),
		),

		'/limb-lengthening-pricing-options/' => array(
			array(
				'q' => 'How much does limb lengthening surgery cost in the USA?',
				'a' => 'Costs range widely depending on the surgeon, the nail technology used, and what is included in the quote. The most important factor when comparing pricing is whether the quoted figure includes the implant, anesthesia, hospitalization, physical therapy, and follow-up care, or whether those are billed separately. Premier Limb Lengthening publishes all-in pricing that covers every component of the surgical journey.',
			),
			array(
				'q' => 'Is limb lengthening surgery covered by insurance?',
				'a' => 'Cosmetic limb lengthening is not covered by health insurance. Insurance may cover the procedure in cases of documented medical necessity, such as a significant limb length discrepancy or congenital condition, but this is evaluated on a case-by-case basis and requires prior authorization. Financing through SoFi and CareCredit is available for qualified applicants.',
			),
			array(
				'q' => 'Why do limb lengthening surgery prices vary so much?',
				'a' => 'Price variation is almost always explained by what is included in the quote. A practice quoting a lower base fee may exclude the implant cost, anesthesia fees, facility charges, physical therapy, and follow-up imaging. The total out-of-pocket cost after all add-ons can exceed a higher all-in quote. Additionally, the nail technology matters. Quotes using the PRECICE 2 will generally be lower than quotes using the PRECICE Max because the devices have different costs.',
			),
		),

		'/limb-lengthening-what-you-gain-what-you-risk/' => array(
			array(
				'q' => 'Is limb lengthening surgery safe?',
				'a' => 'The procedure has a high success rate when performed by an experienced surgeon and when patients follow the prescribed physical therapy and follow-up protocol. Risks include joint stiffness, delayed bone healing, nerve irritation, and, in rare cases, infection or hardware failure. The most important safety factor is surgeon selection. Fellowship training in orthopedic trauma or limb reconstruction indicates the depth of experience to manage the full spectrum of outcomes, including complications.',
			),
			array(
				'q' => 'Does limb lengthening surgery make you disabled?',
				'a' => 'No. Patients who undergo cosmetic limb lengthening and follow their rehabilitation protocol return to full activity. Most patients are walking without assistive devices within 3 to 4 months of completing the lengthening phase. Return to running and sports typically occurs within 6 to 12 months. The procedure does not result in disability when performed within recommended limits by a qualified surgeon.',
			),
			array(
				'q' => 'Is limb lengthening surgery worth it?',
				'a' => 'This is a personal decision that depends on individual goals, financial readiness, and willingness to commit to the recovery timeline. The procedure requires 6 to 12 months of active recovery per bone segment. Patients who report the highest satisfaction are those who had realistic expectations, chose an experienced surgeon, and committed fully to physical therapy. A thorough consultation is the best way to determine whether the procedure aligns with your goals.',
			),
		),

		'/is-leg-lengthening-off-limits-for-athletes/' => array(
			array(
				'q' => 'Can you run after limb lengthening surgery?',
				'a' => 'Most patients return to running 6 to 12 months after surgery, once the bone has fully consolidated and muscle strength has been restored through physical therapy. The timeline depends on the individual\'s healing rate, the bone lengthened (femur typically recovers faster than tibia for running), and compliance with rehabilitation. Some patients return to competitive athletics, though this should be discussed directly with the operating surgeon.',
			),
			array(
				'q' => 'Can you play sports after leg lengthening surgery?',
				'a' => 'Yes, most patients return to recreational and competitive sports after full recovery. The specific timeline varies by sport. Low-impact activities (swimming, cycling) are typically cleared earlier than high-impact activities (running, basketball, football). Dr. Basmajian evaluates each patient\'s activity goals during the consultation and provides a realistic return-to-sport timeline based on the planned procedure.',
			),
			array(
				'q' => 'Can you play football after limb lengthening surgery?',
				'a' => 'Return to contact sports like football is possible but requires full bone consolidation, restored muscle strength, and clearance from the operating surgeon. This typically occurs 9 to 12 months post-surgery at minimum. The decision depends on the specific demands of the sport, the bone segment lengthened, and the total distance lengthened. Conservative lengthening distances (2 to 3 inches) have better outcomes for returning to high-demand physical activity.',
			),
		),

		'/limb-lengthening-pain-the-truth/' => array(
			array(
				'q' => 'How painful is limb lengthening surgery?',
				'a' => 'Immediate post-operative pain is the most intense period, typically lasting 3 to 5 days, and is managed with prescribed medication. During the lengthening phase, most patients describe the sensation as deep muscle aching or tightness rather than sharp surgical pain. The daily 1mm nail activation itself is typically not painful. Pain tolerance varies significantly between patients, and your surgeon should discuss a personalized pain management plan during the consultation.',
			),
			array(
				'q' => 'Does height surgery hurt?',
				'a' => 'The surgery itself is performed under general anesthesia, so there is no pain during the procedure. Post-operative discomfort is managed with medication and typically improves significantly within the first two weeks. The lengthening phase involves progressive muscle tightness that is managed primarily through physical therapy. Most patients describe the experience as challenging but manageable.',
			),
			array(
				'q' => 'How much pain is there during the lengthening phase?',
				'a' => 'Pain during the lengthening phase is predominantly muscular. As the bone lengthens by approximately 1 millimeter per day, the surrounding muscles stretch to accommodate the new length. This creates a sensation of tightness and aching that increases gradually as the total lengthening distance grows. Physical therapy is the primary management tool. Patients who maintain consistent therapy sessions report lower pain levels and better outcomes.',
			),
		),

		'/is-limb-lengthening-covered-by-insurance/' => array(
			array(
				'q' => 'Does insurance cover leg lengthening surgery?',
				'a' => 'Health insurance does not cover cosmetic leg lengthening surgery. The procedure is considered elective when performed for height augmentation in otherwise healthy individuals. In cases of documented medical necessity, such as a congenital limb length discrepancy or condition causing functional impairment, insurance may cover part or all of the cost, but this requires prior authorization and is evaluated on a case-by-case basis.',
			),
			array(
				'q' => 'How do people pay for limb lengthening surgery?',
				// No payment figure and no APR here. The "as low as $1,200"
				// claim was removed 2026-09-07 at the owner's request, pending
				// approved CareCredit material. This answer is published twice,
				// as visible copy and as FAQPage JSON-LD, so a financing term
				// stated here is a term advertised in a search result. Do not
				// reinstate one without written sign-off.
				'a' => 'Most patients pay through a combination of savings and medical financing. Premier Limb Lengthening works with financing partners including SoFi and CareCredit, and terms depend on the applicant and the lender. Payment plans are structured before the surgery date so patients have clarity on their financial commitment before proceeding.',
			),
			array(
				'q' => 'Is cosmetic limb lengthening tax deductible?',
				'a' => 'Cosmetic procedures are generally not tax deductible as medical expenses. However, if the procedure is performed to correct a documented medical condition (such as a limb length discrepancy), it may qualify as a deductible medical expense. Patients should consult a qualified tax professional for advice specific to their situation. Premier Limb Lengthening provides detailed receipts and documentation for all procedures.',
			),
		),
	);
}

/**
 * PAA set for a single path, or empty array if none.
 *
 * @param string $path Root-relative path with trailing slash.
 * @return array<int, array{q: string, a: string}>
 */
function pll_seo_paa( $path ) {
	$all = pll_seo_paa_all();
	return isset( $all[ $path ] ) ? $all[ $path ] : array();
}
