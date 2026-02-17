# Geek Layer Content Pack — Emailexpert

Internal reference document. Not served publicly.

---

## 1. Concept Overview

The Geek Layer is a controlled, lightweight content layer that rewards technically
curious visitors — people who view source, open devtools, or inspect robots.txt —
with subtle signals of craftsmanship and CRM maturity.

It does not affect performance, layout, SEO, or security. It reinforces authority
without distracting from the core experience. It exists for the same reason a
well-structured database schema does: because the people who notice it are the
people worth impressing.

The layer is gated behind a single feature flag and can be removed in seconds.

---

## 2. Content Pack

### A. HTML Source Comments

Placed in the global footer template (`footer.php`), just before `wp_footer()`.

**Deployed variant:**

```html
<!--
  Curious minds tend to run better pipelines.
  If you are reading this, you probably segment with intent.
  emailexpert.com
-->
```

**Alternative variants (swap as desired):**

Variant 2 — Pipeline discipline:
```html
<!--
  Clean data in, clean decisions out.
  Pipeline discipline is a growth system.
  emailexpert.com
-->
```

Variant 3 — Lifecycle:
```html
<!--
  A lifecycle without segmentation is just a list with anxiety.
  Build systems that compound.
  emailexpert.com
-->
```

Variant 4 — Founders/operators:
```html
<!--
  Built by operators, for operators.
  The best CRM work happens where nobody is watching.
  emailexpert.com
-->
```

Variant 5 — Automation:
```html
<!--
  If your automation requires daily intervention,
  it is not automation. It is a todo list.
  emailexpert.com
-->
```

Variant 6 — Networking invitation:
```html
<!--
  You found the layer most people never see.
  If you hold a ticket, reply to your order confirmation
  with "Closed Won" and we will be in touch.
  emailexpert.com
-->
```

Variant 7 — Craft:
```html
<!--
  Good senders test, measure, and iterate.
  Great senders view source.
  emailexpert.com
-->
```

Variant 8 — Systems thinking:
```html
<!--
  Deliverability is a symptom. Systems are the cause.
  emailexpert.com
-->
```

---

### B. Console Messages

**Primary (deployed):**

```
%cYou read source. We respect that.                          [bold]
Emailexpert is built for operators who care about pipeline discipline,
lifecycle architecture, and systems that compound.
If you hold a ticket, reply to your order confirmation with
"Closed Won" for access to a private networking experience.
Curious minds tend to run better pipelines. Welcome.
```

**Alternate Variant 1 (shorter):**

```
%cEmailexpert — built for operators.                         [bold]
Reply "Closed Won" to your order confirmation for something worth attending.
```

**Alternate Variant 2 (shorter):**

```
%cYou inspect. We appreciate that.                           [bold]
Ticket holders: reply "Closed Won" to your confirmation email.
The operators' table has a seat for you.
```

---

### C. robots.txt Easter Egg

Appended to the WordPress virtual robots.txt via the `robots_txt` filter.

**Deployed:**

```
# ----------------------------------------------------------
# If you are reading this, you probably automate with intent.
# Good bots segment before they crawl.
# The best pipelines are the ones nobody has to babysit.
# emailexpert.com — systems that compound.
# ----------------------------------------------------------
```

**Notes:**
- No real paths referenced.
- No `Disallow` directives for actual admin routes.
- No impact on crawl behaviour.

---

### D. 404 Source Comment

Placed in `404.php` above the page content container.

**Deployed:**

```html
<!-- This page returned nothing. Much like a lifecycle with no nurture sequence. -->
```

---

### E. Optional Hidden Static Route — /operators

Not deployed in this release. Available for future use.

**Page title:** Operators

**Headline:** You found the quiet room.

**Body:**

This page is not linked from the navigation. It is not indexed. It exists
for the same reason a well-maintained suppression list exists — because
attention to what most people ignore is what separates operators from
everyone else.

Emailexpert is built for people who think in systems: lifecycle architecture,
pipeline discipline, segmentation logic, and the compound growth that follows
when these things are done well.

If you hold a ticket and you are reading this, reply to your order
confirmation email with "Closed Won." We will know what it means.

---

## 3. Networking Invite Framing (Internal Use Only)

This section is never visible in source code or to the public.

### Event Positioning

