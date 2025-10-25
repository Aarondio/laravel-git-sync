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

];
