/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        'resources/**/*.blade.php',
        'resources/**/*.js',
    ],
    theme: {
        // Override default screens to match the bootstrap-style grid spec:
        // mobile <768 (fluid), tablet-portrait 768-991, tablet-landscape 992-1199, desktop ≥1200
        screens: {
            'sm':  '768px',   // tablet portrait start
            'md':  '992px',   // tablet landscape start
            'lg':  '1200px',  // large desktop start
            'xl':  '1400px',  // extra-large (optional)
        },
        extend: {
            maxWidth: {
                // Container widths per breakpoint
                'container-sm': '720px',   // sm: 768+  → 720px
                'container-md': '960px',   // md: 992+  → 960px
                'container-lg': '1140px',  // lg: 1200+ → 1140px
            },
            colors: {
                primary: '#1B2A4A',
                secondary: '#2D2D2D',
                accent: '#F59E0B',
                surface: '#FFFFFF',
                muted: '#F8F8F8',
                border: '#E5E7EB',
                textprimary: '#1A1A1A',
                textsecondary: '#6B7280',
                success: '#10B981',
                danger: '#EF4444',
            },
            fontFamily: {
                heading: ['Poppins', 'Tiro Bangla', 'sans-serif'],
                body: ['Inter', 'Tiro Bangla', 'sans-serif'],
            },
            fontSize: {
                h1: '36px',
                h2: '28px',
                h3: '22px',
                h4: '18px',
                base: '16px',
                sm: '13px',
            },
            gap: {
                'gutter': '24px',  // standard 24px gutter (1.5rem)
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/aspect-ratio'),
    ],
};
