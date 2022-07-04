<?php

namespace Karpack\Statusable\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Karpack\Contracts\Statuses\Statusable;

class StatusChanged
{
    use InteractsWithSockets, SerializesModels;
    
    /**
     * The statusable entity that has it's status changed.
     * 
     * @var \Karpack\Contracts\Statuses\Statusable
     */
    public $statusable;

    public function __construct(Statusable $statusable)
    {
        $this->statusable = $statusable;
    }
}