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

window.Alpine = Alpine;

Alpine.start();
