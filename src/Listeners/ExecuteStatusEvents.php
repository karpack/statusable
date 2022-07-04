<?php

namespace Karpack\Statusable\Listeners;

use Illuminate\Support\Facades\Event;
use Karpack\Statusable\Events\StatusChanged;
use Karpack\Support\Traits\HasTransactionAwareness;

class ExecuteStatusEvents
{
    use HasTransactionAwareness;

    /**
     * Calls the events registered for the current status
     * 
     * @return void
     */
    public function handle(StatusChanged $event)
    {
        if (is_null($statusable = $event->statusable)) {
            return;
        }

        foreach ($statusable->registeredStatusEvents() as $status => $eventClass) {
            if (!$statusable->statusIs($status)) {
                continue;
            }
            $afterCommit = true;

            $eventToExecute = new $eventClass($statusable);

            if (property_exists($eventToExecute, 'afterCommit')) {
                $afterCommit = $eventToExecute->afterCommit;
            }

            return !!$afterCommit
                ? $this->afterTransactionCommitted(fn () => Event::dispatch($eventToExecute))
                : Event::dispatch($eventToExecute);
        }
    }
}