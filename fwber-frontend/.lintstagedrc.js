/**
 * Lint-staged configuration
 *
 * This configuration runs checks on staged files before commit.
 * Only files that are staged (git add) will be checked, making commits faster.
 */

module.exports = {
  // TypeScript and JavaScript files
  '**/*.{ts,tsx,js,jsx}': [
    // Run ESLint with auto-fix
    'eslint --fix --max-warnings=0',
    // Run Prettier to format
    'prettier --write',
    // Type-check files (no emit, just check)
    () => 'tsc --noEmit',
  ],

  // JSON, Markdown, CSS files
  '**/*.{json,md,css,scss}': [
    // Format with Prettier
    'prettier --write',
  ],

  // Prevent committing large files or sensitive data
  '*': [
    // Check file size (warn if larger than 500KB)
    (files) => {
      const largeFiles = files.filter((file) => {
        try {
          const fs = require('fs');
          const stats = fs.statSync(file);
          return stats.size > 500 * 1024; // 500KB
        } catch {
          return false;
        }
      });

      if (largeFiles.length > 0) {
        throw new Error(
          `❌ Large files detected (>500KB):\n${largeFiles.join('\n')}\n\n` +
          'Consider:\n' +
          '  1. Optimizing images/assets\n' +
          '  2. Using Git LFS for large files\n' +
          '  3. Excluding from Git if not needed\n'
        );
      }

      return [];
    },

    // Check for common secrets patterns
    (files) => {
      const fs = require('fs');
      const secretPatterns = [
        /API[_-]?KEY\s*=\s*['"][^'"]+['"]/i,
        /SECRET[_-]?KEY\s*=\s*['"][^'"]+['"]/i,
        /PASSWORD\s*=\s*['"][^'"]+['"]/i,
        /PRIVATE[_-]?KEY\s*=\s*['"][\s\S]+['"]/i,
        /aws_access_key_id/i,
        /aws_secret_access_key/i,
      ];

      const filesWithSecrets = [];

      files.forEach((file) => {
        try {
          const content = fs.readFileSync(file, 'utf8');
          const hasSecret = secretPatterns.some((pattern) => pattern.test(content));
          if (hasSecret) {
            filesWithSecrets.push(file);
          }
        } catch {
          // Ignore read errors
        }
      });

      if (filesWithSecrets.length > 0) {
        throw new Error(
          `❌ Possible secrets detected in files:\n${filesWithSecrets.join('\n')}\n\n` +
          'Please ensure:\n' +
          '  1. No actual secrets are committed\n' +
          '  2. Use .env.example for templates\n' +
          '  3. Add sensitive files to .gitignore\n'
        );
      }

      return [];
    },
  ],
};
