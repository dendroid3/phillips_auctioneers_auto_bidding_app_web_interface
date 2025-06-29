<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PhillipsAccount;

class CheckEmailStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-email-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Phillips accounts with active email status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Query active accounts
        $activeAccounts = PhillipsAccount::where('email_status', 'active')->get();

        // Process each account
        foreach ($activeAccounts as $account) {
            // Add your processing logic here
            $this->info("Processing account: {$account->id}");

            // Example: Send email or update something
            // $account->sendWelcomeEmail();
            // $account->update(['last_processed_at' => now()]);
        }

        $this->info('Processed ' . $activeAccounts->count() . ' active accounts.');

    }
}
