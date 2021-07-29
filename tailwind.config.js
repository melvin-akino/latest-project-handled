module.exports = {
    theme: {
        extend: {
            width: {
                '160':'40rem',
                '120': '32rem',
                '100': '20rem',
            },
            colors: {
                primary: {
                    '100': '#ebf8ff',
                    '200': '#bee3f8',
                    '300': '#90cdf4',
                    '400': '#63b3ed',
                    '500': '#4299e1',
                    '600': '#3182ce',
                    '700': '#2b6cb0',
                    '800': '#2c5282',
                    '900': '#2a4365',
                },
            }
        },
        screens:{
            'xs': '320px',
            'sm': '640px',
            'md': '768px',
            'lg': '1024px',
            'xl': '1367px',
        },
        fontSize: {
            'xs': '.75rem',
            'sm': '.9rem',
            'base': '1rem',
            'lg': '1.125rem',
            'xl': '1.25rem',
            '2xl': '1.5rem',
            '3xl': '1.875rem',
            '4xl': '2.25rem',
            '5xl': '3rem',
            '6xl': '4rem',
            '7xl': '5rem'
        }
    },
    variants: {},
    plugins: [],
    purge: false
}
