# Sender Symposium — WordPress Theme

A premium, performance-first WordPress theme for Sender Symposium. Designed for Elementor, accessible (WCAG 2.2 AA), dark/light mode, variable font support, and fully admin-configurable.

**Design tone:** Architectural, sculptural, calm authority, premium, European.
**Venue inspiration:** La Pedrera (Casa Mila), Barcelona.

---

## Setup & Installation

1. **Upload** the `senders-theme` folder to `wp-content/themes/`
2. **Activate** the theme in Appearance → Themes
3. **Add the font:** Place your licensed `Barcelona-Variable.woff2` in `assets/fonts/` (see `assets/fonts/README.md`)
4. **Configure:** Go to Appearance → Sender Symposium Settings to customise fonts, colours, layout, and dark mode
5. **Set up menus:** Appearance → Menus — assign to "Primary Navigation" and "Footer Navigation"
6. **Set homepage:** Settings → Reading → "A static page" → select your home page

### Requirements
- WordPress 6.4+
- PHP 8.0+
- Elementor (recommended for layout/templates)

---

## Theme Structure

```
senders-theme/
├── style.css                  # Theme header (required by WP)
├── functions.php              # Theme setup, enqueues, settings
├── header.php                 # Site header with accessible nav + theme toggle
├── footer.php                 # Site footer with grid layout
├── index.php                  # Main template
├── page.php                   # Default page template
├── single.php                 # Single post template
├── front-page.php             # Homepage template with all sections
├── 404.php                    # 404 error page
├── inc/
│   └── admin-settings.php     # Settings API page (Appearance → SS Settings)
├── templates/
│   ├── template-canvas.php    # Full-width canvas (no header/footer — for Elementor)
│   └── template-hero.php      # Page with hero section
├── parts/
│   ├── hero.php               # La Pedrera-inspired hero partial
│   └── announcement-bar.php   # Top announcement bar partial
└── assets/
    ├── css/
    │   ├── tokens.css         # Design tokens (palettes, spacing, typography)
    │   ├── base.css           # Reset, typography, accessibility, layout primitives
    │   ├── hero.css           # Hero section styles
    │   └── components.css     # All component styles
    ├── js/
    │   ├── theme-toggle.js    # Dark/light mode toggle
    │   ├── navigation.js      # Mobile nav toggle (accessible)
    │   ├── accordion.js       # FAQ accordion enhancement
    │   └── admin-settings.js  # WP color picker for admin
    ├── fonts/
    │   └── Barcelona-Variable.woff2  # (add your licensed font)
    └── img/                   # Optional optimised images
```

---

## Elementor Usage

### Global Styles Mapping
Map Elementor's Global Colors and Global Fonts to the theme's design tokens:

| Elementor Global    | Theme Token                |
|---------------------|----------------------------|
| Primary Color       | `--action-primary-bg`      |
| Secondary Color     | `--text-secondary`         |
| Text Color          | `--text-primary`           |
| Accent Color        | `--focus-ring`             |
| Primary Font        | `--font-display`           |
| Secondary Font      | `--font-body`              |

### Templates
- **Default:** Standard page with container width and header/footer
- **Canvas (Full Width):** No header, no footer, no container padding — ideal for full Elementor page builds
- **Page with Hero:** Includes the La Pedrera hero section above page content

### Theme Builder
The theme supports Elementor Theme Builder for custom headers and footers. If you create header/footer templates in Elementor, the theme's default header/footer will be replaced automatically.

---

## How to Change Fonts

### Via Admin Settings (recommended)
1. Go to **Appearance → Sender Symposium Settings**
2. Under **Fonts**, set:
   - **Display Font Family:** CSS font-family stack for headings (e.g., `"My Font", Georgia, serif`)
   - **Body Font Family:** CSS font-family stack for body text
   - **Custom Display Font File URL:** URL to your local .woff2 file

### Via Theme Files
1. Replace `assets/fonts/Barcelona-Variable.woff2` with your font file
2. Update the `@font-face` declaration in `functions.php` → `ss_inline_critical_css()` if the font-family name changes

**Important:** Only use locally hosted fonts. No external CDN links.

---

## How to Adjust Palettes

