# Contributing to Scriptlog

Thank you for your interest in contributing to Scriptlog! We welcome contributions from the community.

## Ways to Contribute

There are several ways you can help improve Scriptlog:

- **Reporting Issues**: Report bugs, request features, or suggest improvements
- **Documentation**: Improve existing docs or create new documentation
- **Code Contributions**: Submit bug fixes, new features, or improvements
- **Testing**: Test existing features and report issues

## Prerequisites

Before contributing, ensure you have:

- A [GitHub account](https://github.com/signup/free)
- Basic understanding of Git and GitHub workflows
- Familiarity with PHP 7.4+ and MySQL/MariaDB

## Getting Started

### Reporting Issues

1. **Search first**: Check if the issue already exists before creating a new one
2. **Be detailed**: Include as much information as possible:
   - PHP version
   - Database version (MySQL/MariaDB)
   - Steps to reproduce
   - Expected vs actual behavior
   - Error messages/screenshots
3. **Security issues**: For security vulnerabilities, **do not** open a public issue. Contact us at `scriptlog@yandex.com` instead

### Submitting Code Changes

1. Fork the repository
2. Create a feature branch:
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. Make your changes following our [Developer Guide](docs/DEVELOPER_GUIDE.md)
4. Write tests for new functionality (if applicable)
5. Commit your changes:
   ```bash
   git commit -m 'Add amazing feature'
   ```
6. Push to the branch:
   ```bash
   git push origin feature/amazing-feature
   ```
7. Open a Pull Request

## Development Guidelines

### Code Standards

- Follow PSR coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Keep functions focused and single-purpose

### Architecture

Scriptlog follows a layered architecture:

```
Controller → Service → DAO → Database
```

When contributing new features:

1. **Database**: Add table definitions to `install/include/dbtable.php`
2. **DAO**: Create class in `lib/dao/`
3. **Service**: Create class in `lib/service/`
4. **Controller**: Create class in `lib/controller/`
5. **Routes**: Add route patterns in `lib/core/Bootstrap.php`

### Security

- Always use prepared statements for database queries
- Sanitize user input using existing utility functions
- Include CSRF protection for forms
- Never commit secrets or credentials

### Testing

Run tests before submitting:

```bash
vendor/bin/phpunit
```

## Documentation

Contributions to documentation are welcome:

- Improve existing guides in `docs/`
- Fix typos and unclear explanations
- Add examples where helpful

## License

By contributing to Scriptlog, you agree that your contributions will be licensed under the [MIT License](LICENSE.md).

## Contact

- For security issues: `scriptlog@yandex.com`
- For general questions: Use GitHub Discussions
- Issue tracker: https://github.com/cakmoel/Scriptlog/issues
