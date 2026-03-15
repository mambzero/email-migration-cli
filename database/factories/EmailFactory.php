<?php

namespace Database\Factories;

use App\Models\Email;
use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailFactory extends Factory
{
    protected $model = Email::class;

    /**
     * Use a static variable to ensure we only query the DB once
     * AFTER files have been seeded.
     */
    protected static ?array $cachedFileIds = null;

    /**
     * Get file IDs. If the cache is empty, it attempts to fetch them.
     */
    protected function getFileIds(): array
    {
        // If null, try to fetch. If the table is still empty, it stays null
        // and will try again on the next email generation.
        if (self::$cachedFileIds === null) {
            $ids = File::pluck('id')->toArray();
            if (!empty($ids)) {
                self::$cachedFileIds = $ids;
            }
        }

        return self::$cachedFileIds ?? [];
    }

    public function definition(): array
    {
        $existingFileIds = $this->getFileIds();
        $fileIds = [];

        if (!empty($existingFileIds)) {
            // Pick 1 to 3 random indices from IDs array
            $count = rand(1, min(3, count($existingFileIds)));
            $fileIds = $this->faker->randomElements($existingFileIds, $count);
        }

        return [
            'client_id'         => $this->faker->numberBetween(1, 1000),
            'loan_id'           => $this->faker->numberBetween(1, 10000),
            'email_template_id' => $this->faker->numberBetween(1, 100),
            'receiver_email'    => $this->faker->email(),
            'sender_email'      => $this->faker->email(),
            'subject'           => $this->faker->sentence(),
            'body'              => $this->generateLargeHtmlBody(),
            'file_ids'          => $fileIds, // Model casting handles JSON conversion
            'sent_at'           => $this->faker->dateTime(),
        ];
    }

    private function generateLargeHtmlBody(): string
    {
        // Using a static string start to save memory on large generations
        $paragraphs = $this->faker->paragraphs(30);

        $html = '<html><body>';
        $html .= '<h1>' . $this->faker->sentence() . '</h1>';
        $html .= '<p>' . implode('</p><p>', $paragraphs) . '</p>';

        $html .= '<div class="content">';
        for ($i = 0; $i < 10; $i++) {
            $html .= '<section>';
            $html .= '<h2>' . $this->faker->sentence() . '</h2>';
            $html .= '<p>' . $this->faker->paragraph(10) . '</p>';
            $html .= '</section>';
        }
        $html .= '</div></body></html>';

        return $html;
    }
}
