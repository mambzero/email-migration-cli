<?php

namespace App\Repositories;

use App\Models\Email;
use Illuminate\Support\Collection;

class EmailRepository
{
    public function __construct(
        protected Email $model
    ) {}

    public function find(int $id): ?Email
    {
        return $this->model->find($id);
    }

    public function getEmails(int $limit = 100): Collection
    {
        return $this->model
            ->whereNull('body_s3_path')
            ->limit($limit)
            ->get();
    }

    public function updateBodyS3Path(int $emailId, string $path): bool
    {
        return $this->model
            ->where('id', $emailId)
            ->update([
                'body_s3_path' => $path
            ]);
    }

    public function updateFileS3Paths(int $emailId, array $paths): bool
    {
        return $this->model
            ->where('id', $emailId)
            ->update([
                'file_s3_paths' => $paths
            ]);
    }

    public function updateS3Paths(int $emailId, string $bodyS3Path, array $fileS3Paths): bool
    {
        return $this->model
            ->where('id', $emailId)
            ->update([
                'body_s3_path' => $bodyS3Path,
                'file_s3_paths' => $fileS3Paths,
            ]);
    }

    public function countNotMigrated()
    {
        return Email::whereNull('body_s3_path')->count();
    }

    public function create(array $data): Email
    {
        return $this->model->create($data);
    }
}
