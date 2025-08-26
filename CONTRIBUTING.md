# Contributing to ReplyPilot AI

Thank you for your interest in contributing to ReplyPilot AI! We welcome contributions from the community.

## Code of Conduct

Please read and follow our [Code of Conduct](CODE_OF_CONDUCT.md) to ensure a welcoming environment for all contributors.

## How to Contribute

### Reporting Issues

1. Check existing issues to avoid duplicates
2. Use issue templates when available
3. Provide clear reproduction steps
4. Include system information (PHP version, OS, etc.)

### Pull Requests

1. **Fork & Clone**: Fork the repository and clone locally
2. **Branch**: Create a feature branch from `main`
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Code**: Make your changes following our coding standards
4. **Test**: Ensure all tests pass (see Testing section)
5. **Commit**: Use clear, descriptive commit messages
6. **Push**: Push to your fork
7. **PR**: Open a pull request with a clear description

### Coding Standards

- **PHP**: Follow PSR-12 coding standard
- **Formatting**: Use consistent indentation (4 spaces)
- **Documentation**: Comment complex logic
- **Security**: Never commit sensitive data or credentials

### Commit Messages

Use conventional commit format:
```
type(scope): description

[optional body]

[optional footer]
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

Example:
```
feat(admin): add bulk email export functionality

Added CSV export for admin email management with proper CSRF protection
and rate limiting.

Closes #123
```

## Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/fluent-themes/replypilot-ai.git
   cd replypilot-ai
   ```

2. **Install dependencies** (optional)
   ```bash
   composer install --no-dev
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   ```

4. **Run database migrations**
   ```bash
   php scripts/auto_migrate.php
   ```

## Testing

### Running Tests

Tests are located in the `tests/` directory.

```bash
# If PHPUnit is installed
./vendor/bin/phpunit

# Or run directly
php tests/bootstrap.php
```

### Writing Tests

- Place test files in `tests/` with `Test.php` suffix
- Extend base test class when available
- Mock external dependencies
- Test both success and failure cases

## Project Structure

```
replypilot-ai/
├── admin/          # Admin panel files
├── app/            # Core application code
│   ├── Core/       # Core functionality
│   ├── Repository/ # Data access layer
│   └── Support/    # Helper classes
├── docs/           # Documentation
├── public/         # Public-facing files
├── scripts/        # Utility scripts
├── storage/        # Logs and temporary files
└── tests/          # Test files
```

## Documentation

- Update relevant documentation when adding features
- Include PHPDoc comments for public methods
- Update README.md for significant changes

## Questions?

- Check existing issues and discussions
- Contact: support@fluentthemes.com
- Review documentation in `docs/` directory

## License

By contributing, you agree that your contributions will be licensed under the GPL License.

## .github Directory Templates

Issue and PR templates are available in `.github/` directory to help structure contributions.
