import type { Metadata } from "next";
import Image from "next/image";
import { FooterV2 } from "@/components/v2/FooterV2";
import { NavV2 } from "@/components/v2/NavV2";
import { Reveal } from "@/components/v2/Reveal";
import { FinalCta } from "@/components/v2/FinalCta";
import { JsonLd } from "@/components/content/JsonLd";
import { breadcrumb, physicianSchema } from "@/lib/jsonld";
import {
  specialties,
  credentials,
  training,
  memberships,
  publications,
  bookChapter,
} from "@/lib/basmajian";

import "../v2.css";

export const metadata: Metadata = {
  title: "Dr. Hrayr Basmajian · Limb Lengthening Surgeon",
  description:
    "Board-certified orthopaedic trauma surgeon and Medical Director of Orthopaedic Trauma at Pomona Valley Hospital. Thousands of limb lengthening procedures performed.",
  alternates: { canonical: "/dr-basmajian" },
  openGraph: {
    title: "Dr. Hrayr Basmajian · Limb Lengthening Surgeon",
    description:
      "Board-certified orthopaedic trauma surgeon. Director, Orthopaedic Trauma at Pomona Valley Hospital. Thousands of limb lengthening procedures performed.",
    url: "/dr-basmajian",
    type: "profile",
    images: [{ url: "/dr-picture.jpg", width: 800, height: 1000, alt: "Dr. Hrayr Basmajian" }],
  },
  robots: { index: true, follow: true },
};

/**
 * /dr-basmajian — extended editorial bio page.
 *
 * Composition:
 *   Hero band            → eyebrow + h1 + portrait + specialties
 *   Lede + intro         → drop-cap paragraph + supporting body + pull quote
 *   Credentials          → dossier-style two-column table
 *   Education & Training  → reverse-chronological training record
 *   Memberships          → professional society affiliations
 *   Research             → peer-reviewed publications + book chapter
 *   CV link              → editorial download CTA
 *   FinalCta             → shared closing
 *
 * CV data lives in lib/basmajian.ts (verified from Dr. Basmajian's CV).
 */
