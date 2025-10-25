<?php

namespace Aaronidikko\GitSync\Tests;

use Aaronidikko\GitSync\Commands\GitSyncCommand;
use Illuminate\Support\Facades\Process;
use Orchestra\Testbench\TestCase;

class GitSyncCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\Aaronidikko\GitSync\GitSyncServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default configuration
        $app['config']->set('git-sync.default_commit_prefix', 'chore');
        $app['config']->set('git-sync.timestamp_format', 'Y-m-d H:i');
    }

    /** @test */
    public function it_fails_when_not_in_git_repository()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(
                output: '',
                errorOutput: 'fatal: not a git repository',
                exitCode: 128
            ),
        ]);

        $this->artisan('git:sync')
            ->expectsOutput('Not a git repository. Please initialize git first.')
            ->assertFailed();
    }

    /** @test */
    public function it_shows_dry_run_output()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(output: '.git'),
            'git add .' => Process::result(),
            'git diff --cached --quiet' => Process::result(exitCode: 1), // Has changes
            'git branch --show-current' => Process::result(output: 'main'),
            'git remote' => Process::result(output: 'origin'),
        ]);

        $this->artisan('git:sync --dry-run')
            ->expectsOutput('[DRY RUN] Would execute: git add .')
            ->assertSuccessful();
    }

    /** @test */
    public function it_uses_custom_commit_message()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(output: '.git'),
            'git add .' => Process::result(),
            'git diff --cached --quiet' => Process::result(exitCode: 1),
            '*' => Process::result(),
        ]);

        $this->artisan('git:sync --message="Custom message" --dry-run')
            ->expectsOutputToContain('[DRY RUN] Would execute: git commit -m "Custom message"')
            ->assertSuccessful();
    }

    /** @test */
    public function it_handles_commit_only_mode()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(output: '.git'),
            'git add .' => Process::result(),
            'git diff --cached --quiet' => Process::result(exitCode: 1),
            'git commit*' => Process::result(output: 'Committed successfully'),
        ]);

        $this->artisan('git:sync --commit-only')
            ->expectsOutput(' Done! Changes committed successfully.')
            ->assertSuccessful();

        Process::assertRan('git commit*');
        Process::assertNotRan('git push*');
    }

    /** @test */
    public function it_handles_push_only_mode()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(output: '.git'),
            'git branch --show-current' => Process::result(output: 'main'),
            'git remote' => Process::result(output: 'origin'),
            'git push*' => Process::result(),
        ]);

        $this->artisan('git:sync --push-only')
            ->assertSuccessful();

        Process::assertRan('git push*');
        Process::assertNotRan('git add*');
        Process::assertNotRan('git commit*');
    }

    /** @test */
    public function it_rejects_conflicting_options()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(output: '.git'),
        ]);

        $this->artisan('git:sync --push-only --commit-only')
            ->expectsOutput('Cannot use --push-only and --commit-only together.')
            ->assertFailed();
    }

    /** @test */
    public function it_handles_no_changes_scenario()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(output: '.git'),
            'git add .' => Process::result(),
            'git diff --cached --quiet' => Process::result(exitCode: 0), // No changes
        ]);

        $this->artisan('git:sync')
            ->expectsOutput(' No changes to commit. Working tree is clean.')
            ->assertSuccessful();
    }

    /** @test */
    public function it_handles_missing_remote()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(output: '.git'),
            'git add .' => Process::result(),
            'git diff --cached --quiet' => Process::result(exitCode: 1),
            'git commit*' => Process::result(),
            'git branch --show-current' => Process::result(output: 'main'),
            'git remote' => Process::result(output: ''),
        ]);

        $this->artisan('git:sync')
            ->expectsOutput('No remote repository configured.')
            ->assertFailed();
    }

    /** @test */
    public function it_handles_upstream_branch_setup()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(output: '.git'),
            'git add .' => Process::result(),
            'git diff --cached --quiet' => Process::result(exitCode: 1),
            'git commit*' => Process::result(),
            'git branch --show-current' => Process::result(output: 'feature-branch'),
            'git remote' => Process::result(output: 'origin'),
            'git push origin feature-branch' => Process::result(
                errorOutput: 'has no upstream branch',
                exitCode: 128
            ),
            'git push -u origin feature-branch' => Process::result(output: 'Branch set up'),
        ]);

        $this->artisan('git:sync')
            ->expectsOutput('Setting upstream branch...')
            ->expectsOutput(' Changes pushed successfully')
            ->assertSuccessful();
    }

    /** @test */
    public function it_shows_verbose_output()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(output: '.git'),
            'git add .' => Process::result(),
            'git status --short' => Process::result(output: 'M file.txt'),
            'git diff --cached --quiet' => Process::result(exitCode: 1),
            'git commit*' => Process::result(output: '[main abc123] Commit message'),
            'git branch --show-current' => Process::result(output: 'main'),
            'git remote' => Process::result(output: 'origin'),
            'git push*' => Process::result(output: 'Pushed successfully'),
        ]);

        $this->artisan('git:sync --verbose')
            ->expectsOutputToContain('M file.txt')
            ->assertSuccessful();
    }

    /** @test */
    public function it_uses_custom_branch()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(output: '.git'),
            'git remote' => Process::result(output: 'origin'),
            'git push*' => Process::result(),
        ]);

        $this->artisan('git:sync --push-only --branch=develop --dry-run')
            ->expectsOutputToContain('git push origin develop')
            ->assertSuccessful();
    }

    /** @test */
    public function it_generates_default_commit_message()
    {
        Process::fake([
            'git rev-parse --git-dir' => Process::result(output: '.git'),
            'git add .' => Process::result(),
            'git diff --cached --quiet' => Process::result(exitCode: 1),
            '*' => Process::result(),
        ]);

        $this->artisan('git:sync --commit-only --verbose')
            ->expectsOutputToContain('Message: chore:')
            ->assertSuccessful();
    }
}
