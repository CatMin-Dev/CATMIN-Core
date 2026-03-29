<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Logger\Services\SystemLogService;
use Modules\Webhooks\Services\WebhookDispatcher;

abstract class AbstractCrudController extends Controller
{
    protected string $modelClass;

    protected string $resource;

    /**
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * @var array<int, string>
     */
    protected array $searchable = [];

    /**
     * @var array<int, string>
     */
    protected array $filterable = [];

    /**
     * @var array<int, string>
     */
    protected array $sortable = ['id', 'created_at', 'updated_at'];

    /**
     * @var array<string, string>
     */
    protected array $webhookEvents = [];

    protected int $defaultPerPage = 20;

    protected int $maxPerPage = 100;

    public function index(Request $request): JsonResponse
    {
        /** @var Model $model */
        $model = new $this->modelClass();
        $query = $this->modelClass::query();

        $q = trim((string) $request->query('q', ''));
        if ($q !== '' && $this->searchable !== []) {
            $query->where(function ($builder) use ($q): void {
                foreach ($this->searchable as $index => $column) {
                    if ($index === 0) {
                        $builder->where($column, 'like', '%' . $q . '%');
                    } else {
                        $builder->orWhere($column, 'like', '%' . $q . '%');
                    }
                }
            });
        }

        foreach ($this->filterable as $column) {
            $value = $request->query($column);
            if ($value === null || $value === '') {
                continue;
            }
            $query->where($column, (string) $value);
        }

        $sortBy = (string) $request->query('sort_by', 'id');
        $sortDir = strtolower((string) $request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (!in_array($sortBy, $this->sortable, true)) {
            $sortBy = 'id';
        }

        $query->orderBy($sortBy, $sortDir);

        $perPage = (int) $request->query('per_page', $this->defaultPerPage);
        $perPage = max(1, min($this->maxPerPage, $perPage));

        $paginated = $query->paginate($perPage)->appends($request->query());

        return V1Response::success($paginated->items(), [
            'resource' => $this->resource,
            'pagination' => [
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->modelClass::query()->find($id);

        if (!$item) {
            return V1Response::error('not_found', ucfirst($this->resource) . ' not found.', 404);
        }

        return V1Response::success($item, [
            'resource' => $this->resource,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->extractPayload($request);
        if ($payload === []) {
            return V1Response::error('validation_error', 'No writable fields provided.', 422);
        }

        try {
            /** @var Model $item */
            $item = $this->modelClass::query()->create($payload);
        } catch (\Throwable $exception) {
            return V1Response::error('write_failed', 'Create failed.', 422, [
                'message' => Str::limit($exception->getMessage(), 220),
            ]);
        }

        $this->audit('created', $item->getKey(), $request);
        $this->dispatchWebhook('created', $item);

        return V1Response::success($item, [
            'resource' => $this->resource,
            'action' => 'created',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var Model|null $item */
        $item = $this->modelClass::query()->find($id);
        if (!$item) {
            return V1Response::error('not_found', ucfirst($this->resource) . ' not found.', 404);
        }

        $payload = $this->extractPayload($request);
        if ($payload === []) {
            return V1Response::error('validation_error', 'No writable fields provided.', 422);
        }

        try {
            $item->fill($payload);
            $item->save();
        } catch (\Throwable $exception) {
            return V1Response::error('write_failed', 'Update failed.', 422, [
                'message' => Str::limit($exception->getMessage(), 220),
            ]);
        }

        $this->audit('updated', $item->getKey(), $request);
        $this->dispatchWebhook('updated', $item);

        return V1Response::success($item->fresh(), [
            'resource' => $this->resource,
            'action' => 'updated',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var Model|null $item */
        $item = $this->modelClass::query()->find($id);
        if (!$item) {
            return V1Response::error('not_found', ucfirst($this->resource) . ' not found.', 404);
        }

        $snapshot = $item->toArray();

        try {
            $item->delete();
        } catch (\Throwable $exception) {
            return V1Response::error('delete_failed', 'Delete failed.', 422, [
                'message' => Str::limit($exception->getMessage(), 220),
            ]);
        }

        $this->audit('deleted', $id, $request);
        $this->dispatchWebhook('deleted', (object) $snapshot);

        return V1Response::success([
            'id' => $id,
            'deleted' => true,
        ], [
            'resource' => $this->resource,
            'action' => 'deleted',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractPayload(Request $request): array
    {
        /** @var Model $instance */
        $instance = new $this->modelClass();
        $table = $instance->getTable();

        $payload = [];

        foreach ($this->fillable as $column) {
            if (!$request->exists($column)) {
                continue;
            }

            if (!Schema::hasColumn($table, $column)) {
                continue;
            }

            $payload[$column] = $request->input($column);
        }

        return $payload;
    }

    protected function audit(string $action, mixed $id, Request $request): void
    {
        try {
            app(SystemLogService::class)->logAudit(
                'api.v1.' . $this->resource . '.' . $action,
                strtoupper($action) . ' ' . $this->resource . ' via API v1',
                [
                    'resource' => $this->resource,
                    'resource_id' => $id,
                    'path' => (string) $request->path(),
                    'method' => (string) $request->method(),
                    'ip' => (string) $request->ip(),
                    'auth_type' => $request->attributes->get('catmin_api_auth_type'),
                    'api_key_id' => $request->attributes->get('catmin_api_key_id'),
                    'api_key_name' => $request->attributes->get('catmin_api_key_name'),
                ],
                'info',
                'external-api-v1'
            );
        } catch (\Throwable) {
            // Keep API flow stable.
        }
    }

    protected function dispatchWebhook(string $action, mixed $item): void
    {
        $eventName = $this->webhookEvents[$action] ?? null;
        if ($eventName === null || $eventName === '') {
            return;
        }

        try {
            $payload = $item instanceof Model ? $item->toArray() : (array) $item;
            WebhookDispatcher::dispatch($eventName, [
                'resource' => $this->resource,
                'action' => $action,
                'data' => $payload,
            ]);
        } catch (\Throwable) {
            // webhook delivery failures are non-blocking for API writes
        }
    }
}
