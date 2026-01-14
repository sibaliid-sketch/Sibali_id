module.exports = {
    parser: '@typescript-eslint/parser',
    parserOptions: {
        ecmaVersion: 2022,
        sourceType: 'module',
        ecmaFeatures: {
            jsx: true,
        },
    },
    env: {
        browser: true,
        node: true,
        es6: true,
    },
    extends: [
        'eslint:recommended',
        '@vue/eslint-config-typescript/recommended',
        'plugin:vue/vue3-recommended',
        'plugin:@typescript-eslint/recommended',
        'plugin:prettier/recommended',
    ],
    plugins: ['vue', '@typescript-eslint', 'prettier'],
    rules: {
        'no-unused-vars': 'error',
        'consistent-return': 'error',
        'max-len': ['error', { code: 120 }],
        'prettier/prettier': 'error',
        'vue/multi-word-component-names': 'off',
        '@typescript-eslint/no-unused-vars': 'error',
        '@typescript-eslint/explicit-function-return-type': 'off',
    },
    settings: {
        'import/resolver': {
            alias: {
                map: [['@', './resources/js']],
                extensions: ['.js', '.jsx', '.ts', '.tsx', '.vue'],
            },
        },
    },
};
