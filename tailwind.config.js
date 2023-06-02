/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['includes/**/*.{html,js,php}'],
  theme: {    
    extend: {
      backgroundImage: {
        'sc_banner-bg': "url('bg-banner-img.png')",
      },
      colors:{
        sc_green:{          
          100:"#E9F3F4",
          200:"#60BC4E",         
        },
        sc_blue:{
          100:"#F2F7FB",
          200:"#3EAFEF",
          300:"#5DC4FE",
        },
        sc_grey:{
          100:"#7F8490",
          200:"#7F8490",
        },
        sc_cream:{
          100:"#E7BE9E",
        },
        sc_black:{
          100:"#3A414C",
        },
        sc_yellow:{
          100:"#F7D100",
        },
      },
      fontFamily:{
       poppins: ['Poppins' ,'sans-serif'],
      }
    },
    screens: {
      'sm': '640px',
      // => @media (min-width: 640px) { ... } 
  
      'md': '768px',
      // => @media (min-width: 768px) { ... }
  
      'lg': '1024px',
      // => @media (min-width: 1024px) { ... }
  
      'xl': '1280px',
      // => @media (min-width: 1280px) { ... }
    }
  },
  plugins: [],
}