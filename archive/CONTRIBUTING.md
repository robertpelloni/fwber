# Contributing to fwber

Thank you for your interest in contributing to fwber! This document provides guidelines and instructions for contributing to the project.

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive experience for everyone. We pledge to:

- Use welcoming and inclusive language
- Be respectful of differing viewpoints and experiences
- Gracefully accept constructive criticism
- Focus on what is best for the community
- Show empathy towards other community members

### Our Standards

Examples of behavior that contributes to a positive environment:

- Demonstrating empathy and kindness toward others
- Being respectful of differing opinions, viewpoints, and experiences
- Giving and gracefully accepting constructive feedback
- Accepting responsibility and apologizing to those affected by our mistakes
- Focusing on what is best for the overall community

Examples of unacceptable behavior:

- The use of sexualized language or imagery, and sexual attention or advances of any kind
- Trolling, insulting or derogatory comments, and personal or political attacks
- Public or private harassment
- Publishing others' private information without explicit permission
- Other conduct which could reasonably be considered inappropriate in a professional setting

## Getting Started

### Prerequisites

Before you begin, ensure you have the following installed:

**Backend:**
- PHP 8.4+ with required extensions (pdo_sqlite, mbstring, openssl, fileinfo, tokenizer)
- Composer 2.x
- SQLite 3.x (for local development)
- Laravel 11.x knowledge recommended

**Frontend:**
- Node.js 18+ and npm/yarn
- Next.js 14.x experience helpful
- TypeScript knowledge recommended

### Setting Up Development Environment

1. **Fork and Clone**
   ```bash
   git clone https://github.com/YOUR_USERNAME/fwber.git
   cd fwber
   ```

2. **Backend Setup**
   ```bash
   cd fwber-backend
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   php artisan db:seed
   ```

3. **Frontend Setup**
   ```bash
   cd ../fwber-frontend
   npm install
   cp .env.example .env.local
   # Update NEXT_PUBLIC_API_URL in .env.local
   ```

4. **Run Development Servers**
   ```bash
   # Backend (in fwber-backend/)
   php artisan serve
   
   # Frontend (in fwber-frontend/)
   npm run dev
   ```

## Development Workflow

### Branch Naming

Use descriptive branch names following this pattern:
- `feature/description` - New features
- `fix/description` - Bug fixes
- `docs/description` - Documentation updates
- `refactor/description` - Code refactoring
- `test/description` - Test additions/updates

Example: `feature/websocket-real-time-updates`

### Commit Messages

Write clear, descriptive commit messages:

```
type(scope): Brief description

Detailed explanation of what changed and why.

Fixes #123
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

Examples:
- `feat(proximity): Add shadow throttling for spam prevention`
- `fix(auth): Resolve token expiration handling`
- `docs(api): Update proximity endpoints documentation`
- `test(artifacts): Add coverage for TTL expiry logic`

### Making Changes

1. **Create a branch** from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** following our coding standards (see below)

3. **Write/update tests** - All new features must include tests
   ```bash
   # Backend tests
   cd fwber-backend
   php artisan test
   
   # Frontend tests (when available)
   cd fwber-frontend
   npm test
   ```

4. **Ensure code quality**:
   ```bash
   # Backend
   ./vendor/bin/phpstan analyse
   ./vendor/bin/pint # Code formatting
   
   # Frontend
   npm run lint
   npm run type-check
   ```

5. **Commit your changes** with clear messages

6. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

7. **Open a Pull Request** against the `main` branch

## Pull Request Guidelines

### Before Submitting

- [ ] Code follows project style guidelines
- [ ] All tests pass (`php artisan test` for backend)
- [ ] New features include test coverage
- [ ] Documentation is updated (if applicable)
- [ ] Commit messages are clear and descriptive
- [ ] Branch is up to date with `main`
- [ ] No merge conflicts

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Related Issues
Fixes #(issue number)

## Testing
Describe testing performed:
- [ ] Unit tests added/updated
- [ ] Integration tests added/updated
- [ ] Manual testing performed

## Screenshots (if applicable)
Add screenshots for UI changes

## Checklist
- [ ] My code follows the project's style guidelines
- [ ] I have performed a self-review of my code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
```

