module.exports = {
    plugins: ['syntax-dynamic-import'],
    presets: [
        [
            '@babel/preset-env',
            {
                targets: {
                    node: 'current',
                },
            },
        ],
    ],
}
