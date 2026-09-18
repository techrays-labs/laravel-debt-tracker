# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 2.x     | ✅ Active — current release line |
| < 2.0   | ❌ No longer supported |

> **Note:** As of v2.0.0, all versions prior to 2.0 have reached end of life. No bug fixes, security patches, or updates will be issued for 1.0.x, 1.1.x, 1.2.x, or 1.3.x. The entire `2.x` line (v2.0.0, v2.1.0, ...) is supported together — see [UPGRADE.md](UPGRADE.md) for what changed in each release.

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

If you discover a security vulnerability in Laravel Debt Tracker, email us directly at:

**opensource@techrayslabs.com**

Please include:

- A description of the vulnerability
- Steps to reproduce
- Potential impact
- Your suggested fix (optional but appreciated)

We will acknowledge receipt within **48 hours** and aim to release a patch within **7 days** of confirmation.

We follow responsible disclosure — we ask that you do not publicly disclose the vulnerability until a fix has been released. We will credit you in the release notes if you wish.

## Scope

This package is a development tool (`require-dev`). It is not intended to be installed in production environments. Vulnerabilities that require an attacker to already have filesystem access to the development machine are considered out of scope.
