import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Token definitions live in DESIGN_SYSTEM.md. Keep the two in sync.
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
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                navy: {
                    50: 'oklch(0.972 0.012 252)',
                    100: 'oklch(0.932 0.022 253)',
                    200: 'oklch(0.858 0.040 254)',
                    300: 'oklch(0.755 0.062 255)',
                    400: 'oklch(0.650 0.090 255)',
                    500: 'oklch(0.540 0.110 256)',
                    600: 'oklch(0.450 0.105 256)',
                    700: 'oklch(0.360 0.090 254)',
                    800: 'oklch(0.280 0.070 252)',
                    900: 'oklch(0.210 0.055 250)',
                },
                amber: {
                    100: 'oklch(0.950 0.045 75)',
                    200: 'oklch(0.900 0.080 72)',
                    400: 'oklch(0.820 0.140 70)',
                    500: 'oklch(0.740 0.160 66)',
                    600: 'oklch(0.650 0.150 62)',
                    700: 'oklch(0.550 0.130 58)',
                },
                paper: 'oklch(0.990 0.003 252)',
                surface: 'oklch(0.975 0.006 252)',
                line: {
                    DEFAULT: 'oklch(0.900 0.008 252)',
                    strong: 'oklch(0.820 0.010 252)',
                },
                muted: 'oklch(0.500 0.012 252)',
                ink: 'oklch(0.250 0.014 252)',
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
                panel: '0 1px 2px oklch(0.21 0.055 250 / 0.06)',
                overlay: '0 8px 24px oklch(0.21 0.055 250 / 0.14)',
            },

            transitionTimingFunction: {
                'out-strong': 'cubic-bezier(0.23, 1, 0.32, 1)',
            },

            transitionDuration: {
                fast: '120ms',
                base: '180ms',
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
