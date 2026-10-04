# Lingayen CivicLink, Design System

Companion to `docs/PRD_Lingayen_CivicLink_Complete.md` §11. This is the single source of truth
for color, type, spacing, concept, and component states across all three experiences.

**This is a visual and content revision pass, not a feature rebuild.** Every route, controller,
policy, and data flow in Milestone 1 stays exactly as built. Nothing here changes what the
system does — only what it looks and reads like.

---

## 0. The Distinct Visual Concept

Don't default to generic SaaS. The brief itself refuses that: this is a municipal accreditation
and accountability record for real, named barangay organizations in a coastal Pangasinan town,
not a startup pitching a product. The concept should come from the PRD, not from a template.

**The core idea: a living civic ledger, not a brochure.** Section 2 of the PRD documents a real,
confirmed finding — organizations have historically received LGU benefits without having to show
real community work, and nothing has tracked that until now. The site's entire visual identity
should make that distinction legible at a glance: this isn't a static directory page, it's
*proof of work, accumulating*. Concretely:

- **The hero doesn't open on an abstract tagline.** It opens on real, specific, rotating evidence
  — the most recently verified activity, pulled live from the database: *"Verified: Bantayan
  Women Weavers Cooperative held a skills training in Barangay Bantayan, 3 days ago."* This is
  the opposite of the banned hero-metric template (big number, small label, gradient accent) —
  it's a sentence about a real thing that happened, which no generic SaaS template can fake and
  no stock photo can substitute for.
- **Specificity over abstraction, everywhere.** Not "Empowering communities" — barangay names,
  organization names, sector names, real numbers. The PRD already gives you Lingayen's real
  10-sector taxonomy (fisherfolk, TODA, KALIPI, farmers) and real barangay names — use them in
  copy and in sector iconography instead of generic "community" language.
- **Photography treatment, not photography genericness.** Stock images are a temporary stand-in,
  not a style. Apply one consistent navy-duotone filter treatment (see §9) across every photo,
  stock or real, so the whole site reads as one deliberate documentary style rather than a stock
  photo grid. This is also the mechanism that makes swapping stock for real CSO event photos
  later invisible — the treatment, not the source photo, carries the visual identity.
- **Icons are sector-specific, not generic.** A fishing net or boat silhouette for fisherfolk, a
  tricycle/steering wheel for TODA, a woven pattern motif for KALIPI, not one repeated "people"
  icon recolored five times. Pull these from an approved icon library (§5) by deliberate
  selection — never hand-rolled SVG paths.
- **Two distinct conversion paths, not one generic CTA.** A resident verifying whether an
  organization is legitimately accredited and a CSO representative applying for accreditation are
  different people with different intents. Each page's primary CTA should match the intent of
  whoever is actually on it — the directory's primary action is search/verify, the homepage's is
  split between "browse" and "apply" by audience, not one "Get Started" button doing double duty.

---

## 1. Color

**Palette direction: Civic Rose.** Navy carries authority and structure, as before. The accent
changed from amber to a muted rose/magenta — chosen after seeing the real lingayen.gov.ph site,
which uses a vivid pink as its primary accent. The client confirmed color choice is open (CSOs
are apolitical, so there's no sensitivity around matching or not matching the municipality's
exact brand color). This rose is deliberately in the same family as that pink, visibly related to
it, but desaturated and darkened so it survives being used across dense admin tables and status
badges all day, not just a marketing homepage. Two other directions remain easy to swap to if the
client prefers: a closer match to the municipal site's actual vivid pink, or an institutional
blue-and-gold scheme common to Philippine government seals generally. Swap by replacing the
`rose` scale below; nothing else in this document depends on the specific hue.

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

rose:  { 100: 'oklch(0.950 0.035 005)', 200: 'oklch(0.890 0.065 350)',
         400: 'oklch(0.780 0.130 348)', 500: 'oklch(0.680 0.165 346)',
         600: 'oklch(0.580 0.155 345)', 700: 'oklch(0.480 0.130 344)' }

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
| `rose-500` on `paper` | ~2.4:1 | **Fails.** Never text on light |
| `rose-500` on `navy-900` | ~5.8:1 | Correct usage — badges, underlines, active-state fills on dark |
| `paper` on `rose-600` | ~4.6:1 | Minimum for a rose-filled button label; verify in-browser, this is close to the AA floor |

