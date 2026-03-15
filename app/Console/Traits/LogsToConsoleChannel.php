<?php

namespace App\Console\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait for console commands to log errors only to a dedicated console.log file.
 * Prevents permission errors and reduces log noise.
 */
trait LogsToConsoleChannel
{
    /**
     * Log an error message to the console channel.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::channel('console')->error($message, $context);
    }

    /**
     * Log a critical message to the console channel.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logCritical(string $message, array $context = []): void
    {
        Log::channel('console')->critical($message, $context);
    }

    /**
     * Log a warning message to the console channel.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::channel('console')->warning($message, $context);
    }
}
