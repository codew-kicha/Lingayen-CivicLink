# Lingayen CivicLink design system

This is the rulebook for every screen. Admin and CSO screens share one layout, one set of tokens and
one set of components. The public site uses the same tokens, with its own look (§7).

**Where things live**

| What | File |
|---|---|
| Colour tokens (the only place colour values exist) | `resources/css/tokens.css` |
| Token-to-class mapping, spacing scale, type scale | `tailwind.config.js` |
| Shared component classes | `resources/css/app.css` (`@layer components`) |
| App layout (both roles) | `resources/views/components/layouts/app.blade.php` and `partials/sidebar.blade.php` |
| Navigation per role | `config/navigation.php` |
| Blade components | `resources/views/components/` (`dialog`, `filter-bar`, `pagination`, `status-badge`, `empty-state`, `application-type`, `flash`, `password-field`, charts) |
| Behaviour (dialogs, row keyboard support, combobox, tag picker, review queue) | `resources/js/app.js` (Alpine components) |
| Enforcement | `tests/Feature/DesignTokenComplianceTest.php` (no hard-coded colour, no off-scale spacing) |

---

## 1. Tokens

### 1.1 Colour

Tokens are CSS variables holding sRGB channels, so Tailwind can apply opacity:
`tailwind.config.js` reads each one as `rgb(var(--color-x) / <alpha-value>)`. `bg-primary-dark/60`
works. Never write a colour value in a view or in `app.css`; the compliance test fails if you do.

| Token (`--color-…`) | Tailwind class | Value | Use |
|---|---|---|---|
| `primary` | `primary` | `#8f3339` | Buttons, selected state, focus ring on light surfaces |
| `primary-hover` | `primary-hover` | `#79242b` | Button hover |
| `primary-dark` | `primary-dark` | `#470f14` | Sidebar and mobile top bar surface |
| `primary-dark-hover` | `primary-dark-hover` | `#65171f` | Sidebar hover and active item; public menu bar |
| `primary-subtle` | `primary-subtle` | `#fdf3f3` | Row hover, ghost-button hover, selected option |
| `on-primary` | `on-primary` | `#fdfdfd` | Text on `primary` / `primary-dark` |
| `on-dark` | `on-dark` | `#efc8c6` | Sidebar links (10.2:1) |
| `on-dark-muted` | `on-dark-muted` | `#dd9e9d` | Sidebar group labels and captions (7.0:1) |
| `accent` | `accent` | `#eda62e` | Gold: count badges, active-item marker, public call to action |
| `on-accent` | `accent-fg` | `#470f14` | Text on gold (7.5:1) |
| `focus` / `focus-on-dark` | `focus`, `focus-on-dark` | `#8f3339` / `#f5bd56` | Focus rings |
| `surface` | `paper` | `#fdfdfd` | Cards, inputs |
| `surface-muted` | `canvas` | `#f3f3f3` | App page background, neutral fills |
| `border` | `line` | `#d9d9d9` | Card borders, dividers |
| `border-strong` | `line-strong` | `#8f8f8f` | Input borders (3.18:1, WCAG 1.4.11) |
| `text` | `ink` | `#2b2522` | Body text (14.9:1) |
| `text-muted` | `muted` | `#524c48` | Secondary text (8.4:1 on white, 5.0:1 on `band`) |
| `placeholder` | `placeholder` | `#75706c` | Input placeholders (4.8:1): readable, but clearly lighter than typed text so it is never mistaken for a value |
| `info` / `info-subtle` | `info`, `info-subtle` | `#296898` / `#deefff` | Submitted (5.1:1) |
| `warning` / `warning-subtle` | `warning`, `warning-subtle` | `#9d5900` / `#ffebce` | Under review, pending, needs action (4.67:1) |
| `success` / `success-subtle` | `success`, `success-subtle` | `#187a3b` / `#dbf5df` | Approved, verified, active (4.67:1) |
| `danger` / `danger-subtle` | `danger`, `danger-subtle` | `#b6322d` / `#ffe5e0` | Rejected, revoked, errors (5.0:1) |
| `neutral` | `neutral`, `neutral-subtle` | `text-muted` / `surface-muted` | Draft, expired, "not checked" |
| `chart-self` / `chart-lgu` / `chart-unknown` / `chart-bar` | `chart-*` | `#8f3339` / `#efc8c6` / `#b1b1b1` / `#a8494d` | Chart series only |
| `band` | `band` | `#f5f0ef` | Public site alternating bands only (off-white tinted toward the maroon; muted text 7.6:1) |

