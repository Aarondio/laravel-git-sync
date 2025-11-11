<?php

/**
 * Laravel Git Sync - Example Configuration
 *
 * This is an example configuration showing all available options.
 * Copy this to config/git-sync.php and customize as needed.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Commit Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix used in auto-generated commit messages when no custom
    | message is provided. Common values: 'chore', 'feat', 'fix', 'wip'
    |
    */

    'default_commit_prefix' => env('GIT_SYNC_COMMIT_PREFIX', 'chore'),

    /*
    |--------------------------------------------------------------------------
    | Conventional Commits
    |--------------------------------------------------------------------------
    |
    | Enable conventional commits format validation. When enabled, you can
    | use the --type flag to specify commit types like feat, fix, docs, etc.
    |
    | Usage: php artisan git:sync --type=feat -m "Add user dashboard"
    |
    */

    'conventional_commits' => [
        'enabled' => env('GIT_SYNC_CONVENTIONAL_COMMITS', true),

        'types' => [
            'feat' => 'A new feature',
            'fix' => 'A bug fix',
            'docs' => 'Documentation only changes',
            'style' => 'Changes that do not affect the meaning of the code',
            'refactor' => 'A code change that neither fixes a bug nor adds a feature',
            'perf' => 'A code change that improves performance',
            'test' => 'Adding missing tests or correcting existing tests',
            'build' => 'Changes that affect the build system or external dependencies',
            'ci' => 'Changes to CI configuration files and scripts',
            'chore' => 'Other changes that don\'t modify src or test files',
            'revert' => 'Reverts a previous commit',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timestamp Format
    |--------------------------------------------------------------------------
    |
    | The format for timestamps in auto-generated commit messages.
    | Uses PHP date format: https://www.php.net/manual/en/datetime.format.php
    |
    */

    'timestamp_format' => env('GIT_SYNC_TIMESTAMP_FORMAT', 'Y-m-d H:i'),

    /*
    |--------------------------------------------------------------------------
    | Default Remote
    |--------------------------------------------------------------------------
    |
    | The default remote repository to push to. Typically 'origin', but can
    | be customized if your workflow uses different remotes.
    |
    */

    'default_remote' => env('GIT_SYNC_REMOTE', 'origin'),

    /*
    |--------------------------------------------------------------------------
    | Safety Checks
    |--------------------------------------------------------------------------
    |
    | Enable safety checks before committing and pushing. These checks help
    | prevent common mistakes and maintain repository health.
    |
    */

    'safety_checks' => [
        // Check for large files before committing (in MB)
        // Files larger than this will trigger a warning
        'max_file_size' => 10,

        // Warn if committing to these branches
        // Users will be asked for confirmation before proceeding
        'protected_branches' => ['main', 'master', 'production'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hooks
    |--------------------------------------------------------------------------
    |
    | Run custom commands at different stages of the sync process.
    | Each command will be executed in the repository root directory.
    |
    | Example use cases:
    | - Run code formatters before staging
    | - Run tests before committing
    | - Send notifications after successful push
    | - Deploy to staging after push
    |
    */

    'hooks' => [
        // Commands to run before staging changes
        'pre_stage' => [
            // 'composer format',
            // './vendor/bin/pint',
        ],

        // Commands to run before committing (after staging)
        // These hooks can stop the commit if they fail
        'pre_commit' => [
            // './vendor/bin/phpstan analyze',
            // './vendor/bin/pint --test',
            // 'npm run lint',
            // 'composer test',
        ],

        // Commands to run after successful commit
        'post_commit' => [
            // 'echo "✓ Changes committed successfully!"',
        ],

        // Commands to run after successful push
        'post_push' => [
            // 'echo "✓ Changes pushed to remote!"',
            // './deploy.sh staging',
            // 'curl -X POST https://example.com/webhook',
        ],
    ],

];
