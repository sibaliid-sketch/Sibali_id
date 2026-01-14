# Contributing to Sibali.id

## Branch Strategy

### Main Branches
- `main` - Production-ready code
- `staging` - Pre-production testing
- `develop` - Development integration

### Feature Branches
- `feature/*` - New features
- `bugfix/*` - Bug fixes
- `hotfix/*` - Urgent production fixes
- `refactor/*` - Code refactoring
- `docs/*` - Documentation updates

## Commit Message Convention

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Code style (formatting)
- `refactor`: Code refactoring
- `perf`: Performance improvement
- `test`: Adding tests
- `chore`: Maintenance tasks
- `security`: Security fixes

### Examples
```
feat(lms): add quiz auto-grading system
fix(payment): resolve QRIS callback verification
docs(api): update authentication endpoints
security(firewall): implement Layer 5 bot detection
```

## Pull Request Checklist

Before submitting a PR, ensure:

- [ ] Code follows PSR-12 standards
- [ ] All tests pass (`composer test`)
- [ ] PHPStan level 8 passes
- [ ] ESLint passes for frontend code
- [ ] New features have tests (>80% coverage)
- [ ] Database migrations are backward-compatible
- [ ] CHANGELOG.md is updated
- [ ] Security considerations documented
- [ ] Performance impact assessed
- [ ] Documentation updated

## Code Review SLAs

- **Critical fixes**: 2 hours
- **Bug fixes**: 24 hours
- **Features**: 48 hours
- **Refactoring**: 72 hours

## Running Tests Locally

```bash
# PHP Unit Tests
composer test

# Frontend Tests
npm test

# Static Analysis
./vendor/bin/phpstan analyse

# Code Style
./vendor/bin/pint
```

## Code of Conduct

### Our Standards
- Professional and respectful communication
- Constructive feedback
- Focus on code quality and security
- Collaborative problem-solving

### Unacceptable Behavior
- Harassment or discrimination
- Sharing sensitive credentials
- Bypassing security measures
- Committing untested code to main branches

## Security

Report security vulnerabilities to: security@sibali.id

Do NOT create public issues for security vulnerabilities.

## Questions?

Contact the development team:
- Email: dev@sibali.id
- Slack: #sibali-dev
