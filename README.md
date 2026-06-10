# Smart Redirect for Returning Users

A WordPress plugin that redirects returning visitors based on **cookie detection**, with URL targeting, role exclusions, GA4 analytics, and logging — all controlled from an admin panel.

**Version:** 1.0 · **Stack:** PHP, WordPress · **License:** GPL-2.0-or-later

## What it does

When a configured cookie is present (e.g. a "logged-in" or "returning user" marker), matching visitors are redirected to a configurable destination. URL matching supports an inversion mode (redirect everywhere *except* listed URLs), and specific user roles can be excluded.

## Key technical details

- Cookie name/value, destination URL, applicable-URL list, and invert-logic are all **admin options** (`get_option`) — the default destination is the site home URL, nothing client-specific is hardcoded.
- Role-based exclusions via `wp_get_current_user()`.
- **GA4 analytics**: client-side gtag plus an optional server-side Measurement Protocol fallback (API secret entered in settings).
- Redirect logging and CSV export; full settings UI under the WordPress admin.

## Installation

1. Copy to `wp-content/plugins/wp-smart-redirect/` and activate.
2. Configure cookie, destination URL, applicable URLs, exclusions, and (optionally) GA4 under **Settings → Smart Redirect**.

## Built by

George Nnanwubar — [george.ng](https://george.ng) · [github.com/georgennanwubar](https://github.com/georgennanwubar) · Manndi Technologies
