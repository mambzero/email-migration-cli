<?php

namespace App\Services;

use App\Models\Email;
use App\Models\File;
use App\Repositories\EmailRepository;
use App\Repositories\FileRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EmailMigrationService
{
    protected $emails;
    protected $files;
    protected $s3Service;
    protected $batchSize = 100;

    public function __construct(
        EmailRepository $emails,
        FileRepository $files,
        S3UploadService $s3Service
    ) {
        $this->emails = $emails;
        $this->files = $files;
        $this->s3Service = $s3Service;
    }

    /**
     * Migrate emails using chunkById for memory efficiency and safety.
     */
    public function migrate(?callable $progressCallback = null): array
    {
        $stats = [
            'total_emails' => $this->emails->countNotMigrated(),
            'successful_migrations' => 0,
            'failed_migrations' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        if (!$this->s3Service->initializeBucket()) {
            $stats['errors'][] = 'Failed to initialize S3 bucket';
            return $stats;
        }

        Email::whereNull('body_s3_path')
            ->chunkById($this->batchSize, function ($emails) use (&$stats, $progressCallback) {
                foreach ($emails as $email) {
                    try {
                        if ($this->migrateEmail($email)) {
                            $stats['successful_migrations']++;
                        } else {
                            $stats['skipped']++;
                        }
                    } catch (\Exception $e) {
                        $stats['failed_migrations']++;
                        $stats['errors'][] = "Email ID {$email->id}: " . $e->getMessage();
                        Log::error("Migration failed for Email {$email->id}: " . $e->getMessage());
                    }

                    if ($progressCallback) {
                        $processed = $stats['successful_migrations'] + $stats['failed_migrations'] + $stats['skipped'];
                        $progressCallback($email->id, $processed, $stats['total_emails']);
                    }
                }
            });

        return $stats;
    }

    /**
     * Migrate a single email and its attachments.
     */
    protected function migrateEmail(Email $email): bool
    {
        if ($email->body_s3_path !== null) {
            return false;
        }

        return DB::transaction(function () use ($email) {

            $bodys3Path = $this->s3Service->uploadHtmlBody($email->id, $email->body);

            if (!$bodys3Path) {
                throw new \Exception('S3 Body upload returned empty path');
            }

            $filEs3Paths = [];
            if (!empty($email->file_ids) && is_array($email->file_ids)) {

                $files = $this->files->getFilesByIds($email->file_ids);

                foreach ($files as $file) {
                    $absolutePath = storage_path($file->path);

                    if (!file_exists($absolutePath)) {
                        Log::warning("Physical file missing: {$absolutePath}");
                        continue;
                    }

                    $s3Path = $this->s3Service->uploadFile($email->id, $file->id, $absolutePath, $file->name);

                    if ($s3Path) {
                        $filEs3Paths[] = $s3Path;
                    }
                }
            }

            $updated = $this->emails->updateS3Paths($email->id, $bodys3Path, $filEs3Paths);

            if (!$updated) {
                throw new \Exception("Database save failed for Email ID {$email->id}");
            }

            return true;
        });
    }

    public function setBatchSize(int $size): self
    {
        $this->batchSize = max(1, $size);
        return $this;
    }

    /**
     * Verify integrity using cursor to handle large volumes without memory spikes.
     */
    public function verify(): array
    {
        $results = [
            'total_emails_checked' => 0,
            'valid' => 0,
            'invalid' => 0,
            'issues' => [],
        ];

        foreach (Email::whereNotNull('body_s3_path')->cursor() as $email) {
            $results['total_emails_checked']++;
            $isValid = true;

            if (!$this->s3Service->fileExists($email->body_s3_path)) {
                $isValid = false;
                $results['issues'][] = "Email {$email->id}: Body missing on S3";
            }

            if ($email->file_s3_paths) {
                foreach ($email->file_s3_paths as $fileId => $s3Path) {
                    if (!$this->s3Service->fileExists($s3Path)) {
                        $isValid = false;
                        $results['issues'][] = "Email {$email->id}: File {$fileId} missing on S3";
                    }
                }
            }

            $isValid ? $results['valid']++ : $results['invalid']++;
        }

        return $results;
    }
}
