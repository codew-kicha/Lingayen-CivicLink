# Lingayen CivicLink, Design System

Companion to `docs/PRD_Lingayen_CivicLink_Complete.md` §11. This is the single source of truth
for color, type, spacing, and component states across all three experiences.

**Design read:** a public-sector civic service platform for LGU staff, CSO representatives, and
residents, with a trust-first institutional language, leaning toward GOV.UK / USWDS conventions
implemented in plain Tailwind v4 utilities (no component library).

**Dials:** DESIGN_VARIANCE 3 (symmetric, predictable), MOTION_INTENSITY 2 (states only, no
choreography), VISUAL_DENSITY 4 on public pages / 7 on admin queues.

Why restrained: this is a government accreditation record. A CSO officer checking whether their
application cleared second reading needs to find that answer in two seconds, on a shared office
machine, possibly at 1366x768. Visual ambition here costs trust, so the system spends its budget
on legibility, state clarity, and consistent status language instead.

---

## 1. Color

Navy carries authority and structure. Amber is a marker, not a paint: it appears only on the one
thing that matters most in a view (active nav item, primary CTA underline, a pending-count
badge). Neutrals are tinted toward the navy hue so grays never read as dead or muddy.

### Token definitions

Tokens live in `tailwind.config.js` under `theme.extend`. Laravel Breeze's Blade stack pins
Tailwind v3, so this project uses v3 config objects rather than v4's `@theme` block. The OKLCH
values are identical either way; only the declaration site differs. Keep this file and
`tailwind.config.js` in sync.

```js
navy:  { 50: 'oklch(0.972 0.012 252)', 100: 'oklch(0.932 0.022 253)',
         200: 'oklch(0.858 0.040 254)', 300: 'oklch(0.755 0.062 255)',
         400: 'oklch(0.650 0.090 255)', 500: 'oklch(0.540 0.110 256)',
         600: 'oklch(0.450 0.105 256)', 700: 'oklch(0.360 0.090 254)',
         800: 'oklch(0.280 0.070 252)', 900: 'oklch(0.210 0.055 250)' }

amber: { 100: 'oklch(0.950 0.045 75)', 200: 'oklch(0.900 0.080 72)',
         400: 'oklch(0.820 0.140 70)', 500: 'oklch(0.740 0.160 66)',
         600: 'oklch(0.650 0.150 62)', 700: 'oklch(0.550 0.130 58)' }

paper: 'oklch(0.990 0.003 252)'   surface: 'oklch(0.975 0.006 252)'
line:  'oklch(0.900 0.008 252)'   line-strong: 'oklch(0.820 0.010 252)'
muted: 'oklch(0.500 0.012 252)'   ink: 'oklch(0.250 0.014 252)'

success: { 100: 'oklch(0.945 0.040 150)', 600: 'oklch(0.520 0.130 150)' }
warning: { 100: 'oklch(0.950 0.045 75)',  600: 'oklch(0.620 0.140 70)'  }
danger:  { 100: 'oklch(0.945 0.035 27)',  600: 'oklch(0.520 0.170 27)'  }
info:    { 100: 'oklch(0.945 0.028 245)', 600: 'oklch(0.500 0.100 245)' }
```

### Contrast rules (non-negotiable)

| Pair | Approx. ratio | Verdict |
|---|---|---|
| `ink` on `paper` | 15:1 | Body default |
| `muted` on `paper` | 5.8:1 | Smallest allowed muted text, do not go lighter |
| `navy-600` on `paper` | 7.2:1 | Links, primary button fill |
| `navy-500` on `paper` | 4.9:1 | Floor for text, large text only below this |
| `amber-500` on `paper` | 2.2:1 | **Fails.** Never text on light |
| `amber-500` on `navy-900` | 7.7:1 | Correct amber usage |
| `paper` on `navy-600` | 7.2:1 | Primary button label |

Two rules that follow from the table: amber is a fill or a rule, never a foreground on white,
and muted text stops at `--color-muted`. If a value needs to be quieter than that, reduce its
size or weight, do not lighten it further.

### Dark mode

Out of scope for Milestone 1. Shared office desktops in daylight, one rendering path, less to
verify before the client demo. The tokens are structured so a dark block can be added later by
swapping `paper` / `surface` / `ink` / `line` only.

---

## 2. Typography

**Public Sans** for everything. It descends from Libre Franklin, was commissioned for US federal
government use, and is unusually legible at 13 to 14px, which is exactly where the admin tables
live. **IBM Plex Mono** carries verification codes, reference numbers, and tabular figures, where
character disambiguation actually matters (`0` vs `O` in a certificate code).

Scale is a 1.25 major third on a 1rem base, defined in `tailwind.config.js`:

```
xs 0.75rem/1.4    sm 0.875rem/1.45   base 1rem/1.55   lg 1.25rem/1.45
xl 1.5rem/1.3     2xl 1.95rem/1.2    3xl 2.45rem/1.15  display 3.05rem/1.1
```

