#!/usr/bin/env php
<?php

/**
 * Post-install script for Laravel Git Sync
 * Prompts users to star the repository on GitHub
 */

// Only run in interactive terminal
if (!function_exists('posix_isatty') || !posix_isatty(STDOUT)) {
    exit(0);
}

// Check if running in CI environment
$ci_environments = ['CI', 'CONTINUOUS_INTEGRATION', 'GITHUB_ACTIONS', 'GITLAB_CI', 'CIRCLECI'];
foreach ($ci_environments as $env) {
    if (getenv($env)) {
        exit(0);
    }
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                                                              ║\n";
echo "║  Thanks for installing Laravel Git Sync! 🚀                  ║\n";
echo "║                                                              ║\n";
echo "║  If you find this package helpful, please consider          ║\n";
echo "║  giving it a star on GitHub!                                ║\n";
echo "║                                                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Would you like to star this package on GitHub? (yes/no): ";

$handle = fopen("php://stdin", "r");
$response = strtolower(trim(fgets($handle)));
fclose($handle);

if (in_array($response, ['yes', 'y'])) {
    $repoUrl = 'https://github.com/Aarondio/laravel-git-sync';

    echo "\n✨ Thank you for your support!\n";
    echo "Opening GitHub repository in your browser...\n\n";

    // Detect OS and open browser
    $os = PHP_OS_FAMILY;

    switch ($os) {
        case 'Darwin':  // macOS
            exec("open '$repoUrl'");
            break;
        case 'Windows':
            exec("start $repoUrl");
            break;
        case 'Linux':
            // Try common Linux browsers
            $browsers = ['xdg-open', 'gnome-open', 'kde-open'];
            foreach ($browsers as $browser) {
                if (shell_exec("which $browser")) {
                    exec("$browser '$repoUrl' &");
                    break;
                }
            }
            break;
    }

    echo "Repository URL: $repoUrl\n";
} elseif (in_array($response, ['no', 'n'])) {
    echo "\nNo problem! You can always star it later at:\n";
    echo "https://github.com/Aarondio/laravel-git-sync\n\n";
} else {
    echo "\nYou can star the repository anytime at:\n";
    echo "https://github.com/Aarondio/laravel-git-sync\n\n";
}

echo "Happy coding! 🎉\n\n";
exit(0);
