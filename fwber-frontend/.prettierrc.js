/**
 * Prettier configuration
 *
 * This configuration ensures consistent code formatting across the project.
 */

module.exports = {
  // Line width
  printWidth: 100,

  // Indentation
  tabWidth: 2,
  useTabs: false,

  // Semicolons
  semi: false,

  // Quotes
  singleQuote: true,

  // Trailing commas
  trailingComma: 'es5',

  // Bracket spacing
  bracketSpacing: true,

  // Arrow function parentheses
  arrowParens: 'always',

  // Line endings
  endOfLine: 'lf',

  // JSX
  jsxSingleQuote: false,
  jsxBracketSameLine: false,

  // Tailwind CSS plugin
  plugins: ['prettier-plugin-tailwindcss'],

  // Override for specific file types
  overrides: [
    {
      files: '*.json',
      options: {
        printWidth: 80,
      },
    },
    {
      files: '*.md',
      options: {
        proseWrap: 'always',
      },
    },
  ],
};
