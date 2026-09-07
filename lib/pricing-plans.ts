/**
 * The three procedure tiers shown on /limb-lengthening-pricing-options.
 *
 * Femur, Tibia, and Combined prices are Premier's confirmed 2026 numbers.
 * Maximum-height options are noted within the Combined tier rather than sold
 * as a separate package.
 *
 * Single source of truth for the rendered pricing grid
 * (components/v2/pricing/PricingPlans.tsx), the homepage teaser
 * (components/v2/Pricing.tsx), and the JSON-LD ItemList emitted from
 * app/limb-lengthening-pricing-options/page.tsx. Kept out of the client
 * components so the server page can import without crossing "use client".
 */
export const plans = [
  {
    bar: "Plan 01 · Bilateral Femur",
    title: "Bilateral Femur Lengthening",
    gen: "Up to 8 cm / 3.2 in. (single surgery)",
    price: "$95,500",
    from: "Single surgery, both femurs",
    features: [
      "2 PRECICE MAX internal nail implants",
      "Hospitalization",
      "Surgical & anesthesiologist fees",
      "60 on-site sessions",
    ],
  },
  {
    bar: "Plan 02 · Bilateral Tibia",
    title: "Bilateral Tibia Lengthening",
    gen: "Up to 5 cm / 2 in. (single surgery)",
    price: "$105,500",
    from: "Single surgery, both tibias",
    features: [
      "2 PRECICE MAX internal nail implants",
      "Hospitalization",
      "Surgical & anesthesiologist fees",
      "12 weeks of follow-up care",
      "60 on-site sessions",
    ],
  },
  {
    bar: "Plan 03 · Combined · Most Selected",
    badge: "Most Selected",
    title: "Combined Tibia + Femur",
    gen: "Up to 10 cm / 4 in. total (5 cm per leg)",
    price: "$195,000",
    from: "Two surgeries, three weeks apart",
    featured: true,
    features: [
      "4 PRECICE MAX internal nail implants",
      "All hospitalization across both stays",
      "All surgical & anesthesia fees",
      "14 weeks follow-up care",
      "70 on-site sessions",
      "Maximum height increase up to 6 inches, possible consultation required to discuss",
    ],
  },
] as const;
