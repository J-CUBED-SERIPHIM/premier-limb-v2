/**
 * verify-cannibalization — boots the full Playground stack (theme + plugins +
 * WXR + content/setup.php) and asserts that the keyword-cannibalization remediation (audit A2, A4, A5, A6, A7)
 * landed: inbound anchors, cross-links, the retitle in post meta, the founder
 * entity, and the retired fixation URL. Re-requires setup.php first, so it is
 * also the idempotency test.
 *
 * Usage: node scripts/verify-cannibalization.mjs
 */
import { spawnSync } from "node:child_process";
import { readFile, rm, writeFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const RESULT = path.join(ROOT, "scripts", ".cannibalization-result.txt");
// Must sit beside blueprint.json: "bundled" resource paths resolve relative
// to the blueprint file, so content/pll-content.wxr only finds itself here.
const BLUEPRINT = path.join(ROOT, ".cannibalization-blueprint.json");

const baseBlueprint = JSON.parse(
	await readFile(path.join(ROOT, "blueprint.json"), "utf8")
);

// Same boot as blueprint.json, plus the assertion pass once the seed has run.
const blueprint = {
	...baseBlueprint,
	landingPage: undefined,
	steps: [
		...baseBlueprint.steps,
		{
			// Each runPHP step is a fresh PHP process, so the seeder functions
			// defined by the boot step are gone. Re-requiring setup.php both
			// redefines them and re-runs the whole seed, which is itself the
			// idempotency test: a second pass must not duplicate any page.
			step: "runPHP",
			code: "<?php require '/wordpress/wp-load.php'; require '/wordpress/pll-seed/setup.php'; require '/verify-scripts/assert-cannibalization.php';",
		},
	],
};

await rm(RESULT, { force: true });
await writeFile(BLUEPRINT, JSON.stringify(blueprint));

const cliEntry = path.join(ROOT, "node_modules/@wp-playground/cli/wp-playground.js");
const result = spawnSync(
	process.execPath,
	[
		cliEntry,
		"run-blueprint",
		`--blueprint=${BLUEPRINT}`,
		"--blueprint-may-read-adjacent-files",
		"--mount=./wp-content/themes/pll-editorial:/wordpress/wp-content/themes/pll-editorial",
		"--mount=./wp-content/plugins/pll-seo:/wordpress/wp-content/plugins/pll-seo",
		"--mount=./wp-content/plugins/pll-forms:/wordpress/wp-content/plugins/pll-forms",
		"--mount=./wp-content/plugins/pll-seo-pages:/wordpress/wp-content/plugins/pll-seo-pages",
		"--mount=./content:/wordpress/pll-seed",
		"--mount=./scripts:/verify-scripts",
	],
	{ cwd: ROOT, encoding: "utf8" }
);

await rm(BLUEPRINT, { force: true });

let report = "";
try {
	report = await readFile(RESULT, "utf8");
	await rm(RESULT, { force: true });
} catch {
	// missing report = the blueprint itself failed to reach the assertions
}

process.stdout.write(report);

if (result.status !== 0 || !report.includes("ALL ASSERTIONS PASSED")) {
	process.stderr.write(result.stderr ?? "");
	if (!report) {
		process.stdout.write(result.stdout ?? "");
	}
	console.error("\n✗ cannibalization verification FAILED");
	process.exit(1);
}
console.log("\n✓ cannibalization verification passed");
