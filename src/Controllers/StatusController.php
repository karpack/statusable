<?php

namespace Karpack\Statusable\Controllers;

use Illuminate\Http\Request;
use Karpack\Contracts\Statuses\StatusesManager;

class StatusController
{
    /**
     * Status service/repo
     * 
     * @var \Karpack\Contracts\Statuses\StatusesManager
     */
    protected $statusService;

    public function __construct(StatusesManager $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Returns a paginated list of all the registered statuses
     * 
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function all(Request $request)
    {
        return $this->statusService->all(collect($request->all()));
    }

    /**
     * Updates the translation of a status
     * 
     * @return \App\Modules\Abstracts\Models\Status
     */
    public function update(Request $request, $statusId)
    {
        return $this->statusService
            ->update($statusId, $request->all())
            ->append('property_translations');
    }
}