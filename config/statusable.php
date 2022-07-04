<?php

return [
    /*
    |--------------------------------------------------------------------------
    | The models that support statuses
    |--------------------------------------------------------------------------
    |
    | The list of all the models that supports statuses. The one's registered here
    | will be seeded to the database when the `statuses:create` artisan command is
    | executed.
    |
    */
    'statusables' => [
        //\App\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache settings of the statuses
    |--------------------------------------------------------------------------
    |
    | Statuses and status_id can be a core component of application logic. Fetching 
    | the same on the go can be very inefficient. So by default, we cache them.
    | Statuses are memory cached in the service for a single request lifecycle, but
    | status_id is cached in the application cache repository.
    |
    */
    'cache_statuses' => env('STATUSABLE_CACHE_STATUSES', true),

    'cache_status_ids' => env('STATUSABLE_CACHE_STATUS_IDS', true),

    /*
    |--------------------------------------------------------------------------
    | Cache key of status ids
    |--------------------------------------------------------------------------
    |
    | The key that has to be used to store status ids in the application cache
    | repository
    */
    'cache_key' => env('STATUSABLE_CACHE_KEY', 'statuses'),
];
