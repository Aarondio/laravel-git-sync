<?php

namespace Aaronidikko\GitSync\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class GitSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:sync
                            {--message= : Custom commit message}
                            {--m= : Shorthand for custom commit message}
                            {--type= : Conventional commit type (feat, fix, docs, etc.)}
                            {--branch= : Specify branch to push to}
                            {--commit-only : Only commit, do not push}
                            {--push-only : Only push existing commits}
                            {--pull : Pull changes from remote before pushing}
                            {--dry-run : Show what would be done without executing}
                            {--i|interactive : Review changes before committing}
                            {--status : Show git status before and after operations}
                            {--stats : Show sync statistics after completion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stage, commit, and push changes to Git with a single command';

    /**
     * Statistics tracking
     */
    protected $startTime;
    protected $stats = [
        'files_changed' => 0,
        'insertions' => 0,
        'deletions' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->startTime = microtime(true);

        $dryRun = $this->option('dry-run');
        $verbose = $this->option('verbose');
        $pushOnly = $this->option('push-only');
        $commitOnly = $this->option('commit-only');

        // Validate configuration
        if (!$this->validateConfiguration()) {
            return self::FAILURE;
        }

        // Check if we're in a git repository
        if (!$this->isGitRepository()) {
            $this->error('Not a git repository. Please initialize git first.');
            return self::FAILURE;
        }

        // Validate options
        if ($pushOnly && $commitOnly) {
            $this->error('Cannot use --push-only and --commit-only together.');
            return self::FAILURE;
        }

        // Check for protected branch
        if (!$this->checkProtectedBranch()) {
            return self::FAILURE;
        }

        $this->info('Laravel Git Sync');
        $this->line('https://github.com/Aarondio/laravel-git-sync');
        $this->newLine();

        // Show initial status if requested
        if ($this->option('status')) {
            $this->showGitStatus('Before sync');
        }

        // Push-only mode
        if ($pushOnly) {
            $exitCode = $this->pushChanges($dryRun, $verbose);
            if ($exitCode === self::SUCCESS) {
                if ($this->option('status')) {
                    $this->showGitStatus('After sync');
                }
                if ($this->option('stats')) {
                    $this->showStats();
                }
            }
            return $exitCode;
        }

        // Run pre-stage hooks
        if (!$dryRun && !$this->runHooks('pre_stage')) {
            return self::FAILURE;
        }

        // Stage changes
        if (!$this->stageChanges($dryRun, $verbose)) {
            return self::FAILURE;
        }

        // Check if there are changes to commit
        if (!$this->hasChangesToCommit()) {
            $this->info('No changes to commit. Working tree is clean.');
            return self::SUCCESS;
        }

        // Interactive mode: show diff and ask for confirmation
        if ($this->option('interactive') && !$dryRun) {
            if (!$this->reviewChanges()) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        // Run pre-commit hooks
        if (!$dryRun && !$this->runHooks('pre_commit')) {
            return self::FAILURE;
        }

        // Commit changes
        if (!$this->commitChanges($dryRun, $verbose)) {
            return self::FAILURE;
        }

        // Run post-commit hooks
        if (!$dryRun) {
            $this->runHooks('post_commit');
        }

        // Push changes (unless commit-only mode)
        if (!$commitOnly) {
            $exitCode = $this->pushChanges($dryRun, $verbose);
            if ($exitCode === self::SUCCESS) {
                if ($this->option('status')) {
                    $this->showGitStatus('After sync');
                }
                if ($this->option('stats')) {
                    $this->showStats();
                }
            }
            return $exitCode;
        }

        $this->newLine();
        $this->info('Done! Changes committed successfully.');

        // Show final status and stats for commit-only mode
        if ($this->option('status')) {
            $this->showGitStatus('After commit');
        }
        if ($this->option('stats')) {
            $this->showStats();
        }

        return self::SUCCESS;
    }

    /**
     * Check if current directory is a git repository.
     */
    protected function isGitRepository(): bool
    {
        $result = Process::run('git rev-parse --git-dir');
        return $result->successful();
    }

    /**
     * Stage all changes.
     */
    protected function stageChanges(bool $dryRun, bool $verbose): bool
    {
        $this->line('[*] Staging changes...');

        // Check for large files before staging
        if (!$this->checkFileSizes()) {
            return false;
        }

        if ($dryRun) {
            $this->info('[DRY RUN] Would execute: git add .');
            return true;
        }

        $result = Process::run('git add .');

        if (!$result->successful()) {
            $this->error('Failed to stage changes. This usually happens when:');
            $this->line('  • Files are locked by another process');
            $this->line('  • Insufficient file permissions');
            $this->line('  • File path contains invalid characters');
            $this->newLine();
            $this->line('Try running with --verbose for more details:');
            $this->line('  php artisan git:sync --verbose');

            if ($verbose) {
                $this->newLine();
                $this->line('Detailed error:');
                $this->line($result->errorOutput());
            }
            return false;
        }

        if ($verbose) {
            $result = Process::run('git status --short');
            $this->line($result->output());
        }

        $this->info('Changes staged');
        return true;
    }

    /**
     * Check if there are changes to commit.
     */
    protected function hasChangesToCommit(): bool
    {
        $result = Process::run('git diff --cached --quiet');
        return !$result->successful(); // Returns non-zero if there are changes
    }

    /**
     * Commit changes with message.
     */
    protected function commitChanges(bool $dryRun, bool $verbose): bool
    {
        $message = $this->getCommitMessage();

        $this->line('[*] Committing changes...');

        if ($verbose) {
            $this->line("Message: {$message}");
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Would execute: git commit -m \"{$message}\"");
            return true;
        }

        $result = Process::run(['git', 'commit', '-m', $message]);

        if (!$result->successful()) {
            $errorOutput = $result->errorOutput();

            // Check for specific error types
            if (str_contains($errorOutput, 'nothing to commit')) {
                $this->info('Nothing to commit. Working tree is clean.');
                return true;
            } elseif (str_contains($errorOutput, 'pre-commit hook')) {
                $this->error('Pre-commit hook failed.');
                $this->line('Your repository has a pre-commit hook that rejected the commit.');
                $this->line('This usually happens when:');
                $this->line('  • Code linting/formatting checks fail');
                $this->line('  • Tests fail');
                $this->line('  • Security checks detect issues');
                if ($verbose) {
                    $this->newLine();
                    $this->line($errorOutput);
                }
            } else {
                $this->error('Failed to commit changes. This usually happens when:');
                $this->line('  • Git configuration is incomplete (name/email not set)');
                $this->line('  • Commit message contains invalid characters');
                $this->line('  • Repository is in a locked state');
                $this->newLine();
                $this->line('Check your Git configuration:');
                $this->line('  git config user.name');
                $this->line('  git config user.email');
                if ($verbose) {
                    $this->newLine();
                    $this->line('Detailed error:');
                    $this->line($errorOutput);
                }
            }
            return false;
        }

        $this->info('Changes committed');

        if ($verbose) {
            $this->line($result->output());
        }

        return true;
    }

    /**
     * Push changes to remote.
     */
    protected function pushChanges(bool $dryRun, bool $verbose): int
    {
        // Get current branch
        $branch = $this->option('branch') ?? $this->getCurrentBranch();

        if (!$branch) {
            $this->error('Could not determine current branch.');
            return self::FAILURE;
        }

        // Validate branch name
        if (!$this->validateBranchName($branch)) {
            $this->error("Invalid branch name: {$branch}");
            $this->line('Branch names can only contain letters, numbers, hyphens, underscores, and forward slashes.');
            return self::FAILURE;
        }

        // Check if remote exists
        if (!$this->hasRemote()) {
            $this->error('No remote repository configured.');
            $this->line('Hint: Add a remote with: git remote add origin <url>');
            return self::FAILURE;
        }

        // Get remote name from config
        $remote = config('git-sync.default_remote', 'origin');

        // Pull changes if --pull option is used
        if ($this->option('pull')) {
            if (!$this->pullChanges($branch, $dryRun, $verbose)) {
                return self::FAILURE;
            }
            $this->newLine();
        }

        $this->line('Pushing to remote...');

        if ($verbose) {
            $this->line("Branch: {$branch}");
            $this->line("Remote: {$remote}");
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Would execute: git push {$remote} {$branch}");
            $this->newLine();
            $this->info('Dry run completed successfully.');
            return self::SUCCESS;
        }

        $result = Process::run(['git', 'push', $remote, $branch]);

        if (!$result->successful()) {
            $errorOutput = $result->errorOutput();

            // Check for common errors
            if (str_contains($errorOutput, 'failed to push some refs')) {
                $this->error('Failed to push. Remote has changes you do not have locally.');
                $this->line("Hint: Pull changes first with: git pull {$remote} {$branch}");
            } elseif (str_contains($errorOutput, 'has no upstream branch')) {
                $this->line('Setting upstream branch...');
                $result = Process::run(['git', 'push', '-u', $remote, $branch]);

                if ($result->successful()) {
                    $this->info('Changes pushed successfully');
                    $this->newLine();
                    $this->info('Done!');
                    return self::SUCCESS;
                }
            } else {
                $this->error('Failed to push changes.');
            }

            if ($verbose) {
                $this->line($errorOutput);
            }

            return self::FAILURE;
        }

        $this->info('Changes pushed successfully');

        if ($verbose) {
            $this->line($result->output());
            $this->line($result->errorOutput());
        }

        // Run post-push hooks
        if (!$dryRun) {
            $this->runHooks('post_push');
        }

        $this->newLine();
        $this->info('Done!');

        return self::SUCCESS;
    }

    /**
     * Get the commit message from options or generate default.
     */
    protected function getCommitMessage(): string
    {
        $message = $this->option('message') ?? $this->option('m');
        $type = $this->option('type');

        // If type is specified, validate it for conventional commits
        if ($type) {
            if (!$this->validateConventionalCommitType($type)) {
                return '';
            }

            // If message provided with type, combine them
            if ($message) {
                $message = "{$type}: {$message}";
            } else {
                // Type only, add timestamp
                $timestamp = now()->format(config('git-sync.timestamp_format', 'Y-m-d H:i'));
                $message = "{$type}: {$timestamp}";
            }
        }

        if ($message) {
            // Validate custom message
            $this->validateCommitMessage($message);
            return $message;
        }

        // Generate default message
        $prefix = config('git-sync.default_commit_prefix', 'chore');
        $timestamp = now()->format(config('git-sync.timestamp_format', 'Y-m-d H:i'));

        return "{$prefix}: {$timestamp}";
    }

    /**
     * Get current git branch.
     */
    protected function getCurrentBranch(): ?string
    {
        $result = Process::run('git branch --show-current');

        if (!$result->successful()) {
            $this->warn('Unable to determine current branch.');
            $this->line('This may indicate a repository issue.');
            return null;
        }

        $branch = trim($result->output());

        // Check for detached HEAD state
        if (empty($branch)) {
            // Try to get the commit hash for detached HEAD
            $headResult = Process::run('git rev-parse --short HEAD');
            if ($headResult->successful()) {
                $commit = trim($headResult->output());
                $this->error("You are in a detached HEAD state (at commit {$commit}).");
                $this->line('A detached HEAD means you\'re not on any branch.');
                $this->newLine();
                $this->line('To fix this, you need to:');
                $this->line('  1. Create a new branch: git checkout -b <new-branch-name>');
                $this->line('  2. Or checkout an existing branch: git checkout <branch-name>');
                $this->newLine();
                $this->line('Example:');
                $this->line('  git checkout -b feature/my-work');
            } else {
                $this->error('Unable to determine repository state.');
            }
            return null;
        }

        return $branch;
    }

    /**
     * Check if git remote exists.
     */
    protected function hasRemote(): bool
    {
        $result = Process::run('git remote');
        return $result->successful() && !empty(trim($result->output()));
    }

    /**
     * Pull changes from remote.
     */
    protected function pullChanges(string $branch, bool $dryRun, bool $verbose): bool
    {
        // Get remote name from config
        $remote = config('git-sync.default_remote', 'origin');

        $this->line('Pulling changes from remote...');

        if ($verbose) {
            $this->line("Branch: {$branch}");
            $this->line("Remote: {$remote}");
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Would execute: git pull {$remote} {$branch}");
            return true;
        }

        $result = Process::run(['git', 'pull', $remote, $branch]);

        if (!$result->successful()) {
            $errorOutput = $result->errorOutput();

            // Check for merge conflicts
            if (str_contains($errorOutput, 'CONFLICT')) {
                $this->error('Pull failed due to merge conflicts.');
                $this->line('Please resolve conflicts manually and try again.');
                return false;
            }

            $this->error('Failed to pull changes from remote.');

            if ($verbose) {
                $this->line($errorOutput);
            }

            return false;
        }

        $this->info('Successfully pulled changes');

        if ($verbose) {
            $this->line($result->output());
            if ($result->errorOutput()) {
                $this->line($result->errorOutput());
            }
        }

        return true;
    }

    /**
     * Check if current branch is protected and warn user.
     */
    protected function checkProtectedBranch(): bool
    {
        $currentBranch = $this->getCurrentBranch();

        if (!$currentBranch) {
            return true; // Can't check, proceed
        }

        $protectedBranches = config('git-sync.safety_checks.protected_branches', []);

        if (in_array($currentBranch, $protectedBranches)) {
            $this->warn("You are about to sync to a protected branch: {$currentBranch}");
            $this->line('Protected branches typically require pull requests for changes.');

            if (!$this->confirm('Are you sure you want to continue?', false)) {
                $this->info('Operation cancelled.');
                return false;
            }
        }

        return true;
    }

    /**
     * Check for large files that might be problematic.
     */
    protected function checkFileSizes(): bool
    {
        $maxFileSize = config('git-sync.safety_checks.max_file_size', 10); // in MB

        // Get list of untracked and modified files
        $result = Process::run('git status --porcelain');

        if (!$result->successful() || empty(trim($result->output()))) {
            return true; // Can't check or no files, proceed
        }

        $files = explode("\n", trim($result->output()));
        $largeFiles = [];

        foreach ($files as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Extract filename from git status output (format: "?? file" or "M  file")
            $filename = trim(substr($line, 3));

            // Skip if file doesn't exist (might be deleted, or in test environment)
            if (!file_exists($filename) || !is_file($filename)) {
                continue;
            }

            $fileSize = @filesize($filename);
            if ($fileSize === false) {
                continue; // Can't get size, skip
            }

            $sizeInMB = $fileSize / 1024 / 1024;

            if ($sizeInMB > $maxFileSize) {
                $largeFiles[] = [
                    'name' => $filename,
                    'size' => round($sizeInMB, 2),
                ];
            }
        }

        if (!empty($largeFiles)) {
            $this->warn('Large files detected:');
            foreach ($largeFiles as $file) {
                $this->line("  - {$file['name']} ({$file['size']} MB)");
            }
            $this->newLine();
            $this->line("Files larger than {$maxFileSize} MB can cause repository bloat.");
            $this->line('Consider using Git LFS for large files: https://git-lfs.github.com');

            if (!$this->confirm('Do you want to continue?', false)) {
                $this->info('Operation cancelled.');
                return false;
            }
        }

        return true;
    }

    /**
     * Validate branch name format.
     */
    protected function validateBranchName(string $branch): bool
    {
        // Git branch names can contain letters, numbers, hyphens, underscores, dots, and slashes
        // They cannot start with a dot, slash, or hyphen
        // They cannot contain consecutive dots, spaces, or special characters like @, {, }, etc.
        return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\/_.-]*$/', $branch) === 1
            && !str_contains($branch, '..')
            && !str_contains($branch, ' ')
            && !str_contains($branch, '~')
            && !str_contains($branch, '^')
            && !str_contains($branch, ':')
            && !str_contains($branch, '?')
            && !str_contains($branch, '*')
            && !str_contains($branch, '[');
    }

    /**
     * Validate commit message and provide warnings if needed.
     */
    protected function validateCommitMessage(string $message): void
    {
        $length = strlen($message);

        // Warn if message is too short (less than 3 characters)
        if ($length < 3) {
            $this->warn('Commit message is very short. Consider adding more context.');
        }

        // Warn if message is too long (more than 72 characters for first line)
        $firstLine = explode("\n", $message)[0];
        if (strlen($firstLine) > 72) {
            $this->warn('First line of commit message is longer than 72 characters.');
            $this->line('Consider keeping the first line concise and adding details in subsequent lines.');
        }

        // Check for empty or whitespace-only message
        if (trim($message) === '') {
            $this->warn('Commit message is empty or contains only whitespace.');
        }
    }

    /**
     * Review changes interactively before committing.
     */
    protected function reviewChanges(): bool
    {
        $this->newLine();
        $this->info('=== Review Changes ===');
        $this->newLine();

        // Show staged files summary
        $statusResult = Process::run('git diff --cached --stat');
        if ($statusResult->successful() && !empty(trim($statusResult->output()))) {
            $this->line('Files to be committed:');
            $this->line($statusResult->output());
            $this->newLine();
        }

        // Ask if user wants to see full diff
        if ($this->confirm('Would you like to see the full diff of changes?', true)) {
            $diffResult = Process::run('git diff --cached --color=always');
            if ($diffResult->successful() && !empty(trim($diffResult->output()))) {
                $this->newLine();
                $this->line($diffResult->output());
                $this->newLine();
            } else {
                $this->line('No diff to display.');
            }
        }

        // Final confirmation
        return $this->confirm('Do you want to proceed with committing these changes?', true);
    }

    /**
     * Run configured hooks for a specific stage.
     */
    protected function runHooks(string $stage): bool
    {
        $hooks = config("git-sync.hooks.{$stage}", []);

        if (empty($hooks)) {
            return true; // No hooks configured, continue
        }

        $verbose = $this->option('verbose');
        $stageName = str_replace('_', '-', $stage);

        $this->line("[*] Running {$stageName} hooks...");

        foreach ($hooks as $hook) {
            if ($verbose) {
                $this->line("  Executing: {$hook}");
            }

            $result = Process::run($hook);

            if (!$result->successful()) {
                $this->error("Hook failed: {$hook}");
                $this->line('Output:');
                $this->line($result->output());
                if ($result->errorOutput()) {
                    $this->line('Error:');
                    $this->line($result->errorOutput());
                }

                // Pre-stage and pre-commit hooks should stop execution
                if (in_array($stage, ['pre_stage', 'pre_commit'])) {
                    $this->newLine();
                    $this->error('Operation aborted due to hook failure.');
                    return false;
                }

                // Post hooks just warn but don't fail
                $this->warn('Hook failed but continuing...');
            } elseif ($verbose && $result->output()) {
                $this->line($result->output());
            }
        }

        if ($verbose) {
            $this->info("All {$stageName} hooks completed successfully.");
        }

        return true;
    }

    /**
     * Show git status with a label.
     */
    protected function showGitStatus(string $label): void
    {
        $this->newLine();
        $this->info("=== Git Status: {$label} ===");

        $result = Process::run('git status --short --branch');

        if ($result->successful()) {
            $output = trim($result->output());
            if (empty($output)) {
                $this->line('  Working tree is clean');
            } else {
                $this->line($output);
            }
        } else {
            $this->warn('Unable to get git status');
        }

        $this->newLine();
    }

    /**
     * Validate configuration settings.
     */
    protected function validateConfiguration(): bool
    {
        $errors = [];

        // Validate max_file_size
        $maxFileSize = config('git-sync.safety_checks.max_file_size');
        if (!is_numeric($maxFileSize) || $maxFileSize < 0) {
            $errors[] = 'safety_checks.max_file_size must be a positive number';
        }

        // Validate protected_branches is an array
        $protectedBranches = config('git-sync.safety_checks.protected_branches');
        if (!is_array($protectedBranches)) {
            $errors[] = 'safety_checks.protected_branches must be an array';
        }

        // Validate hooks are arrays
        foreach (['pre_stage', 'pre_commit', 'post_commit', 'post_push'] as $hook) {
            $hookValue = config("git-sync.hooks.{$hook}");
            if (!is_array($hookValue)) {
                $errors[] = "hooks.{$hook} must be an array";
            }
        }

        // Validate default_remote is a string
        $defaultRemote = config('git-sync.default_remote');
        if (!is_string($defaultRemote) || empty($defaultRemote)) {
            $errors[] = 'default_remote must be a non-empty string';
        }

        if (!empty($errors)) {
            $this->error('Configuration validation failed:');
            foreach ($errors as $error) {
                $this->line("  • {$error}");
            }
            $this->newLine();
            $this->line('Please check your config/git-sync.php file.');
            return false;
        }

        return true;
    }

    /**
     * Validate conventional commit type.
     */
    protected function validateConventionalCommitType(string $type): bool
    {
        $types = config('git-sync.conventional_commits.types', []);

        if (empty($types)) {
            $this->warn('Conventional commits are not configured.');
            return false;
        }

        if (!isset($types[$type])) {
            $this->error("Invalid conventional commit type: {$type}");
            $this->newLine();
            $this->line('Valid types are:');
            foreach ($types as $validType => $description) {
                $this->line("  <fg=cyan>{$validType}</> - {$description}");
            }
            return false;
        }

        return true;
    }

    /**
     * Collect and show statistics.
     */
    protected function showStats(): void
    {
        $duration = round((microtime(true) - $this->startTime) * 1000);

        $this->newLine();
        $this->info('=== Sync Statistics ===');

        // Get commit stats if available
        $result = Process::run('git diff --shortstat HEAD~1');
        if ($result->successful() && !empty(trim($result->output()))) {
            $output = trim($result->output());
            // Parse output like: "2 files changed, 45 insertions(+), 3 deletions(-)"
            if (preg_match('/(\d+) files? changed/', $output, $matches)) {
                $this->stats['files_changed'] = (int) $matches[1];
            }
            if (preg_match('/(\d+) insertions?/', $output, $matches)) {
                $this->stats['insertions'] = (int) $matches[1];
            }
            if (preg_match('/(\d+) deletions?/', $output, $matches)) {
                $this->stats['deletions'] = (int) $matches[1];
            }
        }

        $this->line("Duration: {$duration}ms");

        if ($this->stats['files_changed'] > 0) {
            $this->line("Files changed: {$this->stats['files_changed']}");
            $this->line("Insertions: <fg=green>+{$this->stats['insertions']}</>");
            $this->line("Deletions: <fg=red>-{$this->stats['deletions']}</>");
        }

        $this->newLine();
    }
}
