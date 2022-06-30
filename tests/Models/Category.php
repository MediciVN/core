<?php

namespace MediciVN\Core\Tests\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MediciVN\Core\Tests\Database\Factories\CategoryFactory;
use MediciVN\Core\Traits\EloquentNestedSet;

class Category extends Model
{
    use EloquentNestedSet, HasFactory;

    const ROOT_ID = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'cover_image',
        'parent_id',
        'depth',
        'order',
        'status',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return CategoryFactory::new();
    }
}
