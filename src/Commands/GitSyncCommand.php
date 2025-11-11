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
                            {--branch= : Specify branch to push to}
                            {--commit-only : Only commit, do not push}
                            {--push-only : Only push existing commits}
                            {--pull : Pull changes from remote before pushing}
                            {--dry-run : Show what would be done without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stage, commit, and push changes to Git with a single command';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $verbose = $this->option('verbose');
        $pushOnly = $this->option('push-only');
        $commitOnly = $this->option('commit-only');

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

        // Push-only mode
        if ($pushOnly) {
            return $this->pushChanges($dryRun, $verbose);
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

        // Commit changes
        if (!$this->commitChanges($dryRun, $verbose)) {
            return self::FAILURE;
        }

        // Push changes (unless commit-only mode)
        if (!$commitOnly) {
            return $this->pushChanges($dryRun, $verbose);
        }

        $this->newLine();
        $this->info('Done! Changes committed successfully.');
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
            $this->error('Failed to stage changes.');
            if ($verbose) {
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
            $this->error('Failed to commit changes.');
            if ($verbose) {
                $this->line($result->errorOutput());
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
            return null;
        }

        return trim($result->output());
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
}
