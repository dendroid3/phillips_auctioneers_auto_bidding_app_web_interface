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
    protected $auction_session_id;
    public function __construct($phillips_account_email, $phillips_account_password, $auction_session_id)
    {
        $this->phillips_account_email = $phillips_account_email;
        $this->phillips_account_password = $phillips_account_password;
        $this->auction_session_id = $auction_session_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $command = [
            'node',
            env('BOT_BASE_PATH').'/initAuctionSession.js',
            '--email',
            $this->phillips_account_email,
            '--password',
            $this->phillips_account_password,
            '--auction_session_id',
            $this->auction_session_id
        ];

        // \Log::info($command);

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
