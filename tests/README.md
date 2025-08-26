# Testing Guide

This directory contains tests for ReplyPilot AI.

## Running Tests

### With PHPUnit (if installed)

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/ExampleTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

### Without PHPUnit

```bash
# Run bootstrap directly
php tests/bootstrap.php
```

## Test Structure

```
tests/
├── README.md           # This file
├── bootstrap.php       # Test bootstrap and autoloader
├── ExampleTest.php     # Example test case
└── phpunit.xml.dist    # PHPUnit configuration (optional)
```

## Writing Tests

### Basic Test Example

```php
<?php
class UserTest extends PHPUnit\Framework\TestCase
{
    public function testUserCreation()
    {
        $user = new User('John', 'john@example.com');
        $this->assertEquals('John', $user->getName());
        $this->assertEquals('john@example.com', $user->getEmail());
    }
}
```

### Test Categories

- **Unit Tests**: Test individual components in isolation
- **Integration Tests**: Test component interactions
- **Feature Tests**: Test complete features end-to-end

## Best Practices

1. **Naming**: Use descriptive test method names
2. **Isolation**: Each test should be independent
3. **Mocking**: Mock external dependencies
4. **Coverage**: Aim for >80% code coverage
5. **Speed**: Keep tests fast (<1 second each)

## Test Database

For database tests, use a separate test database:

```env
# .env.testing
DB_HOST=localhost
DB_NAME=replypilot_test
DB_USER=test_user
DB_PASS=test_pass
```

## Continuous Integration

Tests run automatically on:
- Pull requests
- Commits to main branch
- Tagged releases

## Troubleshooting

**Class not found errors**
- Check autoloader in bootstrap.php
- Verify namespace declarations

**Database connection errors**
- Check test database exists
- Verify .env.testing configuration

**Memory errors**
- Increase PHP memory limit
- Check for memory leaks in tests

## Coverage Reports

Generate coverage reports:

```bash
./vendor/bin/phpunit --coverage-html coverage/
```

View reports at `coverage/index.html`

## Contributing

When adding new features:
1. Write tests first (TDD approach)
2. Ensure all tests pass
3. Maintain or improve coverage
4. Document complex test scenarios

---

For more information, see [CONTRIBUTING.md](../CONTRIBUTING.md)
