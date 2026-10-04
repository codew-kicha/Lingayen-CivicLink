import './bootstrap';

import Alpine from 'alpinejs';

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

window.Alpine = Alpine;

Alpine.start();
