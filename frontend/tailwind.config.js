// frontend/tailwind.config.js
/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    screens: {
      // 375px catches small phones (iPhone SE / mini) before the 640px jump,
      // which is where single-column layouts usually start to feel cramped.
      xs: '375px',
      sm: '640px',
      md: '768px',
      lg: '1024px',
      xl: '1280px',
      '2xl': '1536px',
    },
    darkMode: 'class',
    extend: {
      colors: {
        // Palette drawn from a Mindanao farm at harvest: turned soil, canopy
        // shade, new leaf, husk paper, and the gold of late-afternoon rice.
        soil: '#1E2A1B',
        canopy: '#2F5D3A',
        leaf: '#6FA042',
        sprout: '#C8DDB0',
        husk: '#F8F3E6',
        husk2: '#EFE7D3',
        harvest: '#E0A21C',
        bark: '#7A5C3E',
        clay: '#8C3A22',
        // Dark mode variants
        'dark-soil': '#1E2A1B',
        'dark-canopy': '#2F5D3A',
        'dark-leaf': '#6FA042',
        'dark-sprout': '#C8DDB0',
        'dark-husk': '#F8F3E6',
        'dark-husk2': '#EFE7D3',
        'dark-harvest': '#E0A21C',
        'dark-bark': '#7A5C3E',
        'dark-clay': '#8C3A22',
      },
      fontFamily: {
        display: ['Fraunces', 'Georgia', 'serif'],
        sans: ['"Work Sans"', 'system-ui', 'sans-serif'],
      },
      fontSize: {
        // Fluid scale: these interpolate with the viewport instead of stepping
        // at breakpoints, so headlines stay proportionate on every screen.
        hero: ['clamp(2.5rem, 6.4vw, 5.25rem)', { lineHeight: '0.99', letterSpacing: '-0.022em' }],
        section: ['clamp(1.75rem, 3.4vw, 2.85rem)', { lineHeight: '1.1', letterSpacing: '-0.015em' }],
        title: ['clamp(1.25rem, 2vw, 1.6rem)', { lineHeight: '1.2', letterSpacing: '-0.01em' }],
      },
      boxShadow: {
        crate: '0 18px 40px -24px rgba(30, 42, 27, 0.55)',
        lift: '0 26px 54px -28px rgba(30, 42, 27, 0.65)',
      },
      borderRadius: {
        pod: '1.5rem',
      },
      transitionTimingFunction: {
        // slow, organic easing — the brief asked for a calm pace, not a snappy one
        grow: 'cubic-bezier(0.22, 0.61, 0.36, 1)',
      },
      keyframes: {
        sway: {
          '0%, 100%': { transform: 'rotate(-1.4deg)' },
          '50%': { transform: 'rotate(1.4deg)' },
        },
        riseIn: {
          from: { opacity: '0', transform: 'translateY(20px)' },
          to: { opacity: '1', transform: 'translateY(0)' },
        },
      },
      animation: {
        sway: 'sway 7s ease-in-out infinite',
        riseIn: 'riseIn 0.85s cubic-bezier(0.22, 0.61, 0.36, 1) both',
      },
    },
  },
  plugins: [],
};
