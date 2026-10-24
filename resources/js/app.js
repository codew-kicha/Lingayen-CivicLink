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

    revealInBatches(document.querySelectorAll('.reveal'));
}

// Application form: reads each requirement as it is chosen and says whether it looks like the
// right document. Advisory only; it never blocks submitting (resources/js/ocr.js).
Alpine.data('documentPrecheck', (keywords, minMatches) => ({
    state: 'idle', // idle | reading | matched | mismatch | unreadable | failed
    text: '',
    found: [],

    async check(event) {
        const file = event.target.files[0];
        Object.assign(this, { state: 'idle', text: '', found: [] });

        if (!file || file.size > 5 * 1024 * 1024) return;

        this.state = 'reading';
        try {
            const { readDocument, evaluate } = await import('./ocr.js');
            const text = await readDocument(file);

            if (event.target.files[0] !== file) return; // replaced while reading

            Object.assign(this, { text }, evaluate(text, keywords, minMatches));
        } catch (error) {
            console.warn('Document pre-check failed', error);
            this.state = 'failed';
        }
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

    choose(option) {
        this.query = option;
        this.expanded = false;
        this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
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
        this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
    },

    remove(id) {
        this.selected = this.selected.filter((other) => other !== id);
        this.$refs.input.focus();
        this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
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
    account: initial ?? { id: null, name: '', email: '', phone: '', organization: '', url: '' },
    reset: resetOnLoad,
    password: '',
    show: false,

    edit(account) {
        this.account = { ...account, phone: account.phone ?? '' };
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
