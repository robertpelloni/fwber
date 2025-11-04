# Contributing to FWBer

Thank you for your interest in contributing to FWBer! This document provides guidelines and instructions for contributing to the project.

## Table of Contents
- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [How to Contribute](#how-to-contribute)
- [Coding Standards](#coding-standards)
- [Git Workflow](#git-workflow)
- [Pull Request Process](#pull-request-process)
- [Testing Guidelines](#testing-guidelines)
- [Documentation](#documentation)
- [Community](#community)

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inspiring community for all. Please be respectful and constructive in all interactions.

### Expected Behavior

- Be respectful and inclusive
- Accept constructive criticism gracefully
- Focus on what is best for the community
- Show empathy towards other community members

### Unacceptable Behavior

- Harassment, discrimination, or offensive comments
- Trolling, insulting/derogatory comments
- Public or private harassment
- Publishing others' private information
- Other conduct that could reasonably be considered inappropriate

## Getting Started

### Prerequisites

Before contributing, ensure you have:
- Git installed
- Node.js 18+ (for frontend)
- PHP 8.2+ (for backend)
- MySQL 8.0+
- Redis
- Or Docker (see [DOCKER_SETUP.md](DOCKER_SETUP.md))

### Setting Up Your Development Environment

#### Option 1: Using Setup Scripts (Recommended)

```bash
# Clone the repository
git clone https://github.com/your-username/fwber.git
cd fwber

# Run setup script
# Linux/macOS:
./setup-dev.sh

# Windows:
powershell -ExecutionPolicy Bypass -File setup-dev.ps1
```

#### Option 2: Using Docker

```bash
# Clone and start
git clone https://github.com/your-username/fwber.git
cd fwber
docker-compose up
```

See [DOCKER_SETUP.md](DOCKER_SETUP.md) for detailed Docker instructions.

#### Option 3: Manual Setup

See individual README files in `fwber-frontend/` and `fwber-backend/` directories.

## How to Contribute

### Ways to Contribute

1. **Report Bugs**: Open an issue with reproduction steps
2. **Suggest Features**: Discuss ideas in GitHub Discussions
3. **Fix Bugs**: Look for issues labeled "good first issue"
4. **Add Features**: Discuss major changes in an issue first
5. **Improve Documentation**: Help make our docs better
6. **Review Pull Requests**: Help review others' code
7. **Write Tests**: Increase test coverage

### Before You Start

1. **Check existing issues**: Someone might already be working on it
2. **Discuss major changes**: Open an issue to discuss approach
3. **Start small**: Begin with "good first issue" labels
4. **Read the docs**: Familiarize yourself with the codebase

## Development Setup

### Branch Structure

- `main`: Production-ready code
- `develop`: Integration branch for features
- `feature/*`: New features
- `bugfix/*`: Bug fixes
- `hotfix/*`: Urgent production fixes

### Creating a Feature Branch

```bash
# Update main branch
git checkout main
git pull origin main

# Create feature branch
git checkout -b feature/your-feature-name

# Work on your changes
git add .
git commit -m "feat: your feature description"
git push origin feature/your-feature-name
```

## Coding Standards

### General Principles

- **DRY**: Don't Repeat Yourself
- **KISS**: Keep It Simple, Stupid
- **YAGNI**: You Aren't Gonna Need It
- **SOLID**: Follow SOLID principles
- **Write self-documenting code**: Clear names > comments
- **Test your code**: Write tests for new features

### Frontend (TypeScript/React)

#### Code Style

```typescript
// ✅ Good: Descriptive names, typed
interface UserProfile {
  id: number
  name: string
  email: string
}

const fetchUserProfile = async (userId: number): Promise<UserProfile> => {
  const response = await api.get<UserProfile>(`/users/${userId}`)
  return response
}

// ❌ Bad: Unclear names, untyped
const get = async (id: any) => {
  const res = await api.get(`/users/${id}`)
  return res
}
```

#### Component Structure

```tsx
// Component file structure:
// 1. Imports
import { useState, useEffect } from 'react'
import { api } from '@/lib/api/client'
import type { User } from '@/lib/api/types'

// 2. Types/Interfaces
interface UserCardProps {
  userId: number
  onUpdate?: (user: User) => void
}

// 3. Component
export function UserCard({ userId, onUpdate }: UserCardProps) {
  // Hooks first
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  // Effects
  useEffect(() => {
    fetchUser()
  }, [userId])

  // Handlers
  const fetchUser = async () => {
    // Implementation
  }

  // Render
  return (
    <div>
      {/* JSX */}
    </div>
  )
}
```

#### Hooks Guidelines

```typescript
// ✅ Good: Proper dependency array
useEffect(() => {
  fetchData()
}, [fetchData]) // Include all dependencies

// ❌ Bad: Missing dependencies
useEffect(() => {
  fetchData()
}, []) // fetchData dependency missing

// ✅ Good: Memoized callback
const handleClick = useCallback(() => {
  doSomething(userId)
}, [userId])

// ✅ Good: Ref for callbacks that don't need deps
const handlerRef = useRef(onUpdate)
useEffect(() => {
  handlerRef.current = onUpdate
}, [onUpdate])
```

#### Style Guidelines

- Use Tailwind CSS utility classes
- Keep components under 300 lines
- Extract reusable logic to custom hooks
- Use TypeScript strict mode
- Prefer functional components over class components
- Use proper TypeScript types (no `any`)

### Backend (PHP/Laravel)

#### Code Style

```php
// ✅ Good: Type hints, return types, descriptive names
public function updateUserProfile(User $user, array $data): UserProfile
{
    $profile = $user->profile()->firstOrCreate();
    $profile->update($data);

    return $profile;
}

// ❌ Bad: No types, unclear names
public function update($u, $d)
{
    $p = $u->profile()->firstOrCreate();
    $p->update($d);
    return $p;
}
```

#### Controller Guidelines

```php
// ✅ Good: Thin controllers, single responsibility
class UserController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $user = User::with('profile')->findOrFail($id);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }
}

// ❌ Bad: Fat controllers, business logic in controller
class UserController extends Controller
{
    public function show($id)
    {
        // 100+ lines of business logic here...
    }
}
```

#### Service Pattern

```php
// Create services for complex business logic
class UserService
{
    public function createUserWithProfile(array $userData): User
    {
        DB::beginTransaction();

        try {
            $user = User::create($userData);
            $user->profile()->create($userData['profile'] ?? []);
            $user->location()->create($userData['location'] ?? []);

            DB::commit();
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

#### Eloquent Best Practices

```php
// ✅ Good: Eager loading to prevent N+1
$users = User::with(['profile', 'photos'])->get();

// ❌ Bad: N+1 query problem
$users = User::all();
foreach ($users as $user) {
    echo $user->profile->name; // Triggers query for each user
}

// ✅ Good: Specific columns
User::select('id', 'name', 'email')->get();

// ❌ Bad: Select all columns unnecessarily
User::all();
```

## Git Workflow

### Commit Message Format

We use [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
type(scope?): subject

body?

footer?
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style (formatting, semicolons, etc.)
- `refactor`: Code change that neither fixes bug nor adds feature
- `perf`: Performance improvement
- `test`: Adding or updating tests
- `chore`: Maintenance tasks
- `build`: Build system or dependencies
- `ci`: CI/CD configuration
- `revert`: Revert previous commit

**Examples:**
```bash
feat: add user authentication
feat(auth): add JWT token refresh
fix(api): resolve timeout issue in user endpoint
docs: update setup instructions in README
refactor(components): simplify button logic
test: add unit tests for user service
```

### Git Hooks

We use Husky for Git hooks:

- **pre-commit**: Lints and formats staged files
- **commit-msg**: Validates commit message format
- **pre-push**: Runs type checking and tests

To skip hooks (emergency only):
```bash
git commit --no-verify -m "message"
```

## Pull Request Process

### Before Submitting

1. **Update your branch**:
   ```bash
   git checkout main
   git pull origin main
   git checkout your-feature-branch
   git rebase main
   ```

2. **Run all checks**:
   ```bash
   # Frontend
   cd fwber-frontend
   npm run lint
   npm run type-check
   npm run build
   npm run test

   # Backend
   cd fwber-backend
   composer phpstan
   php artisan test
   ```

3. **Update documentation** if needed

4. **Add tests** for new features

### Creating a Pull Request

1. **Push your branch**:
   ```bash
   git push origin feature/your-feature-name
   ```

2. **Open PR** on GitHub with template:
   ```markdown
   ## Description
   Brief description of changes

   ## Type of Change
   - [ ] Bug fix
   - [ ] New feature
   - [ ] Breaking change
   - [ ] Documentation update

   ## Testing
   - [ ] Tests added/updated
   - [ ] All tests pass
   - [ ] Manual testing performed

   ## Checklist
   - [ ] Code follows style guidelines
   - [ ] Self-reviewed code
   - [ ] Commented complex code
   - [ ] Updated documentation
   - [ ] No new warnings
   - [ ] Added tests
   - [ ] All tests pass
   ```

3. **Link related issues**: Use keywords like "Fixes #123" or "Closes #456"

### Pull Request Review

- Be responsive to feedback
- Make requested changes promptly
- Don't force push after review starts (adds commits instead)
- Thank reviewers for their time

### After Approval

- Squash commits if requested
- Merge when approved by maintainers
- Delete feature branch after merge

## Testing Guidelines

### Frontend Testing

```typescript
// Unit test example (Jest/React Testing Library)
import { render, screen, fireEvent } from '@testing-library/react'
import { UserCard } from './UserCard'

describe('UserCard', () => {
  it('renders user information', () => {
    render(<UserCard userId={1} />)
    expect(screen.getByText('John Doe')).toBeInTheDocument()
  })

  it('calls onUpdate when save button is clicked', () => {
    const onUpdate = jest.fn()
    render(<UserCard userId={1} onUpdate={onUpdate} />)

    fireEvent.click(screen.getByText('Save'))
    expect(onUpdate).toHaveBeenCalled()
  })
})
```

### Backend Testing

```php
// Feature test example (PHPUnit)
class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/users/{$user->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email'],
            ]);
    }

    public function test_user_cannot_view_other_profile(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/users/{$other->id}");

        $response->assertForbidden();
    }
}
```

### Test Coverage Goals

- Aim for >80% code coverage
- Focus on critical paths first
- Test edge cases and error handling
- Don't test framework code

## Documentation

### When to Update Documentation

- Adding new features
- Changing existing functionality
- Fixing bugs with behavior changes
- Adding or changing API endpoints
- Updating environment variables

### Documentation Locations

- `README.md`: Project overview and quick start
- `TESTING_GUIDE.md`: Testing procedures
- `DEPLOYMENT_CHECKLIST.md`: Deployment guide
- `DOCKER_SETUP.md`: Docker instructions
- `CONTRIBUTING.md`: This file
- Code comments: Complex logic only

### Writing Good Documentation

- Be clear and concise
- Include examples
- Keep it up to date
- Use proper formatting
- Add diagrams if helpful

## Community

### Getting Help

- 📖 Read the docs first
- 💬 GitHub Discussions for questions
- 🐛 GitHub Issues for bugs
- 💡 GitHub Discussions for feature ideas

### Communication Channels

- **GitHub Issues**: Bug reports and feature requests
- **GitHub Discussions**: Questions and discussions
- **Pull Requests**: Code review and collaboration
- **Email**: [support@fwber.me](mailto:support@fwber.me)

### Response Times

- Issues: Within 48 hours
- Pull Requests: Within 1 week
- Security issues: Within 24 hours

## Recognition

Contributors are recognized in:
- GitHub contributors page
- Release notes
- Project documentation

Thank you for contributing to FWBer! 🎉

---

**Questions?** Open a [GitHub Discussion](https://github.com/your-repo/discussions) or email [support@fwber.me](mailto:support@fwber.me).