A private, capacity-limited networking experience exclusively for Emailexpert
ticket holders who demonstrate operator-level curiosity. This is not a VIP
upsell. It is a curated gathering for senior practitioners, founders, and
CRM operators who build systems rather than chase tactics.

### Who It Is For

- CRM operators and lifecycle managers
- Marketing technologists and automation architects
- Founders and senior practitioners who run their own growth systems
- People who read source code, inspect headers, and think in data structures

### Why It Exists

To create a space where the people who actually build revenue infrastructure
can connect without the noise of a general conference floor. The barrier is
intentional curiosity, not budget.

### Tone Guidance for Replies

When responding to someone who emails with "Closed Won":

- Acknowledge them as a peer, not a winner.
- Keep it brief and professional.
- Do not overhype the event or imply exclusivity beyond capacity.
- Do not disclose how many people have been invited.
- Do not mention this document or the Geek Layer by name.

### Example Response Template

```
Subject: Re: [Original order confirmation subject]

Good to hear from you.

You have been added to the list for a private networking session
at Emailexpert. Details will follow closer to the event.

This is a small, curated group — operators, builders, and senior
practitioners. No panels, no pitches. Just the right people in
the right room.

We will be in touch with logistics.

[Sender name]
Emailexpert
```

---

## 4. Micro Implementation

### Feature Flag

Defined in `functions.php`:

```php
if ( ! defined( 'SS_GEEK_LAYER_ENABLED' ) ) {
    define( 'SS_GEEK_LAYER_ENABLED', true );
}
```

This allows a `wp-config.php` override:

```php
define( 'SS_GEEK_LAYER_ENABLED', false );
```

### JavaScript (assets/js/geek-layer.js)

```javascript
(function () {
    'use strict';
    var GL = window.SS_GEEK_LAYER_ENABLED;
    if (typeof GL === 'undefined' || !GL) return;
    console.log('%cYou read source. We respect that.', 'font-weight:bold');
    console.log(
        'Emailexpert is built for operators who care about pipeline discipline,\n' +
        'lifecycle architecture, and systems that compound.'
    );
    console.log(
        'If you hold a ticket, reply to your order confirmation with\n' +
        '"Closed Won" for access to a private networking experience.'
    );
    console.log('Curious minds tend to run better pipelines. Welcome.');
})();
```

### Enqueue

The script is enqueued via `wp_enqueue_scripts` at priority 20 with `defer`
strategy. The feature flag is passed as an inline script (`before`) so it is
available when the IIFE executes.

### HTML Comment Placement

The footer comment is placed in `footer.php` immediately before `wp_footer()`.
The 404 comment is placed in `404.php` above the content container.

---

## 5. Kill Switch

### Instant Disable (single flag)

Add to `wp-config.php`:

```php
define( 'SS_GEEK_LAYER_ENABLED', false );
```

This disables:
- Console script enqueue
- robots.txt easter egg lines
- HTML source comments in footer.php and 404.php

### Remove Console Output

Delete `assets/js/geek-layer.js` and remove the `ss_enqueue_geek_layer`
function and its `add_action` call from `functions.php`.

### Remove robots.txt Lines

Remove the `ss_geek_layer_robots` function and its `add_filter` call
from `functions.php`.

### Remove HTML Comments

Remove the comment blocks from `footer.php` and `404.php`.

### Confirmation

Nothing persists after removal. No database entries. No cookies.
No localStorage. No cached state. The feature flag constant prevents
any output when set to `false`.

---

## 6. Security and Performance Compliance Checklist

- [x] No database reads or writes
- [x] No cookies set or read
- [x] No user input accepted or interpolated
- [x] No additional network calls (script is local, deferred)
- [x] No discount codes, promo codes, or pricing exposed in source
- [x] No real admin paths, internal routes, or sensitive URLs referenced
- [x] No internal email addresses exposed
- [x] No query-parameter-triggered behaviour
- [x] No dynamic content — all copy is static
- [x] No DOM manipulation — console output only
- [x] No layout shifts — no visible UI changes
- [x] No external library dependencies
- [x] JS under 1KB minified (script body is approximately 450 bytes)
- [x] No measurable performance impact (deferred, in-footer, static)
- [x] No SEO impact — HTML comments are ignored by crawlers; robots.txt
      lines are comments only with no directive changes
- [x] No customer-facing confusion — nothing visible unless devtools are open
- [x] Single constant kill switch for instant disable
- [x] No secrets, tokens, or credentials in source
