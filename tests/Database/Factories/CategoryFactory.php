<?php

namespace MediciVN\Core\Tests\database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use MediciVN\Core\Tests\Models\Category;

class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'slug' => $this->faker->slug,
            'description' => $this->faker->text,
            'parent_id' => null,
            'lft' => null,
            'rgt' => null,
            'depth' => null
        ];
    }
}
