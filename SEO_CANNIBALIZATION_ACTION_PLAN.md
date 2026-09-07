# SEO Cannibalization Action Plan — Dev Agent Handoff

**Prepared:** 2026-08-05 · **Property:** `sc-domain:premierlimblengthening.com`
**Data window:** 2026-02-01 → 2026-08-02 (GSC query+page), persistence re-checked on 2026-07-06 → 2026-08-02
**Companion report (client-facing):** Claude artifact "PLL · Keyword Cannibalization Audit · August 2026"
**Status: 🟡 IN PROGRESS** — update this line and the per-ticket checkboxes as you ship.

---

## 0. Owner review, 2026-08-27 (artifact comments)

Jaime left nine comments on the audit artifact. **Six are clean approvals. Three
are not**, and reading them as blanket sign-off would ship two things she did not
agree to. Verbatim, by ticket:

| Ticket | Her comment | Reading |
|--------|-------------|---------|
| A1 pain | "hub structure must stay... Have Gus reframe the hub page" | **Rejected as written.** Take the documented fallback, and it is Gus's, not ours |
| A2 head terms | "agree" | Approved |
| A3 height | "agree" | Approved |
| A4 fixation | "agree" | Approved |
| A5 age | "agree" | Approved |
| A6 entity | "agree" (anchored on the `founder` snippet) | Approved |
| A7 sports | "agree" | Approved |
| A8 cost | "not sure i agree" | **Disputed.** See below, this is not a "skip it" |
| A9 local | "this content is fully drafted and ready to be published. It is current task and all content is in notion." | Being done elsewhere, not by us |
| A10 hygiene | *(no comment)* | Never reviewed |

Three things worth being precise about:

1. **A1 is a reversal, not a tweak.** The ticket's primary path was "merge the hub
   page into the blog article and 301 it away". She kept the 7-page hub. That is
   exactly the fallback this plan already documented as Open Decision #1, so the
   decision is now made: re-scope and retitle `will-limb-lengthening-hurt`, do not
   redirect it. She also named an owner for the copy, Gus.

2. **A8's comment sits on the FINDING, not the fix.** It is anchored to the
   sentence claiming the pricing page "correctly dominates real cost queries" and
   that the leak is contained. So the thing she doubts is our claim that cost
   cannibalization is *minor*. That may mean A8 needs to grow, not disappear.
   Do not silently drop it, ask her what she is seeing.

3. **A10 was never reviewed.** It appears in the plan but no comment landed on it.
   It is unapproved rather than approved.

**None of the nine threads is activated for Claude**, so these cannot be replied
to or resolved from here. Someone with write access has to reply in the artifact
(or send the thread to Claude) to close them out.

### Corrections found while checking the approved work against the code

The audit was written 2026-08-05. The codebase has moved since, and three of the
approved tickets are wrong as literally written:

- **A2 must be narrowed.** The audit predates `/height-surgery/` and
  `/leg-lengthening-surgery/`, which now own the "leg lengthening surgery",
  "leg extension surgery" and height families and carry their own
  `MedicalProcedure` schema. Pointing those terms at `/your-surgery/` as well
  would rebuild the very split A2 exists to close. The hub takes **only** the
  "limb lengthening surgery" / "limb lengthening" wording. A2's step 3 (hub
  MedicalProcedure schema) is **already shipped** — `pll_seo_procedure()`.
- **A3 has a destination conflict — needs a decision.** The ticket says to fence
  the "surgery to get taller" family toward How Much Taller. But
  `pll_contextual_links()` already points "surgery to become taller" at
  `/height-surgery/`, the purpose-built pillar. Doing A3 literally would create a
  *new* How Much Taller vs `/height-surgery/` split. Pick one owner for that query
  family before writing the hand-off block. See Open Decisions.
- **A6 is mostly shipped.** The `Physician` node on `/dr-basmajian/` and its
  `@id` references across the graph already exist. What was actually missing was
  the `founder` reference, the name anchors, and the CV PDF redirect.

---

## 1. Context (read first)

