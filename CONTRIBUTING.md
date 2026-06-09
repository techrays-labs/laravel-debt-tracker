# Contributing to Laravel Debt Tracker

Thank you for considering a contribution! This document explains how to get set up, what we expect from pull requests, and how the release process works.

---

## Getting Started

```bash
git clone https://github.com/techrays-labs/laravel-debt-tracker
cd laravel-debt-tracker
composer install
```

Run the full test suite:

```bash
php vendor/bin/testbench package:test
```

All tests must pass before submitting a pull request.

---

## Development Guidelines

### PHP version

The package targets PHP 8.2+. Do not use features unavailable in PHP 8.2.

### Coding style

We follow the PSR-12 standard. Run the linter before committing:

```bash
./vendor/bin/pint
```

### Test-driven development

Every new feature or bug fix must include tests. We use PestPHP with Orchestra Testbench.

- Write the failing test first
- Implement the minimum code to make it pass
- Refactor if needed
- All existing tests must remain green

### Adding a new detector

1. Implement `TechRaysLabs\DebtTracker\Detectors\Contracts\DetectorInterface`
2. Add a toggle key to `config/debt-tracker.php` under `detectors`
3. Wire it into `buildDetectors()` in `src/DebtTracker.php`
4. Add the label to `TerminalReporter` and `MarkdownReporter`
5. Write tests in `tests/Unit/Detectors/`

---

## Pull Request Process

1. Fork the repository and create a branch from `master`
2. Name your branch descriptively: `feat/my-feature` or `fix/issue-description`
3. Make your changes with clear, atomic commits
4. Ensure `php vendor/bin/testbench package:test` passes with 0 failures
5. Open a pull request against `master` and fill in the PR template

Please keep pull requests focused — one feature or fix per PR.

---

## Reporting Bugs

Use the [Bug Report](.github/ISSUE_TEMPLATE/bug_report.md) issue template. Include your PHP version, Laravel version, and the exact command you ran.

For security vulnerabilities, **do not open a public issue** — see [SECURITY.md](SECURITY.md).

---

## Commit Message Style

```
type: short description in present tense

Optional longer explanation.
```

Types: `feat`, `fix`, `test`, `docs`, `refactor`, `chore`

---

## Questions

Open a [GitHub Discussion](https://github.com/techrays-labs/laravel-debt-tracker/discussions) or email us at chirag@techrayslabs.com.
