"use client";

import { motion } from "framer-motion";
import { Reveal } from "../Reveal";
import { plans } from "@/lib/pricing-plans";

export function PricingPlans() {
  return (
    <section id="plans" className="bg-paper-off py-20 lg:py-28">
      <div className="mx-auto max-w-wrap px-6 lg:px-12">
        <Reveal>
          <header className="pb-8 mb-12 border-b border-ink grid grid-cols-12 gap-6 items-end">
            <div className="col-span-12 lg:col-span-8">
              <span className="eyebrow mb-4">By Procedure</span>
              <h2
                className="mt-4 font-serif font-normal tracking-[-0.02em] text-ink leading-[0.98]"
                style={{ fontSize: "clamp(36px, 5.4vw, 76px)" }}
              >
                Three procedures. <em className="italic text-spine">PRECICE MAX cost.</em>
              </h2>
            </div>
            <p className="hidden lg:block col-span-4 text-[14.5px] leading-[1.65] text-ink-soft">
              Each price is the complete surgical bundle: implant, OR time,
              anesthesia, recovery oversight, and on-site sessions.
              Accommodation and home health care are billed separately.
            </p>
          </header>
        </Reveal>

        {/* Mobile: edge-bled swipe row (client request); md+ keeps the grid. */}
        <div className="grid grid-cols-1 md:grid-cols-3 border-t border-b border-ink max-md:flex max-md:overflow-x-auto max-md:snap-x max-md:snap-mandatory max-md:-mx-6 max-md:px-6 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          {plans.map((p, i) => {
            const isFeatured = "featured" in p && p.featured;
            return (
              <motion.article
                key={p.title}
                initial={{ opacity: 0, y: 18 }}
                whileInView={{ opacity: 1, y: 0 }}
                whileHover={{ y: -6 }}
                viewport={{ once: true, margin: "-10% 0px" }}
                transition={{ duration: 0.6, delay: (i % 3) * 0.08, ease: [0.2, 0.65, 0.3, 1] }}
                className={`
                  relative py-10 px-7 md:px-0 md:pr-7
                  max-md:w-[88%] max-md:shrink-0 max-md:snap-start
                  ${i > 0 ? "md:pl-7" : ""}
                  ${i < plans.length - 1 ? "md:border-r border-rule" : ""}
                  ${i > 0 ? "max-md:border-l border-rule" : ""}
                  ${isFeatured ? "bg-paper md:px-7" : "bg-paper-off"}
                `}
              >
                {/* Each row below is given a min-height that fits the worst case
                    (longest title / 2-line gen / wrapped range price) so the
                    horizontal rules between rows align across every card. */}
                <div className="mb-5 min-h-[32px] flex items-center gap-2.5 font-mono uppercase tracking-[0.22em] text-[10.5px] text-muted">
                  {"featured" in p && p.featured && "badge" in p && (
                    <span className="bg-spine text-paper font-medium px-2.5 py-1 tracking-[0.22em]">
                      {p.badge}
                    </span>
                  )}
                  <span>{p.bar}</span>
                </div>
                <h3 className="font-serif font-medium text-[24px] lg:text-[26px] leading-[1.15] tracking-[-0.01em] text-ink mb-1.5 max-w-[20ch] min-h-[58px] lg:min-h-[62px]">
                  {p.title}
                </h3>
                <div className="font-serif italic text-[14.5px] leading-[1.5] text-muted mb-7 max-w-[36ch] min-h-[48px]">
                  {p.gen}
                </div>
                <div className="pt-6 border-t border-rule">
                  <div className="font-serif text-[42px] lg:text-[50px] leading-none tracking-[-0.025em] text-ink min-h-[42px] lg:min-h-[50px]">
                    {p.price}
                  </div>
                  <div className="mt-2 mb-7 font-mono uppercase tracking-[0.18em] text-[10.5px] text-muted min-h-[28px]">
                    {p.from}
                  </div>
                </div>
                <ul className="border-t border-rule">
                  {p.features.map((f) => (
                    <li
                      key={f}
                      className="grid grid-cols-[20px_1fr] gap-1.5 items-baseline py-3 border-b border-rule text-[13.5px] text-ink-soft last:border-b-0"
                    >
                      <span aria-hidden className="font-serif font-medium text-spine text-[15px]">+</span>
                      <span>{f}</span>
                    </li>
                  ))}
                </ul>
              </motion.article>
            );
          })}
        </div>
      </div>
    </section>
  );
}