Google is showing 2+ PLL URLs for the same query on **234 queries / ~35,800 impressions (18% of site total)**. Five topic pairs were authored twice during the content build (blog article + `/your-surgery/` hub sub-page) and are splitting ranking signals. Additionally, the head terms ("limb lengthening surgery", ~3,500 flagged impressions) have **no designated landing page**, so Google rotates 4 URLs, none of which reaches page 1.

Verified live on 2026-08-05 (do NOT redo, already fixed by the July SEO-recovery pass):
- `/your-visit/` → 301 → `/your-surgery/` ✓
- `/author/*` → 301 → `/blog/` ✓
- Non-trailing-slash → 301 → canonical ✓
- Self-referencing canonicals present on all audited pages ✓
- `Basmajian-CV.pdf` returns 404 (redirect still needed, see A6)

### Ground rules for every ticket
1. **Branch discipline:** work on `theme-editability` (current). The `wordpress` branch belongs to a parallel agent, never touch it. Staging worktree: `PLL-staging-main` on `main`.
2. **Brand copy rules (rendered output):** no em dashes, no semicolons, "Up to" qualifier on height claims, procedure counts phrased as "thousands". Do not invent statistics, credentials, or patient stories.
3. **Where things live (WordPress build, the live site):**
   - 301 map: `wordpress/wp-content/plugins/pll-seo/includes/redirects.php` → `pll_seo_redirect_map()`
   - Title/meta blueprint: `wordpress/wp-content/plugins/pll-seo/includes/meta.php`
   - JSON-LD: `wordpress/wp-content/plugins/pll-seo/includes/schema.php`
   - Page/section patterns: `wordpress/wp-content/themes/pll-editorial/patterns/`
   - Article body content: WP post content (WXR source: `wordpress/content/pll-content.wxr`)
   - Package for deploy: `npm run package` (see `wordpress/docs/MIGRATION.md`)
4. **Mirror in Next app where cheap:** the Next.js tree (`app/`, `components/v2/`, `lib/`) is the staging twin. Title/content changes should be mirrored there when the same surface exists, so the two builds don't drift.
5. After each content/redirect deploy, run the WP smoke test (`wordpress/scripts/smoke-test.mjs`) and spot-check the changed URLs manually (200/301 + `<title>`).

---

## 2. Tickets

### A1 · P0 — Re-scope the pain hub page (NOT consolidate) 🔴 REASSIGNED

