<?php

namespace Tests\Unit;

use App\Models\AudioTask;
use App\Services\TranscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TranscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TranscriptionService();
    }

    /**
     * Тест генерации фейковых данных транскрибации
     */
    public function test_generates_fake_transcription_data(): void
    {
        $task = AudioTask::factory()->create();
        
        $result = $this->service->processAudio($task);
        
        $this->assertNotNull($result);
        $this->assertEquals($task->id, $result->audio_task_id);
        $this->assertIsArray($result->transcription_data);
        $this->assertNotEmpty($result->transcription_data);
        
        // Проверяем структуру первого сегмента
        $firstSegment = $result->transcription_data[0];
        $this->assertArrayHasKey('speaker', $firstSegment);
        $this->assertArrayHasKey('start', $firstSegment);
        $this->assertArrayHasKey('end', $firstSegment);
        $this->assertArrayHasKey('text', $firstSegment);
        $this->assertArrayHasKey('confidence', $firstSegment);
        
        // Проверяем типы данных
        $this->assertIsString($firstSegment['speaker']);
        $this->assertIsNumeric($firstSegment['start']);
        $this->assertIsNumeric($firstSegment['end']);
        $this->assertIsString($firstSegment['text']);
        $this->assertIsNumeric($firstSegment['confidence']);
        
        // Проверяем логику
        $this->assertGreaterThan($firstSegment['start'], $firstSegment['end']);
        $this->assertGreaterThanOrEqual(0, $firstSegment['confidence']);
        $this->assertLessThanOrEqual(1, $firstSegment['confidence']);
    }

    /**
     * Тест проверки статуса обработки
     */
    public function test_checks_processing_status(): void
    {
        $task = AudioTask::factory()->processing()->create();
        
        $status = $this->service->checkProcessingStatus($task);
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('status', $status);
        $this->assertArrayHasKey('progress', $status);
        
        $this->assertContains($status['status'], ['processing', 'completed', 'failed']);
        
        if ($status['status'] === 'processing') {
            $this->assertIsNumeric($status['progress']);
            $this->assertGreaterThanOrEqual(0, $status['progress']);
            $this->assertLessThanOrEqual(100, $status['progress']);
        }
    }

    /**
     * Тест создания логов сервиса
     */
    public function test_creates_service_logs(): void
    {
        $task = AudioTask::factory()->create();
        
        $this->service->processAudio($task);
        
        $this->assertDatabaseHas('service_logs', [
            'audio_task_id' => $task->id,
            'service_name' => 'transcription_service',
            'operation' => 'process_audio',
            'status' => 'success'
        ]);
    }

    /**
     * Тест разнообразия спикеров
     */
    public function test_generates_multiple_speakers(): void
    {
        $task = AudioTask::factory()->create();
        
        $result = $this->service->processAudio($task);
        
        $speakers = array_unique(array_column($result->transcription_data, 'speaker'));
        $this->assertGreaterThan(1, count($speakers));
        $this->assertContains('S1', $speakers);
        $this->assertContains('S2', $speakers);
    }

    /**
     * Тест последовательности временных меток
     */
    public function test_timestamps_are_sequential(): void
    {
        $task = AudioTask::factory()->create();
        
        $result = $this->service->processAudio($task);
        
        $segments = $result->transcription_data;
        
        for ($i = 1; $i < count($segments); $i++) {
            $this->assertGreaterThanOrEqual(
                $segments[$i - 1]['end'],
                $segments[$i]['start'],
                'Segment timestamps should be sequential'
            );
        }
    }
}
