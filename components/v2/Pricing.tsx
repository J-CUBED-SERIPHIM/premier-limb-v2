"use client";

import { motion } from "framer-motion";
import { Reveal } from "./Reveal";
import { plans } from "@/lib/pricing-plans";

// Homepage teaser: the first three tiers from the canonical pricing data
// (single source of truth shared with /limb-lengthening-pricing-options) so the
// homepage cards can never drift out of sync with the full pricing page again.
const homePlans = plans.slice(0, 3);

export function Pricing() {
  return (
    <section id="pricing" className="bg-paper-off py-20 lg:py-28">
      <div className="mx-auto max-w-wrap px-6 lg:px-12">
        <Reveal>
          <header className="pb-8 mb-6">
            <span className="eyebrow mb-4">Transparent Pricing</span>
            <h2
              className="mt-4 font-serif font-normal tracking-[-0.02em] text-ink leading-[0.98]"
              style={{ fontSize: "clamp(40px, 6vw, 84px)" }}
            >
              Limb lengthening <em className="italic text-spine">surgery cost.</em>
            </h2>
          </header>
        </Reveal>

        <Reveal>
          <p className="max-w-[60ch] text-[15px] leading-[1.7] text-ink-soft mb-10">
            No hidden fees. Every quote includes surgery, implant, anesthesia,
            hospitalization, and follow-up care.
          </p>
        </Reveal>

        {/* Mobile: edge-bled swipe row (client request); md+ keeps the grid. */}
        <div className="grid grid-cols-1 md:grid-cols-3 border-t border-b border-ink max-md:flex max-md:overflow-x-auto max-md:snap-x max-md:snap-mandatory max-md:-mx-6 max-md:px-6 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          {homePlans.map((p, i) => (
            <motion.article
              key={p.bar}
              initial={{ opacity: 0, y: 18 }}
              whileInView={{ opacity: 1, y: 0 }}
              whileHover={{ y: -6 }}
              viewport={{ once: true, margin: "-10% 0px" }}
              transition={{ duration: 0.6, delay: i * 0.08, ease: [0.2, 0.65, 0.3, 1] }}
              className={`
                relative py-10 px-7 md:px-0 md:pr-7
                max-md:w-[88%] max-md:shrink-0 max-md:snap-start
                ${i > 0 ? "md:pl-7" : ""}
                ${i < homePlans.length - 1 ? "md:border-r border-rule" : ""}
                ${i > 0 ? "max-md:border-l border-rule" : ""}
                ${"featured" in p && p.featured ? "bg-paper md:px-7" : "bg-paper-off"}
              `}
            >
              <div className="mb-5 flex items-center gap-2.5 font-mono uppercase tracking-[0.22em] text-[10.5px] text-muted">
                {"featured" in p && p.featured && "badge" in p && p.badge && (
                  <span className="bg-spine text-paper font-medium px-2.5 py-1 tracking-[0.22em]">
                    {p.badge}
                  </span>
                )}
                <span>{p.bar}</span>
              </div>
              <h3 className="font-serif font-medium text-[28px] lg:text-[30px] leading-[1.1] tracking-[-0.01em] text-ink mb-1.5">
                {p.title}
              </h3>
              <div className="font-serif italic text-[15px] text-muted mb-7">{p.gen}</div>
              <div className="pt-6 border-t border-rule">
                <div className="font-serif text-[52px] lg:text-[62px] leading-none tracking-[-0.025em] text-ink">
                  {p.price}
                </div>
                <div className="mt-2 mb-7 font-mono uppercase tracking-[0.2em] text-[10.5px] text-muted">
                  {p.from}
                </div>
              </div>
              <ul className="border-t border-rule">
                {p.features.map((f) => (
                  <li
                    key={f}
                    className="grid grid-cols-[20px_1fr] gap-1.5 items-baseline py-3 border-b border-rule text-[13.5px] text-ink-soft"
                  >
                    <span aria-hidden className="font-serif font-medium text-spine text-[15px]">+</span>
                    <span>{f}</span>
                  </li>
                ))}
              </ul>
            </motion.article>
          ))}
        </div>

        <Reveal className="pt-8 flex flex-col lg:flex-row lg:justify-between lg:items-center gap-5">
          <p className="max-w-[60ch] text-[13px] text-muted">
            Financing options are available. We will walk you through them
            during your consultation.
          </p>
          <a
            href="/limb-lengthening-pricing-options"
            className="group self-start lg:self-auto inline-flex items-center gap-3 px-5 py-3.5 bg-spine text-paper uppercase tracking-wide text-[12px] font-medium hover:bg-spine-deep transition-colors"
          >
            View Limb Lengthening Surgery Costs
            <span className="font-serif italic text-[17px] transition-transform group-hover:translate-x-1" aria-hidden>→</span>
          </a>
        </Reveal>
      </div>
    </section>
  );
}
