/**
 * smoke-test — fresh-zip verification of the HIPAA Vault handoff path.
 *
 * Unpacks dist/pll-wordpress-handoff-*.zip into a temp dir, builds individual
 * theme/plugin zips, boots a clean Playground (no --mount flags), and verifies
 * every key route returns HTTP 200 with a non-empty HTML body.
 *
 * Usage: node scripts/smoke-test.mjs
 *        PLL_SMOKE_KEEP=1 node scripts/smoke-test.mjs   leave the instance up
 * Prerequisite: npm run package must have been run first.
 *
 * PLL_SMOKE_KEEP holds the Playground open on :9401 after the route checks and
 * skips the temp-dir cleanup, so other guards can be pointed at the PACKAGED
 * build rather than at the mounted dev server. That distinction matters: a fix
 * that only works with --mount is not a fix. Example:
 *     PLL_SMOKE_KEEP=1 node scripts/smoke-test.mjs &
 *     PLL_BASE=http://127.0.0.1:9401 npm run verify:paa
 * Ctrl-C, or kill the process, to release it.
 */
import { spawnSync, spawn } from "node:child_process";
import net from "node:net";
import { mkdir, readFile, rm, writeFile } from "node:fs/promises";
import { createWriteStream } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import archiver from "archiver";
import fg from "fast-glob";
import { chromium, request as pwRequest } from "playwright";

const __dir = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dir, "..");
const DIST = path.join(ROOT, "dist");
const SMOKE_DIR = path.join(ROOT, "dist", "smoke-tmp");
const PLAYGROUND_PORT = 9401; // different from dev :9400 so they don't collide

// ── helpers ────────────────────────────────────────────────────────────────

async function zipDir(srcDir, destZip) {
	const output = createWriteStream(destZip);
	const archive = archiver("zip", { zlib: { level: 6 } });
	archive.pipe(output);
	archive.directory(srcDir, path.basename(srcDir));
	await archive.finalize();
	await new Promise((r) => output.on("close", r));
}

// ── 1. find the latest handoff zip ─────────────────────────────────────────

const zips = (await fg("pll-wordpress-handoff-*.zip", { cwd: DIST })).sort();
if (!zips.length) {
	console.error("✗ No handoff zip found in dist/. Run: npm run package");
	process.exit(1);
}
const HANDOFF_ZIP = path.join(DIST, zips.at(-1));
console.log(`\nSmoke-testing: ${path.basename(HANDOFF_ZIP)}`);

// Kill whatever holds a TCP port, whoever it is. Used by the opt-in reclaim
// path and by teardown, which cannot rely on the child pid: the wp-playground
// launcher exits while its workers keep serving, and on Windows killing a
// parent does not take its children with it.
function killPortOwner(port) {
	if (process.platform === "win32") {
		const net = spawnSync("netstat", ["-ano"], { encoding: "utf8" });
		const pids = new Set(
			(net.stdout ?? "")
				.split("\n")
				.filter((l) => l.includes(`:${port}`) && /LISTENING/i.test(l))
				.map((l) => l.trim().split(/\s+/).pop())
				.filter((pid) => pid && pid !== "0" && Number(pid) !== process.pid)
		);
		for (const pid of pids) spawnSync("taskkill", ["/F", "/T", "/PID", pid], { stdio: "ignore" });
		return pids.size;
	}
	const lsof = spawnSync("lsof", ["-ti", `:${port}`], { encoding: "utf8" });
	const pids = (lsof.stdout ?? "").split("\n").map((x) => x.trim()).filter(Boolean);
	for (const pid of pids) {
		try { process.kill(Number(pid), "SIGKILL"); } catch { /* gone */ }
	}
	return pids.length;
}

// ── 1b. refuse to grade someone else's instance ──────────────────────────────
//
// This harness once printed "18/18 routes passed" while its own Playground had
// died on `EADDRINUSE` and every request was served by a leftover instance from
// the previous day's zip. Exit code 0. AC-14 exists to prove the PACKAGED build
// works, so a gate that will happily measure whatever happens to be on the port
// is worse than no gate at all.
//
// This runs BEFORE the extraction on purpose. It used to sit after, so a run
// that was going to refuse anyway had already done `rm -rf dist/smoke-tmp` and
// wiped the working directory of the very instance it was refusing to touch.
// Check first, destroy second.
//
// Two independent defences. First, refuse to start if the port is already busy:
// a stale listener is a stop, never a fallback. Second, a nonce written into the
// document root by this run's blueprint and read back over HTTP, so readiness
// can only be satisfied by the instance this process spawned.
const RUN_NONCE = `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;

const portBusy = await new Promise((resolve) => {
	const probe = net.createConnection({ host: "127.0.0.1", port: PLAYGROUND_PORT });
	probe.on("connect", () => { probe.destroy(); resolve(true); });
	probe.on("error", () => resolve(false));
	setTimeout(() => { probe.destroy(); resolve(false); }, 3000);
});
if (portBusy) {
	// Opt-in, never automatic. Killing an unidentified listener because it is
	// in our way is exactly the kind of helpfulness that loses someone's work.
	if (process.env.PLL_SMOKE_RECLAIM) {
		console.error(`
