<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionSession;
use App\Models\BidStage;
use App\Models\PhillipsAccount;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Http\Resources\VehicleResource;
use App\Http\Resources\AuctionSessionResource;

use App\Jobs\PlaceBid;

class AuctionSessionController extends Controller
{

    public function get(Request $request)
    {
        $decodedTitle = urldecode($request->id);
        $normalizedTitle = trim(preg_replace('/\s+/', ' ', $decodedTitle));

        $auction = AuctionSession::query()->where('title', $normalizedTitle)->first();
        $bidStages = $auction->bidStages;

        if (!$auction) {
            return response()->json(['error' => 'Auction not found'], 404);
        }

        $phillips_accounts_emails = PhillipsAccount::query()->pluck('email')->toArray();
        // Transform the auction and its vehicles
        $response = [
            'id' => $auction->id,
            'title' => $auction->title,
            'date' => $auction->date,
            'status' => $auction->status,
            'phillips_accounts_emails' => $phillips_accounts_emails,
            'bid_stages' => $bidStages,
            'vehicles' => VehicleResource::collection($auction->vehicles)
        ];

        return response()->json($response);
    }
    public function getAll()
    {
        $auctions = AuctionSession::query()
            ->orderBy('date', 'desc')
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

    public function updateBidStages(Request $request)
    {
        \Log::info($request->all());

        $stages = $request->all();

        foreach ($stages as $stage) {
            $stage_to_be_updated = BidStage::find($stage['id']);
            $stage_to_be_updated->start_time = $stage['start_time'];
            $stage_to_be_updated->end_time = $stage['end_time'];
            $stage_to_be_updated->push();
        }

        // Update Auction Session to configured
        $auction_session = AuctionSession::find($request->all()[0]['auction_session_id']);
        $auction_session->status = 'configured';
        $auction_session->push();

        // Update all vehicles to configurable
        $vehicles = $auction_session->vehicles;

        foreach ($vehicles as $vehicle) {
            $vehicle->status = 'unconfigured';
            $vehicle->push();
        }

        return response()->json([
            "message" => "Bid stages times have been successfully updated. You can now configure the vehicles"
        ]);
    }

    public function initialize(Request $request)
    {
        \Log::info("Called");
        foreach ($request -> email as $key => $value) {
            # code...
        }
        // Call script
        $email = $request->all()[0]['email'];
        $password = $request->all()[0]['password'];

        $cmd = "node " . '/home/wanjohi/Code/web/phillips/puppeteer/initAuctionSession.js' .
            " --email=" . escapeshellarg($email) .
            " --password=" . escapeshellarg($password) .
            " 2>&1";
        // " > /dev/null 2>&1 &";

        exec($cmd, $output, $returnCode);
        \Log::info("Output: " . print_r($output, true));
        \Log::info("Return Code: " . $returnCode);

        return response()->json([
            "id" => ceil(rand(1, 10) * 32874),
            "description" => "request to initialize received, we are running tests in the background, and initialize the auction session if all the tests pass",
            "type" => "success",
            "title" => "Initialization Pending"
        ]);
    }

    public function processInitTestResults(Request $request)
    {
        // TODO:
        /**
         * Send notification to front end
         * Possible request.status = 200, 404, 403
         * 
         * If status 200:
         * ----- Change phillip account status to pending
         * ----- Check if there is any rejected | pending
         * ------------- if no pending | rejected
         * ----------------------- Send notification that auction has bee successfully initated
         * ----------------------- Change auction status to acuve
         * ----------------------- Change all phillips account that are pending to active
         * If 403 || 404 change phillip account status to rejected
         * ------- Front end to alert this so as to suspend all other activity till it is read
         */
        \Log::info($request->all());
    }
    public function processNewEmail(Request $request)
    {
        // TODO:
        /**
         * 
         * Request will have $url = vehicle url and $amount = Amount of bid that outbidded us
         * 
         * Use vehicle url to get vehicle:
         * ---- Check vehicle bids:
         * -------- If the last bid is === to the $request -> amount, then another one of our accounts placed the bid, do not place bid or send notification otherwise:
         * -------------- Dispatch a place bid job, the new amount should be $request -> amount + stage_increment
         * 
         * 
         */
        PlaceBid::dispatch(
            url: $request->url,
            amount: 35000,
            maximum_amount: 40000,
            increment: 2500,
            email: 'Denis@.com',
            password: "ernestotieno95@gmail.com",
            vehicle_id: 1,
            vehicle_name: "KCC-123P-TOYOTA-DEMIO",
            bid_stage: "lazy stage"
        );

        \Log::info($request->all());
    }
}
