<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class FileFactory extends Factory
{
    protected $model = File::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $extension = $this->faker->fileExtension();
        $filename = $this->faker->uuid() . '.' . $extension;
        
        // This is the clean path we store in the DB
        $relativePath = 'files/' . $filename;
        
        // This is the absolute path for the physical file creation
        $absolutePath = storage_path($relativePath);
        
        // Ensure the directory exists
        $directory = dirname($absolutePath);
        if (!is_dir($directory)) {
            // Using 0777 because of Windows/Docker permission mapping
            mkdir($directory, 0777, true);
        }
        
        // Generate random file content (1KB to 10KB)
        $fileSize = $this->faker->numberBetween(1024, 10240);
        $content = random_bytes($fileSize);
        
        // Create the actual file on disk
        file_put_contents($absolutePath, $content);
        
        // Ensure the file itself is writable by the web user
        chmod($absolutePath, 0666);

        return [
            'name' => $this->faker->word() . '.' . $extension,
            'path' => $relativePath, 
            'size' => $fileSize,
            'type' => $this->faker->mimeType(),
        ];
    }
}