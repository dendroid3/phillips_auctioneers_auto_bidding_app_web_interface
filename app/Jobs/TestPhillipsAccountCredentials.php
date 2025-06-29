<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;

class TestPhillipsAccountCredentials implements ShouldQueue
{
    use Queueable;

    protected $phillips_account_email;
    protected $phillips_account_password;
    public function __construct($phillips_account_email, $phillips_account_password)
    {
        $this->phillips_account_email = $phillips_account_email;
        $this->phillips_account_password = $phillips_account_password;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $command = [
            'sudo',
    '-u',
    'www-data',
    'node',
            '/var/www/phillips/bot/initAuctionSession.js',
            '--email',
            $this->phillips_account_email,
            '--password',
            $this->phillips_account_password

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
