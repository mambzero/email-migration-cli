<?php

namespace Tests\Feature\Console;

use App\Models\Email;
use App\Models\File;
use App\Services\EmailMigrationService;
use App\Services\S3UploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EmailsMigrateToS3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock S3 service before tests
        $this->mock(S3UploadService::class, function ($mock) {
            $mock->shouldReceive('initializeBucket')->andReturn(true);
            $mock->shouldReceive('uploadHtmlBody')->andReturn('s3://bucket/email/1.html');
            $mock->shouldReceive('uploadFile')->andReturn('s3://bucket/files/1/test.pdf');
        });
    }

    /**
     * Test command can be executed.
     *
     * @return void
     */
    public function test_command_executes_successfully()
    {
        // Create test data
        Email::factory()->count(10)->create();

        $this->artisan('emails:migrate-to-s3', ['--force' => true])
            ->assertExitCode(0);
    }

    /**
     * Test command respects batch size option.
     *
     * @return void
     */
    public function test_command_respects_batch_size_option()
    {
        Email::factory()->count(50)->create();

        $this->artisan('emails:migrate-to-s3', [
            '--force' => true,
            '--batch-size' => 25,
        ])->assertExitCode(0);
    }

    /**
     * Test command handles no emails gracefully.
     *
     * @return void
     */
    public function test_command_handles_no_emails()
    {
        $this->artisan('emails:migrate-to-s3', ['--force' => true])
            ->assertExitCode(0);
    }

    /**
     * Test command with verify option.
     *
     * @return void
     */
    public function test_command_with_verify_option()
    {
        Email::factory()->create(['body_s3_path' => 's3://bucket/email/1.html']);

        // Also mock the fileExists method for verification
        $this->mock(S3UploadService::class, function ($mock) {
            $mock->shouldReceive('initializeBucket')->andReturn(true);
            $mock->shouldReceive('fileExists')->andReturn(true);
        });

        $this->artisan('emails:migrate-to-s3', [
            '--force' => true,
            '--verify' => true,
        ])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
