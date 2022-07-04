<?php

namespace Karpack\Statusable\Wrappers;

use Illuminate\Support\Facades\Event;
use Karpack\Contracts\Statuses\Statusable;
use Karpack\Hexagon\Wrappers\SimpleModelWrapper;
use Karpack\Statusable\Events\StatusChanged;
use Karpack\Statusable\Traits\WrapperUpdatesStatus;

abstract class StatusableWrapper extends SimpleModelWrapper implements Statusable
{
    use WrapperUpdatesStatus;

     /**
     * Flag that controls the dispatch status change events
     * 
     * @var bool
     */
    protected $dispatchStatusEvents = true;

    /**
     * Saves the underlying model.
     * 
     * @return bool
     */
    public function save()
    {
        $canRaiseStatusChangeEvent = $this->hasStatusChanged();

        $result = $this->model->save();

        if ($canRaiseStatusChangeEvent && $this->dispatchStatusEvents) {
            $this->broadcast();

            Event::dispatch(new StatusChanged($this));
        }

        return $result;
    }

    /**
     * Sets the wrapper to avoid rasing status change events and broadcasting.
     * 
     * @return $this
     */
    public function withoutStatusEvents()
    {
        $this->dispatchStatusEvents = false;

        return $this;
    }
}