import { Reveal } from "../Reveal";

/**
 * PricingHero, top-of-page editorial header for /limb-lengthening-pricing-options. Sits flush
 * under NavV2 (sticky variant) on the paper-off ground; no video stage.
 */
export function PricingHero() {
  return (
    <section className="bg-paper-off border-b border-ink pt-28 lg:pt-36 pb-16 lg:pb-20">
      <div className="mx-auto max-w-wrap px-6 lg:px-12 grid grid-cols-12 gap-6 lg:gap-8">
        <Reveal className="col-span-12 lg:col-span-8">
          <span className="eyebrow mb-5" style={{ color: "#F4D88A" }}>
            <span style={{ color: "var(--spine)" }}>Transparent Pricing · 2026</span>
          </span>
          <h1
            className="mt-5 font-serif font-normal tracking-[-0.025em] text-ink leading-[0.95] max-w-[18ch]"
            style={{ fontSize: "clamp(44px, 7.2vw, 116px)" }}
          >
            Limb Lengthening <em className="italic text-spine">Cost.</em>
          </h1>
        </Reveal>
        <Reveal delay={0.1} className="col-span-12 lg:col-span-4 lg:pl-6 lg:border-l border-rule lg:self-end">
          <p className="font-serif italic text-[20px] lg:text-[22px] leading-[1.35] text-ink-soft">
            Every quote below includes implants, hospitalization, surgical
            fees, anesthesia, follow-up care, and a defined block of on-site
            sessions. No surprises.
          </p>
        </Reveal>
      </div>
    </section>
  );
}
