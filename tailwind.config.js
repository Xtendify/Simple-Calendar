/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['includes/**/*.{html,js,php}'],
  theme: {    
    extend: {
      colors:{
        sc_green:{          
          100:"#E9F3F4",
          200:"#60BC4E",         
        },
        sc_blue:{
          100:"#F2F7FB",
          200:"#3EAFEF",
        },
        sc_grey:{
          100:"#7F8490",
        },
      },
      fontFamily:{
       poppins: ['Poppins', 'sans-serif'],
      },
    },
  },
  plugins: [],
}

