import './bootstrap';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

// x-trap: focus trapping for the navigation drawer.
Alpine.plugin(focus);

// Home-page hero: rotates through recently verified activities (DESIGN_SYSTEM.md §0, §4).
// Every entry is server-rendered, so with JS off the newest one simply stays on screen.
Alpine.data('activityTicker', (count) => ({
    current: 0,
    userPaused: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    hovered: false,
    focused: false,

    init() {
        if (count > 1) {
            setInterval(() => this.advance(), 6000);
        }
    },

    get playing() {
        return !this.userPaused && !this.hovered && !this.focused;
    },

    advance() {
        if (this.playing && !document.hidden) {
            this.current = (this.current + 1) % count;
        }
    },
}));

// Signed-in layout: the phone drawer, and the desktop sidebar that collapses to an icon rail to give the
// page the full width. The collapsed state lives on <html data-sidebar> (set before first paint from localStorage) so
// the CSS in app.css applies it without a flash.
Alpine.data('appShell', () => ({
    drawer: false,
    collapsed: document.documentElement.dataset.sidebar === 'collapsed',

    toggleSidebar() {
        this.collapsed = !this.collapsed;
        if (this.collapsed) {
            document.documentElement.dataset.sidebar = 'collapsed';
        } else {
            delete document.documentElement.dataset.sidebar;
        }
        try { localStorage.setItem('sidebar', this.collapsed ? 'collapsed' : 'open'); } catch (e) { /* private mode */ }
    },
}));

// Signed-in pages: a thin progress bar while the next page loads (link, pagination or filter
// form). Waits 150 ms so fast loads don't flash it, and resets when the page is restored from the
// back/forward cache.
Alpine.data('pageProgress', () => ({
    loading: false,
    timer: null,

    init() {
        const start = () => { this.timer = setTimeout(() => { this.loading = true; }, 150); };
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || link.target
                || link.hasAttribute('download') || link.origin !== location.origin || link.hash) return;
            start();
        });
        document.addEventListener('submit', (event) => { if (!event.defaultPrevented) start(); });
        window.addEventListener('pageshow', () => { clearTimeout(this.timer); this.loading = false; });
    },
}));

// After a pagination link (…#page-title), move focus to the page heading so keyboard and screen
// reader users land on the new results rather than at the top of the document.
window.addEventListener('DOMContentLoaded', () => {
    const target = location.hash && document.getElementById(location.hash.slice(1));
    if (target && target.getAttribute('tabindex') === '-1') target.focus();
});

// Home hero video (public/videos/lingayen_hero.mp4) loops continuously with no controls, by the
// client's choice. It starts only when the device doesn't ask for reduced motion or data saving;
// otherwise the poster stays and the file is never fetched (preload="none").
Alpine.data('heroVideo', () => ({
    init() {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const saveData = navigator.connection && navigator.connection.saveData;

        // Phones only autoplay a video that is muted as a property, not just as an attribute. If the
        // browser still refuses (e.g. iOS Low Power Mode), the poster simply stays.
        this.$refs.video.muted = true;
        if (!reduceMotion && !saveData) this.$refs.video.play().catch(() => {});
    },
}));

// Transparency stats count up once when scrolled into view (§4). The server renders the final
// figure, so no-JS and reduced-motion visitors simply see the real number.
function countUp(element) {
    const target = Number(element.dataset.countTo);
    const duration = 1000;
    const start = performance.now();

    const frame = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        element.textContent = Math.round(target * eased).toLocaleString('en-PH');
        if (progress < 1) requestAnimationFrame(frame);
    };

    requestAnimationFrame(frame);
}

// Directory cards fade in as they enter, staggered within each batch (§4).
function revealInBatches(elements) {
    const observer = new IntersectionObserver((entries) => {
        entries
            .filter((entry) => entry.isIntersecting)
            .forEach((entry, index) => {
                entry.target.style.transitionDelay = `${Math.min(index, 8) * 50}ms`;
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            });
    }, { rootMargin: '0px 0px -40px 0px' });

    elements.forEach((element) => observer.observe(element));
}

