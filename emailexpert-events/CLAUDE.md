# emailexpert Events

WordPress plugin syncing HeySummit events into WP. Read SPEC-emailexpert-events.md first. It is the source of truth.

## Commands

- Start env: `wp-env start`
- Tests: `wp-env run tests-cli --env-cwd=wp-content/plugins/emailexpert-events vendor/bin/phpunit`
  (pure unit tests also run standalone: `composer test` — they use the WP shims in `tests/bootstrap.php`)
- Lint: `composer phpcs`
- Build blocks: `npm run build` (dev: `npm start`)
- Manual sync: `wp-env run cli wp eex sync --force`
- API discovery (read-only): `wp-env run cli wp eex discover`

## Conventions

- Namespace `Emailexpert\Events`, prefix `eex_`, text domain `emailexpert-events`.
- British English in UI strings. No em-dashes in user-facing copy.
- Dynamic blocks only. Render callbacks shared with shortcodes.
- No front-end API calls. Ever.
- Sync owns synced fields; editors own manual meta. See SPEC section 4.4.
- Elementor code lives only in `src/Elementor/`, loads only when Elementor is active, and never forks render logic. See SPEC 7.7.
- API response mapping lives only in `src/Mappers/`. Update `docs/api-notes.md` when the live API differs from the spec.
- File names are PSR-4 (`Plugin.php`, not `class-plugin.php`) to match the spec's autoloader; the WPCS filename sniff is excluded in phpcs.xml.dist.
- The API client is GET-only by construction. Never add a write path against HeySummit in this build (a stray POST can archive a live event).
- This plugin lives in the `emailexpert-events/` subdirectory of the senders-theme repo; the CI workflow is at the repo root (`.github/workflows/eex-plugin-ci.yml`).

## Secrets

- HeySummit API key comes from `EEX_HEYSUMMIT_API_KEY` in the local wp-config. Never commit it, never log it.
