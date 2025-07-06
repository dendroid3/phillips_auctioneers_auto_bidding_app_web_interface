<?php

namespace App\Jobs;

use App\Models\Vehicle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;

use App\Events\NotificationFromInitAuctionTestEvent;
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
    protected $bid_stage_name;
    protected $bid_stage_id;

    public function __construct(
        string $url,
        int $amount,
        int $maximum_amount,
        int $increment,
        string $email,
        string $password,
        int $vehicle_id,
        string $vehicle_name,
        string $bid_stage_name,
        int $bid_stage_id
    ) {
        $this->url = $url;
        $this->amount = $amount;
        $this->maximum_amount = $maximum_amount;
        $this->increment = $increment;
        $this->email = $email;
        $this->password = $password;
        $this->vehicle_id = $vehicle_id;
        $this->vehicle_name = $vehicle_name;
        $this->bid_stage_name = $bid_stage_name;
        $this->bid_stage_id = $bid_stage_id;
    }

    public function handle(): void
    {
        $vehicle_name = Vehicle::find($this->vehicle_id)->phillips_vehicle_id;

        $formatter = new \NumberFormatter('en_KE', \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
        $formatted_bid_amount = $formatter->formatCurrency($this->amount, 'KES');
        $formatted_increment = $formatter->formatCurrency($this->increment, 'KES');

        $id = \Str::random(10);
        $type = 'amber';
        $title = "PLACING BID: " . $vehicle_name . " [" . number_format($this->amount) . "]";
        $description = "Placing a bid of " .
            $formatted_bid_amount .
            " on " .
            $vehicle_name .
            ". We will use " .
            $this->bid_stage_name .
            "'s increment of " .
            $formatted_increment .
            " to chase the highest.";
        NotificationFromInitAuctionTestEvent::dispatch($id, $type, $title, $description);

        $command = [
            'node',
            env('BOT_BASE_PATH').'/placeBid.js',
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
            '--bid_stage_name',
            $this->bid_stage_name,
            '--bid_stage_id',
            $this->bid_stage_id
        ];

        $process = new Process($command);

        \Log::info($command);
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
