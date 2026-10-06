import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Token definitions live in docs/DESIGN_SYSTEM.md. Keep the two in sync.
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Public Sans"', ...defaultTheme.fontFamily.sans],
                // Public-site headings only (pcnc.com.ph reference); admin and CSO screens stay sans.
                serif: ['Merriweather', ...defaultTheme.fontFamily.serif],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                // Palette set by the client team (2026-10-06), after pcnc.com.ph: a maroon brand bar,
                // white and grey surfaces, warm charcoal text. 800 is the exact header maroon.
                brand: {
                    50: 'oklch(0.972 0.010 20)',
                    100: 'oklch(0.935 0.022 20)',
                    200: 'oklch(0.865 0.045 20)',
                    300: 'oklch(0.760 0.075 20)',
                    400: 'oklch(0.640 0.105 20)',
                    500: 'oklch(0.530 0.125 20)',
                    600: 'oklch(0.455 0.125 20)',
                    700: 'oklch(0.395 0.118 20)',
                    800: 'oklch(0.3405 0.11 20)',
                    900: 'oklch(0.270 0.085 20)',
                },
                // Warm gold accent, used sparingly: active menu item, the Apply call to action, rules.
                accent: {
                    100: 'oklch(0.955 0.045 85)',
                    200: 'oklch(0.905 0.085 85)',
                    400: 'oklch(0.830 0.135 80)',
                    500: 'oklch(0.775 0.150 75)',
                    600: 'oklch(0.680 0.140 70)',
                    700: 'oklch(0.540 0.115 65)',
                },
                paper: 'oklch(0.995 0 0)',          // white: page and raised surfaces
                surface: 'oklch(0.8282 0 0)',       // grey: alternating public bands
                canvas: 'oklch(0.965 0 0)',         // admin and CSO page background
                line: {
                    DEFAULT: 'oklch(0.885 0 0)',
                    strong: 'oklch(0.760 0 0)',
                },
                muted: 'oklch(0.420 0.010 50)',     // 4.5:1 or better on the grey bands too
                ink: 'oklch(0.270 0.010 50)',
                success: {
                    100: 'oklch(0.945 0.040 150)',
                    600: 'oklch(0.520 0.130 150)',
                },
                warning: {
                    100: 'oklch(0.950 0.045 75)',
                    600: 'oklch(0.620 0.140 70)',
                },
                danger: {
                    100: 'oklch(0.945 0.035 27)',
                    600: 'oklch(0.520 0.170 27)',
                },
                info: {
                    100: 'oklch(0.945 0.028 245)',
                    600: 'oklch(0.500 0.100 245)',
                },
            },

            fontSize: {
                xs: ['0.75rem', { lineHeight: '1.4' }],
                sm: ['0.875rem', { lineHeight: '1.45' }],
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
                panel: '0 1px 2px oklch(0.27 0.085 20 / 0.06)',
                overlay: '0 8px 24px oklch(0.27 0.085 20 / 0.14)',
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

    plugins: [forms],
};
