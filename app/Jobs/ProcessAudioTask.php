<?php

namespace App\Jobs;

use App\Models\AudioTask;
use App\Services\TranscriptionService;
use App\Services\QualityAssessmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAudioTask implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 минут

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AudioTask $audioTask
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(
        TranscriptionService $transcriptionService,
        QualityAssessmentService $qualityAssessmentService
    ): void {
        try {
            Log::info('Starting audio task processing', ['task_id' => $this->audioTask->id]);
            
            // Отмечаем задачу как обрабатываемую
            $this->audioTask->markAsProcessing();
            
            // Имитируем задержку обработки
            sleep(rand(2, 5));
            
            // Получаем результаты транскрибации и диоризации
            $transcriptionResult = $transcriptionService->processAudio($this->audioTask);
            
            // Отмечаем задачу как завершенную
            $this->audioTask->markAsCompleted();
            
            // Запускаем оценку качества
            $qualityAssessmentService->assessQuality($this->audioTask, $transcriptionResult);
            
            // Отмечаем задачу как оцененную
            $this->audioTask->markAsEvaluated();
            
            Log::info('Audio task processing completed successfully', ['task_id' => $this->audioTask->id]);
            
        } catch (\Exception $e) {
            Log::error('Audio task processing failed', [
                'task_id' => $this->audioTask->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->audioTask->markAsFailed($e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Audio task job failed permanently', [
            'task_id' => $this->audioTask->id,
            'error' => $exception->getMessage()
        ]);
        
        $this->audioTask->markAsFailed('Job failed after maximum retries: ' . $exception->getMessage());
    }
}
