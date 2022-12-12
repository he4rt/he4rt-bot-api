<?php

declare(strict_types=1);

namespace Database\Factories\Gamefication;

use App\Models\Gamefication\ExperienceTable;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExperienceTableFactory extends Factory
{
    protected $model = ExperienceTable::class;

    public function definition(): array
    {
        return [
            'required' => 200
        ];
    }
}