> **Owner decision 2026-08-27 resolved this ticket against its own primary path.**
> "hub structure must stay... Have Gus reframe the hub page". Use the FALLBACK
> below only. Do not add the 301. Copy is Gus's; the retitle and the two-way
> cross-links are ours once his copy lands.
**Conflict:** `/limb-lengthening-pain-the-truth/` (blog, wins at pos 4–10, 9,123 impr / 35 clicks) vs `/your-surgery/will-limb-lengthening-hurt/` (hub, loses at pos 16–62, 1,264 impr / 4 clicks). 13 queries split, e.g. "how painful is limb lengthening surgery" (477 impr: 58% @ #7.3 vs 17% @ #62). Live titles both target pain levels.

**Do:**
1. Diff the two pages' content. Port any unique, medically accurate material from `will-limb-lengthening-hurt` into `pain-the-truth` (e.g., anesthesia/during-surgery specifics if not already covered).
2. Add `'/your-surgery/will-limb-lengthening-hurt' => '/limb-lengthening-pain-the-truth'` to `pll_seo_redirect_map()`.
3. Remove/repoint every internal link to the retired URL (hub index card on `/your-surgery/`, related-article widgets, any pattern in `pll-editorial/patterns/`). The hub index may keep a "Pain" card, pointed at the article.
4. Remove the retired URL from the sitemap; confirm the article's canonical unchanged.
5. Mirror in Next app: `app/your-surgery/[slug]/` sub-page list + redirect in `next.config.mjs`.

**Owner decision needed (blocking):** this drops the hub from 7 sub-pages to 6. If the owner insists the 7-page journey stays, use the fallback instead: re-scope `will-limb-lengthening-hurt` to PLL's pain management protocol (nerve blocks, medication plan, PT) and retitle to "How We Manage Pain During Limb Lengthening Recovery" (no "pain levels" phrasing), plus two-way cross-links. Do not ship both.

**Accept when:** old URL 301s; no internal links to it; GSC (+4 wks) shows a single URL taking ≥90% of impressions on the 4 tracked pain queries.

---

### A2 · P0 — Make `/your-surgery/` the head-term page 🟡 PARTLY SHIPPED
**Conflict:** "limb lengthening surgery" (1,373 impr): `how-much-taller` sub-page 42% @ #40.1, homepage 19% @ #19.9, hub 12% @ #56.6. Same pattern on "limb lengthening" (776), "leg lengthening surgery" (648), "leg extension surgery" (282), "leg lengthening" (246), "lengthening surgery" (124), "bone lengthening surgery" (87). Total ≈ 3,500 impr, 9 clicks in 6 months. Only sitewide anchor to the hub is the nav label "Your Surgery".

**Do:**
1. Expand the hub index into a genuine procedure overview (target 800–1,200 words beyond the card grid): what limb lengthening surgery is, methods (link Ext/Int page), candidacy (link candidate article), recovery arc (link exercise page), results expectations (link How Much Taller), cost pointer (link pricing). Use existing approved copy from `scraped_content/` wherever possible; net-new sentences must follow brand copy rules and be medically conservative.
2. Add in-content, exact-phrase anchors **"limb lengthening surgery" → `/your-surgery/`** from: the How Much Taller page intro, the pros-and-cons article, the pricing page intro, and the first relevant mention in each of the 16 articles (one anchor per page, natural placement, don't stuff).
3. Wire `MedicalProcedure` JSON-LD for the hub in `pll-seo/includes/schema.php` (name "Limb lengthening surgery", `howPerformed` summary, `bodyLocation`, provider → Organization/Physician nodes).
4. Keep the current title (`Limb Lengthening Surgery · How It Works · Premier Limb Lengthening`), it is already correct.
5. Homepage: no changes to targeting (it stays brand/credential focused).

**Accept when:** hub becomes the most-shown URL for "limb lengthening surgery" in GSC (+8 wks) and enters top 10 by +12 wks; schema validates in Rich Results test.

---

### A3 · P1 — Intent-fence the height pair 🔴 BLOCKED (destination conflict)
**Conflict:** `/your-surgery/how-much-taller-can-i-get-with-limb-lengthening/` (site's biggest page, 64,769 impr) vs `/leg-up-or-let-down-can-you-gain-height-without-surgery/` (26,726 impr). On "surgery to get taller" (194) / "surgery to be taller" (163) / "surgery to become taller" (127) both sit at pos 20–26 and split roughly 60/30, the non-surgical article even outranks the money page on one of them. 91 height queries flagged in total (~11,900 impr).

**Do:**
1. On the non-surgical article: compress the sections "When Surgery Becomes a Consideration" and "Realistic Leg Lengthening Expectations" into one short hand-off block that links to How Much Taller with anchor text "surgery to get taller" (keep the medically approved sentences; remove the H2-level surgical targeting, e.g. demote to a paragraph under a neutral heading like "If height surgery is on your mind").
2. On How Much Taller: work the phrase "surgery to get taller" into the intro paragraph and add one FAQ entry ("Is there a surgery to get taller?" with a 2–3 sentence answer + "Up to" qualifier on any height figure).
3. Two-way cross-links stay (already present blog → hub, verify hub → blog exists for the non-surgical alternative).

**Accept when:** How Much Taller holds ≥85% impression share on the "surgery to get/be/become taller" family and its position on them exits the 20s (+8 wks).

---

### A4 · P1 — Consolidate the fixation pages 🟡 PLUMBING SHIPPED, GATED OFF
**Conflict:** `/fixation-methods-in-limb-lengthening-internal-vs-external/` (blog, 85% share @ #8.9 on "internal vs external fixation", 413 impr) vs `/your-surgery/external-internal-lengthening/` (hub, 15% @ #26). Near-identical titles.

**Do:**
1. Merge the blog's comparison content into `/your-surgery/external-internal-lengthening/` (keep the hub page's imagery/layout; port prose the hub lacks).
2. 301 the blog URL → the hub page (`pll_seo_redirect_map()`).
3. Repoint internal links (the pain article links to both today), remove from sitemap, mirror in Next app.
4. Expect a temporary dip on the generic query while equity transfers, note the deploy date in GSC.

**Accept when:** old URL 301s; hub page is the sole PLL URL on "internal vs external fixation" and recovers to ≤ #12 by +8 wks.

---

### A5 · P2 — Differentiate the age pair ✅ DONE (unshipped)
**Conflict:** `/your-surgery/is-there-an-age-limit-for-limb-lengthening/` (wins "age limit": 883 impr @ 83% share #5.5) vs `/am-i-too-old-for-limb-lengthening/` (wins "too old / old age": #10.5). Each still appears as a losing secondary on the other's queries (9–21% bleed).

**Do:**
1. Retitle the blog: `Limb Lengthening in Your 40s and 50s: Are You Too Old? · Premier Limb Lengthening` (via `pll-seo/includes/meta.php`), H1 to match. Remove "age limit" phrasing from its body/headings, replace with "older patients".
2. On the hub page: add a short FAQ ("Is 30 too old? Is 40? Is 50?" one entry, conservative answer consistent with existing approved copy).
3. Two-way cross-links with intent-matched anchors: hub → blog "limb lengthening for patients over 40", blog → hub "age limit for limb lengthening".

**Accept when:** bleed share of the losing page drops under 5% on the top 4 age queries (+8 wks).

---

### A6 · P2 — Entity cleanup for surgeon-name queries 🟡 CODE DONE, PDF REDIRECT OUTSTANDING
**Conflict:** homepage vs `/dr-basmajian/` split ~50/50 on "dr basmajian" (316), "basmajian" (204), "hrayr basmajian" (184), "basmajian hrayr" (170), "dr. basmajian" (127). Zero clicks recorded. Old CV PDF (342 impr) 404s with no redirect. Homepage title leads with the doctor's name.

**Do:**
1. `Physician` JSON-LD on `/dr-basmajian/` in `schema.php`: `name`, `honorificPrefix`, `medicalSpecialty`, `alumniOf` (only credentials already in approved copy), `worksFor` → Organization, `url`.
2. Homepage/site `Organization` (or `MedicalClinic`, see A9) schema: add `founder` → the Physician node (`@id` reference), keep one canonical Person entity.
3. 301 `/wp-content/uploads/2023/09/Basmajian-CV.pdf` → `/dr-basmajian/` (this is a file path, handle at server/htaccess level or via the redirect plugin hook, WP page-level map may not catch uploads).
4. Sweep in-content mentions of "Dr. Basmajian" across articles: first mention per page links to `/dr-basmajian/`.
5. Do NOT change the homepage title (the CTR blueprint just shipped in `c508bf7`), schema + links should resolve the entity without a title fight.

**Accept when:** `/dr-basmajian/` takes ≥70% impression share on the 5 name queries (+8 wks); PDF URL 301s.

---

### A7 · P3 — Sharpen the sports pair 🟡 CROSS-LINKS DONE, H2s OUTSTANDING
**Near-healthy split:** `/is-leg-lengthening-off-limits-for-athletes/` owns "play sports after…" (#3.1) and `/your-surgery/exercise-after-limb-lengthening/` owns "can you run after…" (#3.5–5.3), but each bleeds 23–39% onto the other's queries.

**Do:** add one H2 to the athletes article ("Returning to Competitive Sports After Limb Lengthening") and one to the exercise page ("Your Week-by-Week Return to Running and Exercise" or closest approved phrasing); two-way cross-links with those anchors.

**Accept when:** each page ≥80% share on its own query family (+8 wks).

---

### A8 · P3 — Contain the "how much is height surgery" bleed 🔴 DISPUTED BY OWNER
**Conflict:** pricing page dominates true cost queries (90% @ #6.8 on "how much does limb lengthening surgery cost") but the words "How Much Taller" pull that page into "how much is the height surgery" (126 impr: 45/44 split), "how much is height increasing surgery" (94: 48/47), "how much is height length surgery" (144: 66/29).

**Do:**
1. Pricing page: add FAQ entry "How much does height surgery cost?" answered with the existing approved pricing copy.
2. How Much Taller: add an early in-content link "View limb lengthening surgery costs" → `/limb-lengthening-pricing-options/` (this anchor already exists on the homepage per the June handoff, reuse it).

**Accept when:** pricing page ≥80% share on the "how much is height…" family (+8 wks).

---

### A9 · P3 — Local entity + service-area signals 🔵 CONTENT OWNED ELSEWHERE (Notion)
**Opportunity:** "leg lengthening los angeles" (197 impr, homepage #3.9), "limb lengthening pasadena" (199, #7.7), "limb lengthening surgery near me" (91, #10.8) scatter across 5 URLs. No geographic targeting exists; JSON-LD builders were never wired per `SEO_AUDIT.md` §5.3.

**Do:**
1. Wire `MedicalClinic` (or `MedicalBusiness`) JSON-LD sitewide in `schema.php`: NAP (Upland address, confirmed phone **(951) 620-5663**, per 2026-06-12 confirmation, do NOT use the old (909) numbers), `areaServed` (Upland, Los Angeles, Inland Empire, Southern California), `geo`, `openingHours` if known.
2. Footer already carries "Upland, California" tagline, verify NAP consistency with the GBP listing.
3. Defer a dedicated location page unless the owner wants one; measure schema effect first.

**Accept when:** schema validates; homepage impression share on the 3 local queries ≥70% (+8 wks).

---

### A10 · P3 — Residual hygiene ⬜ NOT REVIEWED BY OWNER
1. `app.premierlimblengthening.com` (GHL) is being indexed (1 impr seen): add `X-Robots-Tag: noindex` via GHL settings or robots meta on the funnel pages.
2. `/category/*` archives: 200 + indexable + thin. Either add 2–3 sentences of intro copy per category (preferred, they 301-inherit legacy equity) or noindex them. Owner call, default to intro copy.
3. Heading anchors are generic (`#heading-0`, `#heading-1`) on several articles, GSC shows sitelink impressions on them. Generate descriptive slugs from heading text in the TOC block for new/edited content. Cosmetic, lowest priority.

---

## 3. Sequencing & deploy

1. **Sprint 1 (this week):** A1 + A2 + A3 in one deploy. Annotate the date in GSC.
2. **Sprint 2 (+1 wk):** A4 + A5 + A6.
3. **Sprint 3 (+2 wks):** A7–A10.
4. All 301s are permanent, never chain them (map old → final destination directly).
5. After each deploy: `npm run package`, run smoke tests, curl-verify every changed URL (`<title>`, canonical, 301 target), submit updated sitemap in GSC.

## 4. Re-measurement protocol

At +4 and +8 weeks, re-run the detection with identical parameters and compare:
- **Source:** GSC API, `sc-domain:premierlimblengthening.com`, dimensions `query,page`, 6-month window (or since-deploy once ≥8 wks).
- **Normalization:** strip `#fragments`, unify trailing slashes, merge fragment rows into parent page (weighted-average position by impressions).
- **Flag:** queries with ≥50 impressions where a secondary URL holds ≥10% impression share (≥10 impressions).
- **Baseline to beat:** 234 flagged queries / 35,837 impressions / 68 clicks. Target: <120 flagged queries at +8 wks.
- Track the named query families per ticket (pain ×4, head ×4, height ×3, age ×4, name ×5, cost ×3, sports ×4, local ×3).
- Caveat: GSC exposes query text for ~38% of impressions; compare within the visible set only.

## 5. Open decisions for the owner

| # | Decision | Blocking | Status |
|---|----------|----------|--------|
| 1 | A1: retire `will-limb-lengthening-hurt` (7→6 hub pages) vs re-scope to pain-management | A1 | ✅ **Decided 2026-08-27:** hub stays, re-scope. Copy assigned to Gus |
| 2 | A10: category archives, intro copy vs noindex | A10 | ⬜ Still open, and A10 itself was never reviewed |
| 3 | A9: dedicated location page later if schema alone underperforms | no | ⬜ Moot for now, content is in Notion under another owner |
| 4 | **A3: who owns the "surgery to get/be/become taller" family — How Much Taller (audit's answer) or `/height-surgery/` (the pillar built since)?** | **A3** | 🔴 **New. Blocks A3.** Doing it the audit's way creates a fresh split |
| 5 | **A8: what is the disagreement?** Her comment lands on the finding that cost bleed is contained, so the fix may need to grow rather than be dropped | A8 | 🔴 **New.** Needs a reply from Jaime |
| 6 | **A4: who writes the fixation content merge, and who clinically reviews it?** The hub page is not signed off (`docs/MEDICAL_REVIEW_LOG.md`) | **A4** | 🔴 **New.** Blocks flipping the A4 gate |

---

## 6. Implementation status (2026-09-07)

All of the below is in the working tree on `theme-editability`, **unshipped**.
Nothing has been deployed to production.

### Landed and verified

| Ticket | What landed | Where |
|--------|-------------|-------|
| A2 | 5 exact-phrase `limb lengthening surgery` → `/your-surgery/` inbound anchors, narrowed to exclude the pillar-owned terms | `content/setup.php` → `pll_cannibalization_links()` |
| A5 | Two-way intent-matched cross-links, plus the retitle to "Limb Lengthening in Your 40s and 50s · Are You Too Old?" | `setup.php` → `pll_related_links()`; `pll-seo/includes/meta.php` |
| A6 | `founder` + `employee` → the Physician `@id` on the site graph; 5 first-mention name anchors → `/dr-basmajian/` | `pll-seo/includes/schema.php`; `setup.php` |
| A7 | Athletes → exercise-page cross-link (the reverse direction already existed in `pll_orphan_links()`) | `setup.php` |
| A4 | Redirect plumbing: map entry, sitemap self-exclusion, inbound-link repointing — all **gated off** | `pll-seo/includes/{redirects,sitemap}.php`; `setup.php` |

Two mechanism bugs were fixed to make the above possible at all:

- **The redirect map only fired on `is_404()`**, so it could never retire a URL
  whose post still exists — which is every consolidation ticket. Now checked on
  every front-end request, with loop and chain guards.
- **Post meta shadows `meta.php`** on any seeded environment, so A5's retitle via
  the map alone would have been a no-op on production. Added
  `pll_seo_targeted_meta_migrations()`: a per-page, one-shot, from/to-guarded
  migration that skips any value an editor has since changed. This is the safe
  alternative to bumping `PLL_SEO_VERSION`, which would re-seed the entire map
  and destroy every editorial SEO change made since launch.

Verification: `npm run verify:cannibalization` (new, `scripts/assert-cannibalization.php`)
asserts every anchor, both gate states, and idempotence. `npm run smoke-test` now
also asserts retired URLs actually 301, reading the gate from the packaged build
rather than assuming it.

### Deliberately NOT done

- **A4's content merge.** The blog page is the one that *ranks* (85% @ #8.9); the
  hub page sits at #26 with ~840 words against the blog's ~1,116. Redirecting the
  winner into the thinner page before its prose moves would throw away the content
  that earned the position. It is also clinical copy on a page Dr. Basmajian has
  not signed off. So the redirect ships **disabled** behind
  `PLL_SEO_A4_FIXATION_MERGED` (default `false`), and the verifier refuses to let
  that flag be flipped unless the merged material is actually present on the hub.
- **A2's hub copy expansion** (800–1,200 words of procedure overview). Net-new
  medical prose, needs an author.
- **A3** entirely — blocked on Open Decision #4.
- **A6's CV PDF 301.** `/wp-content/uploads/2023/09/Basmajian-CV.pdf` is served by
  Apache before WordPress boots, so no plugin hook can catch it. Needs a
  server-level rule, same shape as the sitemap guard in `docs/MIGRATION.md` §2.
- **A7's two H2 additions** (content, not links).
- **Next.js mirror** of any of it.