The short class names `paper`, `canvas`, `line`, `ink` and `muted` were kept on purpose (about 300
uses); they read the spec-named variables shown on the left.

**Rules**
- Semantic colours carry meaning, never decoration. A status is always a word plus a colour.
- Maroon is for actions and the current location. Entity names (organizations, activities, files)
  are `entity-link`: text colour, maroon and underline only on hover.
- Contrast is checked numerically: text at least 4.5:1, large text and UI parts at least 3:1.

### 1.2 Spacing

Padding, margin, gap and space are limited in `tailwind.config.js` to:

| Class step | `1` | `2` | `3` | `4` | `6` | `8` | `12` |
|---|---|---|---|---|---|---|---|
| px | 4 | 8 | 12 | 16 | 24 | 32 | 48 |

Named extras, for one job each: `hair` (2px) and `tight` (6px) for badge padding and icon gaps;
`section` (80px) for the public site's band rhythm. Anything else does not compile, and the
compliance test fails on it. Widths and heights keep Tailwind's full scale (`h-11` touch targets,
`w-64` sidebar).

- Cards: `p-6` (24px). Compact cards and table header bars: `p-4` / `px-4` (16px).
- Stacks inside a card: `space-y-6`; form fields: label `mb-2`, hint and error `mt-2`.

### 1.3 Type

Public Sans everywhere in the app (Merriweather is public-site headings only). Two weights:
400 and 600 (`font-semibold`).

| Role | Class | Size / line |
|---|---|---|
| Page title | `text-page-title font-semibold` | 24 / 32 |
| Card title | `card-title` (`text-card-title`) | 18 / 24 |
| Body | `text-sm` or `text-base` | 14 / 24, 16 / 24.8 |
| Table header | `table-head` | 12, uppercase, tracked |
| Caption | `text-xs` | 12 |

Numbers and dates use the UI font with `tabular-nums`. Monospace is only for codes (verification
codes, audit action names, CSV column names).

### 1.4 Shape, elevation, layers

- One card: `rounded-lg` (8px), `border-line`, `shadow-panel`. `panel` and `panel-flat` are aliases
  of `card`.
- Controls: `rounded-md` buttons, `rounded-sm` inputs, badges and nav items.
- Z-index scale: `z-dropdown` 10, `z-sticky` 20, `z-overlay` 30, `z-dialog` 40, `z-toast` 50.

---

## 2. Layout: one shell for both roles

`<x-layouts.app header="…" subheader="…">` with an optional `<x-slot:actions>`. Admin and CSO
screens use only this. The two roles differ only in `config/navigation.php`.

- **Sidebar** (`lg` and up): `w-64`, `bg-primary-dark`, sticky, scrolls on its own. Items are grouped
  under small labels:
  - Admin: Overview (Dashboard, Analytics & Reports) · Review (Applications, Verify activities) ·
    Directory (Organizations, Scorecards) · Content (News posts, Annual reports) · System (Accounts,
    Audit log)
  - CSO: Overview (Dashboard) · My organization (Organization profile, Activities) ·
    Accreditation (Applications)
- **Every item** has an icon. The current page gets `aria-current="page"`, a `primary-dark-hover`
  fill, semibold text and a 3px gold bar. Counts use `count-badge`.
- **User block**: initials, name, and "Position, Organization" (from `users.position`), each
  truncated on one line with the full text in a tooltip. Then Account settings and Log out.
- **Below `lg`**: a slim top bar (menu button, seal, page title) and an off-canvas drawer with the
  same contents. The drawer traps focus (`x-trap.noscroll.inert`), Esc and the overlay close it.
- **Page frame**: `max-w-7xl`, padding 16 / 24 / 32px by breakpoint. Title, subtitle and actions sit
  in the same place on every page.
- **Page-load bar**: a thin gold bar while the next page loads (still, under reduced motion).

To add a page: add a route, add an item under the right group in `config/navigation.php` with its
`active` route patterns, and wrap the view in `<x-layouts.app>`.

---

## 3. Components

