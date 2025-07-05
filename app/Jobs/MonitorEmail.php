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

    public $tries = 1;
    protected $email;
    protected $email_app_password;
    protected $interval;
    public function __construct($email, $email_app_password, $interval)
    {
        $this->email = $email;
        $this->email_app_password = $email_app_password;
        $this->interval = $interval;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $command = [
            'python3',
            '/home/wanjohi/Code/web/phillips/email/index.py',
            $this->email,
            $this->email_app_password,
            $this->interval
        ];

        $process = new Process($command);
        $process->setTimeout(3600);
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
