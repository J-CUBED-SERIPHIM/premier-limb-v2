import Image from "next/image";
import { Reveal } from "./Reveal";

/**
 * Bio — feature layout: editorial portrait + credentials + 3 paragraphs +
 * inline CTA + 3-up stat strip + credential dossier rows. Paper ground.
 * Sits directly after Article (homepage handoff v2); no border-t — the
 * trust strip above already closes with a border-ink rule.
 */
export function Bio() {
  return (
    <section id="dr" className="bg-paper py-20 lg:py-28 border-b border-rule">
      <div className="mx-auto max-w-wrap px-6 lg:px-12">
        <Reveal>
          <header className="pb-8 mb-12 border-b border-ink">
            <span className="eyebrow mb-4">Your Surgeon</span>
            <h2
              className="mt-4 font-serif font-normal tracking-[-0.02em] text-ink leading-[0.98]"
              style={{ fontSize: "clamp(40px, 6vw, 84px)" }}
            >
              Dr. Hrayr <em className="italic text-spine">Basmajian.</em>
            </h2>
          </header>
        </Reveal>

        <div className="grid grid-cols-1 lg:grid-cols-[5fr_7fr] gap-10 lg:gap-14 items-start">
          {/* Sticky like the Concierge aside: the portrait pins below the nav
              while the (now much taller) text column scrolls past it. */}
          <Reveal className="self-start lg:sticky lg:top-24">
            <figure
              className="v2-portrait aspect-[4/5] bg-paper-warm border border-rule relative overflow-hidden max-w-[85%]"
              aria-label="Editorial portrait of Dr. Basmajian"
            >
              <Image
                src="/Dr-ig-pic.jpg"
                alt="Dr. Hrayr Basmajian consulting with a patient at Pomona Valley Hospital"
                fill
                sizes="(min-width: 1024px) 32vw, 85vw"
                className="object-cover"
              />
              <span className="absolute z-10 top-3 left-3 px-2 py-1 font-mono uppercase tracking-[0.2em] text-[10.5px] text-muted bg-paper">
                With a Patient
              </span>
              <figcaption className="absolute z-10 bottom-3 left-3 right-3 px-3.5 py-2.5 bg-paper/95 backdrop-blur-sm border-l-2 border-spine font-serif italic text-[15px] lg:text-[16px] text-ink">
                &ldquo;A rare combination of trauma precision and cosmetic judgment.&rdquo;
              </figcaption>
            </figure>
          </Reveal>

          <Reveal delay={0.1}>
            <div className="py-3.5 mb-7 border-t border-b border-rule font-mono uppercase tracking-[0.14em] text-[12px] text-ink">
              Fellowship-Trained Orthopaedic Trauma Surgeon · Medical Director, PVHMC · USC Chief Resident
            </div>

            <p className="font-serif text-[18px] leading-[1.5] text-ink mb-4 max-w-[62ch]">
              Dr. Basmajian brings a rare combination of precision trauma
              surgery and cosmetic limb lengthening expertise to every procedure.
            </p>
            <p className="text-[15.5px] leading-[1.7] text-ink-soft mb-4 max-w-[62ch]">
              His fellowship training in orthopaedic trauma and joint
              reconstruction, completed at Sonoran Orthopaedic Trauma Surgeons
              in Scottsdale and under Professor Christian Krettek at Hannover
              Medical School in Germany, gives him a surgical depth that most
              lengthening practices cannot replicate. That training is what
              allows him to take revision cases other surgeons decline.
            </p>
            <p className="text-[15.5px] leading-[1.7] text-ink-soft mb-7 max-w-[62ch]">
              As Medical Director of Orthopaedic Trauma at Pomona Valley
              Hospital Medical Center, one of the busiest Level II trauma
              centers in Los Angeles County, and founder of Premier Orthopaedic
              &amp; Trauma Specialists, a 17+ surgeon orthopaedic group with
              in-house physical therapy and on-site imaging, Dr. Basmajian
              built Premier Limb Lengthening on that institutional depth.
              Premier Limb Lengthening patients have direct access to that
              infrastructure through Dr. Basmajian&rsquo;s network.
            </p>

            <a
              href="/dr-basmajian"
              className="group inline-flex items-center gap-3 px-5 py-3.5 bg-ink text-paper uppercase tracking-wide text-[12px] font-medium border border-ink hover:bg-spine hover:border-spine transition-colors"
            >
              Learn more about Dr. Basmajian
              <span className="font-serif italic text-[17px] transition-transform group-hover:translate-x-1" aria-hidden>→</span>
            </a>

            <div className="mt-10 grid grid-cols-3 border-t border-ink">
              <div className="pt-6 pb-2 px-4 text-center border-r border-rule">
                <div className="font-serif text-[36px] lg:text-[48px] leading-none tracking-[-0.02em] text-ink">
                  <em className="italic text-spine">1,000s</em>
                </div>
                <div className="mt-2.5 font-mono uppercase tracking-[0.2em] text-[10.5px] text-muted">
                  Procedures
                </div>
              </div>
              <div className="pt-6 pb-2 px-4 text-center border-r border-rule">
                <div className="font-serif text-[36px] lg:text-[48px] leading-none tracking-[-0.02em] text-ink">
                  2
                </div>
                <div className="mt-2.5 font-mono uppercase tracking-[0.2em] text-[10.5px] text-muted">
                  Fellowship Programs Completed
                </div>
              </div>
              <div className="pt-6 pb-2 px-4 text-center">
                <div className="font-serif text-[36px] lg:text-[48px] leading-none tracking-[-0.02em] text-ink">
                  Up to <em className="italic text-spine">3-6</em>″
                </div>
                <div className="mt-2.5 font-mono uppercase tracking-[0.2em] text-[10.5px] text-muted">
                  Height Gain
                </div>
              </div>
            </div>

            <div className="mt-10 border-t border-ink">
              {credentialRows.map((c) => (
                <div
                  key={c.label}
                  className="grid grid-cols-1 sm:grid-cols-[170px_1fr] gap-x-6 gap-y-1 py-3.5 border-b border-rule"
                >
                  <div className="font-mono uppercase tracking-[0.2em] text-[10.5px] text-muted pt-1">
                    {c.label}
                  </div>
                  <div className="text-[14px] leading-[1.6] text-ink">
                    {c.value}
                    {c.list && (
                      <ul className="mt-1.5 list-disc pl-5 space-y-0.5">
                        {c.list.map((item) => (
                          <li key={item}>{item}</li>
                        ))}
                      </ul>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  );
}

/**
 * Homepage credential dossier (handoff v2 §03). Curated subset of the
 * verified CV data in lib/basmajian.ts; "Faculty" supplied by the client
 * handoff. Keep medically accurate; do not paraphrase titles.
 */
const credentialRows: readonly { label: string; value: string; list?: readonly string[] }[] = [
  { label: "Board Certified", value: "Double Board-Certified", list: ["Board-certified, American Board of Orthopaedic Surgery (ABOS)", "Fellow, American Academy of Orthopaedic Surgeons (FAAOS)"] as readonly string[] },
  { label: "Residency", value: "Chief Resident, Orthopaedic Surgery, USC-LA County, Los Angeles" },
  { label: "Previously", value: "Chair of Orthopaedic Trauma, Loma Linda University Medical Center" },
  { label: "Faculty", value: "Assistant Professor of Orthopaedic Surgery" },
  { label: "Societies", value: "AAOS, Orthopaedic Trauma Association, AO Trauma, California Orthopaedic Association" },
  { label: "Licensure", value: "California & Arizona · DEA · CA Fluoroscopy/Radiology" },
];
