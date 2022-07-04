<?php

namespace Karpack\Statusable;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Karpack\Contracts\Statuses\StatusableModel;
use Karpack\Contracts\Statuses\StatusesManager;
use Karpack\Statusable\Models\Status;
use Karpack\Translations\Models\Locale;

class Statuses implements StatusesManager
{
    /**
     * Caches all the models with statuses
     * 
     * @var array
     */
    protected $modelsWithStatuses;

    /**
     * Caches all the registered statuses in DB with their translations.
     * 
     * @var \Illuminate\Support\Collection
     */
    protected $cachedStatuses;

    /**
     * A collection of status details stores in the application cache. This is used
     * for faster access to the id of statuses.
     * 
     * @var \Illuminate\Support\Collection
     */
    protected $cachedStatusIds;

    /**
     * Flag that toggles the cached status usage
     * 
     * @var bool
     */
    protected $useCachedStatuses;

    /**
     * Flag that toggles the cached status id usage.
     * 
     * @var bool
     */
    protected $useCachedStatusIds;

    /**
     * Status id cache key
     * 
     * @var string
     */
    protected $cacheKey;

    public function __construct($modelsWithStatuses = [], $useCachedStatuses = true, $useCachedStatusIds = true, $cacheKey = 'statuses')
    {
        $this->modelsWithStatuses = $modelsWithStatuses;
        $this->useCachedStatuses = $useCachedStatuses;
        $this->useCachedStatusIds = $useCachedStatusIds;
        $this->cacheKey = $cacheKey;
    }

    /**
     * Binds the status API routes into the controller.
     *
     * @return void
     */
    public static function routes()
    {
        Route::prefix('statuses')->namespace('Karpack\Statusable\Controllers')->group(function () {
            Route::get('/', 'StatusController@all');
            Route::patch('/{status}', 'StatusController@update');
        });
    }

    /**
     * Returns all the statuses of the given $className. If no $className is provided, we'll
     * return all the statuses.
     * 
     * @param string|\Illuminate\Database\Eloquent\Model|null $className
     * @return \Illuminate\Support\Collection<\Karpack\Statusable\Models\Status>
     */
    public function retrieve($className = null)
    {
        $query = Status::query();

        if (!is_null($className)) {
            if (!is_string($className)) {
                $className = get_class($className);
            }
            $query->where('statusable_type', $className);
        }

        return $query->get();
    }

    /**
     * Returns all the registered status on the application
     * 
     * @param \Illuminate\Support\Collection $request
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function all(Collection $request, $perPage = 25)
    {
        $perPage = $request->get('per_page', $perPage);

        $paginator = Status::query()->latest('id')->paginate($perPage);

        $this->paginatorCollection($paginator)->transform(function ($status) {
            return $status->append('property_translations');
        });
        return $paginator;
    }

    /**
     * Returns the item collection from the paginator.
     * 
     * @param \Illuminate\Pagination\AbstractPaginator $paginator
     * @return \Illuminate\Support\Collection
     */
    protected function paginatorCollection($paginator)
    {
        return $paginator->getCollection();
    }

    /**
     * Updates the translations of the given status with the given data and returns the same
     * model.
     * 
     * @param int $statusId
     * @param array $data
     * @return \Karpack\Statusable\Models\Status
     */
    public function update($statusId, array $data)
    {
        $status = Status::query()->findOrFail($statusId);

        $status->saveAllTranslations(collect($data));

        return $status;
    }

    /**
     * Loads all the registered statuses. We can't actually cache them, because of the need
     * to fetch the status translation based on the request locale. Instead we cache them
     * locally on the service, so that only one call is made throughout the lifecyle of request
     * 
     * @return \Illuminate\Support\Collection
     */
    public function loadAllStatuses()
    {
        if (isset($this->cachedStatuses)) {
            return $this->cachedStatuses;
        }
        return $this->cachedStatuses = Status::all();
    }

    /**
     * Finds the status model of the given query with the translations of the current request.
     * The flag `$useCache` can be set to false to load the status directly from the database
     * which is not required most of the time as the status will be already loaded somewhere
     * in the application, if not, we will load and cache it which will be useful for subsequent
     * requests.
     * 
     * @param int $statusId
     * @param bool|null $useCache
     * @return \Karpack\Statusable\Models\Status|null
     */
    public function find($statusId, $useCache = null)
    {
        if (is_null($statusId)) {
            return null;
        }
        $useCache = $useCache ?? $this->useCachedStatuses;

        if (!$useCache) {
            return Status::query()->find($statusId);
        }
        return $this->loadAllStatuses()->where('id', $statusId)->first();
    }

