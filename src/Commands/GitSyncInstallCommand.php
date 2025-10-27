<?php

namespace Aaronidikko\GitSync\Commands;

use Illuminate\Console\Command;

class GitSyncInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:sync:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add composer sync script to your project\'s composer.json';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $composerPath = base_path('composer.json');

        if (!file_exists($composerPath)) {
            $this->error('composer.json not found in project root.');
            return self::FAILURE;
        }

        $composer = json_decode(file_get_contents($composerPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Failed to parse composer.json: ' . json_last_error_msg());
            return self::FAILURE;
        }

        // Check if sync script already exists
        if (isset($composer['scripts']['sync'])) {
            $existingScript = $composer['scripts']['sync'];

            if ($existingScript === 'php artisan git:sync' || $existingScript === '@php artisan git:sync') {
                $this->info('✓ The "composer sync" command is already configured!');
                return self::SUCCESS;
            }

            $this->warn('A "sync" script already exists in your composer.json:');
            $this->line('  ' . $existingScript);

            if (!$this->confirm('Do you want to overwrite it with "php artisan git:sync"?', false)) {
                $this->info('Installation cancelled. The existing script was not modified.');
                return self::SUCCESS;
            }
        }

        // Add the sync script
        if (!isset($composer['scripts'])) {
            $composer['scripts'] = [];
        }

        $composer['scripts']['sync'] = '@php artisan git:sync';

        // Write back to composer.json with pretty formatting
        $json = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->error('Failed to encode composer.json: ' . json_last_error_msg());
            return self::FAILURE;
        }

        if (file_put_contents($composerPath, $json . PHP_EOL) === false) {
            $this->error('Failed to write to composer.json. Check file permissions.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✓ Successfully added "composer sync" command to your project!');
        $this->newLine();
        $this->line('You can now use:');
        $this->line('  <fg=green>composer sync</>                    # Quick sync');
        $this->line('  <fg=green>composer sync -- -m "message"</>    # With custom message');
        $this->line('  <fg=green>composer sync -- --pull</>          # Pull before push');
        $this->newLine();

        return self::SUCCESS;
    }
}
