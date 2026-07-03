# HeySummit API notes

Working notes for the HeySummit v2 API, maintained during discovery and
mapper development. The spec (SPEC-emailexpert-events.md, section 3) says
v2 wins wherever it differs from the assumed v1 shapes below.

## Status: live discovery PENDING

Live discovery has **not yet run**. The build environment for Milestone 1
had no HeySummit API key available (`EEX_HEYSUMMIT_API_KEY` was not
defined) and its network policy blocks outbound requests to
`app.heysummit.com` (the proxy refuses the CONNECT tunnel). Everything in
"Assumed shapes" below is from the spec, not from observation, and must
not be treated as confirmed.

### How to run discovery

From any WordPress install (wp-env or staging) that has the plugin active,
a connection configured (or `EEX_HEYSUMMIT_API_KEY` defined in
`wp-config.php`), and outbound access to `app.heysummit.com`:

```
wp eex test-connection
wp eex discover --output=wp-content/plugins/emailexpert-events/docs/api-notes.md
```

`wp eex discover` is strictly read-only (GET only — the prober class
exposes no way to issue any other verb, because the API includes an event
archive action and a stray POST against a live event would archive it).
It appends a timestamped report to this file covering:

- every resource's list and detail field names with observed types,
- which event-filter parameter (`event`, `event_id`, `event__id`,
  `events`) each collection honours,
- `page_size` behaviour,
- candidate replay/recording fields on talks (for `_eex_replay_url`).

Attendee email addresses are redacted before they reach the report.

## Confirmed facts (from spec / vendor documentation)

- Base URL v2: `https://app.heysummit.com/api/v2/`. Legacy v1 at
  `https://api.heysummit.com/api/`.
- Auth header: `Authorization: Token <API_KEY>`. Requires the HeySummit
  Business plan.
- Responses are DRF-style paginated JSON: `count`, `next`, `previous`,
  `results`.
- Known resources: `events/`, `events/<id>/`, `talks/`, `talks/<id>/`,
  `speakers/`, `attendees/`, `attendees/<id>/`, `categories/`; ticket data
  rides on attendee records.

## Assumed shapes (UNVERIFIED — verify before building mappers)

Event fields: `id`, `title`, `event_url`, `first_talk_at`, `last_talk_at`,
`is_live`, `is_archived`, `is_evergreen`, `is_open_for_registrations`.

Attendee fields: `email`, `name`, `registration_status`, `event_id`,
`created_at`, `utm_source`, `utm_medium`, `utm_campaign`, `http_referer`,
`affiliate_email`, `talks[]`, `tickets[]`.

Talk and speaker field names: **unknown**. The public v2 docs at
https://api-v2.heysummit.com/ are JavaScript-rendered and were not
readable at spec time.

## Open questions for discovery

1. Exact talk fields: start/end timestamps (names? timezone?), category
   linkage, speaker linkage (IDs or embedded objects?), URL field, any
   replay/recording URL.
2. Exact speaker fields: name parts, headline, company, photo URL, links;
   is email exposed (needed for cross-event dedupe)?
3. The correct filter parameter to scope `talks/` (and `speakers/`,
   `categories/`) to one event.
4. Page size limits and maximum.
5. Whether `categories/` is global or per-event.
6. Rate limits (headers? 429 behaviour?).
7. Whether the key can read `attendees/` (needed for webhook verification
   in M6). Ask before proceeding if access is missing (spec 3.2).

## Client behaviour (implemented, spec 3.3)

- `HeySummitClient` wraps `wp_remote_get()`: timeout 15 s; two retries
  with backoff (1 s, 2 s — filter `eex_client_retry_delay`) on 5xx and
  network errors; no retry on 4xx.
- Pagination followed via `next` until exhausted, hard cap 50 pages
  (filter `eex_client_max_pages`); `next` URLs must stay on the API host
  or the crawl aborts.
- 401/403 → `WP_Error` code `eex_auth`; callers flag the persistent
  admin notice ("HeySummit API key invalid or lacks access") and abort.
- Every request logs endpoint, status and duration through the `eex_log`
  action. The key travels only in the request header and is never logged.

## Mapper policy

Response mapping will live in one mapper class per resource under
`src/Mappers/` (Milestone 2). Mappers are built from the discovery report
in this file — not from the assumed shapes — and any divergence from the
spec is recorded here as it is found.
