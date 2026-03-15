<?php

namespace Database\Seeders;

use App\Models\Email;
use Illuminate\Database\Seeder;

class EmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create 100,000 fake email records
        // This is done in chunks to avoid memory issues
        $chunkSize = 1000;
        $totalCount = 100000;

        for ($i = 0; $i < $totalCount; $i += $chunkSize) {
            Email::factory()->count(min($chunkSize, $totalCount - $i))->create();
            // Clear the factory cache every chunk to free memory
            if ($i % 10000 == 0) {
                \Illuminate\Support\Facades\DB::connection()->disconnect();
            }
        }
    }
}
