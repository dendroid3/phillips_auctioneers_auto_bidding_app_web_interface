<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionSession;
use App\Models\PhillipsAccount;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Http\Resources\VehicleResource;

class AuctionSessionController extends Controller
{

    public function get(Request $request)
    {
        $decodedTitle = urldecode($request->id);
        $normalizedTitle = trim(preg_replace('/\s+/', ' ', $decodedTitle));

        // Eager load all necessary relationships
        $auction = AuctionSession::with([
            'vehicles' => function ($query) {
                $query->latest('updated_at'); // Sort by updated_at in descending order
            },
            'vehicles.bidStages',
            'vehicles.bids' => function ($query) {
                $query->latest();
            },
            'vehicles.activeBidStage'
        ])->where('title', $normalizedTitle)->first();

        if (!$auction) {
            return response()->json(['error' => 'Auction not found'], 404);
        }

        $phillips_accounts_emails = PhillipsAccount::query()->pluck('email')->toArray();
        // Transform the auction and its vehicles
        $response = [
            'id' => $auction->id,
            'title' => $auction->title,
            'date' => $auction->date,
            'phillips_accounts_emails' => $phillips_accounts_emails,
            'vehicles' => VehicleResource::collection($auction->vehicles)
        ];

        return response()->json($response);
    }
    public function getAll()
    {
        $auctions = AuctionSession::query()
            ->orderBy('updated_at', 'asc')
            ->withCount([
                'vehicles as total_vehicles_count'
            ])
            ->withCount([
                'vehicles as unconfigured_vehicles_count' => function ($query) {
                    $query->where('status', 'unconfigured');
                }
            ])
            ->withCount([
                'vehicles as won_vehicles_count' => function ($query) {
                    $query->where('status', 'won');
                }
            ])
            ->withCount([
                'vehicles as lost_vehicles_count' => function ($query) {
                    $query->where('status', 'lost');
                }
            ])
            ->withCount([
                'vehicles as outbudgeted_vehicles_count' => function ($query) {
                    $query->where('status', 'outbudgeted');
                }
            ])
            ->get();

        return response()->json($auctions);
    }
    /**
     * Summary of create
     * @param \Illuminate\Http\Request $request
     * @return void
     * 
     * request payload: 
     * {
     *   title: Friday 30th May, Geminia sell of motor vehicle salvages through online bidding
     *   date: 30th May 2025
     *   vehicles_url: https://phillipsauctioneers.co.ke/friday-30th-may-geminia-sell-of-motor-vehicle-salvages-through-online-bidding/
     * }
     */
    public function create(Request $request)
    {
        // Check if such a record already exist
        $sessionExists = AuctionSession::query()
            ->where("title", $request->title)
            ->where("vehicles_url", $request->vehicles_url)
            ->where("date", $request->date)
            ->exists();

        // Create the auction session if such a record doesn't exists
        if (!$sessionExists) {
            $newAuctionSession = new AuctionSession();
            $newAuctionSession->title = $request->title;
            $newAuctionSession->vehicles_url = $request->vehicles_url;
            $newAuctionSession->date = $request->date;
            $newAuctionSession->save();


            $this->scrapeVehicles($newAuctionSession->vehicles_url, $newAuctionSession->id);

            // Call script to scrape vehicles

            return response()->json([
                "message" => "new auction session created"
            ]);
        }
        return response()->json([
            "message" => "Such a record already exists"
        ]);
    }

    public function scrapeVehicles($vehicles_url, $auction_id)
    {
        // try {
        //     $process = new Process([
        //         '/usr/bin/node',
        //         '/home/wanjohi/Code/web/phillips/puppeteer/scrapeVehicles.js',
        //         '--url',
        //         $vehicles_url,
        //         '--auction_id',
        //         $auction_id
        //     ]);

        //     $process->run();

        //     if (!$process->isSuccessful()) {
        //         throw new ProcessFailedException($process);
        //     }

        //     $output = $process->getOutput();
        //     \Log::info("Script output: " . $output);

        //     return $output;

        // } catch (\Exception $e) {
        //     \Log::error("Error running script: " . $e->getMessage());
        //     return false;
        // }

        $command = '--url ' .
            $vehicles_url .
            '--auction_id ' .
            $auction_id;

        \Log::info($command);
    }
}