| Component | Use | Notes |
|---|---|---|
| `btn-primary` | The one main action | 40px; 44px below `lg` or on a coarse pointer (`touch:`) |
| `btn-secondary` | Other actions | Bordered, maroon text |
| `btn-ghost` | Low-emphasis actions | Add `btn-sm` for row actions |
| `btn-danger` | Destructive actions | Red outline, never filled: the one filled (maroon) button is the primary action. Behind a password re-check where required |
| `icon-btn` | Square icon actions (dialog close, previous/next) | 44px |
| `field-label` / `field-input` / `field-hint` / `field-error` | Every form field | Label above, error below with `aria-describedby`; input border 3:1; inputs 44px tall; placeholder in `placeholder` |
| `field-required` | Required field labels | Adds a red asterisk; the form starts with a "* Required" legend |
| `card` + `card-title` | Grouping | 24px padding, 16px when compact |
| `<x-status-badge :status>` | Every status | One height (24px), never wraps. Submitted = info, Under review / pending = warning, Approved / verified / active = success, Rejected / revoked = danger, draft / expired = neutral |
| `badge-*` | Other labels | Same shape as status pills |
| `count-badge` | Nav and tab counts | Gold, tabular numerals |
| `entity-link` | Names linking to a record | Text colour; maroon and underline on hover |
| `table-wrap` | Around every table | Scrolls inside its own box; the page never scrolls sideways |
| `table-stack` (on the `<table>`) | List tables on phones | Below `sm` each row becomes a block; `data-label` on a cell adds a small label, `stack-hide` hides it (the chevron) |
| `table-scroll` | Around list tables (Applications, Verify activities, News posts) | Scroll box with a sticky header row and sticky first column; capped to the window at `lg` so the page stays short |
| `table-row-link` + `x-data="tableNav"` on the `tbody` | Rows that open a record | Hover tint, pointer, focus ring; arrow keys / Home / End move between rows (one row in the tab order), Enter opens. `data-href` rows are links; others dispatch `row-open` |
| `<x-filter-bar :chips search>` | Above every list | Search box plus one-click filter chips (`filter-chip`, current chip `aria-current`). No Status dropdown + Filter button |
| `<x-dialog name title width>` | Every pop-up | See §3.1 |
| `<x-admin-password-field id :error>` | Dialogs that change access or delete records (Edit login, Merge, Delete organization) | Re-checks the admin's password (`admin_password`, `current_password` rule) without leaving the dialog; set up so browsers don't fill in a representative's saved password |
| `toast` / `toast-action` | Confirmation with Undo | Bottom right, above everything |
| `table-head` / `table-cell` / `table-num` / `table-date` / `table-row` | Tables | 48px rows, cells centred; dates fixed width (`w-32`), one line |
| `<x-empty-state title icon>` | Empty tables and lists | Optional slot text and `action` slot |
| `<x-application-type :type>` | Applications | "Renewal" tag in the organization cell; no Type column (New is the default) |
| `<x-flash>` | Status messages | Rendered by the layout |
| `<x-pagination>` | Every paginated list | See §4 |
| `<x-keep-query :only>` | Filter forms | Carries chosen keys (a scoped organization, an analytics period), never `page` |
| `table-sticky` | Long tables without `table-scroll` | Header sticks at `lg`+ only |

All eight states apply to interactive parts: default, hover, focus (visible ring, never removed),
active, disabled, loading, error, success.

### 3.1 Dialogs

`<x-dialog name="…" title="…" width="sm|md|lg" :open="…">` is the only pop-up. Open it with
`$dispatch('open-dialog', 'name')`; `close-dialog` closes it.

- **Position:** centred in the content area to the right of the sidebar (at `lg`, padding-left is the
  sidebar width plus the gutter), not in the full window. The dimmed overlay covers the whole screen.
- **Size:** `sm` 560px (short forms: Register an organization, Edit login, confirmations), `md` 640px
  (activity review), `lg` 720px (Write a post). Never taller than 80% of the window. Full screen
  below `sm`.
- **Structure:** pinned header (title, optional subtitle, × button) and pinned footer (actions);
  only `dialog-body` scrolls. A `header` slot replaces the title block when it needs more (previous /
  next in the review dialog). Forms wrap body and footer: `<form class="flex min-h-0 flex-1 flex-col">`.
- **Behaviour:** Esc and × close it. A click outside closes it only until something has been typed.
  Focus is trapped (`x-trap`) and returns to the trigger. `role="dialog"`, `aria-modal`,
  `aria-labelledby` the title (`dialog-{name}-title`).