### Via Admin Settings
1. Go to **Appearance → Sender Symposium Settings → Color Overrides**
2. Override individual tokens with hex colour values (#RRGGBB)
3. Leave fields blank to use theme defaults
4. Overrides apply to `:root` — affecting both light and dark mode base values

### Available Overridable Tokens
- Surface: page, section, card
- Text: primary, secondary
- Actions: primary bg, primary hover, primary text
- Links: default, hover
- Focus ring

### Colour Defaults
Full light and dark palette values are defined in `assets/css/tokens.css`.

---

## Dark/Light Mode

### How It Works
1. **Initial load:** A tiny inline script in `<head>` reads `localStorage` → system preference → defaults to light. Applied before first paint (no flash).
2. **Toggle:** The header toggle button switches between modes and persists to `localStorage`.
3. **System changes:** If no manual preference is stored, the theme responds to OS-level dark mode changes.

### Admin Controls
- **Default Mode:** Follow system / Force light / Force dark
- **Toggle Visibility:** Show or hide the dark/light toggle button

### CSS Architecture
All colours use CSS custom properties (`--surface-page`, `--text-primary`, etc.) defined in `tokens.css` under `:root` (light) and `[data-theme="dark"]`.

### Sponsor Logo Guidance
- Add class `sponsor-logo` to logos — they auto-invert in dark mode
- Add class `sponsor-logo--preserve` to logos that should NOT be inverted (they get a subtle background instead)

---

## Accessibility

### Built-in Features
- Skip-to-content link (visible on focus)
- Semantic HTML5 structure: `<header>`, `<nav>`, `<main>`, `<footer>`
- ARIA attributes for nav toggle, theme toggle, and accordion
- Keyboard-operable navigation with Escape key support
- Strong visible `:focus-visible` states (never removed)
- `prefers-reduced-motion` respected: all animations disabled
- Min 48px touch targets on interactive elements

### Contrast
- Light mode: dark text on warm stone backgrounds (>7:1 for body text)
- Dark mode: light warm text on deep backgrounds (>7:1 for body text)
- Focus ring colour meets 3:1 against adjacent backgrounds

---

## Performance

### Architecture
- No jQuery on frontend (deregistered)
- Vanilla JS only, all deferred
- Local font hosting with `font-display: swap`
- Layered CSS enqueue (tokens → base → components)
- Hero CSS loaded conditionally (only on pages with hero)
- Inline critical CSS (font-face + above-the-fold colours)
- Inline theme bootstrap script (prevents FOUC)
- No autoplay video, no parallax, no scroll hijacking

### Targets (Mobile)
- LCP < 1.8s
- CLS < 0.05
- INP < 200ms
- Lighthouse Performance >= 95

---

## QA Checklist

### Core Web Vitals
- [ ] Run Lighthouse on Home, Tickets, Format, Venue pages
- [ ] Verify LCP < 1.8s on mobile
- [ ] Verify CLS < 0.05
- [ ] Verify INP < 200ms
- [ ] No render-blocking resources (check Network tab)

### Accessibility
- [ ] Skip link visible on Tab focus
- [ ] Full keyboard navigation (Tab, Shift+Tab, Enter, Escape)
- [ ] Screen reader announces nav state changes
- [ ] Theme toggle has `aria-pressed` state
- [ ] Contrast check: light mode (all text > 4.5:1)
- [ ] Contrast check: dark mode (all text > 4.5:1)
- [ ] `prefers-reduced-motion: reduce` disables all animation
- [ ] No heading level skips on any page

### Browser / Device Testing
- [ ] Chrome (desktop + mobile)
- [ ] Firefox (desktop)
- [ ] Safari (desktop + iOS)
- [ ] Edge (desktop)
- [ ] Mobile Safari on iPhone (bottom-of-screen tap targets)

### Dark/Light Mode
- [ ] No FOUC on initial load (light or dark)
- [ ] Toggle persists across page navigations
- [ ] Toggle persists after browser restart
- [ ] System preference change updates theme (when no manual override)
- [ ] Forced light/dark from admin works correctly
- [ ] Sponsor logos readable in both modes

### Elementor
- [ ] Canvas template: no theme header/footer, no padding
- [ ] Default template: proper container width
- [ ] Elementor Theme Builder header/footer overrides work
- [ ] Theme tokens cascade into Elementor Global Styles
- [ ] No style conflicts with Elementor widgets

---

## Security Notes

- All output escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`)
- All admin inputs sanitised via Settings API callbacks
- Capability checks (`manage_options`) on settings page
- No inline JavaScript from untrusted sources
- No external resource loading (fonts, scripts, styles)
- WordPress version, RSD, and wlwmanifest removed from `<head>`
- No `eval()`, `new Function()`, or dynamic code execution

---

## License

GPL-2.0-or-later
