<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Issue;

class IssueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Issue::class;

    /**
     * Define the model's default state.
     *
     * This will generate random titles, descriptions, status, and priority for testing.
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence(6), // Random 6-word title
            'description' => $this->faker->paragraph(), // Random paragraph for description
            'status' => $this->faker->randomElement(['open', 'in_progress', 'closed']), // Random status
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']), // Random priority
        ];
    }
}