⚑ :${PLAYGROUND_PORT} is busy and PLL_SMOKE_RECLAIM is set — killing the listener.`);
		killPortOwner(PLAYGROUND_PORT);
		await new Promise((r) => setTimeout(r, 3000));
	} else {
		console.error(`
✗ Something is already listening on :${PLAYGROUND_PORT}.`);
		console.error("  Refusing to run: this harness would otherwise grade that instance");
		console.error("  instead of the zip it just extracted, and report a pass for it.");
		console.error("  That false pass is a real defect this check exists to prevent.");
		console.error("");
		console.error("  Most likely an orphaned PLL_SMOKE_KEEP=1 run. On Windows a hard");
		console.error("  kill (taskkill /F) gives the harness no chance to clean up, so the");
		console.error("  Playground outlives it. Release it with either:");
		console.error("");
		console.error(`      PLL_SMOKE_RECLAIM=1 node scripts/smoke-test.mjs`);
		console.error(`      # or, to see what you are killing first:`);
		console.error(`      netstat -ano | findstr :${PLAYGROUND_PORT}`);
		console.error(`      taskkill /F /T /PID <pid>`);
		process.exit(1);
	}
}

// ── 2. extract the zip ──────────────────────────────────────────────────────

await rm(SMOKE_DIR, { recursive: true, force: true });
await mkdir(SMOKE_DIR, { recursive: true });

// Use Node's built-in unzip (Playground CLI ships yauzl transitively, but
// we can use the native unzip via spawnSync with PowerShell Expand-Archive)
console.log("Extracting handoff zip…");
const extract = spawnSync(
	"powershell.exe",
	["-NoProfile", "-NonInteractive", "-Command",
		`Expand-Archive -Path '${HANDOFF_ZIP}' -DestinationPath '${SMOKE_DIR}' -Force`],
	{ stdio: "inherit" }
);
if (extract.status !== 0) {
	console.error("✗ Extraction failed");
	process.exit(1);
}

// ── 3. create individual theme/plugin zips ──────────────────────────────────

console.log("Building theme + plugin zips…");
const themeDir = path.join(SMOKE_DIR, "wp-content", "themes", "pll-editorial");
const themeSeoDir = path.join(SMOKE_DIR, "wp-content", "plugins", "pll-seo");
const themeFormsDir = path.join(SMOKE_DIR, "wp-content", "plugins", "pll-forms");
const seoPagesDir = path.join(SMOKE_DIR, "wp-content", "plugins", "pll-seo-pages");

const themeZip = path.join(SMOKE_DIR, "pll-editorial.zip");
const seoZip = path.join(SMOKE_DIR, "pll-seo.zip");
const formsZip = path.join(SMOKE_DIR, "pll-forms.zip");
const seoPagesZip = path.join(SMOKE_DIR, "pll-seo-pages.zip");

await Promise.all([
	zipDir(themeDir, themeZip),
	zipDir(themeSeoDir, seoZip),
	zipDir(themeFormsDir, formsZip),
	zipDir(seoPagesDir, seoPagesZip),
]);
console.log("  ✓ pll-editorial.zip, pll-seo.zip, pll-forms.zip, pll-seo-pages.zip");

// ── 4. write smoke blueprint ─────────────────────────────────────────────────

