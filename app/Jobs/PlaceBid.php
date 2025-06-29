<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;

class PlaceBid implements ShouldQueue
{
    use Queueable;
    public $timeout = 600;
    public $tries = 3;

    protected $url;
    protected $amount;
    protected $maximum_amount;
    protected $increment;
    protected $email;
    protected $password;
    protected $vehicle_id;
    protected $vehicle_name;
    protected $bid_stage;

    public function __construct(
        string $url,
        int $amount,
        int $maximum_amount,
        int $increment,
        string $email,
        string $password,
        int $vehicle_id,
        string $vehicle_name,
        string $bid_stage
    ) {
        $this->url = $url;
        $this->amount = $amount;
        $this->maximum_amount = $maximum_amount;
        $this->increment = $increment;
        $this->email = $email;
        $this->password = $password;
        $this->vehicle_id = $vehicle_id;
        $this->vehicle_name = $vehicle_name;
        $this->bid_stage = $bid_stage;
    }

    public function handle(): void
    {
        \Log::info("Place Bid Called");
        $command = [
            'node',
            '/home/wanjohi/Code/web/phillips/bot/placeBid.js',
            '--url',
            $this->url,
            '--amount',
            $this->amount,
            '--maximum_amount',
            $this->maximum_amount,
            '--increment',
            $this->increment,
            '--email',
            $this->email,
            '--password',
            $this->password,
            '--vehicle_id',
            $this->vehicle_id,
            '--vehicle_name',
            $this->vehicle_name,
            '--bid_stage',
            $this->bid_stage
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