// Public pages only: the public layout sets .motion-ok in <head> (MOTION_INTENSITY 5). Admin and
// CSO layouts never do, so they stay at motion 2.
if (document.documentElement.classList.contains('motion-ok')) {
    const counters = document.querySelectorAll('[data-count-to]');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
            countUp(entry.target);
            counterObserver.unobserve(entry.target);
        });
    }, { threshold: 0.6 });
    counters.forEach((counter) => {
        counter.textContent = '0';
        counterObserver.observe(counter);
    });

    revealInBatches(document.querySelectorAll('.reveal, .reveal-line, .reveal-photo'));
}

// Application form: reads each requirement as it is chosen and says whether it looks like the
// right document. Advisory only; it never blocks submitting (resources/js/ocr.js).
Alpine.data('documentPrecheck', (checks) => ({
    state: 'idle', // idle | reading | matched | mismatch | unreadable | failed
    text: '',
    missing: [],

    async check(event) {
        const file = event.target.files[0];
        Object.assign(this, { state: 'idle', text: '', missing: [] });

        if (!file || file.size > 5 * 1024 * 1024) return;

        this.state = 'reading';
        try {
            const { readDocument, evaluate } = await import('./ocr.js');
            const text = await readDocument(file);

            if (event.target.files[0] !== file) return; // replaced while reading

            Object.assign(this, { text }, evaluate(text, checks));
        } catch (error) {
            console.warn('Document pre-check failed', error);
            this.state = 'failed';
        }
    },
}));

// <x-email-field>: helps type an email without limiting it. Gmail is the usual provider here, so a
// faint "@gmail.com" completes a name with no "@" yet (Tab or → accepts it); after "@" the common
// domains are offered as you type; on leaving the field a likely typo gets "Did you mean ...?".
const EMAIL_DOMAINS = ['gmail.com', 'yahoo.com', 'yahoo.com.ph', 'outlook.com', 'hotmail.com', 'icloud.com', 'ymail.com', 'live.com', 'lingayen.gov.ph'];

// Edit distance (insertions, deletions, substitutions, and swapped neighbours count as one).
function editDistance(a, b) {
    const d = Array.from({ length: a.length + 1 }, (_, i) => [i, ...Array(b.length).fill(0)]);
    for (let j = 1; j <= b.length; j++) d[0][j] = j;
    for (let i = 1; i <= a.length; i++) {
        for (let j = 1; j <= b.length; j++) {
            const cost = a[i - 1] === b[j - 1] ? 0 : 1;
            d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + cost);
            if (i > 1 && j > 1 && a[i - 1] === b[j - 2] && a[i - 2] === b[j - 1]) d[i][j] = Math.min(d[i][j], d[i - 2][j - 2] + 1);
        }
    }
    return d[a.length][b.length];
}

// The known domain a typed one was probably meant to be, or null when it looks intended.
export function likelyDomain(domain) {
    domain = domain.toLowerCase();
    if (!domain || EMAIL_DOMAINS.includes(domain)) return null;
    let best = null;
    let bestDistance = 3;
    for (const known of EMAIL_DOMAINS) {
        const distance = editDistance(domain, known);
        if (distance < bestDistance) [best, bestDistance] = [known, distance];
    }
    // "gmail" or "gmail.co" without the rest also counts.
    return best ?? EMAIL_DOMAINS.find((known) => known.startsWith(domain) && domain.length >= 4) ?? null;
}

