<?php

namespace Karpack\Statusable\Traits;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Karpack\Statusable\Events\StatusChangeBroadcasted;
use Karpack\Support\Traits\HasTransactionAwareness;

/**
 * Magic methods and properties
 * 
 * @property \Illuminate\Database\Eloquent\Model $model
 * @method \Illuminate\Database\Eloquent\Model model()
 * @method bool save()
 * @method string modelName()
 */
trait WrapperUpdatesStatus
{
    use HasTransactionAwareness;

    /**
     * Register all the model status mapped to the corresponding events that should be
     * raised.
     * 
     * @var array
     */
    protected $statusEvents = [];

    /**
     * Register all the broadcast identifiers here mapped to the corresponding status.
     * 
     * @var array
     */
    protected $statusBroadcasts = [];

    /**
     * The broadcasting channel name. By default, plural of the wrapper model name
     * is used.
     *
     * @var string
     */
    protected $broadcastChannel;

    /**
     * The key to be used for the broadcast data. By default, the wrapper model name is used.
     * The data is an array mapping the given key to this wrapper, which serializes to the 
     * array representation of the underlying model.
     * 
     * @var string
     */
    protected $broadcastKey;

    /**
     * Updates the status of the underlying wrapper model.
     * 
     * @param string $status
     * @return bool
     */
    public function updateStatus($status)
    {
        $this->setStatus($status);

        return $this->save();
    }

    /**
     * Sets a new status on the underlying model. This does not save it to the database.
     * 
     * @param string $status
     * @return static
     */
    public function setStatus($status)
    {
        $this->model->{$this->statusColumn()} = status_id($this->model, $status);

        return $this;
    }

    /**
     * Checks the status of the underlying model matches the given one.
     * 
     * @param string $status
     * @return bool
     */
    public function statusIs($status)
    {
        return $this->model->{$this->statusColumn()} === status_id($this->model, $status);
    }

    /**
     * Returns true if the model status is dirty
     * 
     * @return bool
     */
    public function hasStatusChanged()
    {
        return $this->model->isDirty($this->statusColumn());
    }

    /**
     * Returns the status id column name.
     * 
     * @return string
     */
    protected function statusColumn()
    {
        return 'status_id';
    }

    /**
     * Raise broadcast if the current status has a listener registered.
     * 
     * @return void
     */
    public function broadcast()
    {
        foreach ($this->registeredStatusBroadcasts() as $status => $identifier) {
            if (!$this->statusIs($status)) {
                continue;
            }
            $eventToExecute = new StatusChangeBroadcasted($this, $identifier);

            return $this->afterTransactionCommitted(fn () => Event::dispatch($eventToExecute));
        }
    }

    /**
     * Returns all the registered status events
     * 
     * @return array
     */
    public function registeredStatusEvents()
    {
        return $this->statusEvents;
    }

    /**
     * Returns all the registered status broadcast identifiers
     * 
     * @return array
     */
    public function registeredStatusBroadcasts()
    {
        return $this->statusBroadcasts;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastChannels()
    {
        if ($this->broadcastChannel) {
            return new PrivateChannel($this->broadcastChannel);
        }
        return new PrivateChannel(Str::plural($this->broadcastKey()));
    }

    /**
     * Returns the payload that has to be broadcasted.
     * 
     * @return array
     */
    public function broadcastData()
    {
        return [$this->broadcastKey() => $this];
    }

    /**
     * Returns the broadcast key
     * 
     * @return string
     */
    protected function broadcastKey()
    {
        if ($this->broadcastKey) {
            return $this->broadcastKey;
        }
        return $this->modelName();
    }
}