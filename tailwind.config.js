/** @type {import('tailwindcss').Config} */
module.exports = {
	prefix: 'simcal-',
	content: ['includes/**/*.{html,js,php}'],
	theme: {
		extend: {
			colors: {
				sc_green: {
					100: '#E9F3F4',
					200: '#60BC4E',
				},
				sc_blue: {
					100: '#F2F7FB',
					200: '#3EAFEF',
					300: '#5DC4FE',
				},
				sc_grey: {
					100: '#7F8490',
					200: '#D4D4D8',
					300: '#71717A',
					400: '#f7fcf6',
				},
				sc_cream: {
					100: '#E7BE9E',
				},
				sc_black: {
					100: '#3A414C',
					200: '#1D2327',
				},
				sc_yellow: {
					100: '#F7D100',
				},
				sc_purple: 'rgba(255, 255, 255, 0.2)',
			},
			fontFamily: {
				poppins: ['Poppins', 'sans-serif'],
			},
		},
		screens: {
			sm: '640px',
			// => @media (min-width: 640px) { ... }

			md: '768px',
			// => @media (min-width: 768px) { ... }

			lg: '1024px',
			// => @media (min-width: 1024px) { ... }

			xl: '1280px',
			// => @media (min-width: 1280px) { ... }

			'2xl': '1536px',
			// => @media (min-width: 1536px) { ... }

			'3xl': '1920px',
			// => @media (min-width: 1920px) { ... }
		},
	},
	plugins: [],
};
