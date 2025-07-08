<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;

use App\Events\NotificationFromInitAuctionTestEvent;

class SnipingJob implements ShouldQueue
{
    use Queueable;

    protected $email, $password, $trigger_time, $bid_stage_id, $phillips_account_id, $auction_session_id;
    public function __construct($email, $password, $trigger_time, $bid_stage_id, $phillips_account_id, $auction_session_id)
    {
        $this->$email = $email;
        $this->password = $password;
        $this->trigger_time = $trigger_time;
        $this->bid_stage_id = $bid_stage_id;
        $this->phillips_account_id = $phillips_account_id;
        $this->auction_session_id = $auction_session_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $id = \Str::random(10);
        $type = 'amber';
        $title = 'SNIPING TRIGGER TIME';
        $description = 'The trigger will be pulled at, ' . $this->trigger_time . '. Fingers crossed.';

        NotificationFromInitAuctionTestEvent::dispatch($id, $type, $title, $description);

        $command = [
            'node',
            env('BOT_BASE_PATH') . '/initSniping.js',
            '--email',
            $this->email,
            '--password',
            $this->password,
            '--trigger_time',
            $this->trigger_time,
            '--bid_stage_id',
            $this->bid_stage_id,
            '--phillips_account_id',
            $this->phillips_account_id,
            '--auction_session_id',
            $this->auction_session_id
        ];

        \Log::info($command);
        $process = new Process($command);

        $process->setTimeout(3600); // 1 hour timeout
        $process->setIdleTimeout(1800);

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
