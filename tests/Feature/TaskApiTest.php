<?php

namespace Tests\Feature;

use App\Models\AudioTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private string $validToken = 'test-token-123';

    /**
     * Тест создания новой задачи
     */
    public function test_can_create_task_with_valid_token(): void
    {
        $response = $this->postJson('/api/tasks', [
            'audio_url' => 'https://example.com/audio.wav',
            'parameters' => ['quality' => 'high'],
            'metadata' => ['source' => 'test']
        ], [
            'Authorization' => 'Bearer ' . $this->validToken
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Audio processing task created successfully'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'audio_url',
                    'created_at'
                ]
            ]);

        $this->assertDatabaseHas('audio_tasks', [
            'audio_url' => 'https://example.com/audio.wav'
        ]);
    }

    /**
     * Тест создания задачи с идентификатором
     */
    public function test_can_create_task_with_identifier(): void
    {
        $response = $this->postJson('/api/tasks', [
            'audio_identifier' => 'AUDIO_123',
            'parameters' => ['format' => 'wav']
        ], [
            'Authorization' => 'Bearer ' . $this->validToken
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('audio_tasks', [
            'audio_identifier' => 'AUDIO_123'
        ]);
    }

    /**
     * Тест отклонения запроса без токена
     */
    public function test_rejects_request_without_token(): void
    {
        $response = $this->postJson('/api/tasks', [
            'audio_url' => 'https://example.com/audio.wav'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Authentication token is required'
            ]);
    }

    /**
     * Тест отклонения запроса с неверным токеном
     */
    public function test_rejects_request_with_invalid_token(): void
    {
        $response = $this->postJson('/api/tasks', [
            'audio_url' => 'https://example.com/audio.wav'
        ], [
            'Authorization' => 'Bearer invalid-token'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid authentication token'
            ]);
    }

    /**
     * Тест валидации - отсутствие аудио данных
     */
    public function test_validates_missing_audio_data(): void
    {
        $response = $this->postJson('/api/tasks', [
            'parameters' => ['quality' => 'high']
        ], [
            'Authorization' => 'Bearer ' . $this->validToken
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ])
            ->assertJsonValidationErrors(['audio_source']);
    }

    /**
     * Тест получения списка задач
     */
    public function test_can_get_tasks_list(): void
    {
        // Создаем тестовые задачи
        AudioTask::factory()->count(3)->create();

        $response = $this->getJson('/api/tasks', [
            'Authorization' => 'Bearer ' . $this->validToken
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'status',
                        'created_at'
                    ]
                ],
                'pagination'
            ]);
    }

    /**
     * Тест получения конкретной задачи
     */
    public function test_can_get_specific_task(): void
    {
        $task = AudioTask::factory()->create([
            'audio_url' => 'https://example.com/test.wav',
            'status' => 'completed'
        ]);

        $response = $this->getJson("/api/tasks/{$task->id}", [
            'Authorization' => 'Bearer ' . $this->validToken
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $task->id,
                    'audio_url' => 'https://example.com/test.wav',
                    'status' => 'completed'
                ]
            ]);
    }

    /**
     * Тест фильтрации по статусу
     */
    public function test_can_filter_tasks_by_status(): void
    {
        AudioTask::factory()->newTask()->create();
        AudioTask::factory()->completed()->create();
        AudioTask::factory()->failed()->create();

        $response = $this->getJson('/api/tasks?status=completed', [
            'Authorization' => 'Bearer ' . $this->validToken
        ]);

        $response->assertStatus(200);
        
        $tasks = $response->json('data');
        $this->assertCount(1, $tasks);
        $this->assertEquals('completed', $tasks[0]['status']);
    }
}
