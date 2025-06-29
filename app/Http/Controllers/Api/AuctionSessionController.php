<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ScrapeVehicles;
use App\Models\AuctionSession;
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

        // \Log::info("we should be scrapping vehicles now..");
        ScrapeVehicles::dispatch($vehicles_url, $auction_id);
    }

    public function updateBidStages(Request $request)
    {
        // \Log::info($request->all());

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
        $phillips_accounts = PhillipsAccount::query()->get();

        foreach ($phillips_accounts as $phillips_account) {
            $phillips_account->status = 'dormant';
            $phillips_account->email_status = 'dormant';
            $phillips_account->account_status = 'dormant';
            $phillips_account->status = 'dormant';
            $phillips_account->push();
        }

        foreach ($request->all()['accounts'] as $account) {
            if ($account['email'] && $account['email_password'] && $account['phillips_account_password']) {
                $phillips_account = PhillipsAccount::query()->where('email', $account['email'])->first();
                $phillips_account->account_status = "testing";
                $phillips_accounts->status = "testing";
                $phillips_account->email_app_password = $account['email_password'];
                $phillips_account->account_password = $account['phillips_account_password'];
                $phillips_account->push();

                $auction_session = AuctionSession::query()->where('id', $request->all()['auction_id'])->first();
                $auction_session->status = "testing";
                $auction_session->push();

                TestPhillipsAccountCredentials::dispatch(
                    phillips_account_email: $account['email'],
                    phillips_account_password: $account['phillips_account_password']
                );

                MonitorEmail::dispatch(
                    email: $account['email'],
                    email_password: $account['email_password']
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
        // \Log::info("processInitTestResult Called");
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
                $auction_session = AuctionSession::query()->where('status', 'testing')->first();
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

            $auction_session = AuctionSession::query()->where('status', 'testing')->first();
            $auction_session->status = "unconfigurable";
            $auction_session->push();
            \Log::info('failed');
            // \Log::info($id, $type, $title, $description;
            event(new NotificationFromInitAuctionTestEvent($id, $type, $title, $description));
        }

        // \Log::info($request->all());
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
        // \Log::info($request->all());
        // \Log::info("placebid");
        // if($request->current_bid == )
        if (ceil(rand(1, 10)) > 5) {
            $vehicle = Vehicle::query()->where('url', 'https://phillipsauctioneers.co.ke/product/kcx-867j-toyota-prado-2/')->first();
        } else {
            $vehicle = Vehicle::query()->where('url', 'https://phillipsauctioneers.co.ke/product/ktcc-364g-case-tractor-2/')->first();
        }

        $auction = $vehicle->AuctionSession;
        $lastBidAmount = $vehicle->bids()->latest()->value('amount');

        // Randomize account selection;

        $active_account = PhillipsAccount::query()->where('status', 'active')
            ->inRandomOrder()
            ->first();
        // \Log::info("Last bid amount: " . $lastBidAmount);
        if ($vehicle->maximum_amount < $request->current_bid) {
            PlaceBid::dispatch(
                url: "http://phillips.adilirealestate.com/bidSuccess.html",// $request->url,
                amount: $request->current_bid + $vehicle->lazy_stage_increment,
                maximum_amount: $vehicle->maximum_amount,
                increment: $vehicle->lazy_stage_increment,
                email: $active_account->email,
                password: $active_account->account_password,
                vehicle_id: $vehicle->id,
                vehicle_name: $vehicle->phillips_vehicle_id,
                bid_stage: "lazy stage"
            )->onQueue('placeBids');
        } else {
            $vehicle->status = "Out budgeted";
            $vehicle->push();
            $id = Str::random(10);
            $type = 'fail';
            $title = $vehicle->phillips_vehicle_id . " Outbudgeted";
            $description = $vehicle->phillips_vehicle_id . " has been out outbudgeted by " . $request->current_bid;
            NotificationFromInitAuctionTestEvent::dispatch($id, $type, $title, $description);
        }
    }

    public function scrape(Request $request)
    {
        // \Log::info("scrapiing");

        ScrapeAuctions::Dispatch();

        return response()->json([
            "message" => "Scrape initiated, please reload the page after 2-3 minutes."
        ]);
    }
}
