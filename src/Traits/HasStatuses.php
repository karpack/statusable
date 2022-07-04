<?php

namespace Karpack\Statusable\Traits;

use Karpack\Statusable\Models\Status;

trait HasStatuses
{
    /**
     * Returns an array of status identifiers used by the model
     * 
     * @return array
     */
    public abstract function statusIdentifiers();

    /**
     * Sets the status string on all the array conversion and hides the status_id field
     * 
     * @return void
     */
    public function initializeHasStatuses()
    {
        $this->append('status')->append('status_identifier');
    }

    /**
     * Returns all the statuses of this model.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function statuses()
    {
        return $this->hasMany(Status::class)->where('statusable_type', static::class);
    }

    /**
     * Returns the status relation of this model
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function statusModel()
    {
        return $this->morphOne(Status::class, 'statusable', null, 'id', $this->statusIdColumn());
    }

    /**
     * Returns the status name of the model.
     * 
     * @return string
     */
    public function getStatusAttribute()
    {
        $status = statuses()->find($this->{$this->statusIdColumn()});

        if (is_null($status)) {
            return '';
        }
        return $status->name;
    }

    /**
     * Returns the status identifier of the model.
     * 
     * @return string
     */
    public function getStatusIdentifierAttribute()
    {
        $status = statuses()->find($this->{$this->statusIdColumn()});

        if (is_null($status)) {
            return '';
        }
        return $status->identifier;
    }

    /**
     * Returns the column name where status id is stored.
     * 
     * @return string
     */
    protected function statusIdColumn()
    {
        return 'status_id';
    }
}