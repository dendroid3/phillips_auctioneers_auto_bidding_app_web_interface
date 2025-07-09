<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ScrapeVehicles;
use App\Models\AuctionSession;
use App\Models\Bid;
use App\Models\BidStage;
use App\Models\PhillipsAccount;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Http\Resources\VehicleResource;
use Illuminate\Support\Str;
use App\Jobs\PlaceBid;
use App\Jobs\TestPhillipsAccountCredentials;
use App\Jobs\MonitorEmail;
use App\Jobs\ScrapeAuctions;

use App\Events\NotificationFromInitAuctionTestEvent;
class AuctionSessionController extends Controller
{

    public function get(Request $request)
    {
        $decodedTitle = urldecode($request->id);
        $normalizedTitle = trim(preg_replace('/\s+/', ' ', $decodedTitle));

        $words = explode(' ', $normalizedTitle);
        $firstThreeWords = implode(' ', array_slice($words, 0, 3));

        $auction = AuctionSession::query()->where('title', 'LIKE', $firstThreeWords . '%')->first();
        $bidStages = $auction->bidStages;

        if (!$auction) {
            return response()->json(['error' => 'Auction not found'], 404);
        }

        $phillips_accounts_emails = PhillipsAccount::query()
            ->where('email', '!=', 'competitor@example.com')
            ->pluck('email')
            ->toArray();
        // Transform the auction and its vehicles
        $response = [
            'id' => $auction->id,
            'title' => $auction->title,
            'date' => $auction->date,
            'start_time' => $auction->start_time,
            'end_time' => $auction->end_time,
            'bid_stage' => BidStage::query()->where('auction_session_id', $auction->id)->where('status', 'active')->select('name')->first(),
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
        ScrapeVehicles::dispatch($vehicles_url, $auction_id);
    }

    public function updateBidStages(Request $request)
    {
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
        $auction_session = AuctionSession::find($request->all()['auction_session_id']);
        $phillips_accounts = PhillipsAccount::query()->get();

        foreach ($phillips_accounts as $phillips_account) {
            $phillips_account->status = 'dormant';
            $phillips_account->email_status = 'dormant';
            $phillips_account->account_status = 'dormant';
            $phillips_account->status = 'dormant';
            $phillips_account->push();
        }

        foreach ($request->all()['accounts'] as $account) {
            if ($account['email'] && $account['email_app_password'] && $account['phillips_account_password']) {
                $phillips_account = PhillipsAccount::query()->where('email', $account['email'])->first();
                $phillips_account->account_status = "testing";
                $phillips_accounts->status = "testing";
                $phillips_account->email_app_password = $account['email_app_password'];
                $phillips_account->account_password = $account['phillips_account_password'];
                $phillips_account->push();

                $auction_session->status = "testing";
                $auction_session->push();

                TestPhillipsAccountCredentials::dispatch(
                    phillips_account_email: $account['email'],
                    phillips_account_password: $account['phillips_account_password'],
                    auction_session_id: $auction_session->id
                );

                MonitorEmail::dispatch(
                    email: $account['email'],
                    email_app_password: $account['email_app_password'],
                    interval: 15
                )->onQueue('email');
                ;
            }
        }

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
        // type, id, title, description
        // \Log::info($request->all());
        if ($request->status == 200) {
            $id = Str::random(10) . "-" . $request->email;
            $type = "success";
            $title = "Account Credentials Confirmed";
            $description = "The credentials for phillips account " . $request->email . " tested successfully";

            $phillips_account = PhillipsAccount::query()->where('email', $request->email)->first();
            $phillips_account->account_status = "active";
            $phillips_account->email_status = "active";
            $phillips_account->status = "active";
            $phillips_account->push();

            $phillips_account_on_test = PhillipsAccount::query()
                ->where('account_status', 'testing')
                ->orWhere('account_status', 'failed')
                ->get();

            if (count($phillips_account_on_test) == 0) {
                $auction_session = AuctionSession::query()->where('status', 'testing')->first();
                $auction_session->status = "unconfigured";
                $auction_session->push();
            }

            $phillips_account_failed = PhillipsAccount::query()
                ->where('account_status', 'failed')
                ->get();

            if (count($phillips_account_failed) > 0) {
                $auction_session = AuctionSession::find($request->auction_session_id);
                $auction_session->status = "unconfigurable";
                $auction_session->push();
            }

            event(new NotificationFromInitAuctionTestEvent($id, $type, $title, $description));
        } else {
            $id = Str::random(10) . "-" . $request->email;
            $type = "fail";
            $title = "Account Credentials Failed";
            $description = "The credentials for phillips account " . $request->email . " failed. Kindly restart the initialization process and ensure the credentials are correct";


            $phillips_account = PhillipsAccount::query()->where('email', $request->email)->first();
            $phillips_account->account_status = "failed";
            $phillips_account->push();

            // \Log::info($request->all());
            $auction_session = AuctionSession::find($request->auction_session_id);
            $auction_session->status = "unconfigurable";
            $auction_session->push();
            event(new NotificationFromInitAuctionTestEvent($id, $type, $title, $description));
        }

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

        // Create a competitor bid. Catch it in the listener.
        $vehicle = Vehicle::query()->where('url', $request->url)->first();
        $auction = $vehicle->AuctionSession;
        $lastBidAmount = $vehicle->bids()->latest()->value('amount');

        // Randomize account selection;
        $active_account = PhillipsAccount::query()->where('status', 'active')
            ->inRandomOrder()
            ->first();

        // Get active Stage 
        $activeBidStage = BidStage::query()->where('auction_session_id', $auction->id)->where('status', 'active')->first();
        $activeBidStageName = $activeBidStage->name . "_stage_increment";
        $activeBidStageIncrement = $vehicle->$activeBidStageName;

        if (
            (int) $lastBidAmount !== (int) $request->current_bid &&
            (int) $vehicle->current_bid !== (int) $request->current_bid &&
            (int) $vehicle->maximum_amount > $request->current_bid &&
            $activeBidStage->name !== 'sniping'
        ) {
            if (
                (int) $vehicle->maximum_amount > ((int) $request->current_bid + (int) $activeBidStageIncrement)
            ) {
                $id = Str::random(10);
                $type = 'fail';
                $title = "TOPPLED: " . $vehicle->phillips_vehicle_id . " [" . number_format($request->current_bid, 0) . "]";
                $description = "We've received an email on the email address " . $request->email .
                    " that " . $vehicle->phillips_vehicle_id .
                    " has been out bidded by a competitor who bid " .
                    $request->current_bid .
                    ". We are placing a new bid starting at " .
                    $request->current_bid + (int) $activeBidStageIncrement .
                    " (competitor's: " .
                    $request->current_bid .
                    " + " .
                    $activeBidStage->name .
                    " stage increment: " .
                    $activeBidStageIncrement .
                    "). Account " .
                    $active_account->email .
                    " will be used."
                ;
                NotificationFromInitAuctionTestEvent::dispatch($id, $type, $title, $description);

                PlaceBid::dispatch(
                    url: $request->url,
                    amount: $request->current_bid + (int) $activeBidStageIncrement,
                    maximum_amount: $vehicle->maximum_amount,
                    increment: (int) $activeBidStageIncrement,
                    email: $active_account->email,
                    password: $active_account->account_password,
                    vehicle_id: $vehicle->id,
                    vehicle_name: $vehicle->phillips_vehicle_id,
                    bid_stage_name: $activeBidStage->name,
                    bid_stage_id: $activeBidStage->id
                )->onQueue('placeBids');
                // return
            } else if (
                (int) $vehicle->maximum_amount < ((int) $request->current_bid + (int) $activeBidStageIncrement)
            ) {
                $id = Str::random(10);
                $type = 'fail';
                $title = "TOPPLED: " . $vehicle->phillips_vehicle_id;
                $description = $vehicle->phillips_vehicle_id .
                    " has been out bidded by a competitor who bid " .
                    $request->current_bid .
                    ". Adding " .
                    $activeBidStage->name .
                    " stage's increment of " .
                    $activeBidStageIncrement .
                    " would go beyond the maximum amount, we will therefore bid with the maximum amount.";
                NotificationFromInitAuctionTestEvent::dispatch($id, $type, $title, $description);

                PlaceBid::dispatch(
                    url: $request->url,
                    amount: $vehicle->maximum_amount,
                    maximum_amount: $vehicle->maximum_amount,
                    increment: 0,
                    email: $active_account->email,
                    password: $active_account->account_password,
                    vehicle_id: $vehicle->id,
                    vehicle_name: $vehicle->phillips_vehicle_id,
                    bid_stage_name: $activeBidStage->name,
                    bid_stage_id: $activeBidStage->id
                )->onQueue('placeBids');
            }
        } else if (
            (int) $lastBidAmount !== (int) $request->current_bid &&
            $activeBidStage->name == 'sniping'
        ) {
            // Here we just change the vehicle status in wait for sniping
            $vehicle->status = "sniping";
            $vehicle->push();
        }

        // \Log::info("lastBidAmount " . $lastBidAmount . "request -> current_bid: " . $request->current_bid . "vehicle -> current_bid: " . $vehicle->current_bid);
        if (
            (int) $lastBidAmount !== (int) $request->current_bid &&
            (int) $vehicle->current_bid !== (int) $request->current_bid
        ) {
            // \Log::info("We inside here 407");
            $bid = new Bid;
            $bid->vehicle_id = $vehicle->id;
            $bid->phillips_account_id = PhillipsAccount::query()->where('email', 'competitor@example.com')->first()->id;
            $bid->bid_stage_id = $activeBidStage->id;
            $bid->status = "Toppled";
            $bid->amount = $request->current_bid;
            $bid->save();
        } else if (
            (int) $lastBidAmount == (int) $request->current_bid &&
            (int) $vehicle->current_bid == (int) $request->current_bid
        ) {
            // Friendly fire
            $id = Str::random(10);
            $type = 'amber';
            $title = "FRIENDLY FIRE: " . $vehicle->phillips_vehicle_id . " [" . number_format($request->current_bid, 0) . "]";
            $description = "We've received an email on the email address " . $request->email .
                " that " . $vehicle->phillips_vehicle_id .
                " has been out bidded by a competitor who bid " .
                $request->current_bid .
                ". We placed a bid of a similar amount. This is friendly fire, we will not place another bid.";
            NotificationFromInitAuctionTestEvent::dispatch($id, $type, $title, $description);
        }

        if (
            (int) $vehicle->maximum_amount < $request->current_bid
        ) {
            $bid = new Bid;
            $bid->vehicle_id = $vehicle->id;
            $bid->phillips_account_id = PhillipsAccount::query()->where('email', 'competitor@example.com')->first()->id;
            $bid->bid_stage_id = $activeBidStage->id;
            $bid->status = "Outbudgeted";
            $bid->amount = $request->current_bid;
            $bid->save();
        }

        $vehicle->current_bid = (int) $request->current_bid;
        $vehicle->push();

    }

    public function scrape(Request $request)
    {
        // \Log::info("scrapping vehicles");
        ScrapeAuctions::Dispatch();

        return response()->json([
            "message" => "Scrape initiated, please reload the page after 2-3 minutes."
        ]);
    }
}
