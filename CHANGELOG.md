# Changelog

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