- **Actions:** right-aligned, secondary first and the maroon primary last. Destructive actions are
  `btn-danger` (red outline). Footer buttons are 44px.
- **Validation errors:** forms post normally. The form sends `_dialog`; the page comes back with
  `:open` true, the typed values and the errors in place.

---

## 4. Pagination

Every long list uses one server-side pagination: `App\Support\ListPagination` in the controller and
`<x-pagination>` in the view. Every list shows **10 rows a page**; there is no page-size selector.

```php
// Controller: wrap the query.
'organizations' => ListPagination::paginate(Organization::query()->...->orderBy('name'), $request),
```

```blade
{{-- View: inside the table card, after the table. `target` is the heading that receives focus after a page change. --}}
<x-pagination :paginator="$organizations" />
<x-pagination :paginator="$activities" target="history" class="card mt-4" />
```

**Behaviour (enforced by `ListPaginationTest` and `PaginationComponentTest`)**
- The page lives in the URL (`?page=2`), so pages are linkable and Back works. A `per_page` in an
  old link is ignored.
- A page past the end redirects to the last page.
- Links keep every filter and sort parameter. The search box and filter chips (`<x-filter-bar>`)
  never carry `page`, so changing a filter returns to page 1.
- The bar (`pagination-bar`) is the table card's footer: "Showing 1-10 of N", then Previous / page
  numbers / Next. It sticks to the bottom of the window while the table is taller than it.
- After a page change, focus moves to the target heading (`#page-title` by default).
- Markup: `aria-label="Pagination"`, `aria-current="page"` on the current page, disabled
  Previous/Next are `aria-disabled` text (not links), ellipses are plain text.
