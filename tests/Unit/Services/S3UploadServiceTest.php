<?php

namespace Tests\Unit\Services;

use App\Services\S3UploadService;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class S3UploadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $s3Service;
    protected $s3ClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock S3 client
        $this->s3ClientMock = Mockery::mock(S3Client::class);
    }

    /**
     * Test initialize bucket.
     *
     * @return void
     */
    public function test_initialize_bucket_success()
    {
        $this->s3ClientMock->shouldReceive('doesBucketExist')->andReturn(true);

        $service = new S3UploadService();
        // Replace the client with our mock through reflection
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $this->s3ClientMock);

        $result = $service->initializeBucket();

        $this->assertTrue($result);
    }

    /**
     * Test extract key from S3 path.
     *
     * @return void
     */
    public function test_extract_key_from_s3_path()
    {
        $service = new S3UploadService();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractKeyFromS3Path');
        $method->setAccessible(true);

        $s3Path = 's3://bucket-name/emails/123.html';
        $key = $method->invoke($service, $s3Path);

        $this->assertEquals('emails/123.html', $key);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
