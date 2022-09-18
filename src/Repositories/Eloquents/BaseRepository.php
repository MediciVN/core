<?php

namespace MediciVN\Core\Repositories\Eloquents;

use Closure;
use MediciVN\Core\Enums\Conditions;
use MediciVN\Core\Enums\Operand;
use MediciVN\Core\Exceptions\RepositoryException;
use MediciVN\Core\Repositories\Interfaces\RepositoryInterface;
use MediciVN\Core\Traits\HasFilters;
use Illuminate\Container\Container as Application;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository implements RepositoryInterface
{
    use HasFilters;

    protected Model|Builder $model;

    protected Closure|null $scopeQuery;

    public function __construct(protected Application $app)
    {
        $this->model = $this->makeModel();
    }

    /** @inheritDoc */
    abstract public function model(): string;

    /** @inheritDoc */
    public function makeModel(): Model
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /** @inheritDoc */
    public function getModel(): Model
    {
        return $this->model;
    }

    /** @inheritDoc */
    public function resetModel(): void
    {
        $this->makeModel();
    }

    /** @inheritDoc */
    public function scopeQuery(Closure $scope): self
    {
        $this->scopeQuery = $scope;
        return $this;
    }

    /** @inheritDoc */
    public function pluck($column, $key = null): Collection
    {
        return $this->model->pluck($column, $key);
    }

    /** @inheritDoc */
    public function all(array $columns = ['*']): Collection
    {
        $this->applyScope();

        if ($this->model instanceof Builder) {
            $results = $this->model->get($columns);
        } else {
            $results = $this->model->all($columns);
        }

        $this->resetModel();
        $this->resetScope();

        return $this->resultParser($results);
    }

    /** @inheritDoc */
    public function sync($id, $relation, $attributes, $detaching = true): mixed
    {
        return $this->find($id)->{$relation}()->sync($attributes, $detaching);
    }

    /** @inheritDoc */
    public function syncWithoutDetaching($id, $relation, $attributes): mixed
    {
        return $this->sync($id, $relation, $attributes, false);
    }

    /** @inheritDoc */
    public function firstOrNew(array $attributes = []): mixed
    {
        $this->applyScope();

        $model = $this->model->firstOrNew($attributes);

        $this->resetModel();
        $this->resetScope();

        return $this->resultParser($model);
    }

    /** @inheritDoc */
    public function firstOrCreate(array $attributes = []): mixed
    {
        $this->applyScope();

        $model = $this->model->firstOrCreate($attributes);

        $this->resetModel();
        $this->resetScope();

        return $this->resultParser($model);
    }

    /** @inheritDoc */
    public function count(array $where = [], string $columns = '*'): int
    {
        $this->applyScope();

        if ($where) {
            $this->applyConditions($where);
        }

        $result = $this->model->count($columns);

        $this->resetModel();
        $this->resetScope();

        return $result;
    }

    /** @inheritDoc */
    public function get(array $columns = ['*']): Collection
    {
        return $this->all($columns);
    }

    /** @inheritDoc */
    public function first(array $columns = ['*']): Model
    {
        $this->applyScope();

        $result = $this->model->first($columns);

        $this->resetModel();
        $this->resetScope();

        return $this->resultParser($result);
    }

    /** @inheritDoc */
    public function paginate(int $limit = 15, array $columns = ['*'], string $method = 'paginate'): mixed
    {
        $this->applyScope();

        $result = $this->model->{$method}($limit, $columns);

        $this->resetModel();
        $this->resetScope();

        return $this->resultParser($result);
    }

    /** @inheritDoc */
    public function simplePaginate(int $limit = 15, array $columns = ['*']): mixed
    {
        return $this->paginate($limit, $columns, 'simplePaginate');
    }

    /** @inheritDoc */
    public function find($id, array $columns = ['*']): Model
    {
        $this->applyScope();

        $model = $this->model->findOrFail($id, $columns);

        $this->resetModel();
        $this->resetScope();

        return $this->resultParser($model);
    }

    /** @inheritDoc */
    public function findByField(string $field, $value = null, array $columns = ['*']): Collection
    {
        $this->applyScope();

        $models = $this->model->where($field, '=', $value)->get($columns);

        $this->resetModel();
        $this->resetScope();

        return $this->resultParser($models);
    }

    /** @inheritDoc */
    public function findWhere(array $where, array $columns = ['*']): Collection
    {
        $this->applyConditions($where);
        $this->applyScope();

        $models = $this->model->get($columns);

        $this->resetModel();
        $this->resetScope();

        return $this->resultParser($models);
    }

    /** @inheritDoc */
    public function findWhereIn(string $field, array $values, array $columns = ['*']): Collection
    {
        $this->applyScope();

        $models = $this->model->whereIn($field, $values)->get($columns);

        $this->resetModel();
        $this->resetScope();

        return $this->resultParser($models);
    }

    /** @inheritDoc */
    public function create(array $attributes): Model
    {
        $model = $this->model->newInstance($attributes);
        $model->save();
        $this->resetModel();
        return $this->resultParser($model);
    }

    /** @inheritDoc */
    public function update(array $attributes, $id): Model
    {
        $this->applyScope();

        $model = $this->model->findOrFail($id);
        $model->fill($attributes);
        $model->save();

        $this->resetModel();
        $this->resetScope();

        return $this->resultParser($model);
    }

    /** @inheritDoc */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $this->applyScope();

        $model = $this->model->updateOrCreate($attributes, $values);

        $this->resetModel();
        $this->resetScope();

        return $this->resultParser($model);
    }

    /** @inheritDoc */
    public function delete($id): bool
    {
        $this->applyScope();

        $model = $this->find($id);

        $this->resetModel();
        $this->resetScope();

        return $model->delete();
    }

    /** @inheritDoc */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->model = $this->model->orderBy($column, $direction);
        return $this;
    }

    /** @inheritDoc */
    public function take(int $limit): self
    {
        $this->model = $this->model->limit($limit);
        return $this;
    }

    /** @inheritDoc */
    public function with($relations): self
    {
        $this->model = $this->model->with($relations);
        return $this;
    }

    /** @inheritDoc */
    public function withCount($relations): self
    {
        $this->model = $this->model->withCount($relations);
        return $this;
    }

    /** @inheritDoc */
    public function has(string $relation): self
    {
        $this->model = $this->model->has($relation);
        return $this;
    }

    /** @inheritDoc */
    public function resultParser($result): mixed
    {
        return $result;
    }

    /** @inheritDoc */
    public function applyConditions(array $where): void
    {
        foreach ($where as $field => $value) {
            if (is_array($value)) {

                if (count($value) < 3) {
                    throw new RepositoryException("Invalid condition format");
                }

                $field = $value[0];
                $condition = $value[1];
                $val = $value[2];
                $operand = (isset($value[3]) ? isset($value[3]) : Operand::EQUAL)->name();

                switch ($condition) {
                    case Conditions::IN:
                        if (!is_array($val)) throw new RepositoryException("Input {$val} mus be an array");
                        $this->model = $this->model->whereIn($field, $val);
                        break;
                    case Conditions::NOT_IN:
                        if (!is_array($val)) throw new RepositoryException("Input {$val} mus be an array");
                        $this->model = $this->model->whereNotIn($field, $val);
                        break;
                    case Conditions::DATE:
                        $this->model = $this->model->whereDate($field, $operand, $val);
                        break;
                    case Conditions::DAY:
                        $this->model = $this->model->whereDay($field, $operand, $val);
                        break;
                    case Conditions::MONTH:
                        $this->model = $this->model->whereMonth($field, $operand, $val);
                        break;
                    case Conditions::YEAR:
                        $this->model = $this->model->whereYear($field, $operand, $val);
                        break;
                    case Conditions::EXISTS:
                        if (!($val instanceof Closure)) throw new RepositoryException("Input {$val} must be closure function");
                        $this->model = $this->model->whereExists($val);
                        break;
                    case Conditions::HAS:
                        if (!($val instanceof Closure)) throw new RepositoryException("Input {$val} must be closure function");
                        $this->model = $this->model->whereHas($field, $val);
                        break;
                    case Conditions::HAS_MORPH:
                        if (!($val instanceof Closure)) throw new RepositoryException("Input {$val} must be closure function");
                        $this->model = $this->model->whereHasMorph($field, $val);
                        break;
                    case Conditions::DOESNT_HAVE:
                        if (!($val instanceof Closure)) throw new RepositoryException("Input {$val} must be closure function");
                        $this->model = $this->model->whereDoesntHave($field, $val);
                        break;
                    case Conditions::DOESNT_HAVE_MORPH:
                        if (!($val instanceof Closure)) throw new RepositoryException("Input {$val} must be closure function");
                        $this->model = $this->model->whereDoesntHaveMorph($field, $val);
                        break;
                    case Conditions::BETWEEN:
                        if (!is_array($val)) throw new RepositoryException("Input {$val} mus be an array");
                        $this->model = $this->model->whereBetween($field, $val);
                        break;
                    case Conditions::BETWEEN_COLUMNS:
                        if (!is_array($val)) throw new RepositoryException("Input {$val} mus be an array");
                        $this->model = $this->model->whereBetweenColumns($field, $val);
                        break;
                    case Conditions::NOT_BETWEEN:
                        if (!is_array($val)) throw new RepositoryException("Input {$val} mus be an array");
                        $this->model = $this->model->whereNotBetween($field, $val);
                        break;
                    case Conditions::NOT_BETWEEN_COLUMNS:
                        if (!is_array($val)) throw new RepositoryException("Input {$val} mus be an array");
                        $this->model = $this->model->whereNotBetweenColumns($field, $val);
                        break;
                    case Conditions::RAW:
                        $this->model = $this->model->whereRaw($val);
                        break;
                    default:
                        $this->model = $this->model->where($field, $condition, $val);
                        break;
                }
            } else {
                $this->model = $this->model->where($field, '=', $value);
            }
        }
    }

    /** @inheritDoc */
    public function applyScope(): self
    {
        if (isset($this->scopeQuery) && is_callable($this->scopeQuery)) {
            $callback = $this->scopeQuery;
            $this->model = $callback($this->model);
        }

        return $this;
    }

    /** @inheritDoc */
    public function resetScope(): self
    {
        $this->scopeQuery = null;
        return $this;
    }

    /** @inheritDoc */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return call_user_func_array([new static(), $method], $arguments);
    }

    /** @inheritDoc */
    public function __call(string $method, array $arguments): mixed
    {
        return call_user_func_array([$this->model, $method], $arguments);
    }
}
