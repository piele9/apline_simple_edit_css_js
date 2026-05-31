# Changelog

All notable changes to **APLINE Simple Edit CSS/JS for PrestaShop 9** will be
documented in this file. Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] – 2026-05-31

Initial public release.

### Added
- Inject custom **CSS and JavaScript snippets** into the front-end without
  editing the theme, for PrestaShop **9.0.x**. Each snippet is managed like a
  row: drag & drop ordering, enable/disable, edit, delete.
- Two render hooks: CSS and head JS in `displayHeader` (CSS first to avoid
  FOUC), body-end JS in `displayBeforeBodyClosingTag`.
- Per-snippet **type** (CSS/JS), **location** (`<head>` / before `</body>`)
  and **load timing** (immediate / after `DOMContentLoaded`, JS only).
- **Code versioning**: each save snapshots the previous code (only when it
  changed), keeping the 3 newest versions per snippet, restorable in the edit
  form via *Load into editor* (you still Save to apply).
- **CSS auto-formatter** (dependency-free): a *Format CSS* button plus optional
  auto-format on save (`ASEC_FORMAT_CSS_ON_SAVE`).
- **CSS brace balance check**: rejects unbalanced `{ }` (comment contents
  ignored).
- Strict, English-only validation: required fields, 255-char name limit
  (rejected, never silently truncated), type/location/load-when whitelists,
  CSS must live in `<head>`.
- Crash-safe hooks (`try/catch` → empty output, never a 500); a failed install
  rolls back to a clean state; uninstall is idempotent and drops both tables.
- Two starter snippets seeded on install: an active CSS placeholder and a
  disabled educational JavaScript YouTube lite-embed player.
- APLINE attribution block on the configuration page **and** under the snippet
  list, with a "Like this module?" call to action linking to https://apline.pl.
- Custom Attribution License v1.0 ([LICENSE.md](LICENSE.md)).

### Trust model
Snippet code is admin-authored and injected verbatim into the front-end. This
is intentional and not XSS: only a back-office administrator with full rights
can create snippets — customers cannot. Trust is enforced at the back-office
permission level, not by sanitizing the snippet body.

### Not in this release (possible 1.1.0)
- Per-page-context targeting (all active snippets load globally on every
  front-end page)
- Multi-language snippets, import/export, JavaScript syntax checking
