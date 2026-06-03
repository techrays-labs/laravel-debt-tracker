# Laravel Debt Tracker

<p align="center">
  <a href="https://techrayslabs.com">
    <img src="https://img.shields.io/badge/Built%20by-Techrays%20Labs-0057FF?style=for-the-badge" alt="Built by Techrays Labs">
  </a>
  &nbsp;
  <img src="https://img.shields.io/packagist/v/techrays-labs/laravel-debt-tracker?style=for-the-badge" alt="Packagist Version">
  &nbsp;
  <img src="https://img.shields.io/packagist/php-v/techrays-labs/laravel-debt-tracker?style=for-the-badge" alt="PHP Version">
  &nbsp;
  <img src="https://img.shields.io/github/license/techrays-labs/laravel-debt-tracker?style=for-the-badge" alt="License">
</p>

<p align="center">
  <strong>Scan, score, and report technical debt in your Laravel application — right from the CLI.</strong>
</p>

---

> **"We should fix this eventually"** — every engineering team, forever.
>
> Laravel Debt Tracker makes the invisible visible. It scans your codebase for technical debt across five categories, assigns a score, estimates developer hours to resolve, and produces a Markdown report you can actually show your product manager.

---

## Features

- **TODO / FIXME detection** — finds every deferred problem in your comments
- **Complexity analysis** — cyclomatic complexity, long methods, God classes, deep nesting
- **Test coverage heuristics** — no Xdebug required; detects untested classes and methods
- **Dependency audit** — flags outdated or abandoned Composer packages
- **Git blame enrichment** — older debt scores higher; age is the multiplier
- **Debt grading** — A through F, with estimated dev hours to resolve
- **Markdown export** — shareable reports with a shield badge for your README

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2, 8.3, 8.4 |
| Laravel | 10, 11, 12, 13 |

---

## Installation

```bash
composer require --dev techrays-labs/laravel-debt-tracker
```

That's it. The package auto-discovers itself.

Optionally publish the config:

```bash
php artisan vendor:publish --tag=debt-tracker-config
```

---

## Usage

### Full scan

```bash
php artisan debt:scan
```

```
╔══════════════════════════════════════════════════════════╗
║       Laravel Debt Tracker · by Techrays Labs            ║
╚══════════════════════════════════════════════════════════╝

  Scanning 312 files...  ████████████████████  Done

  Project Grade: C    Total Score: 412    Est. Hours: 103h

  Debt by Category:
  ┌─────────────────────────┬───────┬───────┐
  │ Category                │ Items │ Score │
  ├─────────────────────────┼───────┼───────┤
  │ TODOs / FIXMEs          │  24   │  112  │
  │ Complexity              │  18   │  180  │
  │ Missing Test Coverage   │  31   │   88  │
  │ Outdated Dependencies   │   6   │   32  │
  └─────────────────────────┴───────┴───────┘

  Top 10 Worst Files:
  ┌────────────────────────────────────────┬───────┬───────┐
  │ File                                   │ Items │ Score │
  ├────────────────────────────────────────┼───────┼───────┤
  │ app/Services/LegacyPaymentService.php  │  14   │  98   │
  │ app/Http/Controllers/OrderController   │   9   │  72   │
  │ ...                                    │       │       │
  └────────────────────────────────────────┴───────┴───────┘
```

### Export to Markdown

```bash
php artisan debt:scan --export=markdown
```

Writes `DEBT_REPORT.md` to your project root — ready to commit or share.

### CI-friendly summary

```bash
php artisan debt:summary
# [Techrays Debt Tracker] Grade: C | Score: 412 | Est: 103h | Files: 312
# Exit code: 1 (C), 0 (A/B), 2 (D/F) — gate your pipeline on debt grade
```

### Scan a specific path

```bash
php artisan debt:scan --path=app/Services
```

### Run specific detectors only

```bash
php artisan debt:scan --only=todos,complexity
```

### Inspect a single file or class

```bash
php artisan debt:show-file app/Services/PaymentService.php
php artisan debt:show-class "App\Services\PaymentService"
```

---

## Configuration

```php
// config/debt-tracker.php

return [
    'scan_paths' => ['app'],
    'exclude_paths' => ['app/Http/Middleware'],

    'thresholds' => [
        'method_length'        => 30,   // lines
        'class_length'         => 500,  // lines
        'max_public_methods'   => 20,
        'nesting_depth'        => 4,
        'complexity_per_method'=> 10,
    ],

    'cost' => [
        'hours_per_point' => 0.25,
        'hourly_rate'     => null, // set to show $ estimates
    ],

    'detectors' => [
        'todos'        => true,
        'complexity'   => true,
        'coverage'     => true,
        'dependencies' => true,
        'git_age'      => true,
    ],
];
```

---

## How Scoring Works

Each detected debt item gets a **base score** multiplied by an **age multiplier**:

```
Item Score = Base Weight × Age Multiplier
```

| Debt Type | Base Score |
|---|---|
| TODO / FIXME | 2 |
| Long method | 5 |
| God class | 15 |
| Deep nesting | 4 |
| Untested class | 8 |
| Outdated major dep | 10 |
| Abandoned package | 20 |

| Debt Age | Multiplier |
|---|---|
| < 30 days | 1.0× |
| 30–90 days | 1.5× |
| 90–180 days | 2.0× |
| 180+ days | 3.0× |

| Total Score | Grade |
|---|---|
| 0–100 | A — Healthy |
| 101–300 | B — Manageable |
| 301–600 | C — Concerning |
| 601–1000 | D — Critical |
| 1000+ | F — Emergency |

---

## Markdown Report

The exported `DEBT_REPORT.md` includes a shields.io badge you can embed in your README:

```markdown
![Debt Grade](https://img.shields.io/badge/Debt%20Grade-C-yellow)
```

---

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) first.

```bash
git clone https://github.com/techrays-labs/laravel-debt-tracker
cd laravel-debt-tracker
composer install
./vendor/bin/pest
```

---

## License

MIT · © [Techrays Labs](https://techrayslabs.com)

---

<p align="center">
  Built with ❤️ by <a href="https://techrayslabs.com"><strong>Techrays Labs</strong></a> · Ahmedabad, India<br>
  <sub>We build software and the teams that build software.</sub>
</p>