Both families load from one `<link>` in the Blade layout with `display=swap`, and the config
carries the fallback stacks (`ui-sans-serif, system-ui, "Segoe UI"` and `ui-monospace`).

Rules:
- Hero h1: `clamp(1.95rem, 5vw, 3.05rem)`, `text-wrap: balance`, two lines maximum.
- Body prose caps at `max-w-[68ch]`. Admin table cells are exempt.
- Headings 600 weight, body 400, table column headers 600 at `text-xs` with `tracking-wide`.
- Numbers in tables, scores, and codes get `font-mono` with `tabular-nums`.
- Letter-spacing: `-0.02em` on `2xl` and above, `0` elsewhere. No all-caps body copy; small caps
  labels are allowed at `text-xs` with `tracking-wide` for table headers only.

---

## 3. Spacing, radius, elevation

4px base. Tailwind's default scale already matches, so no override is needed.

| Context | Rhythm |
|---|---|
| Public section padding | `py-16` to `py-24`, `gap-8` |
| Public card / panel padding | `p-6` |
| CSO dashboard panel padding | `p-5`, `gap-6` |
| Admin queue row padding | `px-4 py-2.5`, `gap-3` |
| Form field stack | `space-y-5`, label to input `gap-1.5` |

```
rounded-sm 0.25rem (badges, inputs)   rounded-md 0.375rem (buttons)
rounded-lg 0.5rem  (cards, panels)

shadow-panel    0 1px 2px  oklch(0.21 0.055 250 / 0.06)
shadow-overlay  0 8px 24px oklch(0.21 0.055 250 / 0.14)
```

One radius system, 8px ceiling. Cards do not nest, ever: inside a panel, separate rows with
`border-t border-line`, not with more cards. Shadows are reserved for things that genuinely
float (dropdown, dialog, toast). Admin surfaces use borders only, no shadow.

Z-index scale, semantic, never `9999`: dropdown 10, sticky 20, overlay 30, dialog 40, toast 50.

---

## 4. Motion

MOTION_INTENSITY 2. Transitions communicate state change and nothing else.

```
ease-out-strong  cubic-bezier(0.23, 1, 0.32, 1)
duration-fast    120ms      duration-base   180ms
```

- Animate `transform`, `opacity`, `background-color`, `border-color`, `color` only.
- Buttons and rows: `transition-colors duration-fast ease-out-strong`.
- Buttons get `active:scale-[0.98]`. Nothing else scales.
- Dropdowns and dialogs fade plus `translate-y-1`, 180ms, ease-out. Never `scale(0)`.
- No scroll-triggered reveals, no parallax, no auto-playing motion anywhere in this product.

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

---

## 5. Components, all eight states

Focus is the same everywhere: `focus-visible:outline-2 focus-visible:outline-offset-2
focus-visible:outline-navy-600`. On navy backgrounds, switch the ring to `outline-amber-400`.
Never remove an outline without a `:focus-visible` replacement. Touch targets are 44px minimum,
which means admin icon buttons get `p-2.5` even though the glyph is 20px.

### 5.1 Button

| State | Primary | Secondary | Ghost | Danger |
|---|---|---|---|---|
| Default | `bg-navy-600 text-paper` | `bg-paper text-navy-700 border border-line-strong` | `text-navy-700` | `bg-danger-600 text-paper` |
| Hover | `bg-navy-700` | `bg-navy-50 border-navy-300` | `bg-navy-50` | `bg-danger-600 brightness-90` |
| Focus | outline ring, fill unchanged | same | same | same |
| Active | `bg-navy-800 scale-[0.98]` | `bg-navy-100 scale-[0.98]` | `bg-navy-100` | `brightness-85 scale-[0.98]` |
| Disabled | `bg-navy-200 text-muted cursor-not-allowed`, no hover | `border-line text-muted` | `text-muted` | `bg-danger-100 text-muted` |
| Loading | spinner replaces label text, width held, `aria-busy="true"`, button disabled | | | |
| Error | not a button state, the form owns it | | | |
| Success | label swaps to a check plus past-tense verb for 2s ("Approved"), then settles | | | |

Base: `inline-flex items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-semibold`.
Labels are verb plus object: "Submit application", "Verify activity", "Publish post". Never "OK",
never "Submit" alone.

### 5.2 Input, select, textarea

Labels sit above the field, always visible. Placeholders are examples, never labels.

| State | Treatment |
|---|---|
| Default | `bg-paper border border-line-strong rounded-sm px-3 py-2 text-base` |
| Hover | `border-navy-300` |
| Focus | `border-navy-600` plus the standard outline ring |
| Active/filled | unchanged, value in `text-ink` |
| Disabled | `bg-surface text-muted border-line cursor-not-allowed` |
| Loading | field disabled, spinner in the trailing slot (async barangay lookup, Milestone 2) |
| Error | `border-danger-600`, message below in `text-sm text-danger-600`, wired via `aria-describedby`, `aria-invalid="true"` |
| Success | `border-success-600` only when a check actually ran (file scanned, code verified), not on every valid keystroke |

