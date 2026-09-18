# Upgrade Guide

## Upgrading to 2.0 from 1.x

**No code or config changes required — upgrade by bumping the version constraint.**

v2.0.0 is additive-only. Every existing command, config key, CLI flag, and
export schema (`--export=markdown`, `--export=json`) behaves exactly as it
did in 1.3.x. The major version bump is a support-policy decision, not a
breaking-change signal: it resets the "only the current release is
supported" clock, so **all `1.x` releases are now end of life** (see
[CHANGELOG.md](CHANGELOG.md) and [SECURITY.md](SECURITY.md)).

```bash
composer require --dev "techrays-labs/laravel-debt-tracker:^2.0"
```

That's the whole migration. Nothing to publish again, no config keys to add
or rename, no class to update a reference to.

### What's new (all opt-in)

- **`--format=agent`** on `debt:scan` / `debt:summary` — a versioned JSON
  contract for AI coding agents. Existing output (the terminal report,
  `--export=markdown`, `--export=json`) is unaffected; this is a new flag
  value, not a change to an existing one.
- **`debt:mcp-serve`** — a new artisan command starting a local MCP server.
  Requires a separate `composer require --dev mcp/sdk` — it is not pulled in
  by upgrading the package itself.

See the [Agent / MCP Integration](README.md#agent--mcp-integration) section
of the README for setup.

### If you were already on a pre-1.3 release

You were already unsupported before this release (see the 1.3.0 CHANGELOG
entry). The advice is unchanged: upgrade straight to `^2.0` — there is no
intermediate migration step, since 1.3.x → 2.0.0 itself introduces no
breaking changes.
