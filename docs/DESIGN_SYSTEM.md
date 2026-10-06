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
| Blade components | `resources/views/components/` (`status-badge`, `empty-state`, `application-type`, `flash`, `password-field`, charts) |
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
| `info` / `info-subtle` | `info`, `info-subtle` | `#296898` / `#deefff` | Submitted (5.1:1) |
| `warning` / `warning-subtle` | `warning`, `warning-subtle` | `#9d5900` / `#ffebce` | Under review, pending, needs action (4.67:1) |
| `success` / `success-subtle` | `success`, `success-subtle` | `#187a3b` / `#dbf5df` | Approved, verified, active (4.67:1) |
| `danger` / `danger-subtle` | `danger`, `danger-subtle` | `#b6322d` / `#ffe5e0` | Rejected, revoked, errors (5.0:1) |
| `neutral` | `neutral`, `neutral-subtle` | `text-muted` / `surface-muted` | Draft, expired, "not checked" |
| `chart-self` / `chart-lgu` / `chart-unknown` / `chart-bar` | `chart-*` | `#8f3339` / `#efc8c6` / `#b1b1b1` / `#a8494d` | Chart series only |
| `band` | `band` | `#c7c7c7` | Public site grey bands only |

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
| `btn-danger` | Destructive actions | Behind a password re-check where required |
| `field-label` / `field-input` / `field-hint` / `field-error` | Every form field | Label above, error below with `aria-describedby`; input border 3:1 |
| `card` + `card-title` | Grouping | 24px padding, 16px when compact |
| `<x-status-badge :status>` | Every status | One height (24px), never wraps. Submitted = info, Under review / pending = warning, Approved / verified / active = success, Rejected / revoked = danger, draft / expired = neutral |
| `badge-*` | Other labels | Same shape as status pills |
| `count-badge` | Nav and tab counts | Gold, tabular numerals |
| `entity-link` | Names linking to a record | Text colour; maroon and underline on hover |
| `table-wrap` | Around every table | Scrolls inside its own box; the page never scrolls sideways |
| `table-head` / `table-cell` / `table-num` / `table-date` / `table-row` | Tables | 48px rows, cells centred; dates fixed width (`w-32`), one line |
| `<x-empty-state title icon>` | Empty tables and lists | Optional slot text and `action` slot |
| `<x-application-type :type>` | Applications | "Renewal" tag in the organization cell; no Type column (New is the default) |
| `<x-flash>` | Status messages | Rendered by the layout |
| `<x-pagination>` | Every paginated list | See §4 |
| `<x-keep-query>` | Filter forms | Carries `per_page` (and chosen keys), never `page` |
| `table-sticky` | Long tables | Header sticks at `lg`+ only |

All eight states apply to interactive parts: default, hover, focus (visible ring, never removed),
active, disabled, loading, error, success.

---

## 4. Pagination

Every long list uses one server-side pagination: `App\Support\ListPagination` in the controller and
`<x-pagination>` in the view.

```php
// Controller: wrap the query; the third argument is the list's default page size.
'organizations' => ListPagination::paginate(Organization::query()->...->orderBy('name'), $request, 25),
```

```blade
{{-- View: after the table. `target` is the heading that receives focus after a page change. --}}
<x-pagination :paginator="$organizations" :default="25" />
<x-pagination :paginator="$activities" :default="10" target="history" />
```

**Behaviour (enforced by `ListPaginationTest` and `PaginationComponentTest`)**
- Page and size live in the URL (`?page=2&per_page=25`), so pages are linkable and Back works.
  `per_page` accepts 10, 25 or 50; anything else quietly falls back to the list default.
  `per_page` is left out of URLs when it equals the default.
- A page past the end redirects to the last page.
- Links keep every filter and sort parameter. Filter forms include `<x-keep-query />` (carries
  `per_page`, never `page`), so changing a filter returns to page 1 at the same size. Tabs that
  act as filters (Verify activities) do the same in their links.
- Changing the page size resets to page 1. The selector has a visible label and works without
  JavaScript (a `noscript` Apply button); with JavaScript it submits on change.
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

| List | Default size | Filters kept across pages |
|---|---|---|
| Audit log | 50 | Event type, actor, account, organization |
| Applications queue | 25 | Status, SB stage |
| Verify activities | 10 (tall cards) | Status tab |
| Organizations | 25 | Search, no login, activity (inactive / at-risk with the analytics period) |
| Accounts | 25 | Search, role, status |
| Scorecards | 25 | Name search |
| News posts | 10 | Status |
| CSO activities | 10 | Status |

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
