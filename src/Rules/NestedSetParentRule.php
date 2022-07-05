<?php

namespace MediciVN\Core\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Just a validation utility for Nested-set tree
 *
 * It will generate additional queries
 */
class NestedSetParentRule implements Rule
{
    /**
     * The id of the requested entity to update
     *
     * @var int|null
     */
    protected int|null $id;

    /**
     * The entity model class name
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Validation message
     *
     * @var string
     */
    protected string $message;

    /**
     * Create a new rule instance.
     *
     * @var string $table
     * @var int|null $id
     */
    public function __construct(string $model, int|null $id)
    {
        $this->id = $id;
        $this->model = App::make($model);
        $this->message = __('validation.parent_nested_sets.descendant');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // optional
        if (!$value) {
            return true;
        }

        if ($this->id) {
            return $this->validateOnUpdate($value);
        }

        return $this->validateOnCreate($value);
    }

    public function validateOnUpdate($parent_id): bool
    {
        if ((int)$this->id === (int)$parent_id) {
            $this->message = __('validation.parent_nested_sets.equal');
            return false;
        }

        $parent = $this->model->withoutGlobalScope('ignore_root')->find($parent_id);

        if (!$parent) {
            $this->message = __('validation.parent_nested_sets.exists');
            return false;
        }

        $entity = $this->model->find($this->id);

        // If the specified entity is not found, skip this validation step
        // TODO: not sure!!!
        if (!$entity) {
            return true;
        }

        // Parent entity cannot be descendant of specified entity
        return !($parent->lft > $entity->lft && $parent->lft < $entity->rgt);
    }

    public function validateOnCreate($parent_id): bool
    {
        $parent = $this->model->find($parent_id);

        if (!$parent) {
            $this->message = __('validation.parent_nested_sets.exists');
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
