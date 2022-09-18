<?php

namespace MediciVN\Core\Repositories\Interfaces;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    /**
     * Retrieve data array for populate field select
     *
     * @param string $column
     * @param string $key
     *
     * @return Collection
     */
    public function pluck(string $column, string $key = ''): Collection;

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Sync relations
     *
     * @param [type] $id
     * @param [type] $relation
     * @param [type] $attributes
     * @param boolean $detaching
     * 
     * @return mixed
     */
    public function sync($id, $relation, $attributes, $detaching = true): mixed;

    /**
     * Sync relations without detaching
     *
     * @param [type] $id
     * @param [type] $relation
     * @param [type] $attributes
     * 
     * @return mixed
     */
    public function syncWithoutDetaching($id, $relation, $attributes): mixed;

    /**
     * Alias of all method
     *
     * @param array $columns
     * @return Collection
     */
    public function get(array $columns = ['*']): Collection;

    /**
     * Retrieve the first record of repository
     *
     * @param array $columns
     * @return Model
     */
    public function first(array $columns = ['*']): Model;

    /**
     * Retrieve first model of repository, or return new Model
     *
     * @param array $attributes
     * @return mixed
     */
    public function firstOrNew(array $attributes = []): mixed;

    /**
     * Retrieve first model of repository, or create new Model
     *
     * @param array $attributes
     * @return mixed
     */
    public function firstOrCreate(array $attributes = []): mixed;

    /**
     * Retrieve all data of repository, paginated
     *
     * @param int $limit
     * @param array $columns
     *
     * @return mixed
     */
    public function paginate(int $limit = 15, array $columns = ['*']): mixed;

    /**
     * Retrieve all data of repository, simple paginated
     *
     * @param int $limit
     * @param array $columns
     *
     * @return mixed
     */
    public function simplePaginate(int $limit = 15, array $columns = ['*']): mixed;

    /**
     * Find data by id
     *
     * @param       $id
     * @param array $columns
     *
     * @return Model
     */
    public function find($id, array $columns = ['*']): Model;

    /**
     * Find data by field and value
     *
     * @param string $field
     * @param       $value
     * @param array $columns
     *
     * @return Collection
     */
    public function findByField(string $field, $value, array $columns = ['*']): Collection;

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     *
     * @return Collection
     */
    public function findWhere(array $where, array $columns = ['*']): Collection;

    /**
     * Find data by multiple values in one field
     *
     * @param string $field
     * @param array $values
     * @param array $columns
     *
     * @return Collection
     */
    public function findWhereIn(string $field, array $values, array $columns = ['*']): Collection;

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     *
     * @return Model
     */
    public function create(array $attributes): Model;

    /**
     * Update a entity in repository by id
     *
     * @param array $attributes
     * @param       $id
     *
     * @return Model
     */
    public function update(array $attributes, $id): Model;

    /**
     * Update or Create an entity in repository
     *
     * @param array $attributes
     * @param array $values
     *
     * @return Model
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * Delete an entity in repository by id
     *
     * @param $id
     *
     * @return boolean
     */
    public function delete($id): bool;

    /**
     * Order collection by a given column
     *
     * @param string $column
     * @param string $direction
     *
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'asc'): self;

    /**
     * Set the limit for query
     *
     * @param integer $limit
     * @return self
     */
    public function take(int $limit): self;

    /**
     * Load relations
     *
     * @param $relations
     *
     * @return $this
     */
    public function with($relations): self;

    /**
     * Add subselect queries to count the relations.
     *
     * @param  $relations
     * @return $this
     */
    public function withCount($relations): self;

    /**
     * Check if model has relation
     *
     * @param string $relation
     * @return self
     */
    public function has(string $relation): self;

    /**
     * Query Scope
     *
     * @param Closure $scope
     *
     * @return $this
     */
    public function scopeQuery(Closure $scope): self;

    /**
     * Reset Query Scope
     *
     * @return $this
     */
    public function resetScope(): self;

    /**
     * Trigger static method calls to the model
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed;

    /**
     * Trigger method calls to the model
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments): mixed;
}
