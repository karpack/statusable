<?php

namespace Karpack\Statusable\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Karpack\Contracts\Statuses\Statusable;

class StatusChangeBroadcasted implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    /**
     * The statusable entity that has it's status changed.
     * 
     * @var \Karpack\Contracts\Statuses\Statusable
     */
    public $statusable;

    /**
     * The event identifier that has to be broadcasted.
     * 
     * @var string
     */
    public $broadcastIndentifier;

    public function __construct(Statusable $statusable, $broadcastIndentifier)
    {
        $this->statusable = $statusable;
        $this->broadcastIndentifier = $broadcastIndentifier;
    }

    /**
     * Returns the event identifier that has to be broadcasted.
     * 
     * @return string
     */
    public function broadcastAs()
    {
        return $this->broadcastIndentifier;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return $this->statusable->broadcastChannels();
    }

    /**
     * Returns the payload that has to be broadcasted.
     * 
     * @return array
     */
    public function broadcastWith()
    {
        return $this->statusable->broadcastData();
    }
}