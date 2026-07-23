# Changelog

> **Support policy:** Only `v1.3.x` is actively maintained. All prior versions (1.0, 1.1, 1.2) are end of life — no further bug fixes or security patches will be issued for them.

## [1.3.1] - 2026-07-02

### Added
- **CI Debt Gate** — block merges when technical debt breaches an absolute policy.
  - New `--fail-on-grade=<A-F>` flag on `debt:scan` and `debt:summary` — exits `1` when the grade is that letter or worse.
  - New `--max-score=<int>` flag on `debt:scan` and `debt:summary` — exits `1` when the total debt score exceeds the value. If both flags are set, the gate fails when either is breached.
  - Invalid flag input (unknown grade letter or non-numeric score) exits `2`.
  - New `ci` config block (`fail_on_grade`, `max_score`, both `null` by default) sets the policy once; CLI flags override the config.
  - New `TechRaysLabs\DebtTracker\Gating\DebtGate` evaluator, `GateResult` value object, and `ResolvesGateOptions` command trait.
  - `GradeResolver::rank()` exposes grade severity ordering (A=1 … F=5).

### Changed
- `debt:scan` now returns exit code `1` when a configured gate threshold is breached (previously always `0`). With no gate configured, it still returns `0`.

### Notes
- `debt:summary` default exit codes are **unchanged** (`0` = A/B, `1` = C, `2` = D/F) unless a gate flag or `ci` config is provided, in which case the gate's `0`/`1` scheme applies.

## [1.3.0] - 2026-06-11

### Added
- **Laravel Pulse integration** — built-in, zero extra package required. Activates automatically when `laravel/pulse ^1.0` and `livewire/livewire ^3.0` are installed in the host app. Tested with Pulse v1.0–v1.7
- **Four Pulse dashboard cards:**
  - `debt-tracker-summary-card` — current grade (A–F), total score, estimated hours, and category breakdown
  - `debt-tracker-score-card` — debt score trend chart — see when PRs made things worse
  - `debt-tracker-files-card` — top 10 files by debt score, updated every scan
  - `debt-tracker-authors-card` — top 10 authors by total debt score via git blame
- `debt:scan` now pushes results to Pulse automatically after every run (gated by `pulse.enabled` config key, default `true`)
- New `pulse.enabled` config key — set to `false` to disable Pulse push without uninstalling Pulse
- `laravel/pulse ^1.0` and `livewire/livewire ^3.0` added to `suggest` in `composer.json`
- Pulse card views are publishable: `php artisan vendor:publish --tag=debt-tracker-pulse-views`

### Fixed
- `DebtPulseIngestor` now explicitly calls `Pulse::ingest()` after writing entries when running in a console context. Pulse normally flushes its in-memory buffer via an HTTP terminating hook — without the explicit `ingest()` call, data written by `debt:scan` would be lost. The call is guarded by `method_exists()` for forward compatibility

### Known Issues
- **MySQL 9 not supported for Pulse integration** — Pulse 1.7.x omits `key_hash` from `pulse_entries` INSERTs on MySQL 9, causing a strict-mode constraint failure (`Field 'key_hash' doesn't have a default value`). This is a bug in Pulse's `DatabaseStorage::requiresManualKeyHash()` which only enables manual hashing for SQLite, not MySQL 9. Use MySQL 8 or MariaDB until an upstream fix is available

## [1.2.4] - 2026-06-09

### Fixed
- `exclude_paths` path patterns (`Modules/User/tests`, `Modules/*/tests`) now work correctly when `scan_paths` points inside a subdirectory (e.g. `['Modules']`). The v1.2.3 fix used Symfony Finder's `notPath()` which measures against the *relative* path from the `in()` root — so when scanning `in('Modules/')`, relative paths start with `User/…` and `Modules/User/tests` was never found. Exclusion is now applied against the **absolute path** (`getRealPath()`) of each file, which always contains the full directory chain regardless of scan root. This fixes the nWidart/laravel-modules layout where `Modules/` sits at the same level as `app/`

## [1.2.3] - 2026-06-09

### Fixed
- `exclude_paths` now supports path-based patterns (e.g. `Modules/User/tests`) and glob wildcards (e.g. `Modules/*/tests`) — previously `Finder::exclude()` was used for all entries, which only understands bare directory basenames and silently ignored anything containing a `/`
- `security_exclude_paths` and the coverage detector's excluded paths also support glob wildcards
- Added `src/Support/PathMatcher` — shared utility that normalises path separators and converts `*` to a single-segment regex so exclusion patterns work consistently across all detectors

## [1.2.2] - 2026-06-09

### Fixed
- `laravel/prompts` version constraint broadened from `^0.1|^1.0` to `^0.1|^0.2|^0.3` — the previous constraint excluded `0.3.x` which Laravel 12 and 13 ship with, causing `composer install` to fail on fresh installs

## [1.2.1] - 2026-06-09

### Improved
- Real-time per-file progress bar during `debt:scan` using `laravel/prompts` — no more silent wait on large projects
- Polished CLI: `intro()` header replaces ASCII art box, `outro()` shows grade + score on completion, export paths shown via `note()`
- Added `laravel/prompts` as an explicit dependency (was previously transitive via `laravel/framework`)
- `debt:scan` signature updated to document `security` and `dead_code` in the `--only` help text

## [1.2.0] - 2026-06-09

### Added
- Security Smell Detector: flags `eval()`/`shell_exec()`/`exec()`, hardcoded credentials, `md5()`/`sha1()` on password-like variables, SQL string concatenation, `unserialize()` on variable input, and `dd()`/`var_dump()` debug leakage
- Dead Code Detector: flags unused `private` methods (score 8), properties (score 5), and constants (score 3) within their own class; skips magic methods, Laravel lifecycle methods, and constructor-promoted properties
- Git Author Leaderboard: all debt items are grouped by git blame author and surfaced as a "Top Debt Authors" table in terminal output, a `## Debt by Author` section in Markdown reports, and an `authors` array in JSON reports
- `detectors.security` and `detectors.dead_code` config keys (both default `true`)
- `security_exclude_paths` config key — files whose path contains any listed string are skipped by the security detector (default: `['tests', 'database/seeders']`)
- `dead_code_ignore_methods` config key — extra method names to never flag as dead code

## [1.1.1] - 2026-06-08

### Fixed
- Dependency detector no longer flags packages as outdated when the installed version is newer than what Packagist reports as latest (e.g. `laravel/framework 13.14.0` was incorrectly flagged as behind `10.50.2`)

## [1.1.0] - 2026-06-08

### Added
- N+1 query detector: flags Eloquent lazy-load patterns inside `foreach` loops and collection iterators (`->each()`, `->map()`, `->filter()`)
- JSON export: `php artisan debt:scan --export=json` writes `DEBT_REPORT.json`
- `--export=markdown,json` runs both exports in a single scan
- `n1_queries` detector toggle and `n1_ignore_properties` config keys
- `json_path` export config key

## [1.0.0] - 2025-06-03

### Added
- Initial release
- TODO/FIXME/HACK/XXX/TEMP/REFACTOR detection
- Cyclomatic complexity analysis
- God class and long method detection
- Deep nesting detection
- Missing test coverage heuristic
- Outdated dependency detection via Packagist API
- Git blame age enrichment
- Terminal reporter with colored output
- Markdown report export
- `debt:scan`, `debt:summary`, `debt:show-file`, `debt:show-class` commands
