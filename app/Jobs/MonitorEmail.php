<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Symfony\Component\Process\Process;

class MonitorEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $email_password;
    public function __construct($email, $email_password)
    {
        $this->email = $email;
        $this->email_password = $email_password;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $command = [
            'python3',
            '/var/www/phillips/email/index.py',
            $this->email,
            $this->email_password
        ];

        $process = new Process($command);
        $process->setTimeout(3600);
        $process->setIdleTimeout(300);

        try {
            $process->run();

            // \Log::info("Python Command Output: " . $process->getOutput());

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

        } catch (\Exception $e) {
            // \Log::error("Python Command failed: " . $e->getMessage());
            throw $e; // This will trigger the job's failed() method
        }
    }
}
