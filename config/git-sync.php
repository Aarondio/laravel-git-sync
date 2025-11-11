<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Commit Prefix
    |--------------------------------------------------------------------------
    |
    | This value determines the prefix used in auto-generated commit messages.
    | Common values: 'chore', 'feat', 'fix', 'update', 'wip'
    |
    */

    'default_commit_prefix' => env('GIT_SYNC_COMMIT_PREFIX', 'chore'),

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
    | The default remote repository to push to.
    | Typically 'origin', but can be customized.
    |
    */

    'default_remote' => env('GIT_SYNC_REMOTE', 'origin'),

    /*
    |--------------------------------------------------------------------------
    | Safety Checks
    |--------------------------------------------------------------------------
    |
    | Enable safety checks before committing and pushing.
    |
    */

    'safety_checks' => [
        // Check for large files before committing (in MB)
        'max_file_size' => 10,

        // Warn if committing to these branches
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
    */

    'hooks' => [
        // Commands to run before staging changes
        'pre_stage' => [
            // Example: 'composer format',
        ],

        // Commands to run before committing (after staging)
        'pre_commit' => [
            // Example: './vendor/bin/phpstan analyze',
            // Example: './vendor/bin/pint --test',
        ],

        // Commands to run after successful commit
        'post_commit' => [
            // Example: 'echo "Commit successful!"',
        ],

        // Commands to run after successful push
        'post_push' => [
            // Example: 'echo "Deployed to remote!"',
        ],
    ],

];