// Paths relative to blueprint file (--blueprint-may-read-adjacent-files)
const blueprint = {
	$schema: "https://playground.wordpress.net/blueprint-schema.json",
	preferredVersions: { php: "8.2", wp: "latest" },
	landingPage: "/",
	features: { networking: false },
	constants: {
		WP_DEBUG: true,
		PLL_SEO_PAGES_SEED_DEMO: true,
		PLL_GHL_WEBHOOK_URL: "https://example.invalid/smoke-test-dummy",
	},
	steps: [
		{ step: "login", username: "admin" },
		{
			step: "setSiteOptions",
			options: {
				blogname: "Premier Limb Lengthening",
				blogdescription: "Cosmetic limb lengthening, founded by Dr. Hrayr Basmajian",
				timezone_string: "America/Los_Angeles",
				permalink_structure: "/%postname%/",
				default_comment_status: "closed",
				default_ping_status: "closed",
			},
		},
		{
			step: "installTheme",
			themeData: { resource: "bundled", path: "./pll-editorial.zip" },
			options: { activate: true },
		},
		{
			step: "installPlugin",
			pluginData: { resource: "bundled", path: "./pll-seo.zip" },
			options: { activate: true },
		},
		{
			step: "installPlugin",
			pluginData: { resource: "bundled", path: "./pll-forms.zip" },
			options: { activate: true },
		},
		{
			step: "installPlugin",
			pluginData: { resource: "bundled", path: "./pll-seo-pages.zip" },
			options: { activate: true },
		},
		{
			step: "importWxr",
			file: { resource: "bundled", path: "./content/pll-content.wxr" },
		},
		// Create parent dir, write setup.php into the Playground VFS, then execute it
		{
			step: "runPHP",
			code: "<?php mkdir('/wordpress/pll-seed', 0755, true);",
		},
		{
			step: "writeFile",
			path: "/wordpress/pll-seed/setup.php",
			data: { resource: "bundled", path: "./content/setup.php" },
		},
		{
			step: "runPHP",
			code: "<?php require '/wordpress/wp-load.php'; require '/wordpress/pll-seed/setup.php';",
		},
		// Identity nonce, written LAST so it cannot appear before the seed is
		// finished. Readiness reads it back over HTTP: a foreign instance either
		// 404s here or serves a different nonce, and both are hard failures.
		{
			step: "writeFile",
			path: "/wordpress/pll-smoke-run-id.txt",
			data: { resource: "literal", name: "pll-smoke-run-id.txt", contents: RUN_NONCE },
		},
	],
};

const blueprintPath = path.join(SMOKE_DIR, "blueprint.smoke.json");
await writeFile(blueprintPath, JSON.stringify(blueprint, null, 2));
console.log("  ✓ blueprint.smoke.json written");

// ── 5. boot playground ───────────────────────────────────────────────────────

console.log(`\nBooting clean Playground on :${PLAYGROUND_PORT} (no mounts)…`);

// Invoke via node directly to avoid shebang/shell quoting issues on Windows.
const cliEntry = path.join(ROOT, "node_modules/@wp-playground/cli/wp-playground.js");
const playground = spawn(
	process.execPath,
	[cliEntry, "server",
		`--blueprint=${blueprintPath}`,
		`--port=${PLAYGROUND_PORT}`,
		"--blueprint-may-read-adjacent-files",
	],
	{ cwd: ROOT, stdio: ["ignore", "pipe", "pipe"] }
);

let bootLog = "";
playground.stdout.on("data", (d) => { bootLog += d; process.stdout.write(d); });
playground.stderr.on("data", (d) => { bootLog += d; process.stderr.write(d); });

// Wait until this harness's OWN instance has finished seeding.
//
// Four traps live here. The first three were found by the developer, the last
// two by QA, and every one of them produced a green run over a site that was
// not what it claimed to be.
//
// 1. The original gate scraped stdout for a readiness banner with a 90s
//    timeout. A cold boot off the handoff zip takes ~106-120s, so AC-14 was
//    simply unrunnable and no one had ever measured the packaged build.
// 2. Polling the port is too eager: PHP answers as soon as WordPress installs,
//    long before setup.php composes the pattern-built pages. 9/18 routes 404.
// 3. The CLI's "Ready!" banner means the HTTP server is up, NOT that the
//    blueprint finished. Six 30s page.goto timeouts.
// 4. A 200 on a seeder-composed route STILL does not mean the seed finished.
//    The static front page is assigned near the very end of setup.php, after
//    the PAA injection, so `/` served the BLOG INDEX at check time and the
//    route sweep recorded it as a pass. The seeded route was necessary, not
//    sufficient.
// 5. None of it proved the answers came from this process's Playground at all.
//    A leftover listener on the port satisfied every check above.
//
// So readiness now requires all three, in order: the child is alive and never
// reported EADDRINUSE; the nonce this run wrote is served back verbatim; and
// `/` is the real front page, not the posts index. The nonce is written by the
// last blueprint step and the front page is assigned by the last thing
// setup.php does, so together they bracket the end of the seed.
const BOOT_TIMEOUT_MS = 900000;
const SEEDED_ROUTE = "/limb-lengthening-pricing-options/";
const bootStart = Date.now();

