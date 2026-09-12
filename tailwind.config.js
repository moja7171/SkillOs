import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Vazirmatn', 'Tahoma', 'Segoe UI', 'sans-serif'],
                mono: ['JetBrains Mono', 'ui-monospace', 'Menlo', 'monospace'],
            },
            // Semantic tokens; the actual values live in app.css (:root = light, .dark = dark).
            colors: {
                bg: 'var(--bg)',
                surface: 'var(--surface)',
                surface2: 'var(--surface2)',
                line: 'var(--line)',
                line2: 'var(--line2)',
                ink: 'var(--ink)',
                muted: 'var(--muted)',
                faint: 'var(--faint)',
                accent: 'var(--accent)',
                'accent-ink': 'var(--accent-ink)',
                ok: 'var(--ok)',
                warn: 'var(--warn)',
                bad: 'var(--bad)',
                code: 'var(--code)',
                hover: 'var(--hover)',
                l0: 'var(--l0)',
                l1: 'var(--l1)',
                l2: 'var(--l2)',
                l3: 'var(--l3)',
                l4: 'var(--l4)',
            },
            borderRadius: {
                card: '10px',
            },
        },
    },

    plugins: [forms({ strategy: 'class' })],
};
