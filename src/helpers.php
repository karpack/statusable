<?php

use Karpack\Contracts\Statuses\StatusesManager;

if (!function_exists('statuses')) {
    /**
     * Get the statuses repo/service
     *
     * @return \Karpack\Contracts\Statuses\StatusesManager
     */
    function statuses()
    {
        return container()->make(StatusesManager::class);
    }
}

if (!function_exists('status_id')) {
    /**
     * Get the status id from status identifier string
     *
     * @param string|\Illuminate\Database\Eloquent\Model $className
     * @param string $statusIdentifier
     * @return int
     */
    function status_id($className, $statusIdentifier)
    {
        return statuses()->statusIdOf($className, $statusIdentifier);
    }
}