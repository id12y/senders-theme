# SPEC: emailexpert Events (HeySummit Connector for WordPress)
Build specification for Claude Code. Phases 1 and 2. Read it fully before writing code.
---
## 1. Project overview
emailexpert.com is a WordPress site. Events (emailexpert FORUM London, Deliverability Summit, Sender Symposium and others) run on HeySummit, including the Member Hub at hub.emailexpert.org.
This plugin syncs HeySummit event data into WordPress and renders it on emailexpert.com. It also receives HeySummit outgoing webhooks for registration activity.
Primary deployment context: at launch there is effectively one HeySummit event, the Member Hub (hub.emailexpert.org), running as an ongoing or evergreen event containing many sessions over time. Sessions are organised by HeySummit categories, and categories are the main dimension for splitting content on the site (for example, one category per programme strand, or a category used to group what would otherwise be separate events). More HeySummit events may be added later (FORUM London and others). Design consequences: nothing may assume multiple events exist; session-level components filtered by category are first-class citizens, not an afterthought; and event-level "upcoming versus past" logic must handle evergreen events, which never become past. Upcoming versus past for sessions is determined by the talk's start time against the current time, timezone aware.
Goals, in priority order:
1. Indexable event, talk and speaker content on emailexpert.com with full Schema.org markup. This is a GEO and SEO asset. It is the primary reason the plugin exists.
2. Display components (blocks and shortcodes) for upcoming events, past events, schedules, speakers and sponsors.
3. Real-time registration signals via webhooks: live counter, notifications, attribution log.
Non-goals for this build (do not implement, do not stub beyond noted extension points):
- On-site registration forms that POST attendees to HeySummit (Phase 3).
- WooCommerce ticketing bridge and external ticket sale import (Phase 3).
- Directory listing mapping and member badges (Phase 4).
- Anything that writes data to HeySummit. This build is read-only against the API.
## 2. Environment and stack
- WordPress 6.4+, PHP 8.1+, MySQL 8 / MariaDB 10.6+. Production runs Elementor Pro; the plugin must be fully functional without Elementor (blocks, shortcodes and PHP templates stand alone), with Elementor support as an optional module that loads only when Elementor is active (see 7.7).
- Plugin slug: `emailexpert-events`. Text domain: `emailexpert-events`. PHP namespace: `Emailexpert\Events`. Prefix for options, meta, hooks, CLI: `eex_`.
- No heavyweight frameworks. No Composer dependencies in the shipped plugin except optionally Action Scheduler (see 6.2). Dev dependencies (PHPUnit, PHPCS) are fine.
- Gutenberg blocks built with `@wordpress/scripts`. All blocks are dynamic (server-rendered). No block stores data in post content.
- Local development with `wp-env`. Provide a working `.wp-env.json`.
- Code style: WordPress Coding Standards (PHPCS with WPCS ruleset). All output escaped. All input sanitised. Nonces and capability checks on every admin action.
- British English in all user-facing strings.
## 3. HeySummit API reference
### 3.1 What is known
- Base URL v2: `https://app.heysummit.com/api/v2/`. A legacy v1 exists at `https://api.heysummit.com/api/`.
- Auth: header `Authorization: Token <API_KEY>`. API access requires the HeySummit Business plan.
- Responses are Django REST Framework style paginated JSON: `count`, `next`, `previous`, `results`.
- Known resources: `events/`, `events/<id>/`, `talks/`, `talks/<id>/`, `speakers/`, `attendees/`, `attendees/<id>/`, `categories/`, tickets data on attendee records.
- Event fields include: `id`, `title`, `event_url`, `first_talk_at`, `last_talk_at`, `is_live`, `is_archived`, `is_evergreen`, `is_open_for_registrations`.
- Attendee fields include: `email`, `name`, `registration_status`, `event_id`, `created_at`, `utm_source`, `utm_medium`, `utm_campaign`, `http_referer`, `affiliate_email`, `talks[]`, `tickets[]`.
### 3.2 What must be verified before hard-coding
The public v2 docs at https://api-v2.heysummit.com/ are JavaScript-rendered and were not fully readable at spec time. Before finalising the API client:
1. Run discovery requests against the live v2 API with the real key. Enumerate available endpoints, exact field names for talks and speakers, filter parameters, and page size limits.
2. Write findings into `docs/api-notes.md` in the repo as you go.
3. Where v2 differs from the v1 shapes above, v2 wins. Design the client so response mapping lives in one mapper class per resource and is easy to adjust.
Ask before proceeding if the key lacks access to a needed resource.
Discovery and sync run against live production events (FORUM London and the others) from the start. All GET requests against live data are safe and expected; the real data is what the mappers must be built from. Two restrictions only: never call any write or action endpoint (the API includes an event archive action; a stray POST against a live event ID would archive it), and do not create test registrations against live events. Webhook payload capture (8.1) is the only task that requires triggering registrations, and it uses a sandbox event or a single self-registration that is deleted afterwards.
### 3.3 Client requirements
- Single `HeySummitClient` class wrapping `wp_remote_get()`. Timeout 15s. Retries: 2 with backoff on 5xx and timeouts. No retry on 4xx.
- Respect pagination. Follow `next` until exhausted, with a hard safety cap (default 50 pages, filterable).
- Handle 401/403 by setting a persistent admin notice ("HeySummit API key invalid or lacks access") and aborting the sync run cleanly.
- Log every request outcome (endpoint, status, duration) to the sync log (see 6.5). Never log the API key.
## 4. Data model
### 4.1 Custom post types
All CPTs: `public => true`, `show_in_rest => true`, archive enabled, Gutenberg enabled but content is machine-managed (see 4.4).
| CPT | Slug | Purpose |
|---|---|---|
| `eex_event` | `/events/` | One per HeySummit event |
| `eex_talk` | `/sessions/` | One per talk |
| `eex_speaker` | `/speakers/` | One per speaker, deduplicated across events |
| `eex_sponsor` | `/sponsors/` | Manually managed in WP, not synced from the API |
### 4.2 Taxonomies
- `eex_event_series` on `eex_event` and `eex_talk`: FORUM, Deliverability Summit, Sender Symposium, Festival of Email. Manually assigned per event; talks inherit from their event.
- `eex_category` on `eex_talk`: synced from HeySummit categories.
- `eex_sponsor_tier` on `eex_sponsor`: Platinum, Gold, Silver, Partner. Tier order controls display order.
### 4.3 Key post meta
Every synced post: `_eex_heysummit_id` (indexed lookup key), `_eex_source_event_id`, `_eex_sync_hash` (hash of the mapped payload, used to skip unchanged records), `_eex_last_synced` (UTC timestamp), `_eex_raw` (last raw API payload, JSON), and `_eex_sync_mode` with three values: `synced` (default, updates flow in), `detached` (post and content kept, sync never overwrites anything on it again), `excluded` (post set to draft and skipped entirely on every future run, even if the record still exists in HeySummit). The mode is set via a meta box on the post edit screen and via quick edit on the list tables. Detached and excluded states are permanent until an editor changes them; sync never resets them.
Events additionally: `_eex_event_url`, `_eex_first_talk_at`, `_eex_last_talk_at`, `_eex_is_live`, `_eex_is_archived`, `_eex_is_open_for_registrations`, `_eex_registration_count` (maintained by webhooks, integer), plus manual fields editable in WP and never overwritten by sync: `_eex_venue_name`, `_eex_venue_address` (structured: street, locality, postcode, country), `_eex_hero_override`.
Talks: `_eex_starts_at`, `_eex_ends_at`, `_eex_speaker_ids` (array of WP speaker post IDs), `_eex_talk_url`, plus `_eex_replay_url` (replay or recording URL: sourced from the API if talk data exposes one, verified during discovery; otherwise a manual editor-owned field, and a manual value always wins over a synced one).
Speakers: `_eex_name`, `_eex_headline`, `_eex_company`, `_eex_photo_attachment_id`, `_eex_links` (array), and a manual field `_eex_directory_listing_id` (unused in this build; reserved for Phase 4, include in the meta registration only).
Sponsors (all manual): `_eex_logo_attachment_id`, `_eex_url` (with UTM parameters as entered), `_eex_event_ids` (which events the sponsorship applies to), `_eex_blurb`.
### 4.4 Ownership rules
- Sync owns: post title, excerpt/description, synced meta, category terms, speaker photo.
- Editors own: manual meta fields, event series terms, featured image if `_eex_hero_override` is set, and any additional content blocks an editor adds below a `<!-- eex:synced-content -->` marker comment. Sync must never destroy editor-added content. Simplest compliant approach: sync writes description into a dedicated meta field and the templates render from meta, leaving `post_content` entirely to editors. Take that approach.
## 5. Admin settings
Settings page under Settings → emailexpert Events. Sections:
1. **API**: connections are a list, not a single key, because different properties (the hub, FORUM, Deliverability Summit) may live under different HeySummit accounts. Each connection has a label and an API key. Stored in an option, write-only in the UI (show last 4 characters). With one connection the UI stays as simple as a single key field; "Add connection" reveals the list. Support a constant override `EEX_HEYSUMMIT_API_KEY` in `wp-config.php` for the first connection; when defined, that field is disabled and a notice says so. "Test connection" button per connection (AJAX, nonce-protected) that calls `events/` and reports the result. Event selection in the Sync section is grouped by connection, and every synced post records its connection ID in meta.
2. **Sync**: which HeySummit events to sync (multi-select populated from the API), then per enabled event a settings row with: independent toggles for talks, speakers and categories; photo sideloading on/off; import status for newly created posts (publish immediately, or pending review for editorial approval before anything appears on the site); and an optional category filter for talk import, either include-only (import talks in these categories only) or exclude (import everything except these categories), populated from the event's HeySummit categories. Talks excluded by the category filter are treated as if they do not exist: not created, and if previously synced, orphan-drafted. Global controls: sync frequency (hourly default, choices: 15 min, hourly, twice daily, daily), "Sync now" button, link to the sync log.
3. **Webhooks**: displays the receiver URL including the generated secret (see 8), button to regenerate the secret, per-action toggles for the notification behaviours.
4. **Display**: default event series colours, date format override, toggle for schema output.
Capability required: `manage_options`.
## 6. Sync engine
### 6.1 Flow
Per enabled event, per run, honouring that event's resource toggles (a disabled resource type is skipped entirely, and existing posts of that type are left untouched):
1. Fetch event detail. Map, hash, upsert `eex_event` by `_eex_heysummit_id`.
2. Fetch talks for the event (verify the correct filter parameter during API discovery). Upsert `eex_talk` posts. Resolve speaker relationships.
3. Fetch speakers. Deduplicate: match on HeySummit speaker ID first; if the same human appears under multiple events with different IDs, secondary match on normalised email if exposed, else on exact name plus company. Upsert `eex_speaker`.
4. Fetch categories. Sync into `eex_category` terms.
5. Orphan handling: any previously synced post whose HeySummit record no longer appears is set to `draft` and flagged with `_eex_orphaned = 1`. Never delete.
Upsert rules: before writing, check `_eex_sync_mode`. `detached` and `excluded` posts are skipped without any write. New posts are created with the event's configured import status (publish or pending). Posts an editor has moved to pending stay pending; sync updates content but never changes post status after creation. If `_eex_sync_hash` is unchanged, skip the write entirely.
### 6.2 Scheduling
- WP-Cron event `eex_sync_cron` at the configured frequency.
- If Action Scheduler is already present on the site (Woo installs it), use it for per-event jobs so one slow event cannot exhaust the run. Otherwise chunk within a single cron run with a time budget (max 20 seconds per run, resume via a queued continuation).
- "Sync now" triggers an immediate run via an async request, not inline in the admin request.
### 6.3 Media
Speaker photos: sideload once into the media library, store attachment ID, re-download only when the source URL changes (track `_eex_photo_source_url`). Set meaningful alt text (speaker name).
### 6.4 WP-CLI
Provide `wp eex sync [--event=<id>] [--force]`, `wp eex status`, and `wp eex orphans --list`. `--force` ignores hashes.
### 6.5 Sync log
Custom table `{$wpdb->prefix}eex_log`: id, timestamp, context (sync|webhook|api), level (info|warning|error), message, data (JSON). Admin list view under the settings page, filterable by context and level. Retention: 30 days, pruned daily. Redact email addresses in stored webhook payload data (replace the local part with its SHA-256 hash prefix); the attribution table is the system of record for attribution, the log is for debugging. All API failures and webhook receipts land here.
### 6.6 Sync health
A silently dead sync is the most likely long-term failure mode, so it must be loud:
- Track consecutive failed runs per connection. After 3, show a persistent admin notice; after 6, send an email to the admin address (toggleable).
- Register a WordPress Site Health test reporting last successful sync per event, webhook receipt recency, and cron reliability.
- `wp eex status` reports the same in the CLI.
## 7. Front-end output
### 7.1 Components
Each component ships as a dynamic Gutenberg block and an equivalent shortcode sharing one render callback. Blocks live under an "emailexpert Events" category. Every component that lists talks accepts `event` and `category` attributes (category accepts a slug or comma-separated slugs); with one event configured, `event` may be omitted and defaults to the sole synced event.
| Component | Block | Shortcode | Notes |
|---|---|---|---|
| Upcoming sessions | `eex/upcoming-sessions` | `[eex_upcoming_sessions category="" limit="6"]` | Talks with start time in the future, soonest first. Card: title, date and time, category badge, speaker chips, register CTA. The workhorse component for the hub |
| Past sessions | `eex/past-sessions` | `[eex_past_sessions category="" limit="12" paginate="1"]` | Talks with start time in the past, newest first, paginated archive grid |
| Upcoming events | `eex/upcoming-events` | `[eex_upcoming_events limit="3" series=""]` | Event-level cards for multi-event futures. Evergreen events with registrations open always count as upcoming |
| Past events | `eex/past-events` | `[eex_past_events]` | Event-level archive, newest first. Evergreen events never appear here |
| Countdown | `eex/countdown` | `[eex_countdown event="" talk=""]` | Counts to an event's first talk or to a specific session. Vanilla JS, no jQuery. Graceful text fallback without JS |
| Schedule | `eex/schedule` | `[eex_schedule event="" category=""]` | Grouped by day, ordered by start time, category tag per talk, speaker chips linking to speaker pages. Times rendered in event local time with timezone label |
| Speaker grid | `eex/speakers` | `[eex_speakers event="" category="" columns="4"]` | Photo, name, headline, company. Category filter shows speakers with at least one talk in that category. Links to speaker single |
| Featured talks | `eex/featured-talks` | `[eex_featured_talks event="" ids=""]` | Manual selection via attribute |
| Sponsors wall | `eex/sponsors` | `[eex_sponsors event=""]` | Grouped by tier in tier order, logos link out with `rel="sponsored noopener"` |
| Registration counter | `eex/reg-counter` | `[eex_reg_counter event=""]` | Renders `_eex_registration_count` with a threshold attribute (hide below N, default 50) |
All components render from the local database only. No API calls in any front-end request path. Server-rendered output may be cached in a transient keyed by component and attributes, TTL 5 minutes, flushed on sync completion and webhook receipt.
Card behaviour requirements, applying wherever sessions render:
- **Live now**: while a session is between start and end time, its card shows a live indicator and the CTA becomes "Join now" linking to `_eex_talk_url`. Within 60 minutes of start, show "Starting soon".
- **Add to calendar**: every upcoming session card and session single page offers an `.ics` download and a Google Calendar link, generated server-side from the talk data.
- **Replays**: past session cards and singles with `_eex_replay_url` show a "Watch replay" CTA; on the single page, embed the replay when the URL is embeddable (YouTube, Vimeo, standard oEmbed), otherwise link out. This turns the past-sessions archive into a browsable content library rather than a dead list.
- **Empty states**: every listing block has an `empty_text` attribute (sensible default such as "New sessions are announced soon"). Blocks never render a blank void or a PHP notice when the query is empty.
### 7.2 Templates
Provide `single-eex_event.php`, `single-eex_talk.php`, `single-eex_speaker.php`, archive templates, and `taxonomy-eex_category.php` via the template loader pattern: theme override in `emailexpert-events/` directory wins, plugin fallback otherwise. The category archive matters: each synced category becomes an indexable page listing its upcoming and past sessions, which is where the "split past events by category" model surfaces on the site. Speaker singles must list that speaker's upcoming and past sessions (with replay links where present), so each speaker page is a genuine profile rather than a photo and a bio. Fallback templates must be clean, semantic and unopinionated (inherit theme typography; minimal plugin CSS, one small stylesheet, no framework). Templates are built from overridable template parts (card, speaker chip, schedule row), so a theme can restyle a card without replacing a whole template.
### 7.3 Structured data
Output JSON-LD in `wp_head` on relevant pages. This is a hard requirement and must validate in Google's Rich Results Test.
- Event pages: `@type: Event` (use `BusinessEvent` for FORUM-style conferences), with `name`, `startDate`, `endDate`, `eventAttendanceMode`, `location` (from manual venue meta; `Place` with `PostalAddress` for in-person, `VirtualLocation` with `_eex_event_url` for online), `organizer` (emailexpert UK Ltd), `offers` when `is_open_for_registrations` is true (URL only if price data is unavailable; omit price rather than guessing), `performer` array from speakers.
- Talk pages: `Event` nested with `superEvent` reference to the parent event. Past talks with a replay additionally emit `VideoObject` (`name`, `description`, `uploadDate`, `contentUrl` or `embedUrl`), which is what makes the session library surface in video search results.
- Speaker pages: `Person` with `name`, `jobTitle`, `worksFor`, `image`, `sameAs` from links.
- Emit nothing when required fields are missing. Never emit placeholder values.
SEO plugin coexistence: emailexpert.com may run Yoast, Rank Math or similar, which output their own schema graphs. Detect the active SEO plugin; where it supports graph integration (both Yoast and Rank Math expose filters), inject the Event/Person/VideoObject pieces into its graph rather than emitting a second standalone block. Duplicate conflicting schema on one page is worse than none. Provide per-type schema toggles in Display settings as the manual escape hatch. When no SEO plugin handles these CPTs, also output basic Open Graph and Twitter card tags (title, description, speaker photo or event hero) so shared session and speaker links unfurl properly.
### 7.4 Time handling and cache safety
The audience is global and the site may sit behind full-page caching (host cache, Cloudflare, a caching plugin). Both facts shape one rule: server-rendered HTML never bakes in time-relative state.
- All timestamps render as `<time datetime="...">` with UTC in the attribute and event-local time as the visible fallback text.
- A small vanilla JS module (one file, loaded only when components are present) converts visible times to the visitor's timezone with a timezone label, and computes upcoming/starting-soon/live-now/past states client-side.
- Countdown and live-now must be correct even when the cached HTML is hours old. Without JS, the fallback is event-local times with the timezone stated, and no live-state claims.
- The registration counter refreshes via a lightweight public REST read (`GET /wp-json/eex/v1/counter/<event>`) so a cached page never shows a stale number; the server-rendered figure is the no-JS fallback.
### 7.5 Calendar feeds
Beyond per-session `.ics` downloads (7.1), provide subscribable feeds: `/feeds/eex/calendar.ics` for all upcoming sessions, filterable by `?event=` and `?category=`. A member subscribes once and every future hub session lands in their calendar automatically. Feeds regenerate on sync, are cached to disk or transient, validate against RFC 5545, and include the session URL and speaker names in the description. Surface a "Subscribe to calendar" link in the upcoming-sessions block via an attribute.
### 7.6 Front-end quality bar and extension surface
- **Accessibility**: WCAG 2.2 AA. Semantic headings, keyboard-reachable interactive elements, visible focus states, `aria-live` on the countdown, alt text everywhere, colour contrast of category and series badges checked programmatically against their backgrounds.
- **Performance**: assets enqueue only on pages where a component is present. Speaker and hero images use `loading="lazy"`, responsive `srcset` from sideloaded sizes, and explicit width/height to prevent layout shift. No jQuery, no icon fonts, no CSS framework. Budget: plugin CSS and JS combined under 30KB gzipped.
- **Theming**: all colours, spacing and radii in plugin CSS defined as CSS custom properties (`--eex-*`) with sane defaults, so restyling means overriding variables in the theme, not editing plugin files.
- **Extension surface**: documented filters on every component query (`eex_query_args`), on card output (`eex_card_html`), on schema arrays (`eex_schema_data`), and the template parts in 7.2. These, plus the webhook actions in 8.2, are the complete public API of the plugin. Note for later: because CPTs are `show_in_rest`, other properties (festivalofemail.com, sendersymposium.com) can consume the synced data over the WP REST API with no extra work; do not build anything that assumes rendering happens only on this site.
### 7.7 Elementor integration (optional module)
Production runs Elementor Pro, but this module is strictly additive. It lives in `src/Elementor/`, registers on `elementor/init`, and none of its files load when Elementor is absent. Every capability it provides must also be achievable without it (shortcodes work in Elementor's Shortcode widget regardless). Detect Elementor and Elementor Pro separately; Pro-only features (dynamic tags in Theme Builder, Loop Grid queries) degrade silently on free Elementor.
1. **Native widgets.** One Elementor widget per component in 7.1, in an "emailexpert Events" widget category, wrapping the exact same render callbacks as the blocks and shortcodes. Content controls map one-to-one to the component attributes (event, category, limit, columns, empty_text and so on), with the event and category controls populated as select fields from synced data. Style controls (colour, typography, spacing groups) write to the `--eex-*` CSS custom properties scoped to the widget wrapper, so Elementor styling and theme styling use the same mechanism and cannot conflict.
2. **Theme Builder compatibility.** When an Elementor Pro theme template's display conditions match an `eex_` single or archive view, the plugin's template loader must yield completely and let Elementor render. No double headers, no fallback markup leaking through. This is a correctness requirement, not a feature.
3. **Dynamic Tags.** Register a tag group exposing synced data for use in Theme Builder: session start and end (formatted, site or event timezone), category list, register URL, replay URL, event URL, live status, speaker name, headline, company, photo, links, event registration count, venue fields. Tags return empty (not placeholder text) when data is missing, consistent with the schema policy.
4. **Loop Grid query IDs.** Register named queries via `elementor/query/{id}`: `eex_upcoming_sessions`, `eex_past_sessions`, `eex_event_sessions`, each respecting the same ordering and evergreen rules as the components, so Loop Grid card designs get correct data without the UI needing to express meta date logic.
5. **Editor experience.** Widgets render real previews in the Elementor editor (server-side render, same path as the front end). No "preview unavailable" placeholders.
The card behaviour requirements in 7.1 (live-now, add to calendar, replays, empty states) and the time handling rules in 7.4 apply identically inside Elementor-rendered output, because the render callbacks are shared. Do not fork rendering logic for Elementor under any circumstances; the module is a thin control-mapping layer.
## 8. Webhook receiver
### 8.1 Endpoint
REST route: `POST /wp-json/eex/v1/heysummit/<secret>`. The secret is a 32+ character random token generated on activation, shown on the settings page, regenerable. Constant-time comparison. Requests with a bad secret get 404 (not 403, do not confirm the route exists). Also rate-limit: max 60 requests per minute per IP via transient counter, 429 beyond that.
HeySummit sends outgoing webhooks only, as HTTP POST with a JSON body, for three actions: **Attendee registration started**, **Checkout complete**, and **Talk added to attendee schedule**. Failed deliveries are retried every 15 minutes up to 3 times, so the endpoint must be idempotent. Deduplicate on a hash of (action, attendee identifier, timestamp bucket).
HeySummit does not sign payloads. Treat payload data as untrusted. For any action that mutates meaningful state, verify by fetching the attendee record back from the API using the ID in the payload, and use the fetched data, not the payload.
Payload shapes are not fully documented. During development, register a webhook against a test event, capture real payloads for all three actions into `docs/webhook-payloads.md`, and build the parser from those.
### 8.2 Behaviours
On receipt, always: log raw payload to the sync log (context `webhook`), respond 200 immediately, process asynchronously (queued single event).
Then per action, gated by settings toggles:
1. **Checkout complete**: increment `_eex_registration_count` on the matching event (map via HeySummit event ID). Insert a row into the attribution table (8.3). Fire `do_action( 'eex_checkout_complete', $attendee, $event_post_id )`. Optional admin email notification.
2. **Registration started**: insert attribution row with status `started`. Fire `do_action( 'eex_registration_started', ... )`. Schedule a single check 60 minutes later; if no matching checkout complete has arrived for that email and event, fire `do_action( 'eex_registration_abandoned', ... )`. The plugin fires the hook only. It sends no email to attendees. Downstream automation is out of scope.
3. **Talk added**: fire `do_action( 'eex_talk_signup', ... )` and log. No other behaviour in this build.
The three `do_action` hooks are the official extension surface for Phase 3+ and for ESP integration. Document them in the README with payload shapes.
### 8.3 Attribution table
`{$wpdb->prefix}eex_attribution`: id, created_at, event_hs_id, attendee_hs_id, email_hash (SHA-256 of lowercased email; do not store the raw email in this table), status (started|completed), utm_source, utm_medium, utm_campaign, referer_domain, affiliate_email, ticket_name, amount_gross. Admin report screen: table plus totals by utm_source and by status, filterable by event and date range, CSV export (nonce-protected, `manage_options`).
Privacy compliance: register with the WordPress personal data exporter and eraser (Tools → Export/Erase Personal Data). Given a requester's email, hash it the same way and export or delete matching attribution rows and any log entries carrying the matching hash. Attribution retention is configurable (default 24 months) with automatic pruning. This is an email industry property; its own data handling has to be exemplary.
## 9. Security checklist (enforced, not aspirational)
- API key never rendered in full, never in front-end output, never in logs, never in REST responses.
- All admin actions: capability check plus nonce. All AJAX: same.
- All output through `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` as appropriate. All DB access through `$wpdb->prepare` or higher-level APIs.
- Webhook payloads sanitised before storage; `_eex_raw` meta and log data JSON-encoded, never unserialised PHP.
- Uninstall routine (`uninstall.php`): remove options, custom tables, scheduled events. Leave CPT content by default; delete only if an "on uninstall, delete all data" option was enabled.
## 10. Testing and acceptance
### 10.1 Automated
- PHPUnit via `wp-env`. Cover: client pagination and retry logic (mocked HTTP), mapper output for each resource, upsert idempotency (same payload twice produces one post and one write), hash skipping, orphan drafting, webhook secret comparison, webhook dedupe, attribution insertion, schema generator output for complete and incomplete data.
- Fixtures: store captured real API responses (redacted) under `tests/fixtures/`.
- PHPCS passes with WPCS. CI config (GitHub Actions) running PHPCS and PHPUnit on PHP 8.1 and 8.3.
### 10.2 Acceptance criteria
1. Fresh install, key entered, two events enabled: within one manual sync, event, talk and speaker posts exist and render on the fallback templates.
2. Editing a synced talk's title in WP and re-syncing restores the HeySummit title; editing venue meta and re-syncing preserves it.
3. Running sync twice in a row performs zero post writes on the second run (verified via log).
4. Every block renders correctly in the editor preview and front end, and each shortcode matches its block output.
5. Event page JSON-LD passes the Rich Results Test with zero errors for an event that has venue meta filled in.
6. Webhook: a simulated checkout-complete POST with the correct secret increments the counter once, even when delivered three times.
7. A POST with the wrong secret returns 404 and logs nothing to attribution.
8. Site continues to render all components with the API unreachable.
9. No front-end page load triggers an outbound HTTP request to HeySummit (verify with a request logger).
10. A speaker set to detached keeps a hand-edited bio through repeated forced syncs; a talk set to excluded stays in draft through repeated forced syncs even though the record exists in HeySummit.
11. With speakers toggled off for an event, a forced sync creates and updates no speaker posts, and previously synced speakers are left untouched.
12. With import status set to pending review, newly synced items do not appear on the front end until approved, and approval survives subsequent syncs.
13. With a single evergreen event configured: upcoming and past sessions blocks split that event's talks correctly by start time; the category attribute filters both; the evergreen event never appears in past events; and no component errors or renders oddly because only one event exists.
14. A per-event category exclude filter removes matching talks on the next sync (orphan-drafted) and they do not return on subsequent runs.
15. A session's `.ics` download imports cleanly into Google Calendar, Apple Calendar and Outlook with correct time, title and URL; the subscribe feed validates against RFC 5545 and filters by category.
16. With JS disabled, session times display in event-local time with a timezone label and no live-state claims; with JS enabled, times display in the visitor's timezone and a session in progress shows Join now.
17. Serving a component from HTML cached before a session started still shows the correct live state via JS, and the registration counter shows the current figure via the REST read.
18. A past session with a YouTube replay URL embeds the player and emits valid VideoObject JSON-LD; with Yoast active, Event schema appears once on the page, inside the Yoast graph.
19. A personal data erasure request for a known attendee email removes their attribution rows, verified by hash lookup before and after.
20. Axe or an equivalent checker reports no critical accessibility violations on the fallback templates, and combined plugin CSS and JS is under 30KB gzipped.
21. With Elementor deactivated, the plugin loads no Elementor code and every feature works via blocks, shortcodes and PHP templates.
22. With Elementor Pro active: each widget's front-end output matches its block equivalent byte-for-byte apart from wrapper classes; dynamic tags resolve correctly on a Theme Builder session template and render empty for missing data; a Loop Grid using `eex_upcoming_sessions` returns only future sessions soonest first; and a Theme Builder template targeting session singles fully replaces the plugin's PHP template with no leaked markup.
## 11. Milestones
Work in this order. Each milestone ends with passing tests and a short summary in `docs/progress.md`. Stop and ask if API discovery (M1) contradicts this spec materially.
- **M1. Scaffold and API discovery.** Plugin skeleton, settings page with connections, client, live endpoint discovery, `docs/api-notes.md`.
- **M2. Data layer.** CPTs, taxonomies, meta registration, mappers, upsert logic, log table.
- **M3. Sync engine.** Cron, chunking, media sideloading, orphans, WP-CLI, Sync now, sync health.
- **M4. Display.** Blocks, shortcodes, templates and template parts, time handling module, calendar downloads and feeds, replays, schema with SEO plugin coexistence, caching, quality bar.
- **M5. Elementor module.** Widgets, Theme Builder yield, dynamic tags, Loop Grid queries, editor previews. Test against Elementor Pro in wp-env.
- **M6. Webhooks.** Receiver, payload capture, behaviours, attribution table and report, counter REST read, privacy exporter and eraser.
- **M7. Hardening.** Security pass, accessibility pass, uninstall routine, README (installation, hooks reference, shortcode reference, filter reference, Elementor guide), CI.
## 12. Repo conventions
- Repo root is the plugin. `emailexpert-events.php` bootstrap, `src/` (PSR-4 via a small autoloader, no Composer requirement at runtime), `blocks/`, `templates/`, `assets/`, `tests/`, `docs/`, `.wp-env.json`, `.github/workflows/ci.yml`.
- Conventional commits. One milestone per branch is unnecessary; commit granularly on main or a single dev branch.
- Keep a `CLAUDE.md` at the root (starter content below). Update it whenever a convention is decided during the build.