// Recorded for the timeout message, NOT treated as fatal on its own. The
// wp-playground CLI's launcher process exits while its worker processes keep
// serving: an instance whose launcher reported code 1 was verified still
// answering on the port, still serving this run's nonce, and still fully
// seeded. Aborting on the exit code therefore kills healthy boots. The nonce
// below is what actually proves the instance is alive AND ours, which is the
// guarantee that matters.
let bootDied = null;
playground.on("exit", (code) => { bootDied = code; });

const fatalBootLog = () => /EADDRINUSE|address already in use/i.test(bootLog);

// Polled with Playwright's request client rather than node's fetch: undici
// surfaces the connection resets a mid-boot Playground hands out as an
// unhandled error, which killed this script with a bare exit 1 and no message.
// It also keeps the cookie jar the auto-login redirect needs.
const bootCtx = await pwRequest.newContext({ ignoreHTTPSErrors: true });
const base = `http://127.0.0.1:${PLAYGROUND_PORT}`;
try {
	for (;;) {
		if (fatalBootLog()) {
			throw new Error(
				`Playground could not bind :${PLAYGROUND_PORT} (EADDRINUSE). Refusing to ` +
				`measure whatever else is on that port. Last log:\n${bootLog.slice(-800)}`
			);
		}
		if (Date.now() - bootStart > BOOT_TIMEOUT_MS) {
			throw new Error(
				`Playground boot timeout (${BOOT_TIMEOUT_MS / 1000}s)` +
				`${bootDied === null ? "" : `, and the launcher exited with code ${bootDied}`}. Last log:\n${bootLog.slice(-800)}`
			);
		}
		try {
			// (a) identity: our nonce, served by our instance.
			const idRes = await bootCtx.get(`${base}/pll-smoke-run-id.txt`, { timeout: 15000 });
			if (idRes.status() === 200) {
				const served = (await idRes.text()).trim();
				if (served !== RUN_NONCE) {
					throw new Error(
						`:${PLAYGROUND_PORT} is serving run id "${served}" but this run is ` +
						`"${RUN_NONCE}". That is a different Playground. Aborting.`
					);
				}
				// (b) the seeder composed its pages.
				const seeded = await bootCtx.get(base + SEEDED_ROUTE, { timeout: 15000 });
				// (c) the front page is the front page, not the posts index.
				const home = await bootCtx.get(base + "/", { timeout: 15000 });
				const homeTitle = (await home.text()).match(/<title[^>]*>([^<]*)<\/title>/i)?.[1] ?? "";
				const frontPageReady = home.status() === 200 && !/blog|articles/i.test(homeTitle);
				if (seeded.status() === 200 && frontPageReady) {
					console.log(`  \u2713 seeded after ${Math.round((Date.now() - bootStart) / 1000)}s`);
					console.log(`    run id ${RUN_NONCE} confirmed, front page "${homeTitle.slice(0, 60)}"`);
					break;
				}
			}
		} catch (err) {
			// A run-id mismatch is fatal; anything else is "still booting".
			if (/different Playground/.test(err.message)) throw err;
		}
		await new Promise((r) => setTimeout(r, 5000));
	}
} finally {
	await bootCtx.dispose();
}

console.log("\nPlayground up — running route checks via Playwright…\n");

// ── 6. verify routes via Playwright ──────────────────────────────────────────
// Use a real browser instead of fetch() to avoid Windows loopback quirks.