### Review Process

1. Maintainers will review your PR within 3-5 business days
2. Address any requested changes
3. Once approved, a maintainer will merge your PR
4. Your contribution will be included in the next release!

## Coding Standards

### Backend (PHP/Laravel)

- Follow PSR-12 coding standards
- Use Laravel conventions and best practices
- Type hint all method parameters and return types
- Write descriptive variable/method names
- Add PHPDoc blocks for complex methods
- Keep methods focused (Single Responsibility Principle)
- Use form requests for validation
- Leverage Eloquent relationships and scopes
- Write feature tests for all endpoints

**Example:**
```php
/**
 * Calculate compatibility score between two users.
 *
 * @param User $user1
 * @param User $user2
 * @return float Score between 0.0 and 1.0
 */
public function calculateCompatibility(User $user1, User $user2): float
{
    // Implementation
}
```

### Frontend (TypeScript/React)

- Use TypeScript for all code
- Follow React best practices and hooks patterns
- Use functional components
- Leverage React Query for data fetching
- Keep components focused and composable
- Use Tailwind CSS for styling
- Ensure accessibility (ARIA labels, keyboard navigation)
- Write descriptive prop types

**Example:**
```typescript
interface ArtifactCardProps {
  artifact: ProximityArtifact;
  onFlag: (id: string) => void;
  onDelete?: (id: string) => void;
}

export function ArtifactCard({ artifact, onFlag, onDelete }: ArtifactCardProps) {
  // Implementation
}
```

### Database Migrations

- Use descriptive migration names
- Always include `down()` method
- Test migrations both up and down
- Avoid data loss in production migrations
- Add indexes for frequently queried columns

### Testing

- Aim for 80%+ code coverage
- Write unit tests for business logic
- Write feature tests for API endpoints
- Test edge cases and error conditions
- Use meaningful test names

**Test Naming Convention:**
```php
public function test_proximity_artifacts_expire_after_ttl(): void
{
    // Arrange
    // Act
    // Assert
}
```

## Project Structure

```
fwber/
├── fwber-backend/          # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── ...
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   └── api.php
│   └── tests/
│       ├── Feature/
│       └── Unit/
│
├── fwber-frontend/         # Next.js App
│   ├── app/                # App router
│   ├── components/         # React components
│   ├── lib/
│   │   ├── api/           # API clients
│   │   └── hooks/         # Custom hooks
│   ├── types/             # TypeScript types
│   └── public/
│
└── docs/                   # Documentation
    ├── ROADMAP.md
    ├── PHASE_1_COMPLETION.md
    └── ...
```

## Feature Development Guidelines

### Avatar Enforcement (AVATAR_MODE)

When working with avatar-only features:
- Always check `config('features.avatar_mode')` in controllers
- Return 403 for non-avatar profile attempts
- Write tests for both avatar and non-avatar scenarios
- Update relevant documentation

### Proximity Features

When working with proximity/geolocation:
- Validate latitude/longitude ranges
- Use appropriate radius limits (min: 500m, max: 100km)
- Consider privacy through location fuzzing
- Test with edge cases (poles, dateline, etc.)
- Include distance calculations in responses

### Content Moderation

When handling user-generated content:
- Always sanitize input (strip_tags, htmlspecialchars)
- Enforce content length limits
- Implement flagging mechanisms
- Log moderation actions
- Consider privacy implications

## Reporting Issues

### Bug Reports

Include:
- Clear, descriptive title
- Steps to reproduce
- Expected vs. actual behavior
- Screenshots/logs if applicable
- Environment details (PHP version, OS, browser)

### Feature Requests

Include:
- Clear description of the feature
- Use case/problem it solves
- Proposed implementation (if any)
- Willingness to contribute

## Questions?

- Open a GitHub Discussion
- Join our community chat (if available)
- Email: [Contact info if available]

## Recognition

Contributors will be recognized in:
- CHANGELOG.md for each release
- GitHub contributors list
- Special mentions for significant contributions

Thank you for contributing to fwber! 🎉
