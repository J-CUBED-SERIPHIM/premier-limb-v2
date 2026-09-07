import { Reveal } from "../Reveal";

/**
 * Financing — spine-blue editorial callout. Two financing partners (SoFi and
 * CareCredit), each opening in a new tab. The indicative monthly and 0% intro
 * stats were removed 2026-09-07 at the owner's request, pending approved
 * CareCredit material. Do not reinstate a payment figure or an APR without it.
 */
export function Financing() {
  return (
    <section id="financing" className="bg-spine text-paper py-24 lg:py-32">
      <div className="mx-auto max-w-wrap px-6 lg:px-12 grid grid-cols-12 gap-6 lg:gap-8 items-end">
        <Reveal className="col-span-12 lg:col-span-7">
          <span className="font-mono uppercase text-[11px] tracking-[0.2em] inline-flex items-center gap-3" style={{ color: "#F4D88A" }}>
            <span aria-hidden className="inline-block w-[22px] h-px" style={{ background: "#F4D88A" }} />
            Financing
          </span>
          <h2
            className="mt-5 font-serif font-medium tracking-[-0.03em] text-paper leading-[0.98]"
            style={{ fontSize: "clamp(40px, 6vw, 88px)" }}
          >
            Pay it down over time, <em className="italic" style={{ color: "#F4D88A" }}>not all at once.</em>
          </h2>

        </Reveal>

        <Reveal
          delay={0.1}
          className="col-span-12 lg:col-span-5 lg:pl-6 lg:border-l border-white/25"
        >
          <p className="font-serif italic text-[20px] lg:text-[22px] text-paper/95 mb-7 leading-[1.3]">
            We have partnered with SoFi and CareCredit so you can spread the
            cost over time. We&rsquo;ll walk you through the options during your
            consultation. No obligation, no sales pressure.
          </p>
          <div className="flex flex-col gap-3 items-start">
            <a
              href="https://www.sofi.com/personal-loans/family-planning/"
              target="_blank"
              rel="noopener noreferrer"
              className="group inline-flex items-center gap-3 px-5 py-3.5 uppercase tracking-wide text-[12px] font-medium text-ink hover:bg-paper transition-colors"
              style={{ background: "#F4D88A" }}
            >
              Apply with SoFi
              <span className="font-serif italic text-[17px] transition-transform group-hover:translate-x-1" aria-hidden>↗</span>
            </a>
            <a
              href="https://www.carecredit.com/"
              target="_blank"
              rel="noopener noreferrer"
              className="group inline-flex items-center gap-3 px-5 py-3.5 bg-transparent text-paper border border-white/40 uppercase tracking-wide text-[12px] font-medium hover:bg-paper hover:text-spine hover:border-paper transition-colors"
            >
              Apply with CareCredit
              <span className="font-serif italic text-[17px] transition-transform group-hover:translate-x-1" aria-hidden>↗</span>
            </a>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
