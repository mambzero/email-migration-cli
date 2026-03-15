<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class S3UploadService
{
    /**
     * S3 client instance.
     *
     * @var S3Client
     */
    protected $client;

    /**
     * S3 bucket name.
     *
     * @var string
     */
    protected $bucket;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->bucket = config('filesystems.disks.s3.bucket');
        
        $this->client = new S3Client([
            'version' => 'latest',
            'region'  => config('filesystems.disks.s3.region'),
            'credentials' => [
                'key'    => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
            'endpoint' => config('filesystems.disks.s3.endpoint'),
            'use_path_style_endpoint' => config('filesystems.disks.s3.use_path_style_endpoint', false),
        ]);
    }

    /**
     * Upload HTML body to S3.
     *
     * @param int    $emailId
     * @param string $htmlContent
     * @return string|null S3 path if successful, null otherwise
     */
    public function uploadHtmlBody(int $emailId, string $htmlContent): ?string
    {
        try {
            $key = "$emailId/email_{$emailId}.html";
            
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'Body'   => $htmlContent,
                'ContentType' => 'text/html',
            ]);

            Log::info("Successfully uploaded HTML body for email {$emailId}", ['s3_key' => $key]);
            
            return "s3://{$this->bucket}/{$key}";
        } catch (AwsException $e) {
            Log::error("Failed to upload HTML body for email {$emailId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload file to S3.
     *
     * @param int    $fileId
     * @param string $filePath Local file path
     * @param string $fileName  Original file name
     * @return string|null S3 path if successful, null otherwise
     */
    public function uploadFile(int $emailId,int $fileId, string $filePath, string $fileName): ?string
    {
        try {
            if (!file_exists($filePath)) {
                Log::warning("File not found for upload", ['file_id' => $fileId, 'path' => $filePath]);
                return null;
            }

            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                Log::error("Failed to read file content", ['file_id' => $fileId, 'path' => $filePath]);
                return null;
            }

            $key = "$emailId/files/" . basename($fileName);
            
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'Body'   => $fileContent,
            ]);

            Log::info("Successfully uploaded file {$fileId}", ['s3_key' => $key]);
            
            return "s3://{$this->bucket}/{$key}";
        } catch (AwsException $e) {
            Log::error("Failed to upload file {$fileId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify if a file exists in S3.
     *
     * @param string $s3Path S3 path in format s3://bucket/key
     * @return bool
     */
    public function fileExists(string $s3Path): bool
    {
        try {
            $key = $this->extractKeyFromS3Path($s3Path);
            return $this->client->doesObjectExist($this->bucket, $key);
        } catch (\Exception $e) {
            Log::warning("Error checking if file exists in S3", ['s3_path' => $s3Path, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Extract S3 key from S3 path.
     *
     * @param string $s3Path
     * @return string
     */
    protected function extractKeyFromS3Path(string $s3Path): string
    {
        $parts = explode('/', $s3Path, 4);
        return isset($parts[3]) ? $parts[3] : '';
    }

    /**
     * Initialize S3 bucket if it doesn't exist.
     *
     * @return bool
     */
    public function initializeBucket(): bool
    {
        try {
            if (!$this->client->doesBucketExist($this->bucket)) {
                $this->client->createBucket(['Bucket' => $this->bucket]);
                Log::info("Created S3 bucket", ['bucket' => $this->bucket]);
            }
            return true;
        } catch (AwsException $e) {
            Log::error("Failed to initialize S3 bucket: " . $e->getMessage());
            return false;
        }
    }
}
