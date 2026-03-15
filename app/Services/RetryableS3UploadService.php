<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Aws\Exception\AwsException;

class RetryableS3UploadService extends S3UploadService
{
    /**
     * Maximum number of retry attempts.
     *
     * @var int
     */
    protected $maxRetries = 3;

    /**
     * Initial delay in milliseconds.
     *
     * @var int
     */
    protected $initialDelay = 1000;

    /**
     * Upload HTML body to S3 with retry logic.
     *
     * @param int    $emailId
     * @param string $htmlContent
     * @return string|null S3 path if successful, null otherwise
     */
    public function uploadHtmlBody(int $emailId, string $htmlContent): ?string
    {
        return $this->retryOperation(function () use ($emailId, $htmlContent) {
            return parent::uploadHtmlBody($emailId, $htmlContent);
        }, "HTML body {$emailId}");
    }

    /**
     * Upload file to S3 with retry logic.
     *
     * @param int    $fileId
     * @param string $filePath
     * @param string $fileName
     * @return string|null S3 path if successful, null otherwise
     */
    public function uploadFile(int $fileId, string $filePath, string $fileName): ?string
    {
        return $this->retryOperation(function () use ($fileId, $filePath, $fileName) {
            return parent::uploadFile($fileId, $filePath, $fileName);
        }, "File {$fileId}");
    }

    /**
     * Execute an operation with retry logic.
     *
     * @param callable $operation
     * @param string   $operationName
     * @return mixed|null
     */
    protected function retryOperation(callable $operation, string $operationName)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                return $operation();
            } catch (AwsException $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    $delay = $this->calculateBackoff($attempt);
                    Log::warning("Retry attempt {$attempt}/{$this->maxRetries} for {$operationName}. Waiting {$delay}ms");
                    usleep($delay * 1000);
                }
            } catch (\Exception $e) {
                Log::error("Unexpected error for {$operationName}: " . $e->getMessage());
                throw $e;
            }
        }

        Log::error("Failed {$operationName} after {$this->maxRetries} attempts", ['error' => $lastException->getMessage()]);
        return null;
    }

    /**
     * Calculate exponential backoff delay.
     *
     * @param int $attempt
     * @return int Delay in milliseconds
     */
    protected function calculateBackoff(int $attempt): int
    {
        // Exponential backoff with jitter
        $delay = $this->initialDelay * pow(2, $attempt - 1);
        $jitter = rand(0, (int)($delay * 0.1)); // 10% jitter
        return min($delay + $jitter, 30000); // Max 30 seconds
    }

    /**
     * Set maximum retry attempts.
     *
     * @param int $maxRetries
     * @return $this
     */
    public function setMaxRetries(int $maxRetries): self
    {
        $this->maxRetries = max(1, $maxRetries);
        return $this;
    }

    /**
     * Set initial delay for exponential backoff.
     *
     * @param int $delayMs
     * @return $this
     */
    public function setInitialDelay(int $delayMs): self
    {
        $this->initialDelay = max(100, $delayMs);
        return $this;
    }
}
