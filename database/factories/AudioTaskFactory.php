<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AudioTask>
 */
class AudioTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['new', 'processing', 'completed', 'failed', 'evaluated'];
        
        return [
            'audio_url' => $this->faker->url() . '/audio.wav',
            'audio_identifier' => $this->faker->optional()->regexify('AUDIO_[0-9]{3}'),
            'status' => $this->faker->randomElement($statuses),
            'parameters' => [
                'quality' => $this->faker->randomElement(['low', 'medium', 'high']),
                'format' => $this->faker->randomElement(['wav', 'mp3', 'flac']),
                'sample_rate' => $this->faker->randomElement([16000, 44100, 48000]),
            ],
            'metadata' => [
                'source' => $this->faker->randomElement(['api', 'upload', 'import']),
                'client_id' => $this->faker->uuid(),
                'duration_estimate' => $this->faker->numberBetween(30, 600),
            ],
            'started_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => $this->faker->optional(0.5)->dateTimeBetween('-30 minutes', 'now'),
            'error_message' => $this->faker->optional(0.1)->sentence(),
        ];
    }
    
    /**
     * Состояние для новых задач
     */
    public function newTask(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'new',
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ]);
    }
    
    /**
     * Состояние для обрабатываемых задач
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => null,
            'error_message' => null,
        ]);
    }
    
    /**
     * Состояние для завершенных задач
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => $this->faker->dateTimeBetween('-2 hours', '-1 hour'),
            'completed_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'error_message' => null,
        ]);
    }
    
    /**
     * Состояние для неудачных задач
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => $this->faker->dateTimeBetween('-2 hours', '-1 hour'),
            'completed_at' => null,
            'error_message' => $this->faker->sentence(),
        ]);
    }
}
