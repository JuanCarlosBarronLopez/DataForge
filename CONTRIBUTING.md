# Contributing to DataForge CRUD Manager

Thank you for your interest in contributing! Here's how you can help.

## 🚀 Quick Start

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Make your changes
4. Test thoroughly on your local LAMP/XAMPP environment
5. Commit: `git commit -m 'feat: add my feature'`
6. Push: `git push origin feature/my-feature`
7. Open a Pull Request

## 📋 Coding Standards

- **PHP**: Follow PSR-12 coding style
- **Functions**: Use descriptive names, add PHPDoc blocks
- **Security**: Always use prepared statements, never trust user input
- **HTML**: Use semantic elements, escape all output with `htmlspecialchars()`
- **CSS**: Use CSS custom properties (design tokens) defined in `:root`
- **JS**: Use `const`/`let` (no `var`), ES6+ syntax

## 🔒 Security Guidelines

- Never hardcode credentials — use `.env`
- All forms must include CSRF tokens via `csrfField()`
- Destructive operations (delete) must use POST method
- Validate and sanitize all user input
- Use `require_once` with `__DIR__` for includes

## 📝 Commit Convention

Follow [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation
- `style:` Formatting (no code change)
- `refactor:` Code restructuring
- `security:` Security improvements

## 🐛 Reporting Issues

Use GitHub Issues with:
- Clear title describing the problem
- Steps to reproduce
- Expected vs actual behavior
- PHP/MySQL version
