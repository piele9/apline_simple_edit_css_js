# APLINE Simple Edit CSS/JS for PrestaShop 9

A lightweight, distributable PrestaShop **9.0.x** module that lets a
back-office administrator **inject custom CSS and JavaScript** into the
storefront **without editing the theme** or touching files in `themes/`.
Each fragment is a **snippet** managed like a row — drag & drop ordering,
enable/disable, edit, delete — with built-in **code versioning** and a
dependency-free **CSS formatter**.

> Created by **[APLINE](https://apline.pl)** — custom PrestaShop development,
> performance optimization and integrations.

---

## ✨ Features

- ✅ **CSS and JavaScript snippets** injected inline into the front-end,
  managed like rows (drag & drop, enable/disable per snippet)
- ✅ Per-snippet **type** (CSS / JS), **location** (inline in `<head>` or
  just before `</body>`) and **load timing** (immediate / after
  `DOMContentLoaded`, JS only)
- ✅ **Code versioning** — every save snapshots the previous code (only
  when it actually changed), keeps the **3 newest** versions per snippet,
  restorable from the edit form (*Load into editor* — you still Save to
  apply)
- ✅ **CSS auto-formatter** — a *Format CSS* button plus optional
  auto-format on save; **CSS brace balance check** rejects unbalanced `{ }`
- ✅ Strict, English-only validation: required fields, 255-char name limit
  (rejected, never silently truncated), type/location/load-when whitelists
- ✅ **Crash-safe**: a rendering/data error yields empty output, never a
  500; a failed install rolls back to a clean state
- ✅ Two starter snippets on install: an active CSS placeholder and a
  disabled educational **YouTube lite-embed player** (JS)
- ✅ No external dependencies, no DRM, no telemetry; classic PrestaShop API
- ✅ Public GitHub, custom attribution license, modifiable

## 📦 Requirements

- PrestaShop **9.0.x** (not supported on 1.7 / 8.x — the module's
  `ps_versions_compliancy` blocks installation outside 9.0.x)
- PHP compatible with your PrestaShop 9 install

> Always test on a staging copy of your shop before enabling a snippet in
> production. A snippet with broken code can affect your storefront's
> appearance or behavior. The module itself is crash-safe (a render error
> yields empty output, never a 500), but the code **you** inject runs on
> your live pages.

## 🚀 Installation

**Via Back Office**

1. Download the official zip from the *Releases* page (or build it — see
   the build recipe in the workspace `CLAUDE.md`). The archive must
   contain the `apline_simple_edit_css_js/` folder at its root with
   forward-slash paths. The folder name, the main `.php` file name and the
   PHP class name MUST all be `apline_simple_edit_css_js` — otherwise
   PrestaShop refuses the zip with "This file doesn't seem to be a valid
   zip module".
2. *Modules → Upload a module* → select the ZIP → install.

**Via FTP**

1. Upload the `apline_simple_edit_css_js/` folder to `modules/`.
2. *Modules* → find **APLINE Simple Edit CSS/JS for PrestaShop 9** → Install.

On install, two starter snippets are created: an active CSS placeholder and
a **disabled** JavaScript YouTube lite-embed player you can study and turn on.

## 🧹 Uninstall

**From Back Office** (recommended): *Modules → Module Manager → find
**APLINE Simple Edit CSS/JS for PrestaShop 9** → Uninstall*.

Uninstall is **destructive and idempotent**:

- the `ps_asec_snippet` and `ps_asec_snippet_version` tables are dropped —
  all snippets and their version history are deleted
- the `ASEC_FORMAT_CSS_ON_SAVE` configuration entry is removed
- the hidden admin tab (`AdminAplineSimpleEditCssJsSnippet`) is removed
- module hook registrations are unregistered

If you want to keep your snippets, **back up the `ps_asec_snippet` table
before uninstalling** — there is no built-in export.

## ⚙️ Usage

1. *Modules* → configure the module: toggle **Auto-format CSS on save**.
2. **Manage snippets** → add/edit snippets:
   - **Name** — a label for your own reference
   - **Type** — CSS or JavaScript
   - **Code** — the snippet body (injected verbatim into the front-end)
   - **Location** — inline in `<head>`, or just before `</body>` (JS only)
   - **Load when** (JS only) — execute immediately, or after
     `DOMContentLoaded`
   - **Active** — on/off
3. Reorder snippets by drag & drop — the order is the inline render order
   (e.g. put a CSS reset first, your overrides last).

### Code versioning

Each time you save a snippet whose **code changed**, the previous code is
snapshotted. The edit form shows the **last 3 versions** by timestamp;
*Load into editor* fills the textarea with that version's code — you then
Save to make it the current code.

### CSS formatter

For CSS snippets, the *Format CSS* button reformats the textarea
(one declaration per line, indented nested blocks, preserved comments). If
**Auto-format CSS on save** is enabled, CSS is also formatted on every save.
The formatter is intentionally simple; for exotic CSS, disable auto-format
and format the code in your own editor.

## 🖼️ Screenshots

**Module configuration page:**

![Module configuration page](docs/config.png)

**Snippet management** — drag & drop ordering, type badges:

![Snippet management list](docs/list.png)

**Snippet edit form** — type toggle, Format CSS, previous versions:

![Snippet edit form](docs/form.png)

## 🛠️ Troubleshooting

### "This file doesn't seem to be a valid zip module"

PrestaShop's installer requires that the **folder name**, the **main
`.php` file name** and the **PHP class name** all match
(`apline_simple_edit_css_js`) — and the zip must contain that folder at its
root with **forward-slash** paths. Re-download the official zip from the
*Releases* page; do not rezip the source folder with Windows Explorer (it
sometimes writes `\` separators that PrestaShop rejects).

### A snippet does not appear / breaks the page

- Make sure the snippet's **Active** switch is on.
- For JS that manipulates the DOM, use **load when = after
  `DOMContentLoaded`** so the elements exist when your code runs.
- A CSS snippet must be in `<head>`; JS can be in `<head>` or before
  `</body>`.
- Open your browser DevTools — each injected block carries a
  `data-asec-id` attribute so you can identify which snippet produced it.
- Clear the PrestaShop cache (*Advanced Parameters → Performance → Clear
  cache*).

### "Unbalanced curly braces in CSS"

The CSS brace balance check found a different number of `{` and `}`
(comment contents are ignored). Fix the mismatch — it usually means a
missing `}`.

## 📝 License

Custom Attribution License v1.0 — see [LICENSE.md](LICENSE.md).

You may use, modify, distribute and ship this module commercially and in
client projects. You may **not** remove or hide the APLINE attribution link
on the module configuration page. The attribution must stay visible, link to
<https://apline.pl>, and use a readable font size (≥ 12px).

## 🏢 About APLINE

Need custom PrestaShop development, performance optimization or integrations?

→ **[APLINE.PL](https://apline.pl)**
