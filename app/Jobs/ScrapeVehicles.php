<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;

class ScrapeVehicles implements ShouldQueue
{
    use Queueable;

    protected $url;
    protected $auction_id;
    
    public function __construct($url, $auction_id)
    {
        $this -> url = $url;
        $this-> auction_id = $auction_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $command = [
            'node',
            '/var/www/phillips/bot/scrapeVehicles.js',
            '--url',
            $this->url,
            '--auction_id',
            $this->auction_id
        ];
        $process = new Process($command);

        $process->setTimeout(3600); // 1 hour timeout
        $process->setIdleTimeout(300);

        try {
            $process->run();

            // \Log::info("Command Output: " . $process->getOutput());

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

        } catch (\Exception $e) {
            // \Log::error("Command failed: " . $e->getMessage());
            throw $e; // This will trigger the job's failed() method
        }
    }
}
