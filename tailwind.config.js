import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import plugin from 'tailwindcss/plugin';

// Colour values live in resources/css/tokens.css; this file only maps class names to them.
const token = (name) => `rgb(var(--color-${name}) / <alpha-value>)`;
const stepScale = {
    0: '0', px: '1px', hair: '2px', 1: '0.25rem', tight: '6px', 2: '0.5rem', 3: '0.75rem', 4: '1rem',
    6: '1.5rem', 8: '2rem', 12: '3rem', section: '5rem',
};
const scale = (name, steps) => Object.fromEntries(steps.map((step) => [step, token(`${name}-${step}`)]));
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        // Padding, margin, gap and space are locked to the 8-pt scale (4/8/12/16/24/32/48). `hair` (2px)
        // and `tight` (6px) exist only for badges and icon gaps; `section` (80px) is the public site's
        // band rhythm. Widths and heights keep Tailwind's full scale (h-11 touch targets, w-64 sidebar).
        padding: stepScale,
        margin: { ...stepScale, auto: 'auto' },
        gap: stepScale,
        space: stepScale,

        extend: {
            fontFamily: {
                sans: ['"Public Sans"', ...defaultTheme.fontFamily.sans],
                // Public-site headings only (pcnc.com.ph reference); admin and CSO screens stay sans.
                serif: ['Merriweather', ...defaultTheme.fontFamily.serif],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },

            // Every colour reads a CSS variable from resources/css/tokens.css (the single source of
            // truth), so opacity utilities like bg-primary-dark/60 work. See docs/DESIGN_SYSTEM.md.
            colors: {
                primary: {
                    DEFAULT: token('primary'),
                    hover: token('primary-hover'),
                    dark: token('primary-dark'),
                    'dark-hover': token('primary-dark-hover'),
                    subtle: token('primary-subtle'),
                },
                'on-primary': token('on-primary'),
                'on-dark': token('on-dark'),
                'on-dark-muted': token('on-dark-muted'),
                accent: {
                    DEFAULT: token('accent'),
                    fg: token('on-accent'),
                    ...scale('accent', [100, 200, 400, 500, 600, 700]),
                },
                facebook: token('facebook'),   // Facebook icon hover in the public header
                focus: {
                    DEFAULT: token('focus'),
                    'on-dark': token('focus-on-dark'),
                },
                // Public-site scale; admin and CSO screens use the roles above.
                brand: scale('brand', [50, 100, 200, 300, 400, 500, 600, 700, 800, 900]),
                // Spec names in tokens.css; the short class names were kept to avoid renaming ~300 uses.
                paper: token('surface'),        // --color-surface
                canvas: token('surface-muted'), // --color-surface-muted
                band: token('band'),            // public grey bands only
                line: {
                    DEFAULT: token('border'),
                    strong: token('border-strong'),
                },
                ink: token('text'),             // --color-text
                muted: token('text-muted'),     // --color-text-muted
                info: { DEFAULT: token('info'), subtle: token('info-subtle') },
                success: { DEFAULT: token('success'), subtle: token('success-subtle') },
                warning: { DEFAULT: token('warning'), subtle: token('warning-subtle') },
                danger: { DEFAULT: token('danger'), subtle: token('danger-subtle') },
                neutral: { DEFAULT: token('text-muted'), subtle: token('surface-muted') },
                chart: {
                    self: token('chart-self'),
                    lgu: token('chart-lgu'),
                    unknown: token('chart-unknown'),
                    bar: token('chart-bar'),
                },
            },

            fontSize: {
                xs: ['0.75rem', { lineHeight: '1.4' }],
                sm: ['0.875rem', { lineHeight: '1.5rem' }],
                'card-title': ['1.125rem', { lineHeight: '1.5rem' }],  // 18/24
                'page-title': ['1.5rem', { lineHeight: '2rem' }],      // 24/32
                base: ['1rem', { lineHeight: '1.55' }],
                lg: ['1.25rem', { lineHeight: '1.45' }],
                xl: ['1.5rem', { lineHeight: '1.3' }],
                '2xl': ['1.95rem', { lineHeight: '1.2', letterSpacing: '-0.02em' }],
                '3xl': ['2.45rem', { lineHeight: '1.15', letterSpacing: '-0.02em' }],
                display: ['3.05rem', { lineHeight: '1.1', letterSpacing: '-0.02em' }],
            },


            borderRadius: {
                sm: '0.25rem',
                md: '0.375rem',
                lg: '0.5rem',
            },

            boxShadow: {
                // One elevation for cards; overlay is for the drawer and dialogs only.
                panel: '0 1px 2px rgb(var(--color-primary-dark) / 0.06)',
                overlay: '0 8px 24px rgb(var(--color-primary-dark) / 0.14)',
            },

            transitionTimingFunction: {
                'out-strong': 'cubic-bezier(0.23, 1, 0.32, 1)',
            },

            transitionDuration: {
                fast: '120ms',
                base: '180ms',
                slow: '400ms',
            },

            zIndex: {
                dropdown: '10',
                sticky: '20',
                overlay: '30',
                dialog: '40',
                toast: '50',
            },
        },
    },

    plugins: [
        forms,
        // `touch:` targets coarse pointers (phones, tablets) for 44px hit areas. A plugin, not a
        // `screens` entry, because raw screens switch off Tailwind's max-* variants.
        plugin(({ addVariant }) => addVariant('touch', '@media (pointer: coarse)')),
    ],
};
