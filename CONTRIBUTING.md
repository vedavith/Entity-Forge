# Contributing to EntityForge

Thanks for taking the time to contribute. EntityForge is a small project and every improvement — whether it's a bug report, a fix, or a suggestion — is genuinely appreciated.

## Getting Started

```bash
git clone https://github.com/vedavith/Entity-Forge.git
cd Entity-Forge
composer install
```

Run the test suite to make sure everything is working:

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

All tests must pass and PHPStan must report no errors (level 8) before submitting a pull request.

## Ways to Contribute

- **Bug reports** — open an issue with a clear description of what happened, what you expected, and how to reproduce it
- **Bug fixes** — pick an open issue, fix it, and open a pull request
- **Feature suggestions** — open an issue to discuss the idea before building it; this saves time on both sides
- **Documentation** — improvements to the README, code comments, or examples are always welcome
- **Tests** — additional test coverage for untested paths is a great first contribution

## Pull Request Guidelines

- Branch off `main` and keep your branch focused on one thing
- Follow the existing code style — PHP 8.3+, strict types, no magic
- Write or update tests for any code you change
- Keep commits clean and descriptive
- Do not include unrelated changes in the same PR

## Code Style

- PHP 8.3+ features encouraged (enums, readonly, named arguments, fibers where appropriate)
- No commented-out code
- No `var_dump`, `print_r`, or debug output left in
- Column names passed to repository methods must be validated — never interpolate raw user input into SQL

## Reporting Security Issues

Please do **not** open a public issue for security vulnerabilities. Email **veda_ravula@outlook.com** directly with the details. I'll respond as quickly as possible.

## Code of Conduct

This project follows the [Code of Conduct](CODE_OF_CONDUCT.md). Please read it before participating.