Rose is a fill or a rule, never a foreground on white. Muted text stops at `--color-muted`.
**Verify `paper` on `rose-600` directly in the browser once implemented** — the approximation
above is not final sign-off.

### Dark mode

Out of scope for Milestone 1. Shared office desktops in daylight, one rendering path, less to
verify before the client demo. Tokens are structured so a dark block can be added later by
swapping `paper` / `surface` / `ink` / `line` only.

---

## 2. Typography

**Public Sans** for everything. Commissioned for US federal government use, unusually legible at
13 to 14px, exactly where admin tables live. **IBM Plex Mono** carries verification codes,
reference numbers, and tabular figures, where character disambiguation matters (`0` vs `O` in a
certificate code).

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
- Letter-spacing: `-0.02em` on `2xl` and above, `0` elsewhere. No all-caps body copy.

### Copy voice — specific, human, never corporate

- Write like the Civil Society Desk Office would actually speak, not like a SaaS landing page.
  "See which organizations are active in your barangay" instead of "Empowering community
  engagement." "Apply for accreditation" instead of "Get Started."
- Use real names wherever the data provides them: barangay names, sector names, organization
  names. Never "various organizations" when `{{ $organization->name }}` is sitting right there.
- No filler verbs (elevate, unlock, streamline, revolutionize). No invented statistics. No
  "trusted by" claims unless they're literally true and sourced from the database.
- One voice across all three roles: plain, direct, respectful of the reader's time. The CSO
  dashboard's "What PESO is waiting on" pattern (already built) is the right tone — say exactly
  what's true, nothing more.

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

## 4. Motion — dynamic on public pages, calm everywhere else

Two different MOTION_INTENSITY settings, deliberately: **5 on public pages** (home, directory,
organization profiles — this is where "dynamic" lives), **2 on admin and CSO screens** (where a
PESO officer needs speed and predictability, not delight, across dozens of rows a day). This
split is intentional, not an oversight — see PRD §7 on per-role density.

```
ease-out-strong  cubic-bezier(0.23, 1, 0.32, 1)
duration-fast    120ms      duration-base   180ms      duration-slow   400ms
```

**Public pages, what's earned (each animation has a stated purpose, per the Iron Law: no motion
without a reason)**

- **Hero activity ticker**: the rotating "most recently verified activity" line (§0) fades and
  slides between real entries every 6 seconds, `duration-slow`, pauses on hover/focus. Purpose:
  the mechanism that makes the site feel alive, because it's showing something that's actually
  changing, not animation for decoration's sake.
- **Stat counters**: transparency numbers (accredited count, verified activities, barangays
  represented) count up once when scrolled into view, via `IntersectionObserver` with
  `{ once: true }`. Purpose: draws attention to real figures the PRD treats as a core
  transparency feature, not a cosmetic flourish.
- **Directory card stagger**: 30-80ms delay between cards as they enter, same pattern as any
  list reveal. Purpose: large result sets don't dump on screen at once.
- **Nav underline, button press, hover states**: standard interaction feedback per §5, unchanged.

**What stays banned everywhere, public pages included:** scroll-hijacking/pinned sections,
parallax, auto-playing carousels the user can't pause, GSAP ScrollTrigger choreography, anything
that fires on every page load without the user doing something to trigger it. This is a
municipal government site, not an agency portfolio — dynamic means "alive with real data," not
"cinematic."

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

The ticker and counters above must both have a static, correct fallback under this query — show
the latest activity and final numbers immediately, no animation, not a frozen mid-animation frame.

---

## 5. Components, all eight states

Focus is the same everywhere: `focus-visible:outline-2 focus-visible:outline-offset-2
focus-visible:outline-navy-600`. On navy backgrounds, switch the ring to `outline-rose-400`.
Never remove an outline without a `:focus-visible` replacement. Touch targets are 44px minimum.

**Icon library:** Phosphor or Tabler, one family for the whole project. Select icons
deliberately per §0 (sector-specific, not generic) — never hand-roll SVG icon paths.

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
Labels are verb plus object: "Submit application", "Verify activity", "Publish post".

### 5.2 Input, select, textarea