    /**
     * This function loads all the status ids registered in the application. The result is
     * locally cached and also cached at the application cache store. The parameter $reload
     * is used to trigger a fetch from database.
     * 
     * Caching the status id comes in handy as it is used throughout the application and a 
     * single request might need multiple status ids.
     * 
     * The returned collection is a collection of arrays with `id`, `statusable_type` and
     * `identifier` as the only fields in each one of the item.
     * 
     * @param bool|null $reload
     * @return \Illuminate\Support\Collection
     */
    public function loadStatusIds($reload = null)
    {
        $reload = $reload ?? !$this->useCachedStatusIds;

        if (!$reload && isset($this->cachedStatusIds) && $this->cachedStatusIds->isNotEmpty()) {
            return $this->cachedStatusIds;
        }
        $statusIds = $reload ? null : Cache::get($this->cacheKey);

        if (is_null($statusIds)) {
            $statuses = Status::query()->setEagerLoads([])->get();

            $statusIds = $statuses->map(function (Status $status) {
                return $status->makeHidden(['created_at', 'updated_at'])->toArray();
            })->all();

            Cache::put($this->cacheKey, $statusIds);
        }
        return $this->cachedStatusIds = collect($statusIds);
    }

    /**
     * Returns the status id of the given model and identifier. If the given status
     * does not exists for the model, we will try to create a new one and use the newly
     * created id.
     * 
     * @param string|\Illuminate\Database\Eloquent\Model $className
     * @param string $statusIdentifier
     * @return int
     */
    public function statusIdOf($className, $statusIdentifier)
    {
        if (!is_string($className)) {
            $className = get_class($className);
        }
        $statusIdDetails = $this->loadStatusIds()
            ->where('statusable_type', $className)
            ->where('identifier', $statusIdentifier)
            ->first();

        if (is_null($statusIdDetails)) {
            $statusIdDetails = $this->createStatus($className, $statusIdentifier)->toArray();
        }
        return $statusIdDetails['id'];
    }

    /**
     * Creates a new status entry for the given className and identifier, caches the same to local
     * and application cache store and returns it. If some sort of error happens when creating a
     * new status, an exception is thrown. This is important and status creation should not be
     * halted under any circumstances.
     * 
     * @param string $className
     * @param string $statusIdentifier
     * @return \App\Modules\Abstracts\Models\Status
     * @throws \Exception
     */
    protected function createStatus($className, $statusIdentifier)
    {
        $status = new Status();
        $status->statusable_type = $className;
        $status->identifier = $statusIdentifier;

        if (!$status->save()) {
            throw new Exception("Error creating new status $statusIdentifier for $className");
        }
        // English translation of the status is done such that, the identifier is converted
        // to a normal word or words with first letter of each word capitalized.
        $statusName = str_replace('_', ' ', Str::title($statusIdentifier));

        $status->saveTranslations(['name' => $statusName], Locale::ENGLISH);

        $this->loadAllStatuses()->add($status);
        $this->addStatusIdToCache($status->makeHidden(['created_at', 'updated_at'])->toArray());

        return $status;
    }

    /**
     * Adds the given status array to existing status ids collection and caches it into
     * the application cache store and local object cache.
     * 
     * @return \Illuminate\Support\Collection
     */
    protected function addStatusIdToCache(array $status)
    {
        $statusIds = $this->loadStatusIds();

        $statusIds->push($status);

        Cache::put($this->cacheKey, $statusIds->all());

        return $statusIds;
    }

    /**
     * Creates the missing statuses for the models defined in the `modelsWithStatus` property
     * 
     * @return void
     */
    public function createRegisteredModelStatuses()
    {
        foreach ($this->modelsWithStatuses as $modelClass) {
            $model = new $modelClass;

            if ($model instanceof StatusableModel) {
                $this->createStatusesOfModel($model);
            }
        }
    }

    /**
     * Iterate through all the status identifiers of a model class and create statuses if 
     * they does not already exists in the database. The newly create status is also loaded 
     * to the statusIds cache also.
     * 
     * @return void
     */
    protected function createStatusesOfModel(StatusableModel $model)
    {
        $registeredStatuses = $this->loadAllStatuses();

        foreach ($model->statusIdentifiers() as $statusIdentifier) {
            $registeredStatus = $registeredStatuses
                ->where('statusable_type', $className = get_class($model))
                ->where('identifier', $statusIdentifier)
                ->first();

            if (!is_null($registeredStatus)) {
                continue;
            }
            // Since the $registeredStatus turned out to be null, it is pretty sure
            // that the status identifier does not exists for the model. We will create
            // one using the bare minimum data we have. This won't contain any translations
            // That part is left for the presentation layer to take care of.
            $this->createStatus($className, $statusIdentifier);
        }
    }

    /**
     * Register models with statuses. This function comes in handy to load models from different 
     * service providers or packages. Since the `modelsWithStatus` will be used mainly to load the 
     * statuses at the time of deployment using `status:load` command, it is necessary that the 
     * service providers using this function are non-deferred. Deferred services won't be booted 
     * automatically and hence the models won't be registered here at the time of loading the 
     * command.
     * 
     * Either use non-deferred service provider or register model on the StatusServiceProvider.
     * 
     * @param \Illuminate\Database\Eloquent\Model|string
     */
    public function registerModelWithStatus($model)
    {
        if (!is_string($model)) {
            $model = get_class($model);
        }
        $this->modelsWithStatuses = array_unique(array_merge($this->modelsWithStatuses, $model));
    }

    /**
     * Returns all the models registered on the service that has statuses
     * 
     * @return array
     */
    public function registeredModels()
    {
        return $this->modelsWithStatuses;
    }
}
