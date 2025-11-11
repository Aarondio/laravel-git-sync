<?php

namespace Aaronidikko\GitSync\Tests;

use Orchestra\Testbench\TestCase;

class GitSyncInstallCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\Aaronidikko\GitSync\GitSyncServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary composer.json for testing
        $this->composerPath = base_path('composer.json');
        $this->backupComposerJson();
    }

    protected function tearDown(): void
    {
        $this->restoreComposerJson();
        parent::tearDown();
    }

    private function backupComposerJson(): void
    {
        if (file_exists($this->composerPath)) {
            copy($this->composerPath, $this->composerPath . '.backup');
        }
    }

    private function restoreComposerJson(): void
    {
        if (file_exists($this->composerPath . '.backup')) {
            rename($this->composerPath . '.backup', $this->composerPath);
        } elseif (file_exists($this->composerPath)) {
            unlink($this->composerPath);
        }
    }

    private function createTestComposerJson(array $content = []): void
    {
        $defaultContent = [
            'name' => 'test/project',
            'description' => 'Test project',
            'require' => [],
        ];

        $composerContent = array_merge($defaultContent, $content);
        file_put_contents($this->composerPath, json_encode($composerContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /** @test */
    public function it_adds_sync_script_to_composer_json()
    {
        $this->createTestComposerJson();

        $this->artisan('git:sync:install')
            ->expectsOutputToContain('Successfully added "composer sync" command')
            ->assertSuccessful();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertArrayHasKey('scripts', $composer);
        $this->assertArrayHasKey('sync', $composer['scripts']);
        $this->assertEquals('@php artisan git:sync', $composer['scripts']['sync']);
    }

    /** @test */
    public function it_handles_existing_sync_script()
    {
        $this->createTestComposerJson([
            'scripts' => [
                'sync' => '@php artisan git:sync',
            ],
        ]);

        $this->artisan('git:sync:install')
            ->expectsOutputToContain('already configured')
            ->assertSuccessful();
    }

    /** @test */
    public function it_asks_confirmation_when_overwriting_existing_script()
    {
        $this->createTestComposerJson([
            'scripts' => [
                'sync' => 'some-other-command',
            ],
        ]);

        $this->artisan('git:sync:install')
            ->expectsQuestion('Do you want to overwrite it with "php artisan git:sync"?', false)
            ->expectsOutputToContain('Installation cancelled')
            ->assertSuccessful();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertEquals('some-other-command', $composer['scripts']['sync']);
    }

    /** @test */
    public function it_overwrites_existing_script_when_confirmed()
    {
        $this->createTestComposerJson([
            'scripts' => [
                'sync' => 'some-other-command',
            ],
        ]);

        $this->artisan('git:sync:install')
            ->expectsQuestion('Do you want to overwrite it with "php artisan git:sync"?', true)
            ->expectsOutputToContain('Successfully added "composer sync" command')
            ->assertSuccessful();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertEquals('@php artisan git:sync', $composer['scripts']['sync']);
    }

    /** @test */
    public function it_creates_scripts_section_if_missing()
    {
        $this->createTestComposerJson(); // No scripts section

        $this->artisan('git:sync:install')
            ->assertSuccessful();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertArrayHasKey('scripts', $composer);
        $this->assertArrayHasKey('sync', $composer['scripts']);
    }

    /** @test */
    public function it_fails_when_composer_json_not_found()
    {
        if (file_exists($this->composerPath)) {
            unlink($this->composerPath);
        }

        $this->artisan('git:sync:install')
            ->expectsOutputToContain('composer.json not found')
            ->assertFailed();
    }

    /** @test */
    public function it_preserves_existing_composer_json_formatting()
    {
        $this->createTestComposerJson([
            'name' => 'test/project',
            'description' => 'Test description',
            'require' => [
                'php' => '^8.2',
            ],
        ]);

        $this->artisan('git:sync:install')
            ->assertSuccessful();

        $composer = json_decode(file_get_contents($this->composerPath), true);

        // Check that existing content is preserved
        $this->assertEquals('test/project', $composer['name']);
        $this->assertEquals('Test description', $composer['description']);
        $this->assertArrayHasKey('php', $composer['require']);
    }
}