Alpine.data('emailAssist', (initial = '') => ({
    value: initial,
    open: false,
    active: 0,
    suggestion: '',

    get local() { return this.value.split('@')[0]; },
    get domainPart() { return this.value.includes('@') ? this.value.split('@')[1] : null; },
    // Shown only while the caret is at the end of a name with no "@" yet.
    get ghost() { return this.value && !this.value.includes('@') && !/\s/.test(this.value) ? '@gmail.com' : ''; },
    get options() {
        const typed = this.domainPart;
        if (typed === null || EMAIL_DOMAINS.includes(typed.toLowerCase())) return [];
        return EMAIL_DOMAINS.filter((domain) => domain.startsWith(typed.toLowerCase())).slice(0, 6);
    },

    update() {
        this.suggestion = '';
        this.active = 0;
        this.open = this.domainPart !== null;
    },

    key(event) {
        // Email inputs don't report the caret position (selectionStart is null); treat that as the end.
        const input = this.$refs.input;
        const atEnd = input.selectionStart === null || input.selectionStart === this.value.length;
        if (this.ghost && atEnd && (event.key === 'Tab' || event.key === 'ArrowRight') && !event.shiftKey) {
            event.preventDefault();
            this.value += this.ghost;
            return;
        }
        if (!this.open || !this.options.length) return;
        if (event.key === 'ArrowDown') { event.preventDefault(); this.active = (this.active + 1) % this.options.length; }
        if (event.key === 'ArrowUp') { event.preventDefault(); this.active = (this.active + this.options.length - 1) % this.options.length; }
        if (event.key === 'Enter' || (event.key === 'Tab' && !event.shiftKey)) { event.preventDefault(); this.pick(this.options[this.active]); }
        if (event.key === 'Escape') this.open = false;
    },

    pick(domain) {
        this.value = this.local + '@' + domain;
        this.open = false;
        this.$nextTick(() => this.$refs.input.focus());
    },

    check() {
        this.open = false;
        const domain = this.domainPart;
        const fix = domain ? likelyDomain(domain) : null;
        this.suggestion = fix ? this.local + '@' + fix : '';
    },

    useSuggestion() {
        this.value = this.suggestion;
        this.suggestion = '';
        this.$refs.input.focus();
    },
}));

// <x-member-table>: the member list, five rows to a page. Every row stays rendered (hidden when on
// another page) so the whole list is submitted; a completely blank row has no input names, so it is
// not. The first row is the representative and can't be removed. A spreadsheet upload is read by the
// server and appended for review. Adding or removing a row fires `input` so save bars notice.
const MEMBER_FIELDS = ['last_name', 'first_name', 'middle_name', 'position', 'contact_number', 'email'];
const PER_PAGE = 5;

