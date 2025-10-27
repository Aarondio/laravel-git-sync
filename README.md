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

**Note:** If you get "command not found: git-sync", see the [PATH setup instructions](#troubleshooting) below.

**Per-Project Installation (Recommended for Teams):**
```bash
# Install in your Laravel project
composer require aaronidikko/laravel-git-sync

# Use it immediately!
php artisan git:sync                     # Default chore message
php artisan git:sync -m "New feature"    # Custom message
```

**Optional:** Run `php artisan git:sync:install` to enable the shorter `composer sync` command. [Details below](#optional-add-composer-script-shortcut).

## Features

- **Dual Installation Modes**: Install globally or per-project
- **Single command** to stage, commit, and push changes
- **Custom or auto-generated** timestamped commit messages
- **Multiple workflows**: commit-only, push-only, pull-before-push, dry-run modes
- **Comprehensive error handling** for common Git scenarios
- **Configurable** commit message prefixes and timestamp formats
- **Automatic branch detection** and upstream setup
- **Pull integration**: Optionally pull remote changes before pushing
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

**Optional: Enable `composer sync` (One-Time Setup)**

The `php artisan git:sync` command is **automatically available** after installation. The install command below is **optional** and only adds a convenient `composer sync` shortcut.

Run this command once to add a `composer sync` shortcut to your project:

```bash
php artisan git:sync:install
```

**What it does:**
- Adds `"sync": "@php artisan git:sync"` to your project's `composer.json`
- Creates a shortcut: `composer sync` → calls → `php artisan git:sync`
- Does **not** affect the `php artisan git:sync` command (always works)

After running install, you can use:
```bash
composer sync                   # Shortcut (calls php artisan git:sync)
composer sync -- -m "message"   # With custom message
composer sync -- --pull         # With options
```

**Available Commands:**
```bash
composer sync                   # Only if you ran git:sync:install
php artisan git:sync            # Always available (no setup needed)
vendor/bin/git-sync             # Always available (no setup needed)
```

### Option 2: Global Installation (Recommended for Personal Use)

Install once, use everywhere:

```bash
composer global require aaronidikko/laravel-git-sync
```

**Important: Add Composer's bin directory to your PATH**

After installation, you need to add Composer's global bin directory to your PATH to use the `git-sync` command:

**Step 1: Find your Composer bin directory**
```bash
composer global config bin-dir --absolute
```
This is usually `~/.composer/vendor/bin` or `~/.config/composer/vendor/bin`

**Step 2: Add to PATH based on your shell**

For **Bash** (add to `~/.bashrc` or `~/.bash_profile`):
```bash
echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

For **Zsh** (add to `~/.zshrc`):
```bash
echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

For **Fish** (add to `~/.config/fish/config.fish`):
```bash
fish_add_path ~/.composer/vendor/bin
```

**Step 3: Verify installation**
```bash
which git-sync
# Should output: /Users/yourusername/.composer/vendor/bin/git-sync

git-sync --help
# Should show the help message
```

**Benefits:**
- Install once, use in all Laravel projects
- Works even in non-Laravel git repositories
- Quick access via `git-sync` command

**Usage:**
```bash
git-sync                        # From any directory
git-sync -m "New feature"       # Works globally
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
| **Command** | `git-sync` | `php artisan git:sync` or `vendor/bin/git-sync` |
| **Install Once** | ✅ Yes, use everywhere | ❌ No, per project |
| **Team Sharing** | ❌ Manual install for each | ✅ Auto via composer.json |
| **Config File** | ❌ Not available | ✅ Customizable |
| **Works in Non-Laravel** | ✅ Yes, basic git sync | ❌ No |
| **Command Length** | ✅ Shortest (`git-sync`) | ⚠️ Longer |
| **Best For** | Personal projects, quick use | Team projects, consistency |

## Usage

The package provides different commands based on installation type:

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
git-sync --pull
git-sync --dry-run
git-sync --branch=develop
```

### 2. Artisan Command (Per-Project Installation)

Standard Laravel command:

```bash
# Default chore message
php artisan git:sync

# With custom message
php artisan git:sync -m "Add new feature"
php artisan git:sync --message="Fix bug"

# Other options
php artisan git:sync --verbose
php artisan git:sync --commit-only
php artisan git:sync --pull
php artisan git:sync --dry-run
```

### 3. Direct Binary (Per-Project Installation)

Alternative for per-project installations:

```bash
# Default chore message
vendor/bin/git-sync

# With custom message
vendor/bin/git-sync -m "Add new feature"

# Other options
vendor/bin/git-sync --verbose
vendor/bin/git-sync --pull
```

### Optional: Add Composer Script Shortcut

**Note:** The `php artisan git:sync` command works immediately after installation. This section is **optional** and only adds a shorter `composer sync` alias.

To enable `composer sync` in your project, run this one-time setup command:

```bash
php artisan git:sync:install
```

This automatically adds `"sync": "@php artisan git:sync"` to your project's `composer.json`, creating a shortcut.

Then use:
```bash
composer sync                      # Calls php artisan git:sync
composer sync -- -m "Custom message"
composer sync -- --pull
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
| Per-Project | `php artisan git:sync` | Standard Laravel convention |
| Per-Project (Alt) | `vendor/bin/git-sync` | Direct binary access |
| Per-Project (Optional) | `composer sync` | Requires running `git:sync:install` first |

### Understanding the Commands

**Q: What's the difference between `php artisan git:sync` and `composer sync`?**

A: They do the exact same thing! `composer sync` is just a shortcut that internally calls `php artisan git:sync`.

- `php artisan git:sync` - Available immediately after `composer require`
- `composer sync` - Only available after running `php artisan git:sync:install` (one-time setup)

**Q: Do I need to run `git:sync:install`?**

A: No, it's completely optional! Use it only if you prefer typing `composer sync` instead of `php artisan git:sync`.

**Q: What does `git:sync:install` do?**

A: It adds this line to your project's `composer.json`:
```json
"scripts": {
    "sync": "@php artisan git:sync"
}
```

This creates a Composer script alias, nothing more.

### Command Options Reference

All commands support the same options:

| Option | Shorthand | Description |
|--------|-----------|-------------|
| `--message="text"` | `-m "text"` | Custom commit message |
| `--commit-only` | - | Commit without pushing |
| `--push-only` | - | Push without committing |
| `--pull` | - | Pull changes from remote before pushing |
| `--dry-run` | - | Preview actions without executing |
| `--verbose` | - | Show detailed output |
| `--branch=name` | - | Push to specific branch |

**Examples with git-sync (Global):**
```bash
git-sync -m "Add new feature"
git-sync --commit-only
git-sync --pull
git-sync --dry-run
git-sync --verbose
git-sync --branch=develop
```

**Examples with artisan (Per-Project):**
```bash
php artisan git:sync -m "Add new feature"
php artisan git:sync --commit-only
php artisan git:sync --pull
php artisan git:sync --dry-run
php artisan git:sync --branch=develop
```

**Examples with vendor/bin (Per-Project):**
```bash
vendor/bin/git-sync -m "Add new feature"
vendor/bin/git-sync --pull
vendor/bin/git-sync --verbose
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
php artisan git:sync

# With message
php artisan git:sync -m "Implement user authentication"

# Or use direct binary
vendor/bin/git-sync -m "Implement user authentication"
```

### Working with Feature Branches

**Global:**
```bash
git-sync -m "Add feature X"
git-sync --branch=feature/new-ui -m "Update UI components"
```

**Per-Project:**
```bash
php artisan git:sync -m "Add feature X"
php artisan git:sync --branch=feature/new-ui -m "Update UI components"
```

### Using in Non-Laravel Projects

If you have the package installed globally, it works even in non-Laravel git repositories with basic git sync functionality:

```bash
cd any-git-project
git-sync -m "Quick update"
```

**Note:** Configuration options (custom prefixes, timestamp format, etc.) only work in Laravel projects with the config file published.

## Handling Remote Changes

When the remote repository has commits that you don't have locally, you need to pull those changes before pushing. The `--pull` option handles this automatically:

**Global:**
```bash
# Pull and then push in one command
git-sync --pull -m "Update feature"

# With verbose output to see what's happening
git-sync --pull --verbose
```

**Per-Project:**
```bash
php artisan git:sync --pull -m "Update feature"
vendor/bin/git-sync --pull -m "Update feature"
```

The pull respects your git configuration. If you have `pull.rebase=true` set, it will rebase your commits. Otherwise, it will merge.

**Note:** If there are merge conflicts during the pull, the command will stop and prompt you to resolve them manually.

## Error Handling

The package handles common Git scenarios:

- **Not a Git repository**: Prompts to initialize Git
- **No remote configured**: Shows how to add a remote
- **No upstream branch**: Automatically sets upstream
- **Remote has changes**: Suggests pulling first (or use `--pull` option)
- **Merge conflicts**: Stops and prompts to resolve manually
- **No changes to commit**: Informs you the working tree is clean

## Troubleshooting

### "command not found: git-sync" (Global Installation)

If you get this error after global installation, Composer's bin directory is not in your PATH.

**Solution:**

1. Find your Composer bin directory:
   ```bash
   composer global config bin-dir --absolute
   ```

2. Add it to your PATH (choose based on your shell):

   **For Bash:**
   ```bash
   echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.bashrc
   source ~/.bashrc
   ```

   **For Zsh:**
   ```bash
   echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.zshrc
   source ~/.zshrc
   ```

3. Verify it works:
   ```bash
   which git-sync
   git-sync --help
   ```

### "Command sync is not defined" (Per-Project Installation)

The `composer sync` command doesn't work automatically after installing the package. It requires a one-time setup.

**Solution:**

Run the install command to enable `composer sync`:
```bash
php artisan git:sync:install
```

This automatically adds the `sync` script to your `composer.json`.

**Alternative:** Use these commands without setup:
```bash
php artisan git:sync
# or
vendor/bin/git-sync
```

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

### Version 1.2.0
- Added `--pull` option to pull remote changes before pushing
- Prevents commit history issues when remote has new commits
- Works with both rebase and merge strategies
- Handles merge conflicts gracefully
- Available in all command modes (artisan, composer, global)
- Added `php artisan git:sync:install` command for one-time composer sync setup
- Automatically adds `composer sync` script to project's composer.json
- Added comprehensive PATH setup instructions for global installation
- Added troubleshooting section for common installation issues
- Clarified usage commands for per-project vs global installation

### Version 1.0.0
- Initial release
- Basic git sync functionality
- Custom and auto-generated commit messages
- Multiple workflow modes
- Comprehensive error handling
- Configuration options
- Test suite
