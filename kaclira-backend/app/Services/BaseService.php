<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
    protected $model;

    /**
     * Get all records with pagination
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Find record by ID
     *
     * @param int $id
     * @return Model|null
     */
    public function findById(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Create new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update record
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->findById($id);
        if (!$record) {
            return false;
        }

        return $record->update($data);
    }

    /**
     * Delete record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $record = $this->findById($id);
        if (!$record) {
            return false;
        }

        return $record->delete();
    }

    /**
     * Search records
     *
     * @param string $query
     * @param array $fields
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, array $fields = [], int $perPage = 15): LengthAwarePaginator
    {
        $queryBuilder = $this->model->query();

        if (!empty($fields)) {
            $queryBuilder->where(function ($q) use ($query, $fields) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$query}%");
                }
            });
        }

        return $queryBuilder->paginate($perPage);
    }
}
