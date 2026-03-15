<?php

namespace App\Console\Commands;

use App\Services\EmailMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EmailsMigrateToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:migrate-to-s3 {--batch-size=100 : Number of emails to process per batch} {--verify : Verify migration after completion} {--force : Skip confirmation prompt}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Migrate email data and attachments from the database to Amazon S3';

    /**
     * Email migration service.
     *
     * @var EmailMigrationService
     */
    protected $migrationService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(EmailMigrationService $migrationService)
    {
        parent::__construct();
        $this->migrationService = $migrationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Display warning and get confirmation unless --force is used
        if (!$this->option('force')) {
            $this->warn('This command will migrate email data to S3. This is a long-running process.');
            
            if (!$this->confirm('Do you want to proceed?')) {
                $this->info('Migration cancelled.');
                return 0;
            }
        }

        $this->info('Starting email migration to S3...');
        $this->newLine();

        // Set batch size
        $batchSize = (int) $this->option('batch-size');
        $this->migrationService->setBatchSize($batchSize);

        // Create progress bar
        $bar = $this->output->createProgressBar();
        $bar->start();

        // Define progress callback
        $progressCallback = function ($emailId, $processed, $total) use ($bar) {
            $bar->setProgress($processed);
        };

        // Start migration with timing
        $startTime = microtime(true);

        try {
            $stats = $this->migrationService->migrate($progressCallback);
            
            $bar->finish();
            $this->newLine(2);

            // Display statistics
            $this->displayStats($stats, $startTime);

            // Verify migration if requested
            if ($this->option('verify')) {
                $this->verifyMigration();
            }

            return 0;

        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine();
            $this->error('Migration failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Display migration statistics.
     *
     * @param array $stats
     * @param float $startTime
     * @return void
     */
    protected function displayStats(array $stats, float $startTime): void
    {
        $duration = microtime(true) - $startTime;
        $durationStr = gmdate('H:i:s', (int)$duration);

        $this->info('Migration Statistics:');
        $this->line('');
        
        $this->info('  Total Emails:              ' . $stats['total_emails']);
        $this->info('  Successfully Migrated:    ' . $stats['successful_migrations'] . ' ✓');
        $this->error('  Failed:                   ' . $stats['failed_migrations']);
        $this->info('  Skipped (Already Done):   ' . $stats['skipped']);
        $this->info('  Duration:                 ' . $durationStr);

        if (!empty($stats['errors'])) {
            $this->line('');
            $this->warn('Errors:');
            foreach (array_slice($stats['errors'], 0, 10) as $error) {
                $this->line('  • ' . $error);
            }
            if (count($stats['errors']) > 10) {
                $this->line('  ... and ' . (count($stats['errors']) - 10) . ' more errors');
            }
        }

        if ($stats['successful_migrations'] === $stats['total_emails']) {
            $this->info('');
            $this->line('✨ Migration completed successfully!');
        }
    }

    /**
     * Verify the migration integrity.
     *
     * @return void
     */
    protected function verifyMigration(): void
    {
        $this->line('');
        $this->info('Verifying migration integrity...');

        $results = $this->migrationService->verify();

        $this->line('');
        $this->info('Verification Results:');
        $this->line('  Total Checked:    ' . $results['total_emails_checked']);
        $this->info('  Valid:            ' . $results['valid'] . ' ✓');
        $this->error('  Invalid:          ' . $results['invalid']);

        if (!empty($results['issues'])) {
            $this->line('');
            $this->warn('Issues Found:');
            foreach (array_slice($results['issues'], 0, 10) as $issue) {
                $this->line('  • ' . $issue);
            }
            if (count($results['issues']) > 10) {
                $this->line('  ... and ' . (count($results['issues']) - 10) . ' more issues');
            }
        }
    }
}