Alpine.data('memberTable', ({ rows, errors, minimum, representative, importUrl }) => ({
    rows: rows.map((row, i) => ({ ...Object.fromEntries(MEMBER_FIELDS.map((f) => [f, row[f] ?? ''])), src: row.src, key: i, locked: i === 0 })),
    errors,
    minimum,
    page: 1,
    nextKey: rows.length,
    importing: false,
    notice: '',
    noticeError: false,
    skipped: [],

    init() {
        // After a failed save, open the page holding the first row with an error.
        const first = this.rows.findIndex((row) => MEMBER_FIELDS.some((f) => this.error(row, f)));
        if (first > 0) this.page = Math.floor(first / PER_PAGE) + 1;
    },

    get pages() { return Math.max(1, Math.ceil(this.rows.length / PER_PAGE)); },
    get filled() { return this.rows.filter((row) => row.last_name.trim() && row.first_name.trim()).length; },
    onPage(index) { return Math.floor(index / PER_PAGE) + 1 === this.page; },
    blank(row) { return !row.locked && MEMBER_FIELDS.every((f) => !String(row[f]).trim()); },
    error(row, field) { return row.src === undefined ? '' : (this.errors[`members.${row.src}.${field}`] ?? [''])[0]; },
    changed() { this.$root.dispatchEvent(new Event('input', { bubbles: true })); },

    add(values = {}) {
        this.rows.push({ ...Object.fromEntries(MEMBER_FIELDS.map((f) => [f, values[f] ?? ''])), key: this.nextKey++, locked: false });
        if (!Object.keys(values).length) {
            this.page = this.pages;
            const key = this.rows.at(-1).key;
            this.$nextTick(() => document.getElementById(`member-${key}-last_name`)?.focus());
        }
        this.changed();
    },

    remove(row) {
        if (row.locked) return;
        this.rows = this.rows.filter((r) => r !== row);
        this.page = Math.min(this.page, this.pages);
        this.changed();
    },

    async upload(event) {
        const file = event.target.files[0];
        event.target.value = '';
        if (!file) return;
        this.importing = true;
        this.notice = '';
        this.skipped = [];
        try {
            const body = new FormData();
            body.append('file', file);
            const response = await fetch(importUrl, {
                method: 'POST',
                body,
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message ?? Object.values(data.errors ?? {})[0]?.[0] ?? 'The file could not be read.');

            // Empty rows give way to the imported ones; the representative stays first.
            this.rows = this.rows.filter((row) => !this.blank(row));
            const rep = this.rows[0];
            let added = 0;
            for (const row of data.rows) {
                const sameAsRep = row.last_name.toLowerCase() === rep.last_name.toLowerCase() && row.first_name.toLowerCase() === rep.first_name.toLowerCase();
                if (sameAsRep) continue;
                if (row.position.toLowerCase() === representative.toLowerCase()) row.position = '';
                this.add(row);
                added++;
            }
            this.page = 1;
            this.noticeError = false;
            this.skipped = data.skipped ?? [];
            this.notice = `${added} ${added === 1 ? 'member was' : 'members were'} added from the file. Check them, then save.`;
        } catch (error) {
            this.noticeError = true;
            this.notice = error.message;
        } finally {
            this.importing = false;
        }
    },
}));

// Admin dashboard tiles: a number that counts up from zero when the page opens (about 0.6 s, easing
// out). Under reduced motion it simply shows the number. The server-rendered number is the fallback.
Alpine.data('countUp', (target) => ({
    shown: target,

    init() {
        if (target <= 0 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const start = performance.now();
        const step = (now) => {
            // A frame's timestamp can be a little earlier than `start`; never go below zero.
            const t = Math.min(1, Math.max(0, (now - start) / 600));
            this.shown = Math.round(target * (1 - Math.pow(1 - t, 3)));
            if (t < 1) requestAnimationFrame(step);
        };
        this.shown = 0;
        requestAnimationFrame(step);
    },
}));

// Analytics charts: one read-out that follows the pointer or keyboard focus, the mark that was clicked
// (its details open under the chart), and the measure shown on the monthly chart. Each mark carries
// its read-out as JSON in data-tip; it is shown with x-text, so names stay text. `bar` charts put
// the read-out at the end of the bar, `column` charts above the column.
Alpine.data('chart', ({ shape = 'column', metric = null } = {}) => ({
    tip: null,
    picked: null,
    // On the sector chart: the colour that was clicked within the picked bar, and the colour the
    // legend highlights across every bar.
    pickedSource: null,
    source: null,
    metric,
    more: false,

    show(event) {
        const target = event.currentTarget;
        const plot = this.$refs.plot.getBoundingClientRect();
        const marks = [...target.querySelectorAll('[data-mark]')].filter((el) => el.getClientRects().length);
        const mark = (marks[0] ?? target).getBoundingClientRect();
        const x = shape === 'bar' ? mark.left + Math.max(mark.width, 0) : mark.left + mark.width / 2;
        const { title, rows } = JSON.parse(target.dataset.tip);
        const half = Math.min(112, plot.width / 2);
        this.tip = { title, rows, x: Math.min(Math.max(x - plot.left, half), plot.width - half), y: mark.top - plot.top };
    },

    hide() {
        this.tip = null;
    },

    pick(key, source = null) {
        const same = this.picked === key && this.pickedSource === source;
        this.picked = same ? null : key;
        this.pickedSource = same ? null : source;
    },
}));

// <x-info-tip>: a metric's definition beside it. Hover or focus shows it; a click pins it open until
// Esc, a click elsewhere, or scrolling. It sits in the top layer (popover) where supported, placed
// under the button (above when there's no room) and kept inside the window.
Alpine.data('infoTip', () => ({
    open: false,
    pinned: false,

    show() {
        this.open = true;
        this.$nextTick(() => this.place());
    },

    hide(force = false) {
        if (this.pinned && !force) return;
        this.pinned = false;
        this.open = false;
        if (this.$refs.tip.matches?.(':popover-open')) this.$refs.tip.hidePopover();
    },

    pin() {
        if (this.pinned) return this.hide(true);
        this.pinned = true;
        this.show();
    },

    place() {
        const tip = this.$refs.tip;
        if (tip.showPopover && !tip.matches(':popover-open')) tip.showPopover();
        const button = this.$refs.button.getBoundingClientRect();
        const width = Math.min(288, window.innerWidth - 32);
        tip.style.inset = 'auto';
        tip.style.width = `${width}px`;
        tip.style.left = `${Math.min(Math.max(button.left + button.width / 2 - width / 2, 16), window.innerWidth - width - 16)}px`;
        const below = button.bottom + 8;
        tip.style.top = below + tip.offsetHeight > window.innerHeight - 8 ? `${Math.max(8, button.top - 8 - tip.offsetHeight)}px` : `${below}px`;
    },
}));

// Sortable tables on the analytics page: a header button sorts by its column, numbers by value
// (cells may carry data-sort), text alphabetically; a second click reverses.
Alpine.data('sortTable', () => ({
    column: null,
    ascending: false,

    sort(index, text = false) {
        this.ascending = this.column === index ? ! this.ascending : text;
        this.column = index;
        const body = this.$refs.body;
        const value = (row) => row.cells[index].dataset.sort ?? row.cells[index].textContent.trim();
        const rows = [...body.rows].sort((a, b) => {
            const x = value(a);
            const y = value(b);
            const order = text ? x.localeCompare(y) : parseFloat(x) - parseFloat(y);
            return this.ascending ? order : -order;
        });
        body.append(...rows);
    },

    order(index) {
        if (this.column !== index) return 'none';
        return this.ascending ? 'ascending' : 'descending';
    },
}));

// <x-file-field>: the × beside a file input. Clearing fires `change` so listeners (the pre-check)
// reset as if the person had cleared it themselves; focus returns to the input.
Alpine.data('fileField', () => ({
    has: false,

    clear() {
        const input = this.$root.querySelector('input[type=file]');
        input.value = '';
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.focus();
    },
}));

// Dialogs (<x-dialog>, DESIGN_SYSTEM.md §3). Opened with $dispatch('open-dialog', name). Esc and the
// close button always close it; a click outside closes it only while nothing has been typed.
// x-trap keeps focus inside and returns it to the trigger on close.
Alpine.data('dialog', (name, startOpen = false) => ({
    open: startOpen,
    dirty: startOpen, // reopened after a validation error: the form already holds typed values

    init() {
        // Reopened after a validation error: go to the first field with an error, so its message is in view.
        if (startOpen) {
            setTimeout(() => {
                const invalid = this.$el.querySelector('[aria-invalid="true"]');
                invalid?.focus();
                invalid?.scrollIntoView({ block: 'center' });
            }, 100);
        }

        window.addEventListener('open-dialog', (event) => {
            if (event.detail === name) {
                this.dirty = false;
                this.open = true;
            }
        });
        window.addEventListener('close-dialog', (event) => {
            if (event.detail === name) this.open = false;
        });
    },

    close() {
        this.open = false;
    },

    clickOutside() {
        if (!this.dirty) this.close();
    },
}));

// List tables: rows marked data-row open their record. Arrow keys, Home and End move between rows
// (one row is in the tab order at a time); Enter opens. A row with data-href is a link (Ctrl/⌘-click
// opens a new tab); any other row dispatches `row-open` with its data-index for the page to handle.
Alpine.data('tableNav', () => ({
    rows() {
        return [...this.$el.querySelectorAll('tr[data-row]')].filter((row) => row.offsetParent !== null);
    },

    focusRow(row) {
        this.$el.querySelectorAll('tr[data-row]').forEach((other) => { other.tabIndex = -1; });
        row.tabIndex = 0;
        row.focus();
    },

    openRow(row, newTab = false) {
        if (row.dataset.href) {
            newTab ? window.open(row.dataset.href, '_blank', 'noopener') : (window.location.href = row.dataset.href);
        } else {
            this.$dispatch('row-open', { index: Number(row.dataset.index) });
        }
    },

    onClick(event) {
        const row = event.target.closest('tr[data-row]');
        if (!row || event.target.closest('a, button, input, select, textarea, label, form')) return;
        this.focusRow(row);
        this.openRow(row, event.ctrlKey || event.metaKey);
    },

    onKeydown(event) {
        const row = event.target.closest('tr[data-row]');
        if (!row || event.target !== row) return;

        const rows = this.rows();
        const index = rows.indexOf(row);
        const target = { ArrowDown: rows[index + 1], ArrowUp: rows[index - 1], Home: rows[0], End: rows[rows.length - 1] }[event.key];

        if (target) {
            event.preventDefault();
            this.focusRow(target);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            this.openRow(row);
        }
    },
}));

// Searchable select (Barangay). The visible input is the form field itself, so the server still
// validates the value; picking from the list fills in the exact spelling.
Alpine.data('combobox', (options, initial = '') => ({
    query: initial,
    expanded: false,
    active: -1,

    get matches() {
        const query = this.query.trim().toLowerCase();
        return query ? options.filter((option) => option.toLowerCase().includes(query)) : options;
    },

    show() {
        this.expanded = true;
        this.active = Math.max(0, this.matches.indexOf(this.query));
    },

    move(step) {
        if (!this.expanded) return this.show();
        this.active = (this.active + step + this.matches.length) % this.matches.length;
        this.$nextTick(() => this.$refs.listbox?.querySelector('[data-active]')?.scrollIntoView({ block: 'nearest' }));
    },

    // Fires `change`, not `input`: x-model listens to input and would copy the half-typed text back
    // over the choice. Dialogs count change as typing too.
    choose(option) {
        this.query = option;
        this.$refs.input.value = option;
        this.expanded = false;
        this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
    },

    chooseActive(event) {
        if (this.expanded && this.matches[this.active]) {
            event.preventDefault();
            this.choose(this.matches[this.active]);
        }
    },

    // Typed text that matches an option (any case) becomes that option's spelling.
    settle() {
        setTimeout(() => {
            if (this.$el.contains(document.activeElement)) return;
            this.expanded = false;
            const exact = options.find((option) => option.toLowerCase() === this.query.trim().toLowerCase());
            if (exact) this.query = exact;
        }, 120);
    },
}));

// Tag picker (Featured organizations, Merge into): search, pick from the list, remove with the chip's ×.
// `selected` (ids) is x-modelable, so the page binds it with x-model. `single` keeps one choice.
Alpine.data('tagPicker', (options, single = false) => ({
    options,                       // [{ id, name }]
    selected: [],
    query: '',
    expanded: false,
    active: 0,

    get chosen() {
        return this.selected.map((id) => this.options.find((option) => option.id === id)).filter(Boolean);
    },

    get matches() {
        const query = this.query.trim().toLowerCase();
        return this.options
            .filter((option) => !this.selected.includes(option.id))
            .filter((option) => !query || option.name.toLowerCase().includes(query))
            .slice(0, 50);
    },

    add(option) {
        this.selected = single ? [option.id] : [...this.selected, option.id];
        this.query = '';
        this.active = 0;
        // Single choice hides the search box, so focus goes to the choice's × instead of being lost.
        single ? this.$nextTick(() => this.$root.querySelector('button')?.focus()) : this.$refs.input.focus();
        this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
    },

    remove(id) {
        this.selected = this.selected.filter((other) => other !== id);
        this.$refs.input.focus();
        this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
    },

    move(step) {
        this.expanded = true;
        if (this.matches.length) this.active = (this.active + step + this.matches.length) % this.matches.length;
    },

    addActive(event) {
        if (this.expanded && this.matches[this.active]) {
            event.preventDefault();
            this.add(this.matches[this.active]);
        }
    },

    removeLast() {
        if (this.query === '' && this.selected.length) this.selected.pop();
    },

    settle() {
        setTimeout(() => {
            if (!this.$el.contains(document.activeElement)) this.expanded = false;
        }, 120);
    },
}));

// News posts: one dialog writes a new post or edits a row from this page (`items`, table order).
Alpine.data('postEditor', (items, initial = null) => ({
    items,
    post: initial ?? { id: null, title: '', body: '', status: 'draft', organizations: [], url: null },

    write() {
        this.post = { id: null, title: '', body: '', status: 'draft', organizations: [], url: null };
        this.$dispatch('open-dialog', 'post');
    },

    edit(index) {
        this.post = { ...this.items[index], organizations: [...this.items[index].organizations] };
        this.$dispatch('open-dialog', 'post');
    },
}));

// Accounts › Edit login: one dialog for every representative row. `generate` makes a password that
// meets Password::defaults() (12+ characters, mixed case, a number, a symbol) without look-alikes.
Alpine.data('loginEditor', (initial = null, resetOnLoad = false) => ({
    account: initial ?? { id: null, last_name: '', first_name: '', middle_name: '', suffix: '', email: '', phone: '', organization: '', url: '' },
    reset: resetOnLoad,
    password: '',
    show: false,

    edit(account) {
        this.account = { ...account, phone: account.phone ?? '', middle_name: account.middle_name ?? '', suffix: account.suffix ?? '' };
        Object.assign(this, { reset: false, password: '', show: false });
        if (this.$refs.adminPassword) this.$refs.adminPassword.value = ''; // drop anything a browser filled in
        this.$dispatch('open-dialog', 'edit-login');
    },

    generate() {
        const sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789', '-!#@$%?'];
        const all = sets.join('');
        const pick = (chars) => chars[crypto.getRandomValues(new Uint32Array(1))[0] % chars.length];
        const chars = [...sets.map(pick), ...Array.from({ length: 10 }, () => pick(all))];
        for (let i = chars.length - 1; i > 0; i--) {
            const j = crypto.getRandomValues(new Uint32Array(1))[0] % (i + 1);
            [chars[i], chars[j]] = [chars[j], chars[i]];
        }
        this.password = chars.join('');
        this.show = true;
    },
}));

// Sidebar counts (data-nav-count): kept in step when a page acts on an item without reloading.
function adjustNavCount(key, delta) {
    document.querySelectorAll(`[data-nav-count="${key}"]`).forEach((badge) => {
        const count = Math.max(0, Number(badge.textContent) + delta);
        badge.textContent = count;
        badge.setAttribute('aria-label', `${count} waiting`);
        badge.style.display = count === 0 ? 'none' : ''; // not `hidden`: .count-badge sets display
    });
}

// Verify activities: the review dialog for the rows on this page. Verifying or rejecting closes the
// dialog, hides the row, lowers the sidebar badge and shows a toast with Undo. The decision is only
// sent once the toast times out (or right away when the next decision is made or the page is left),
// so Undo never has to reverse a notification or a recalculated score.
Alpine.data('activityReview', (items) => ({
    items,                  // [{ id, title, ..., verifyUrl, rejectUrl }] in table order
    decided: [],            // ids hidden from the list (sent, or waiting in `pending`)
    index: 0,
    source: '',
    rejecting: false,
    reason: '',
    pending: null,          // { item, url, body, message, timer }
    toast: null,            // { message, undo }
    toastTimer: null,

    init() {
        // Leaving through a link or the search form: send the decision first, so the next page
        // already reflects it. Closing the tab falls back to a keepalive request.
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!this.pending || !link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey
                || link.target || link.hasAttribute('download') || link.origin !== location.origin) return;
            event.preventDefault();
            this.send().finally(() => { window.location.href = link.href; });
        }, true);
        document.addEventListener('submit', (event) => {
            if (!this.pending || event.defaultPrevented) return;
            event.preventDefault();
            this.send().finally(() => event.target.submit());
        }, true);
        window.addEventListener('pagehide', () => this.send(true));
    },

    get queue() {
        return this.items.filter((item) => !this.decided.includes(item.id));
    },

    get current() {
        return this.items[this.index] ?? null;
    },

    get position() {
        return this.queue.indexOf(this.current) + 1;
    },

    get reasonLength() {
        return this.reason.trim().length;
    },

    show(index) {
        this.index = index;
        this.source = '';
        this.rejecting = false;
        this.reason = '';
        this.$dispatch('open-dialog', 'review');
    },

    step(delta) {
        const next = this.queue[this.position - 1 + delta];
        if (next) this.show(this.items.indexOf(next));
    },

    verify() {
        if (this.source) this.decide(this.current.verifyUrl, { activity_source: this.source }, 'Activity verified.');
    },

    reject() {
        if (this.reasonLength >= 10) this.decide(this.current.rejectUrl, { rejection_reason: this.reason.trim() }, 'Activity rejected.');
    },

    decide(url, body, message) {
        this.send();

        const item = this.current;
        const following = this.queue[this.position] ?? this.queue[this.position - 2];
        this.decided.push(item.id);
        adjustNavCount('pendingActivities', -1);
        this.$dispatch('close-dialog', 'review');

        this.pending = { item, url, body, message, timer: setTimeout(() => this.send(), 6000) };
        this.notify(message, true);

        // After x-trap hands focus back (to the row that just disappeared), move it to the next row.
        setTimeout(() => {
            const row = following && document.querySelector(`tr[data-row][data-index="${this.items.indexOf(following)}"]`);
            row ? row.focus() : document.getElementById('page-title')?.focus();
        }, 50);
    },

    undo() {
        if (!this.pending) return;
        const { item, timer } = this.pending;
        clearTimeout(timer);
        this.pending = null;
        this.decided = this.decided.filter((id) => id !== item.id);
        adjustNavCount('pendingActivities', 1);
        this.notify('Undone. The activity is back in the list.');
        this.$nextTick(() => document.querySelector(`tr[data-row][data-index="${this.items.indexOf(item)}"]`)?.focus());
    },

    async send(leaving = false) {
        if (!this.pending) return;
        const { item, url, body, timer } = this.pending;
        clearTimeout(timer);
        this.pending = null;
        if (this.toast?.undo) this.toast = null;

        try {
            const response = await fetch(url, {
                method: 'POST',
                keepalive: leaving,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(body),
            });
            if (!response.ok) throw await response.json().catch(() => ({}));
        } catch (error) {
            if (leaving) return;
            this.decided = this.decided.filter((id) => id !== item.id);
            adjustNavCount('pendingActivities', 1);
            const detail = error?.errors ? Object.values(error.errors)[0][0] : error?.message;
            this.notify(`“${item.title}” was not saved${detail ? `: ${detail}` : '. Check your connection and try again.'}`);
        }
    },

    notify(message, undo = false) {
        clearTimeout(this.toastTimer);
        this.toast = { message, undo };
        if (!undo) this.toastTimer = setTimeout(() => { this.toast = null; }, 6000);
    },
}));

window.Alpine = Alpine;

Alpine.start();