Validate on blur, not per keystroke. Required fields get a `*` in the label with
`aria-required="true"`, and the form states "All fields required unless marked optional" once at
the top rather than decorating every optional field.

### 5.3 Card / panel

`bg-paper border border-line rounded-lg p-6 shadow-panel`. Admin variants drop the shadow.

A card earns its box only when it represents one addressable object (one organization, one
application, one activity). Dashboard stat groupings use a bordered row with dividers, not four
floating tiles. Interactive cards (directory entries) get `hover:border-navy-300`, the standard
focus ring on the wrapping anchor, and no lift or scale on hover.

### 5.4 Badge, the status language

Status vocabulary is shared across all three roles, so a CSO rep and a PESO officer are always
reading the same word for the same database state.

| Domain state | Badge | Token pair |
|---|---|---|
| `draft` | Draft | `bg-surface text-muted border-line` |
| `submitted` | Submitted | `bg-info-100 text-info-600` |
| `under_review` | Under review | `bg-info-100 text-info-600` |
| `pending` (activity) | Pending verification | `bg-warning-100 text-warning-600` |
| `approved`, `verified`, `active` | Approved / Verified / Active | `bg-success-100 text-success-600` |
| `rejected`, `revoked` | Rejected / Revoked | `bg-danger-100 text-danger-600` |
| `expired` | Expired | `bg-surface text-muted border-line-strong` |

SB reading stage is a stepper, not a badge: `Not endorsed > First reading > Second reading >
Third reading > Endorsed`, current step in `navy-700` with an amber underline, completed steps in
`success-600`, future steps in `muted`.

Base: `inline-flex items-center gap-1.5 rounded-sm px-2 py-0.5 text-xs font-semibold`. Status is
never communicated by color alone, the word is always present.

### 5.5 Table (the admin workhorse)

```
thead th   text-xs font-semibold tracking-wide text-muted uppercase px-4 py-2 border-b border-line-strong text-left
tbody td   text-sm px-4 py-2.5 border-b border-line align-middle
numeric    font-mono tabular-nums text-right
```

| State | Treatment |
|---|---|
| Default | zebra-free, 1px dividers only |
| Hover | `hover:bg-navy-50` on the whole row |
| Focus | row-level focus ring when the row itself is a link |
| Active | `bg-navy-100` while a row action runs |
| Disabled | row `opacity-60`, actions removed rather than greyed |
| Loading | 5 skeleton rows at true row height, never a centered spinner |
| Error | inline banner above the table, table keeps its last good data |
| Empty | one sentence naming what would appear plus the action that creates it, for example "No activities are waiting for verification." |

Sticky header (`sticky top-0 bg-paper`) on any queue expected to exceed ~15 rows. Every list
endpoint paginates, no exceptions. Destructive row actions use undo-after-action, not a
confirmation dialog, except for irreversible ones (revoking an accreditation), which confirm.

---

## 6. Per-role application

Same tokens, three densities.

**Public (density 4).** Navy-900 header bar, amber underline on the active nav item. Nav is one
line, under 80px. Hero states what the site does in one sentence with the directory search
directly beneath it, because search is the actual job most visitors arrive with. Sections at
`py-20`. Directory cards in `repeat(auto-fit, minmax(280px, 1fr))`. A skip link to `#main` is
mandatory.

**PESO Admin (density 7).** Persistent left nav, navy-900, 240px. Content on `surface` to make
white panels read as objects. Queues are tables, not cards. Pending counts sit in amber badges
against the navy nav, the one place amber carries meaning per screen. Rows are scannable at
`py-2.5` with mono numerics.

**CSO Representative (density 5).** Single-column, max `max-w-5xl`, top nav rather than a
sidebar (fewer destinations). The dashboard answers three questions in order, before any scroll:
accreditation status and expiry, what PESO is waiting on from me, current performance score. The
score displays with its "informational only, does not affect renewal" caption attached
permanently, per PRD §15.

---

## 7. Pre-flight before any screen is called done

- [ ] Contrast verified against §1, muted text not lighter than `--color-muted`
- [ ] Amber used as fill or rule only, never as text on a light surface
- [ ] All eight states present on every interactive element, focus distinct from hover
- [ ] Labels above inputs, errors below and wired with `aria-describedby`
- [ ] Status communicated by word plus color, never color alone
- [ ] Keyboard path complete: skip link, visible focus, logical tab order, 44px targets
- [ ] Tables paginate, have an empty state, and a skeleton loading state
- [ ] `prefers-reduced-motion` honored
- [ ] No nested cards, one radius system, semantic z-index
- [ ] Renders correctly at 1366x768, the realistic municipal office screen
