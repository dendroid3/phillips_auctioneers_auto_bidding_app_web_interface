<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationFromInitAuctionTestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $id;
    protected $type;
    protected $title;
    protected $description;
    public function __construct($id, $type, $title, $description)
    {
        $this->id = $id;
        $this->type = $type;
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('public-channel'),
        ];
    }

    public function BroadcastWith()
    {
        return [
            "id" => $this->id,
            "type" => $this->type,
            "title" => $this->title,
            "description" => $this->description
        ];
    }
    public function broadcastAs(): string
    {
        return 'account.testresults';
    }
}
