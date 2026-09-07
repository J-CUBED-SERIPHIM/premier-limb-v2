<?php
/**
 * Title: Pricing — Financing
 * Slug: pll/pricing-financing
 * Categories: pll-sections
 *
 * Port of components/v2/pricing/Financing.tsx. Keeps the #financing
 * anchor (the footer links to /limb-lengthening-pricing-options/#financing).
 *
 * @package pll-editorial
 */

?>
<!-- wp:group {"tagName":"section","layout":{"type":"default"},"templateLock":"contentOnly","anchor":"financing","className":"bg-spine text-paper py-24 lg:py-32"} -->
<section class="wp-block-group bg-spine text-paper py-24 lg:py-32" id="financing">
	<!-- wp:group {"layout":{"type":"default"},"className":"mx-auto max-w-wrap px-6 lg:px-12 grid grid-cols-12 gap-6 lg:gap-8 items-end"} -->
	<div class="wp-block-group mx-auto max-w-wrap px-6 lg:px-12 grid grid-cols-12 gap-6 lg:gap-8 items-end">
		<!-- wp:group {"layout":{"type":"default"},"className":"js-reveal col-span-12 lg:col-span-7"} -->
		<div class="wp-block-group js-reveal col-span-12 lg:col-span-7">
			<!-- wp:paragraph {"className":"font-mono uppercase text-[11px] tracking-[0.2em] inline-flex items-center gap-3 text-gold"} -->
			<p class="font-mono uppercase text-[11px] tracking-[0.2em] inline-flex items-center gap-3 text-gold"><span aria-hidden="true" class="inline-block w-[22px] h-px bg-gold"></span>Financing</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":2,"className":"mt-5 font-serif font-medium tracking-[-0.03em] text-paper leading-[0.98] text-[clamp(40px,6vw,88px)]"} -->
			<h2 class="wp-block-heading mt-5 font-serif font-medium tracking-[-0.03em] text-paper leading-[0.98] text-[clamp(40px,6vw,88px)]">Pay it down over time, <em class="italic text-gold">not all at once.</em></h2>
			<!-- /wp:heading -->

		</div>
		<!-- /wp:group -->

		<!-- wp:group {"layout":{"type":"default"},"className":"js-reveal pll-delay-100 col-span-12 lg:col-span-5 lg:pl-6 lg:border-l border-white/25"} -->
		<div class="wp-block-group js-reveal pll-delay-100 col-span-12 lg:col-span-5 lg:pl-6 lg:border-l border-white/25">
			<!-- wp:paragraph {"className":"font-serif italic text-[20px] lg:text-[22px] text-paper/95 mb-7 leading-[1.3]"} -->
			<p class="font-serif italic text-[20px] lg:text-[22px] text-paper/95 mb-7 leading-[1.3]">We have partnered with SoFi and CareCredit so you can spread the cost over time. We’ll walk you through the options during your consultation. No obligation, no sales pressure.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"className":"flex flex-col gap-3 items-start"} -->
			<div class="wp-block-buttons flex flex-col gap-3 items-start"><!-- wp:pll/cta {"label":"Apply with SoFi<span class=\"font-serif italic text-[17px] transition-transform group-hover:translate-x-1\" aria-hidden=\"true\">↗</span>","url":"https://www.sofi.com/personal-loans/family-planning/","linkClass":"wp-block-button__link wp-element-button group inline-flex items-center gap-3 px-5 py-3.5 uppercase tracking-wide text-[12px] font-medium text-ink hover:bg-paper transition-colors bg-gold","newTab":true} /--><!-- wp:pll/cta {"label":"Apply with CareCredit<span class=\"font-serif italic text-[17px] transition-transform group-hover:translate-x-1\" aria-hidden=\"true\">↗</span>","url":"https://www.carecredit.com/","linkClass":"wp-block-button__link wp-element-button group inline-flex items-center gap-3 px-5 py-3.5 bg-transparent text-paper border border-white/40 uppercase tracking-wide text-[12px] font-medium hover:bg-paper hover:text-spine hover:border-paper transition-colors","newTab":true} /--></div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