| State | Treatment |
|---|---|
| Default | `bg-paper border border-line-strong rounded-sm px-3 py-2 text-base` |
| Hover | `border-navy-300` |
| Focus | `border-navy-600` plus the standard outline ring |
| Disabled | `bg-surface text-muted border-line cursor-not-allowed` |
| Error | `border-danger-600`, message below, `aria-describedby`, `aria-invalid="true"` |
| Success | `border-success-600` only when a real check ran, not on every valid keystroke |

Labels above inputs, always visible. Validate on blur, not per keystroke.

### 5.3 Card / panel

`bg-paper border border-line rounded-lg p-6 shadow-panel`. Admin variants drop the shadow. A card
earns its box only when it represents one addressable object. No nested cards, ever.

### 5.4 Badge, the status language

| Domain state | Badge | Token pair |
|---|---|---|
| `draft` | Draft | `bg-surface text-muted border-line` |
| `submitted` | Submitted | `bg-info-100 text-info-600` |
| `under_review` | Under review | `bg-info-100 text-info-600` |
| `pending` (activity) | Pending verification | `bg-warning-100 text-warning-600` |
| `approved`, `verified`, `active` | Approved / Verified / Active | `bg-success-100 text-success-600` |
| `rejected`, `revoked` | Rejected / Revoked | `bg-danger-100 text-danger-600` |
| `expired` | Expired | `bg-surface text-muted border-line-strong` |

SB reading stage is a stepper: current step `navy-700` with a rose underline, completed
`success-600`, future `muted`. Status is never color alone, the word is always present.

### 5.5 Table (the admin workhorse)

```
thead th   text-xs font-semibold tracking-wide text-muted uppercase px-4 py-2 border-b border-line-strong text-left
tbody td   text-sm px-4 py-2.5 border-b border-line align-middle
numeric    font-mono tabular-nums text-right
```

Zebra-free. Hover `bg-navy-50` on the whole row. Sticky header past ~15 rows. Every list
paginates. Empty state names what would appear plus the action that creates it.

---

## 6. Per-role application

**Public (density 4, motion 5).** Navy-900 header, rose underline on active nav. Hero carries
the activity ticker (§0, §4). Sections at `py-20`. Directory cards in
`repeat(auto-fit, minmax(280px, 1fr))`. Skip link to `#main` mandatory.

**PESO Admin (density 7, motion 2).** Persistent left nav, navy-900, 240px. Queues are tables.
Pending counts in rose badges against the navy nav — the one place rose carries meaning here.

**CSO Representative (density 5, motion 2).** Single-column, `max-w-5xl`, top nav. Dashboard
answers three questions before any scroll: accreditation status, what PESO is waiting on, current
score (with its "informational only" caption attached permanently, per PRD §15).

---

## 7. Anti-generic guardrails

This project already has the `design-taste` skill installed with its own anti-slop catalogue
(`.claude/skills/design-taste/reference/anti-slop.md`) — that reference is authoritative and
more exhaustive than this list. These are the project-specific reminders worth restating because
they're the easiest defaults to slip back into:

**Banned outright, no exceptions:**
- Purple-blue gradients, or any gradient as a decorative default
- Glassmorphism / frosted-glass cards
- Floating dashboard mockups or fake div-built screenshots in the hero
- Glowing blobs, blurred color orbs, mesh gradients as background texture
- Generic three-equal-card feature grids
- A startup-style centered hero over a dark gradient with a "Get Started" button
- Vague corporate copy ("empowering," "seamless," "unlock your potential") anywhere on the site
- Em dashes anywhere in shipped copy (see the anti-slop doc §9.G — this is a hard ban, not a
  style preference)

**What replaces each one, specifically for this project:** real-data hero ticker instead of a
gradient hero (§0), bordered panels instead of glass cards (§5.3), the real activity feed instead
of a fake dashboard screenshot, sector-specific icon selection instead of blob decoration (§0),
asymmetric or zig-zag layout instead of three equal cards where a feature section is genuinely
needed, plain-spoken PESO-voice copy instead of corporate copy (§2).

**Before calling any public page done, run the design-taste skill's pre-flight check explicitly**
— don't assume it ran automatically. Ask Claude Code directly: *"Run the design-taste pre-flight
check against this page before marking it done."*

---

## 8. Images — logos and CSO event photography

### Where logos actually go, right now

