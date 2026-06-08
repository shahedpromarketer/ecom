/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        'resources/**/*.blade.php',
        'resources/**/*.js',
    ],
    theme: {
        extend: {
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
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/aspect-ratio'),
    ],
};
