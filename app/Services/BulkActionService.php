<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BulkActionService
{
    /**
     * Publish multiple records
     */
    public function publish(array $ids, Model $model, string $idColumn = 'id'): int
    {
        return $model->whereIn($idColumn, $ids)->update(['published_at' => now()]);
    }

    /**
     * Unpublish multiple records
     */
    public function unpublish(array $ids, Model $model, string $idColumn = 'id'): int
    {
        return $model->whereIn($idColumn, $ids)->update(['published_at' => null]);
    }

    /**
     * Soft delete multiple records
     */
    public function delete(array $ids, Model $model, string $idColumn = 'id'): int
    {
        return $model->whereIn($idColumn, $ids)->delete();
    }

    /**
     * Force delete multiple records
     */
    public function forceDelete(array $ids, Model $model, string $idColumn = 'id'): int
    {
        // Use forceDelete if the model uses SoftDeletes
        $count = 0;
        foreach ($model->whereIn($idColumn, $ids)->get() as $record) {
            $record->forceDelete();
            $count++;
        }
        return $count;
    }

    /**
     * Restore multiple soft-deleted records
     */
    public function restore(array $ids, Model $model, string $idColumn = 'id'): int
    {
        $count = 0;
        foreach ($model->withTrashed()->whereIn($idColumn, $ids)->get() as $record) {
            if (method_exists($record, 'restore')) {
                $record->restore();
                $count++;
            }
        }
        return $count;
    }

    /**
     * Activate multiple records
     */
    public function activate(array $ids, Model $model, string $idColumn = 'id'): int
    {
        return $model->whereIn($idColumn, $ids)->update(['active' => true]);
    }

    /**
     * Deactivate multiple records
     */
    public function deactivate(array $ids, Model $model, string $idColumn = 'id'): int
    {
        return $model->whereIn($idColumn, $ids)->update(['active' => false]);
    }

    /**
     * Update a column for multiple records
     */
    public function updateColumn(array $ids, Model $model, string $column, mixed $value, string $idColumn = 'id'): int
    {
        return $model->whereIn($idColumn, $ids)->update([$column => $value]);
    }

    /**
     * Validate that all IDs exist and are accessible
     */
    public function validateIds(array $ids, Model $model, string $idColumn = 'id'): bool
    {
        $count = $model->whereIn($idColumn, $ids)->count();
        return $count === count($ids);
    }

    /**
     * Get valid IDs from request (sanitize)
     */
    public function getValidIds(\Illuminate\Http\Request $request, Model $model, string $idColumn = 'id'): array
    {
        $ids = collect($request->input('bulk_select', []))
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Validate that IDs exist in the model
        if (empty($ids)) {
            return [];
        }

        return $model->whereIn($idColumn, $ids)->pluck($idColumn)->all();
    }
}