**Individual CSO logos** already have a working upload path — `OrganizationProfileController`
stores them via `$request->file('logo')->store('logos', 'public')`, landing in
`storage/app/public/logos/` and served at `/storage/logos/{filename}` once
`php artisan storage:link` has been run (standard Laravel setup, almost certainly already done
since the public disk is configured). For the 202 real organizations, you have two paths:
1. **Real-world path:** each CSO logs in and uploads their own logo through the existing profile
   form — correct long-term, but means waiting on 202 individual logins.
2. **Fast-import path, for getting real data in before your next client check-in:** place the
   logo files directly into `storage/app/public/logos/` (e.g. `logos/org-{id}.png`) and set
   `logo_path` to that value directly in your import script/seeder. No new code needed — this
   uses the exact same field the real upload flow writes to.

**The municipal/office seal** replaces the placeholder in
`resources/views/components/civic-mark.blade.php` (currently a plain "LC" text badge, built that
way deliberately per PRD §11 until the real file arrives). Once you have the real seal: if it's
vector (SVG/AI/EPS), inline it directly in that component for crispness at every size; if it's
only a raster file (PNG/JPG), place it in `public/images/` and swap the component to an `<img>`
tag pointing there. Either way, that one component change updates the seal everywhere it's used
(nav, footer, About Us) since nothing else references it directly.

### Event/activity imagery — stock now, real later, same mechanism

Per the design-taste skill's own rules, prefer `https://picsum.photos/seed/{descriptive-string}/{w}/{h}`
over hotlinked Unsplash URLs — hotlinks break, picsum with a descriptive seed is stable and still
gives you varied, real-feeling photography for placeholder purposes. Example:
`https://picsum.photos/seed/lingayen-coastal-cleanup/1600/900`.

**Set up the swap mechanism now, before you need it:**
1. Create `public/images/stock/` and `public/images/events/` as two separate folders.
2. Use stock images (picsum, or downloaded — not hotlinked — Unsplash/Pexels files) in
   `public/images/stock/` today, with descriptive filenames (`coastal-cleanup.jpg`, not
   `hero1.jpg`), referenced directly in your Blade views.
3. When real CSO event photos arrive, drop them into `public/images/events/` with similarly
   descriptive names, and swap the file paths in the views that use them. Because every photo
   gets the same navy-duotone CSS treatment (§0) rather than relying on the stock photo's own
   look, the visual identity doesn't change when the source photos do — only the swap itself.

**The duotone treatment**, applied consistently to every photo on the site:
```css
.photo-duotone {
  filter: grayscale(1) contrast(1.1);
  position: relative;
}
.photo-duotone::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(180deg, oklch(0.21 0.055 250 / 0.55), oklch(0.21 0.055 250 / 0.15));
  mix-blend-mode: multiply;
}
```
This is also the exact mechanism from §0 that makes generic stock photography stop looking
generic — the treatment carries the identity, not the photo.

### How to actually tell Claude Code which images to use

Be specific about paths, don't let it pick: *"Use `public/images/stock/coastal-cleanup.jpg` for
the hero background, and `public/images/stock/skills-training.jpg`,
`public/images/stock/youth-assembly.jpg` for the two supporting images in the About section.
Apply the `.photo-duotone` treatment from DESIGN_SYSTEM.md §8 to all three."* Naming exact
filenets prevents it from generating a new `picsum.photos` URL per request, which would make the
same section show different photos on every reload.

---

## 9. Pre-flight before any screen is called done

- [ ] Design concept check: does this page look like it could only be Lingayen CivicLink, or
      could it be any generic civic/SaaS site with the logo swapped? If the latter, it's not done.
- [ ] Contrast verified against §1; `paper` on `rose-600` checked directly in-browser
- [ ] All eight states present on every interactive element, focus distinct from hover
- [ ] Labels above inputs, errors below, wired with `aria-describedby`
- [ ] Status communicated by word plus color, never color alone
- [ ] Keyboard path complete: skip link, visible focus, logical tab order, 44px targets
- [ ] Public-page motion (§4) has a stated purpose and a correct reduced-motion fallback
- [ ] Admin/CSO screens stayed at motion 2 — dynamism wasn't accidentally carried over
- [ ] No banned pattern from §7 present anywhere on the page
- [ ] Copy is specific and human (§2) — no filler verbs, no vague claims, real names used
      wherever the data provides them
- [ ] Image paths are explicit and point to real files in `public/images/`, not generated on
      the fly per load
- [ ] Renders correctly at 1366x768, the realistic municipal office screen