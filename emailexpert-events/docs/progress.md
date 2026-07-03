# Build progress

## M1 — Scaffold and API discovery (2026-07-03)

**Done:**

- Plugin skeleton per SPEC 12: `emailexpert-events.php` bootstrap, PSR-4
  autoloader (no Composer at runtime), `src/`, `blocks/`, `templates/`,
  `assets/`, `tests/`, `docs/`, `.wp-env.json`, CI workflow.
- `HeySummitClient` (SPEC 3.3): 15 s timeout, 2 retries with backoff on
  5xx/network errors, no retry on 4xx, pagination with a filterable
  50-page cap, same-host guard on `next` URLs, `eex_auth` error for
  401/403, per-request logging with the key never logged.
- Connections store (SPEC 5.1): list of labelled connections, write-only
  keys (last four shown), `EEX_HEYSUMMIT_API_KEY` constant override for
  the first connection (never persisted to the database).
- Settings → emailexpert Events: API section with connections table,
  add/remove rows, per-connection AJAX "Test connection" (nonce +
  `manage_options`). Sync/Webhooks/Display sections land with M3/M6/M4.
- Persistent admin notice for auth failures, cleared by a successful test.
- Logging facade firing the `eex_log` action; the persistent log table
  subscribes to it in M2 without call-site changes.
- Read-only `Discovery` prober + `wp eex discover` / `wp eex
  test-connection` WP-CLI commands.
- PHPUnit suite (client pagination/retry/auth/logging hygiene,
  connections, discovery redaction) on WP shims; PHPCS with WPCS; GitHub
  Actions CI on PHP 8.1 and 8.3.

**Blocked / carried forward:**

- **Live API discovery could not run**: the build environment had no API
  key and its network policy blocks `app.heysummit.com`. `docs/api-notes.md`
  records the assumed shapes as UNVERIFIED and documents how to run
  `wp eex discover` from an environment with access. Per SPEC 11, M2
  (mappers) must not start until api-notes.md reflects real discovery
  output and has been reviewed.

**Deviations from spec:**

- The repository root is the Sender Symposium theme, so the plugin lives
  in the `emailexpert-events/` subdirectory rather than at the repo root
  (SPEC 12 assumed a dedicated repo). The CI workflow sits at the repo
  root and targets the subdirectory.
- PSR-4 file names (`Plugin.php`) instead of WPCS `class-*.php`, to match
  the spec's PSR-4 autoloader requirement; the corresponding PHPCS sniff
  is excluded.
