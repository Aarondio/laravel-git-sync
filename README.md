# Laravel Git Sync

A Laravel package that enables developers to commit and push code changes to Git with a single command.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aaronidikko/laravel-git-sync.svg?style=flat-square)](https://packagist.org/packages/aaronidikko/laravel-git-sync)
[![Total Downloads](https://img.shields.io/packagist/dt/aaronidikko/laravel-git-sync.svg?style=flat-square)](https://packagist.org/packages/aaronidikko/laravel-git-sync)

## Quick Start

**Global Installation (Recommended for Personal Use):**
```bash
# Install once
composer global require aaronidikko/laravel-git-sync

# Use anywhere!
git-sync                        # Default chore message
git-sync -m "New feature"       # Custom message
```

**Per-Project Installation (Recommended for Teams):**
```bash
# Install in your Laravel project
composer require aaronidikko/laravel-git-sync

# Use it!
composer sync                        # Default chore message
php artisan git:sync -m "New feature"    # Custom message
```

## Features

- **Dual Installation Modes**: Install globally or per-project
- **Single command** to stage, commit, and push changes
- **Custom or auto-generated** timestamped commit messages
- **Multiple workflows**: commit-only, push-only, dry-run modes
- **Comprehensive error handling** for common Git scenarios
- **Configurable** commit message prefixes and timestamp formats
- **Automatic branch detection** and upstream setup
- **Verbose mode** for detailed output
- **Works in non-Laravel projects** (when installed globally)

## Installation

You can install this package in two ways:

### Option 1: Per-Project Installation (Recommended for Teams)

Install in a specific Laravel project:

```bash
cd your-laravel-project
composer require aaronidikko/laravel-git-sync
```

**Benefits:**
- Team members get the package automatically via `composer install`
- Configuration can be version-controlled
- Consistent across the team

**Usage:**
```bash
composer sync                    # Quick sync
php artisan git:sync            # Alternative
```

### Option 2: Global Installation (Recommended for Personal Use)

Install once, use everywhere:

```bash
composer global require aaronidikko/laravel-git-sync
```

Make sure `~/.composer/vendor/bin` (or `~/.config/composer/vendor/bin` on some systems) is in your PATH.

**Benefits:**
- Install once, use in all Laravel projects
- Works even in non-Laravel git repositories
- Quick access via `git-sync` command

**Usage:**
```bash
git-sync                        # From any Laravel project
git-sync -m "New feature"       # Works globally
composer sync                   # Also works if installed per-project
```

### Publishing Configuration (Per-Project Only)

If you installed per-project and want to customize settings:

```bash
php artisan vendor:publish --tag=git-sync-config
```

This creates `config/git-sync.php` where you can customize commit message format, prefixes, etc.

### Installation Comparison

| Feature | Global Installation | Per-Project Installation |
|---------|-------------------|-------------------------|
| **Command** | `git-sync` | `composer sync` or `php artisan git:sync` |
| **Install Once** | ✅ Yes, use everywhere | ❌ No, per project |
| **Team Sharing** | ❌ Manual install for each | ✅ Auto via composer.json |
| **Config File** | ❌ Not available | ✅ Customizable |
| **Works in Non-Laravel** | ✅ Yes, basic git sync | ❌ No |
| **Command Length** | ✅ Shortest (`git-sync`) | ⚠️ Longer |
| **Best For** | Personal projects, quick use | Team projects, consistency |

## Usage

The package provides three ways to sync your changes:

### 1. Global Command (If Installed Globally)

Simplest and fastest option:

```bash
# Default chore message with timestamp
git-sync

# With custom message
git-sync -m "Add new feature"
git-sync --message="Fix authentication bug"

# Other options
git-sync --verbose
git-sync --commit-only
git-sync --dry-run
git-sync --branch=develop
```

### 2. Composer Script (Per-Project or Global)

Works with both installation methods:

```bash
# Default chore message
composer sync

# With custom message (note the -- separator for per-project)
composer sync -- -m "New Feature"
composer sync -- --message="Fix authentication bug"

# Other options
composer sync -- --verbose
composer sync -- --commit-only
composer sync -- --dry-run
```

### 3. Artisan Command (Per-Project Only)

Traditional Laravel command:

```bash
# Default chore message
php artisan git:sync

# With custom message
php artisan git:sync -m "Add new feature"
php artisan git:sync --message="Fix bug"

# Other options
php artisan git:sync --verbose
php artisan git:sync --commit-only
php artisan git:sync --dry-run
```

### How It Works

All commands will:
1. Stage all changes (`git add .`)
2. Commit with message like `chore: 2025-10-25 09:30` (or your custom message)
3. Push to the current branch

### Which Command Should I Use?

| Installation Type | Recommended Command | Why? |
|------------------|---------------------|------|
| Global | `git-sync` | Shortest, fastest |
| Per-Project (Solo) | `composer sync` | Quick, no artisan prefix |
| Per-Project (Team) | `php artisan git:sync` | Clear, discoverable |

### Command Options Reference

All commands support the same options:

| Option | Shorthand | Description |
|--------|-----------|-------------|
| `--message="text"` | `-m "text"` | Custom commit message |
| `--commit-only` | - | Commit without pushing |
| `--push-only` | - | Push without committing |
| `--dry-run` | - | Preview actions without executing |
| `--verbose` | - | Show detailed output |
| `--branch=name` | - | Push to specific branch |

**Examples with git-sync (Global):**
```bash
git-sync -m "Add new feature"
git-sync --commit-only
git-sync --dry-run
git-sync --verbose
git-sync --branch=develop
```

**Examples with composer sync (Per-Project):**
```bash
composer sync -- -m "Add new feature"
composer sync -- --commit-only
composer sync -- --dry-run
composer sync -- --branch=develop
```

**Examples with artisan (Per-Project):**
```bash
php artisan git:sync -m "Add new feature"
php artisan git:sync --commit-only
php artisan git:sync --dry-run
php artisan git:sync --branch=develop
```


## Configuration

After publishing the config file, you can customize settings in `config/git-sync.php`:

```php
return [
    // Default prefix for auto-generated commit messages
    'default_commit_prefix' => env('GIT_SYNC_COMMIT_PREFIX', 'chore'),

    // Timestamp format for commit messages
    'timestamp_format' => env('GIT_SYNC_TIMESTAMP_FORMAT', 'Y-m-d H:i'),

    // Default remote repository
    'default_remote' => env('GIT_SYNC_REMOTE', 'origin'),

    // Safety checks
    'safety_checks' => [
        'max_file_size' => 10, // MB
        'protected_branches' => ['main', 'master', 'production'],
    ],
];
```

### Environment Variables

You can also configure via `.env`:

```env
GIT_SYNC_COMMIT_PREFIX=feat
GIT_SYNC_TIMESTAMP_FORMAT="Y-m-d H:i:s"
GIT_SYNC_REMOTE=origin
```

## Examples

### Daily Development Workflow

**With Global Installation:**
```bash
# Quick save with timestamp
git-sync

# Save with meaningful message
git-sync -m "Implement user authentication"

# Check what would happen first
git-sync --dry-run

# Commit locally without pushing
git-sync --commit-only
```

**With Per-Project Installation:**
```bash
# Quick save
composer sync

# With message
composer sync -- -m "Implement user authentication"

# Or use artisan
php artisan git:sync -m "Implement user authentication"
```

### Working with Feature Branches

**Global:**
```bash
git-sync -m "Add feature X"
git-sync --branch=feature/new-ui -m "Update UI components"
```

**Per-Project:**
```bash
composer sync -- -m "Add feature X"
php artisan git:sync --branch=feature/new-ui -m "Update UI components"
```

### Using in Non-Laravel Projects

If you have the package installed globally, it works even in non-Laravel git repositories with basic git sync functionality:

```bash
cd any-git-project
git-sync -m "Quick update"
```

**Note:** Configuration options (custom prefixes, timestamp format, etc.) only work in Laravel projects with the config file published.

## Error Handling

The package handles common Git scenarios:

- **Not a Git repository**: Prompts to initialize Git
- **No remote configured**: Shows how to add a remote
- **No upstream branch**: Automatically sets upstream
- **Remote has changes**: Suggests pulling first
- **No changes to commit**: Informs you the working tree is clean

## Requirements

- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x
- Git installed on your system

## Testing

Run the test suite:

```bash
composer test
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

Created by [Aaron Idikko](https://github.com/aaronidikko)

## Support

If you find this package helpful, please consider:
- Starring the repository on GitHub
- Sharing it with others
- Reporting issues or suggesting features

## Changelog

### Version 1.0.0
- Initial release
- Basic git sync functionality
- Custom and auto-generated commit messages
- Multiple workflow modes
- Comprehensive error handling
- Configuration options
- Test suite
