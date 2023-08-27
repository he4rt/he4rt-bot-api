<?php

namespace Heart\Team\Infrastructure\Factories;

use Heart\Team\Infrastructure\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->sentence(),
        ];
    }
}
