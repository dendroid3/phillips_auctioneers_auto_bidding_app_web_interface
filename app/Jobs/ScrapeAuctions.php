<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;

class ScrapeAuctions implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */

    public function handle(): void
    {
        $command = [
            'node',
            env('BOT_BASE_PATH').'/scrapeAuctions.js',
        ];
        $process = new Process($command);

        $process->setTimeout(3600); // 1 hour timeout
        $process->setIdleTimeout(300);

        try {

            $process->run();


            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

        } catch (\Exception $e) {
            throw $e; // This will trigger the job's failed() method

        }
    }
}