- Below `sm`: Previous / "Page X of Y" / Next only. Controls are 36px on desktop, 44px on touch.
- Look: rectangular, 4px radius (`page-link`), current page solid maroon (`page-link-current`).
- Long tables use `table-wrap table-sticky`: the header sticks to the viewport at `lg` and up only
  (below `lg` the table scrolls sideways in its box, where a sticky header can't work). Cards
  around sticky tables must not use `overflow-hidden`.
- Empty lists use `<x-empty-state :filtered="…" :clear-url="…" title="…">`: "No results for these
  filters" with Clear filters, or the list's own "nothing here yet" message.
- Loading: the app layout's page-load bar.

| List | Order | Filters kept across pages |
|---|---|---|
| Audit log | Newest first | Event type, actor, account, organization |
| Applications queue | Waiting (submitted, under review) first, then Filed date (newest first; the header toggles) | Search, chip: All / Under review / Approved / Rejected, direction |
| Verify activities | Waiting: oldest logged first. Verified / Rejected: latest decision first | Search, chip: Waiting / Verified / Rejected |
| Organizations | Name | Search, no login, activity (inactive / at-risk with the analytics period) |
| Accounts | Role, name | Search, role, status |
| Scorecards | Name | Name search |
| News posts | Newest first | Search, chip: All posts / Published / Draft |
| CSO activities | Newest first | Status |

**Capped instead of paginated** (the limit is in the query, and the total is named):

| List | Cap | Where the rest is |
|---|---|---|
| Admin dashboard widgets | 5 | "View all N" links to the queue |
| Organization page history | 5 | "Full history": audit log filtered to the organization |
| Analytics at-risk | 10 | "Showing 10 of N at-risk": filtered Organizations list and the Excel workbook (computed from aggregated rows in `ReportData`, so the cap is applied in the controller) |
| CSO "Tagged as a partner" | 10 | "Showing the latest 10 of N" |

Not paginated by design: Annual reports (one per year), CSO applications (one per three years),
the CSO dashboard.

**Audit log performance** (MySQL, 50,000 rows): page 1 about 40 ms, page 500 about 83 ms, event
filter about 25 ms, organization filter about 19 ms, using the existing `created_at`, `action` and
`(subject_type, subject_id)` indexes. Optional: adding `created_at` to the subject index would
remove a small in-memory sort on the organization filter.


---

## 5. Accessibility

- Contrast: numeric checks above. Sidebar text on maroon is 7.0:1 or better; gold badge text 7.5:1.
- Focus: `:focus-visible` ring on everything (`focus` on light, `focus-on-dark` on maroon).
- Touch: 44px targets below `lg` or on coarse pointers; dense table rows stay compact on desktop.
- Colour is never the only signal: statuses carry words, the active nav item has weight and a bar.
- Phones: tables scroll inside `table-wrap`; checked at 360, 768 and 1440px on every admin and CSO
  page (no page-level horizontal scroll; no wrapped dates, pills or nav labels).
- Motion: everything respects `prefers-reduced-motion`.

---

## 6. Checklist for a new screen

1. Wrap it in `<x-layouts.app>` and add the nav item to `config/navigation.php`.
2. Use only token classes and the spacing scale. Run `php artisan test --filter=DesignTokenCompliance`.
3. Cards `p-6`, tables inside `table-wrap`, dates `table-date`, statuses `<x-status-badge>`.
4. Names are `entity-link`; one `btn-primary` per area.
5. Lists over about 10 rows use `ListPagination` + `<x-pagination>` (§4), or are capped in the query.
6. Check 360, 768 and 1440px: nothing scrolls sideways, nothing wraps that shouldn't.

---

## 7. Public site

The public pages (home, directory, about, accreditation, resources, contact, sign-in) follow the
pcnc.com.ph reference: an identity bar over a sticky maroon menu bar, Merriweather headings with a
gold rule (`section-title`), alternating white and `band` sections at `py-section`, photo banners
(`page-banner`, `photo-wash`), and the home video hero. They use the same tokens; the raw `brand-*`
and `accent-*` scales exist for these pages only. Images: the Lingayen seal is
`public/images/brand/lingayen-seal.png` (original in `storage/app/public/logos`); stock photos are
placeholders listed in `public/images/stock/SOURCES.txt`.

---

## 8. Review screens (Applications, Verify activities, News posts)

One family: `<x-filter-bar>` above a `card` holding a `table-scroll` table and the pagination bar.
Rows open their record; actions that change data live in dialogs, not in the rows.

- **Applications:** Organization (name, Renewal tag, barangay, documents-to-check badge) · Status (chip,
  Sangguniang Bayan stage as a grey second line) · Filed (sorted, arrow on the header) · chevron.
  "Register an organization" (top right) opens the Register dialog (560px): name, barangay (searchable
  combobox), sector, advocacy, and "Create a login for the representative" (off by default; reveals
  full name and email, and the account gets a password-setup link). The same dialog is on
  Organizations and Accounts; `/admin/organizations/create` opens it.
- **Verify activities:** Activity (title; organization and barangay beneath) · Date · Residents
  reached · Waiting (days since logged, amber past 30) · chevron. A row opens the review dialog
  (640px): title, organization and barangay, previous / next with "3 of 10", date and description,
  Residents reached / Logged by / Claimed source, the sign-in sheet (image preview or PDF tile, Open
  file), and a required "Verified source" select that starts empty. Verify activity (maroon) unlocks
  once a source is chosen; Reject (red outline) reveals a reason (10 characters minimum, live count)
  with Confirm rejection and Cancel. A decision closes the dialog, hides the row, lowers the sidebar
  badge and shows a toast with Undo. It is sent when the toast times out (6 s), when the next decision
  is made, or before a link or search leaves the page (a keepalive request if the tab is closed), so
  Undo never has to reverse a notification or a score.
- **News posts:** Title · Status chip · Featuring · Published · Delete (red outline, confirmed in a
  dialog). "Write a post" and a row both open the post dialog (720px): Title, Post content, Featured
  organizations (tag search). Publish (maroon), Save as draft (outlined), Cancel.
  `/admin/news/create` and `/admin/news/{id}/edit` open it.

### 7.1 Public pages since October 2026

- Section titles (`section-title`) are left-aligned with no gold rule; the rule was repeated on every
  section and read as a template.
- Page banners (`<x-page-banner photo="events/cso3.jpg" alt="...">`) use the client's event photos
  in `public/images/events`, under `photo-wash-banner` (even wash on phones, opening to the right from `lg`).
- Numbers and dates use Public Sans with `tabular-nums`; monospace is for codes only.
- Motion (only with `.motion-ok`, so reduced-motion and no-JS visitors see everything still):
  `reveal` (fade up on scroll, staggered), `reveal-line reveal-line-x|y` (timeline lines draw in),
  `reveal-photo` (photo eases up and un-zooms), `banner-enter` / `banner-photo` (banner text rises,
  photo settles on load). Transform and opacity only.
- Process steps: numbered circles with connecting lines, a row from `lg`, a vertical timeline on phones.