const BASE = `http://127.0.0.1:${PLAYGROUND_PORT}`;
// Each route carries a substring its <title> must contain. HTTP 200 plus a
// non-empty body is not enough: `/` returned the BLOG INDEX with a perfectly
// good 200 while the front page had not been assigned yet, and the sweep
// scored it. The titles are the SEO contract (verify-seo-meta.mjs guards their
// exact text), so pinning a distinctive fragment here costs nothing and turns
// "something answered" into "the right page answered".
const ROUTES = [
	["/", "Cosmetic Limb Lengthening Surgery"],
	["/blog/", "Blog"],
	["/about/", "About"],
	["/consult/", "Contact"],
	["/book-a-consultation/", "Consultation"],
	["/dr-basmajian/", "Basmajian"],
	["/limb-lengthening-pricing-options/", "Cost"],
	["/height-surgery/", "Height Surgery"],
	["/leg-lengthening-surgery/", "Leg Lengthening Surgery"],
	["/evaluate-your-surgeon/", "Surgeon"],
	["/your-surgery/", "How It Works"],
	["/your-surgery/will-limb-lengthening-hurt/", "Hurt"],
	["/seo-pattern-demo/", "How Much Taller"],
	["/seo-image-led-demo/", "How Much Taller"],
	["/seo-structured-demo/", "How Much Taller"],
	["/privacy/", "Privacy"],
	["/terms/", "Terms"],
	["/accessibility/", "Accessibility"],
	["/are-you-a-good-candidate-for-limb-lengthening/", "Right for You"],
	["/am-i-too-old-for-limb-lengthening/", "Too Old"],
	["/category/limb-lengthening/", "Articles"],
];

let passed = 0;
let failed = 0;
let instanceDied = false;

const browser = await chromium.launch();
const context = await browser.newContext();
const page = await context.newPage();

for (const [route, expectTitle] of ROUTES) {
	try {
		const resp = await page.goto(BASE + route, { waitUntil: "domcontentloaded", timeout: 30000 });
		const status = resp?.status() ?? 0;
		const html = await page.content();
		const title = await page.title().catch(() => "");
		const served = status === 200 && html.length > 500 && html.includes("<html");
		const rightPage = title.toLowerCase().includes(expectTitle.toLowerCase());
		if (served && rightPage) {
			console.log(`  ✓ ${route} — HTTP ${status} "${title}" (${(html.length / 1024).toFixed(0)} KB)`);
			passed++;
		} else if (served) {
			console.error(`  ✗ ${route} — HTTP 200 but the WRONG PAGE: title "${title}" does not contain "${expectTitle}"`);
			failed++;
		} else {
			console.error(`  ✗ ${route} — HTTP ${status}, body ${html.length}B`);
			failed++;
		}
	} catch (e) {
		console.error(`  ✗ ${route} — ${e.message.split("\n")[0]}`);
		failed++;
	}

	// A dead instance is not a route failure and must not be reported as one.
	// php.wasm can take the whole Playground down mid-sweep (zend_mm_panic).
	// When that happened, every remaining route "failed" and the run reported a
	// 30/41 result that reads exactly like a broken build. It is not: the thing
	// under test stopped existing. Confirm the instance is still ours and still
	// answering before blaming the next route.
	if (failed > 0 && !instanceDied) {
		let alive = false;
		try {
			const id = await fetch(`${BASE}/pll-smoke-run-id.txt`, { signal: AbortSignal.timeout(10000) });
			alive = id.ok && (await id.text()).trim() === RUN_NONCE;
		} catch {
			alive = false;
		}
		if (!alive) {
			console.error("");
			console.error(`✗ The Playground on :${PLAYGROUND_PORT} stopped answering as run ${RUN_NONCE}.`);
			console.error("  It died mid-sweep, so the route failures above are noise, not results.");
			console.error("  Check the boot log for a php.wasm abort (zend_mm_panic, out of memory).");
			console.error("  This run proves NOTHING about the packaged build. Re-run it.");
			instanceDied = true;
			break;
		}
	}
}

// ── 5b. retired URLs must 301, not serve ────────────────────────────────────
//
// A consolidation ticket retires a duplicate page by mapping it in
// pll-seo/includes/redirects.php. The post deliberately stays in the database
// (WXR re-import fidelity), so it would still resolve 200 on its own, and the
// map's old 404-only guard could not have stopped it. Asserting the FINAL URL
// rather than just the rendered title is what makes this a redirect test: a
// title check alone would also pass if the duplicate simply kept serving under
// the destination's name.
// A4's redirect is gated on its content merge (PLL_SEO_A4_FIXATION_MERGED in
// pll-seo/includes/redirects.php), so the expectation is read from the build
// under test rather than hardcoded here. Asserting a redirect that the shipped
// code deliberately disables would just teach everyone to ignore this check.
const a4Gate = /define\(\s*'PLL_SEO_A4_FIXATION_MERGED'\s*,\s*true\s*\)/.test(
	await readFile(
		path.join(SMOKE_DIR, "wp-content/plugins/pll-seo/includes/redirects.php"),
		"utf8"
	).catch(() => "")
);

