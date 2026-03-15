<?php

namespace App\Repositories;

use App\Models\File;
use Illuminate\Support\Collection;

class FileRepository
{
    public function __construct(
        protected File $model
    ) {}

    public function find(int $id): ?File
    {
        return $this->model->find($id);
    }

    public function getFilesByIds(array $ids): Collection
    {
        return $this->model
            ->whereIn('id', $ids)
            ->get();
    }

    public function create(array $data): File
    {
        return $this->model->create($data);
    }
}