export default function V2DrBasmajianPage() {
  return (
    <>
      <NavV2 forceVisible />
      <JsonLd
        data={[
          physicianSchema(),
          breadcrumb([
            { name: "Home", url: "/" },
            { name: "Dr. Basmajian", url: "/dr-basmajian" },
          ]),
        ]}
      />

      <section className="bg-paper-off border-b border-ink pt-28 lg:pt-36 pb-16 lg:pb-20">
        <div className="mx-auto max-w-wrap px-6 lg:px-12 grid grid-cols-12 gap-6 lg:gap-8 items-end">
          <Reveal className="col-span-12 lg:col-span-7">
            <span className="eyebrow mb-5">Profile · Your Surgeon</span>
            <h1
              className="mt-5 font-serif font-normal tracking-[-0.025em] text-ink leading-[0.94] max-w-[16ch]"
              style={{ fontSize: "clamp(48px, 8vw, 132px)" }}
            >
              Dr. Hrayr <em className="italic text-spine">Basmajian.</em>
            </h1>
            <div className="mt-6 pt-4 border-t border-rule font-mono uppercase tracking-[0.18em] text-[12px] text-ink">
              MD · Fellowship-Trained Trauma Surgeon · Director, PVHMC
            </div>
            <ul className="mt-5 flex flex-wrap gap-2">
              {specialties.map((s) => (
                <li
                  key={s}
                  className="font-mono uppercase tracking-[0.16em] text-[10px] text-spine border border-spine/40 px-2.5 py-1.5"
                >
                  {s}
                </li>
              ))}
            </ul>
            <div className="mt-6 inline-flex items-center gap-3">
              <Image
                src="/FAAOS-Badge-150x150.png"
                alt="Fellow of the American Academy of Orthopaedic Surgeons"
                width={54}
                height={54}
                className="border border-rule bg-paper"
              />
              <span className="font-mono uppercase tracking-[0.16em] text-[10px] leading-[1.4] text-muted max-w-[22ch]">
                Fellow, American Academy of Orthopaedic Surgeons
              </span>
            </div>
          </Reveal>

          <Reveal delay={0.1} className="col-span-12 lg:col-span-5">
            <figure className="v2-portrait aspect-[4/5] bg-paper-warm border border-rule relative overflow-hidden max-w-[90%]">
              <Image
                src="/dr-picture.jpg"
                alt="Dr. Hrayr Basmajian, Orthopaedic Trauma Surgeon"
                fill
                sizes="(min-width: 1024px) 36vw, 90vw"
                className="object-cover"
                priority
              />
              <span className="absolute z-10 top-3 left-3 px-2 py-1 font-mono uppercase tracking-[0.2em] text-[10.5px] text-muted bg-paper">
                Dr. Basmajian
              </span>
            </figure>
          </Reveal>
        </div>
      </section>

      <section className="bg-paper-off py-20 lg:py-28">
        <div className="mx-auto max-w-wrap px-6 lg:px-12 grid grid-cols-12 gap-6 lg:gap-8">
          <Reveal className="col-span-12 lg:col-span-7">
            <p className="text-[18px] leading-[1.6] text-ink mb-7 max-w-[58ch]">
              <span className="v2-dropcap-word">Dr.</span> Basmajian is a board-certified orthopaedic surgeon
              specializing in trauma and non-trauma musculoskeletal concerns.
              He is the Orthopaedic Trauma Medical Director at Pomona Valley
              Hospital Medical Center, one of the busiest trauma centers in
              Los Angeles, and an assistant professor of orthopaedic surgery.
              Previously, he was Chair of Orthopaedic Trauma at Loma Linda
              University Medical Center.
            </p>
            <p className="text-[15.5px] leading-[1.7] text-ink-soft mb-5 max-w-[58ch]">
              Dr. Basmajian&rsquo;s career has been punctuated by an
              insatiable drive toward quality improvement in the orthopaedic
              programs he helms as well as always striving to hone his own
              skills. His knowledge, compassion, and deep understanding of
              orthopaedic surgery and patient needs have made him a go-to
              provider across Southern California and the United States.
            </p>
            <p className="text-[15.5px] leading-[1.7] text-ink-soft max-w-[58ch]">
              Dr. Basmajian has developed a particular interest in limb
              lengthening for several reasons, not least of which is the
              stunning transformation a patient undergoes physically and
              emotionally. Further, as a trauma surgeon, safe and effective
              limb lengthening is a cornerstone of his practice, with
              thousands of these procedures performed successfully over his
              time in practice. Dr. Basmajian has also seen the effects of
              improper limb lengthening that can result in deformity and a
              need for costly and painful correction.
            </p>
            <p className="mt-5 font-serif font-medium text-[20px] text-ink max-w-[58ch]">
              Doing it right the first time is an integral part of his practice.
            </p>
          </Reveal>

          <Reveal delay={0.1} as="aside" className="col-span-12 lg:col-span-5 lg:pl-2">
            <blockquote className="relative bg-paper p-7 lg:p-9">
              <span aria-hidden className="font-serif italic text-spine text-[88px] leading-[0.4] block mb-3">&ldquo;</span>
              <p className="font-serif italic text-[20px] lg:text-[22px] leading-[1.4] text-ink">
                The need for quality care in orthopaedic surgery, limb
                lengthening, and medicine in general is as significant today as
                ever. Despite technological advances, a surgeon&rsquo;s
                dedication to their craft can still be the difference between
                good and great results. At Premier Limb Lengthening, each
                patient is integral to our practice and
                deserves the utmost in attention and care. Our patients always
                come first and know they will receive direct and honest advice
                from our team.
              </p>
              <footer className="mt-6 pt-4 border-t border-rule font-mono uppercase tracking-[0.18em] text-[11px] text-muted">
                <span className="text-spine font-medium">Dr. Hrayr Basmajian</span>
                <br />Founder · Premier Limb Lengthening
              </footer>
            </blockquote>
          </Reveal>
        </div>
      </section>

      <section className="bg-paper py-20 lg:py-28 border-t border-b border-rule">
        <div className="mx-auto max-w-wrap px-6 lg:px-12">
          <Reveal>
            <header className="pb-8 mb-12 border-b border-ink">
              <span className="eyebrow mb-4">Credentials</span>
              <h2
                className="mt-4 font-serif font-normal tracking-[-0.02em] text-ink leading-[0.98] max-w-[22ch]"
                style={{ fontSize: "clamp(36px, 5.4vw, 76px)" }}
              >
                A career built on <em className="italic text-spine">trauma precision.</em>
              </h2>
            </header>
          </Reveal>

          <Reveal>
            <div className="border-t border-ink">
              {credentials.map((row, i) => (
                <div
                  key={row.label}
                  className="grid grid-cols-1 sm:grid-cols-[180px_1fr] gap-2 sm:gap-8 py-5 border-b border-rule items-baseline"
                >
                  <div className="font-mono uppercase tracking-[0.2em] text-[10.5px] text-spine">
                    {String(i + 1).padStart(2, "0")} · {row.label}
                  </div>
                  <div className="font-serif text-[18px] lg:text-[20px] leading-[1.35] text-ink max-w-[60ch]">
                    {row.value}
                    {"list" in row && row.list && (
                      <ul className="mt-2 list-disc pl-5 space-y-1 text-[16px] lg:text-[17px] leading-[1.5]">
                        {row.list.map((item) => (
                          <li key={item}>{item}</li>
                        ))}
                      </ul>
                    )}
                  </div>
                </div>
              ))}
            </div>

            <p className="mt-10 text-[15.5px] leading-[1.7] text-ink-soft max-w-[62ch]">
              In addition to his private practice endeavors, Dr. Basmajian
              trains other surgeons in the most advanced and newest
              orthopaedic techniques to improve outcomes and decrease
              complications. He works with various surgical programs and
              medical centers to enhance quality of care through novel
              operating room procedures and setups.
            </p>
          </Reveal>
        </div>
      </section>

      {/* Education & Training */}
      <section className="bg-paper-off py-20 lg:py-28 border-b border-rule">
        <div className="mx-auto max-w-wrap px-6 lg:px-12">
          <Reveal>
            <header className="pb-8 mb-12 border-b border-ink">
              <span className="eyebrow mb-4">Education &amp; Training</span>
              <h2
                className="mt-4 font-serif font-normal tracking-[-0.02em] text-ink leading-[0.98] max-w-[24ch]"
                style={{ fontSize: "clamp(36px, 5.4vw, 76px)" }}
              >
                Trained at the <em className="italic text-spine">busiest trauma centers.</em>
              </h2>
            </header>
          </Reveal>
          <Reveal>
            <div className="border-t border-ink">
              {training.map((t) => (
                <div
                  key={`${t.role}-${t.place}`}
                  className="grid grid-cols-1 sm:grid-cols-[200px_1fr_auto] gap-1 sm:gap-8 py-5 border-b border-rule items-baseline"
                >
                  <div className="font-mono uppercase tracking-[0.2em] text-[10.5px] text-spine">
                    {t.role}
                  </div>
                  <div className="max-w-[60ch]">
                    <div className="font-serif text-[18px] lg:text-[20px] leading-[1.3] text-ink">
                      {t.detail}
                    </div>
                    <div className="mt-1 text-[14px] leading-[1.5] text-ink-soft">
                      {t.place}
                    </div>
                  </div>
                  <div className="font-mono uppercase tracking-[0.18em] text-[10.5px] text-muted sm:text-right">
                    {t.year}
                  </div>
                </div>
              ))}
            </div>
          </Reveal>
        </div>
      </section>

      {/* Memberships & Affiliations */}
      <section className="bg-paper py-20 lg:py-28 border-b border-rule">
        <div className="mx-auto max-w-wrap px-6 lg:px-12 grid grid-cols-12 gap-6 lg:gap-8">
          <Reveal className="col-span-12 lg:col-span-4">
            <span className="eyebrow mb-4">Memberships</span>
            <h2
              className="mt-4 font-serif font-normal tracking-[-0.02em] text-ink leading-[1] max-w-[14ch]"
              style={{ fontSize: "clamp(32px, 4.2vw, 56px)" }}
            >
              Societies &amp; <em className="italic text-spine">affiliations.</em>
            </h2>
          </Reveal>
          <Reveal delay={0.1} className="col-span-12 lg:col-span-8">
            <ul className="border-t border-ink">
              {memberships.map((m) => (
                <li
                  key={m}
                  className="grid grid-cols-[24px_1fr] gap-3 items-baseline py-4 border-b border-rule text-[16px] lg:text-[17px] leading-[1.4] text-ink"
                >
                  <span aria-hidden className="font-serif font-medium text-spine text-[18px]">+</span>
                  <span>{m}</span>
                </li>
              ))}
            </ul>
          </Reveal>
        </div>
      </section>

      {/* Research & Publications */}
      <section className="bg-paper-off py-20 lg:py-28 border-b border-rule">
        <div className="mx-auto max-w-wrap px-6 lg:px-12">
          <Reveal>
            <header className="pb-8 mb-12 border-b border-ink">
              <span className="eyebrow mb-4">Research</span>
              <h2
                className="mt-4 font-serif font-normal tracking-[-0.02em] text-ink leading-[0.98] max-w-[26ch]"
                style={{ fontSize: "clamp(36px, 5.4vw, 76px)" }}
              >
                Peer-reviewed <em className="italic text-spine">publications.</em>
              </h2>
              <p className="mt-5 text-[15.5px] leading-[1.7] text-ink-soft max-w-[62ch]">
                Selected publications in the orthopaedic literature, alongside
                national podium presentations at the Orthopaedic Trauma
                Association and the American Academy of Orthopaedic Surgeons.
              </p>
            </header>
          </Reveal>
          <Reveal>
            <ol className="border-t border-ink">
              {publications.map((p, i) => (
                <li
                  key={p.title}
                  className="grid grid-cols-1 sm:grid-cols-[44px_1fr] gap-2 sm:gap-6 py-6 border-b border-rule"
                >
                  <div className="font-mono uppercase tracking-[0.2em] text-[10.5px] text-spine pt-1">
                    {String(i + 1).padStart(2, "0")}
                  </div>
                  <div className="max-w-[78ch]">
                    <p className="font-serif text-[17px] lg:text-[19px] leading-[1.4] text-ink">
                      <a
                        href={p.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="underline decoration-rule underline-offset-4 hover:text-spine hover:decoration-spine transition-colors"
                      >
                        {p.title}
                      </a>
                    </p>
                    <p className="mt-1.5 text-[13.5px] leading-[1.5] text-ink-soft">
                      {p.authors}
                    </p>
                    <p className="mt-1 font-mono uppercase tracking-[0.14em] text-[10.5px] text-muted">
                      <span className="text-spine">{p.journal}</span> · {p.year}
                    </p>
                  </div>
                </li>
              ))}
            </ol>

            <div className="mt-8 border border-rule bg-paper p-6 lg:p-7 max-w-[80ch]">
              <div className="font-mono uppercase tracking-[0.2em] text-[10.5px] text-spine mb-2">
                Book Chapter
              </div>
              <p className="font-serif text-[17px] lg:text-[19px] leading-[1.4] text-ink">
                {bookChapter.title}
              </p>
              <p className="mt-1.5 text-[13.5px] leading-[1.5] text-ink-soft">
                {bookChapter.authors} {bookChapter.source}
              </p>
            </div>

            <a
              href="https://premierlimblengthening.com/wp-content/uploads/2023/09/Basmajian-CV.pdf"
              target="_blank"
              rel="noopener noreferrer"
              className="group mt-10 inline-flex items-center gap-3 px-5 py-3.5 bg-ink text-paper uppercase tracking-wide text-[12px] font-medium hover:bg-spine transition-colors"
            >
              Full Curriculum Vitae &amp; PubMed Articles
              <span className="font-serif italic text-[17px] transition-transform group-hover:translate-x-1" aria-hidden>→</span>
            </a>
          </Reveal>
        </div>
      </section>

      <FinalCta />
      <FooterV2 />
    </>
  );
}