const REDIRECTS = a4Gate
	? [
			[
				"/fixation-methods-in-limb-lengthening-internal-vs-external/",
				"/your-surgery/external-internal-lengthening/",
			],
		]
	: [];

if (!a4Gate) {
	console.log(
		"\n  ⓘ A4 fixation redirect is gated off pending its content merge — not asserted."
	);
}

if (!instanceDied && REDIRECTS.length) {
	console.log("\nChecking retired URLs redirect…\n");
	for (const [from, to] of REDIRECTS) {
		try {
			await page.goto(BASE + from, { waitUntil: "domcontentloaded", timeout: 30000 });
			const landed = new URL(page.url()).pathname;
			if (landed === to) {
				console.log(`  ✓ ${from} → ${landed}`);
				passed++;
			} else {
				console.error(`  ✗ ${from} — landed on ${landed}, expected ${to}`);
				failed++;
			}
		} catch (e) {
			console.error(`  ✗ ${from} — ${e.message.split("\n")[0]}`);
			failed++;
		}
	}
}

await browser.close();

// ── 6a. teardown that actually releases the port ─────────────────────────────
//
// playground.kill() alone is not enough, for two reasons discovered the hard
// way. The wp-playground CLI forks worker processes and its launcher exits
// while they keep serving, so by teardown time the pid we hold may already be
// gone while :9401 is still bound. And on Windows killing a parent does not
// take its children with it.
//
// The consequence, when this script was interrupted, was an orphaned
// Playground squatting on :9401 forever. The NEXT run then found the port busy
// and, before the pre-bind check existed, silently graded that stale instance
// and reported 18/18. So this leak is not untidiness, it is the thing that
// manufactured the false pass.
//
// Release by port, not just by pid: whatever owns :9401 dies.
let releasedAlready = false;
function releaseInstance() {
	if (releasedAlready) return;
	releasedAlready = true;
	try { playground.kill(); } catch { /* already gone */ }
	if (process.platform === "win32" && playground.pid) {
		spawnSync("taskkill", ["/F", "/T", "/PID", String(playground.pid)], { stdio: "ignore" });
	} else if (playground.pid) {
		try { process.kill(-playground.pid, "SIGKILL"); } catch { /* no process group */ }
	}
	killPortOwner(PLAYGROUND_PORT);
}

// Every abnormal exit path, not just the happy one. Without these, Ctrl-C or a
// thrown assertion leaves the instance running and the next run inherits it.
process.on("exit", releaseInstance);
process.on("SIGINT", () => { releaseInstance(); process.exit(130); });
process.on("SIGTERM", () => { releaseInstance(); process.exit(143); });
process.on("SIGHUP", () => { releaseInstance(); process.exit(129); });
process.on("uncaughtException", (err) => {
	console.error(err);
	releaseInstance();
	process.exit(1);
});
process.on("unhandledRejection", (err) => {
	console.error(err);
	releaseInstance();
	process.exit(1);
});

// ── 6b. optional hold ─────────────────────────────────────────

if (process.env.PLL_SMOKE_KEEP) {
	console.log(`
${passed}/${ROUTES.length + REDIRECTS.length} checks passed the smoke test`);
	if (failed > 0) console.error(`✗ ${failed} route(s) returned unexpected responses`);
	console.log(`
PLL_SMOKE_KEEP set — holding the PACKAGED instance on ${BASE}`);
	console.log(`Point the guards at it, e.g. PLL_BASE=${BASE} npm run verify:paa`);
	console.log("Ctrl-C to stop. dist/smoke-tmp/ is left in place on purpose.");
	console.log("If this process is killed, the instance is released with it.");
	await new Promise(() => {});
}

// ── 7. teardown ───────────────────────────────────────────────────────────────

releaseInstance();

console.log(`\n${passed}/${ROUTES.length + REDIRECTS.length} checks passed the smoke test`);

if (instanceDied) {
	console.error(`\n✗ Smoke test INCONCLUSIVE — the Playground died mid-run, so nothing was proven. Re-run.`);
	process.exit(1);
}

if (failed > 0) {
	console.error(`\n✗ Smoke test FAILED — ${failed} route(s) returned unexpected responses`);
	process.exit(1);
} else {
	console.log(`\n✓ Smoke test PASSED — handoff zip is import-ready for HIPAA Vault`);
}

// cleanup temp dir
await rm(SMOKE_DIR, { recursive: true, force: true });
console.log("  Cleaned up smoke-tmp/");
