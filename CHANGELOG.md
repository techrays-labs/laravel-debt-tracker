# Changelog

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
