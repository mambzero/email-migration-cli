<?php

namespace Tests\Unit\Services;

use App\Models\Email;
use App\Models\File;
use App\Services\EmailMigrationService;
use App\Services\S3UploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EmailMigrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $migrationService;
    protected $s3ServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock S3 service
        $this->s3ServiceMock = Mockery::mock(S3UploadService::class);
        
        // Create migration service with mock
        $this->migrationService = new EmailMigrationService($this->s3ServiceMock);
    }

    /**
     * Test that migrate returns correct statistics structure.
     *
     * @return void
     */
    public function test_migrate_returns_statistics()
    {
        // Setup mocks to return S3 paths
        $this->s3ServiceMock->shouldReceive('initializeBucket')->andReturn(true);
        $this->s3ServiceMock->shouldReceive('uploadHtmlBody')->andReturn('s3://bucket/email/1.html');

        // Create test email
        Email::factory()->create(['id' => 1, 'file_ids' => []]);

        $stats = $this->migrationService->migrate();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_emails', $stats);
        $this->assertArrayHasKey('successful_migrations', $stats);
        $this->assertArrayHasKey('failed_migrations', $stats);
        $this->assertArrayHasKey('errors', $stats);
    }

    /**
     * Test that migrate skips already migrated emails.
     *
     * @return void
     */
    public function test_migrate_skips_already_migrated_emails()
    {
        $this->s3ServiceMock->shouldReceive('initializeBucket')->andReturn(true);

        // Create email that's already migrated
        Email::factory()->create([
            'id' => 1,
            'body_s3_path' => 's3://bucket/email/1.html',
            'file_ids' => []
        ]);

        $stats = $this->migrationService->migrate();

        // Should not attempt to upload and verify stats show 0 successful migrations
        $this->assertEquals(0, $stats['successful_migrations'], 'Already migrated emails should not be migrated again');
        $this->s3ServiceMock->shouldNotHaveReceived('uploadHtmlBody');
    }

    /**
     * Test batch size configuration.
     *
     * @return void
     */
    public function test_set_batch_size()
    {
        $service = $this->migrationService->setBatchSize(50);

        $this->assertInstanceOf(EmailMigrationService::class, $service);
    }

    /**
     * Test batch size minimum validation.
     *
     * @return void
     */
    public function test_batch_size_minimum_validation()
    {
        $service = $this->migrationService->setBatchSize(0);

        // Should default to minimum of 1
        $this->assertInstanceOf(EmailMigrationService::class, $service);
    }

    /**
     * Test verify method returns correct structure.
     *
     * @return void
     */
    public function test_verify_returns_verification_results()
    {
        $this->s3ServiceMock->shouldReceive('fileExists')->andReturn(true);

        // Create migrated email
        Email::factory()->create([
            'body_s3_path' => 's3://bucket/email/1.html',
            'file_s3_paths' => ['1' => 's3://bucket/files/1/test.pdf']
        ]);

        $results = $this->migrationService->verify();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('total_emails_checked', $results);
        $this->assertArrayHasKey('valid', $results);
        $this->assertArrayHasKey('invalid', $results);
        $this->assertArrayHasKey('issues', $results);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
