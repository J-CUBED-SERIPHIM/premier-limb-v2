/**
 * Dr. Hrayr G. Basmajian — verified CV data.
 *
 * Source: Dr. Basmajian's curriculum vitae (PDF) + the Premier Orthopaedic
 * provider profile. Transcribed verbatim for the /dr-basmajian page so the
 * page reflects his full credentials, training, memberships, and research.
 * Keep medically accurate; do not paraphrase titles or citations.
 */

export const specialties = [
  "Cosmetic & Reconstructive Limb Lengthening",
  "Orthopaedic Trauma",
  "Joint Reconstruction",
  "Fragility & Complex Fracture Care",
  "Revision & Deformity Correction",
] as const;

/** Headline credentials, shown as the dossier table. */
export const credentials = [
  { label: "Board Certified", value: "Double Board-Certified", list: ["Board-certified, American Board of Orthopaedic Surgery (ABOS)", "Fellow, American Academy of Orthopaedic Surgeons (FAAOS)"] as readonly string[] },
  { label: "Medical Director", value: "Orthopaedic Trauma · Pomona Valley Hospital Medical Center" },
  { label: "Founder & CEO", value: "Premier Orthopaedic & Trauma Specialists" },
  { label: "Previously", value: "Chair of Orthopaedic Trauma · Loma Linda University Medical Center" },
  { label: "Procedures", value: "Thousands of limb lengthening procedures performed" },
  { label: "Licensure", value: "California & Arizona medical licenses · DEA · CA fluoroscopy/radiology" },
] as const;

/** Education & training, most advanced first. */
export const training = [
  {
    role: "Fellowship",
    detail: "Orthopaedic Trauma & Joint Reconstruction",
    place: "Sonoran Orthopaedic Trauma Surgeons & Sonoran Hip Center, Scottsdale, AZ",
    year: "2011–2012",
  },
  {
    role: "Fellowship",
    detail: "Orthopaedic Trauma (Prof. Christian Krettek)",
    place: "Hannover Medical School, Hannover, Germany",
    year: "2012",
  },
  {
    role: "Residency",
    detail: "Orthopaedic Surgery · Chief Resident, 2010–2011",
    place: "University of Southern California–LA County, Los Angeles",
    year: "2007–2011",
  },
  {
    role: "Doctor of Medicine",
    detail: "MD",
    place: "RFUMS / The Chicago Medical School, North Chicago, IL",
    year: "2002–2006",
  },
  {
    role: "Master of Science",
    detail: "Applied Physiology",
    place: "Rosalind Franklin University of Medicine & Science",
    year: "2001–2002",
  },
  {
    role: "Bachelor of Science",
    detail: "Biology",
    place: "Wayne State University, Detroit, MI",
    year: "1996–1999",
  },
] as const;

/** Professional organizations / society memberships. */
export const memberships = [
  "American Academy of Orthopaedic Surgeons (AAOS)",
  "Orthopaedic Trauma Association (OTA)",
  "AO Trauma",
  "California Orthopaedic Association",
  "Western Orthopaedic Association",
  "Armenian American Orthopaedic Association",
  "San Bernardino Medical Association",
] as const;

/** Peer-reviewed publications, most recent first. */
export const publications = [
  {
    authors: "Chintalapudi N, Agarwalla A, Bortman J, Lu J, Basmajian HG, Amin NH, Liu JN.",
    title: "Liposomal Bupivacaine Associated with Cost Savings during Postoperative Pain Management in Fragility Intertrochanteric Hip Fractures.",
    journal: "Clin Orthop Surg",
    year: "2022;14(2):162–168",
    url: "https://pubmed.ncbi.nlm.nih.gov/35685981/",
  },
  {
    authors: "Cunningham BP, Ali A, Basmajian HG, et al.",
    title: "Immediate weight bearing as tolerated correlates with decreased length of stay post intramedullary fixation for subtrochanteric fractures: a multicenter retrospective cohort study.",
    journal: "Eur J Orthop Surg Traumatol",
    year: "2021;31(2):235–243",
    url: "https://pubmed.ncbi.nlm.nih.gov/32797351/",
  },
  {
    authors: "Basmajian HG, Farmer T, Lu JC, Amin NH.",
    title: "Liposomal Bupivacaine for Post-Operative Pain Control in Fragility Intertrochanteric Femur Fractures.",
    journal: "J Orthop Trauma",
    year: "2020;34(3):139–144",
    url: "https://pubmed.ncbi.nlm.nih.gov/31634285/",
  },
  {
    authors: "Basmajian HG, Liu JN, Scudday T, Campbell ST, Amin NH.",
    title: "Kirschner wire prepared pilot holes improve screw pull-out strength in synthetic osteoporotic-type bone.",
    journal: "J Clin Orthop Trauma",
    year: "2020;11(Suppl 1):S100–104",
    url: "https://pubmed.ncbi.nlm.nih.gov/31992927/",
  },
  {
    authors: "Vercio R, Basmajian HG.",
    title: "Fracture of a Carbon Fiber Re-Enforced Intramedullary Femoral Nail (Case Report).",
    journal: "J Am Acad Orthop Surg",
    year: "2019;27(12):e585–588",
    url: "https://pubmed.ncbi.nlm.nih.gov/31170099/",
  },
  {
    authors: "Amin NH, Basmajian HG, et al.",
    title: "Nerve blocks in the geriatric patient with hip fracture: a review of the current literature and relevant neuroanatomy.",
    journal: "Geriatr Orthop Surg Rehabil",
    year: "2017;8(4):268–275",
    url: "https://pubmed.ncbi.nlm.nih.gov/29318091/",
  },
  {
    authors: "Cunningham B, Swanson D, Basmajian HG, McLemore R, Ortega G.",
    title: "Professional demands and job satisfaction in orthopaedic trauma: an Orthopaedic Trauma Association member survey.",
    journal: "J Orthop Trauma",
    year: "2015",
    url: "https://pubmed.ncbi.nlm.nih.gov/26053466/",
  },
  {
    authors: "Rog D, Basmajian HG.",
    title: "A rare presentation of sciatic palsy due to hematoma after use of the Kocher-Langenbeck approach to the acetabulum.",
    journal: "JBJS Case Connect",
    year: "2015;5(1)",
    url: "https://pubmed.ncbi.nlm.nih.gov/29252602/",
  },
  {
    authors: "Basmajian HG, Choi PD, Huh K, Sankar WN, Wells L, Arkader A.",
    title: "Radial neck fractures in children: experience from two level-1 trauma centers.",
    journal: "J Pediatr Orthop B",
    year: "2014;23(4):369–374",
    url: "https://pubmed.ncbi.nlm.nih.gov/24811086/",
  },
  {
    authors: "Liodakis E, Liodaki E, Basmajian HG, Hawi N, Petri M, Krettek C, Jagodzinski M.",
    title: "Pectus excavatum in blunt chest trauma: a case report.",
    journal: "J Med Case Rep",
    year: "2013;7(1):22",
    url: "https://pubmed.ncbi.nlm.nih.gov/23320897/",
  },
] as const;

/** Book chapter. */
export const bookChapter = {
  authors: "Basmajian HG, Ortega G.",
  title: "Minimally invasive reduction and fixation techniques for acetabular fractures.",
  source: "In: Minimally Invasive Orthopaedic Surgery. Philadelphia: Lippincott, Williams & Wilkins; 2014.",
} as const;
