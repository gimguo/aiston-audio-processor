<?php

namespace App\Console\Commands;

use App\Models\AudioTask;
use App\Services\TranscriptionService;
use App\Jobs\ProcessAudioTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckTaskStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check-status {--limit=10 : Maximum number of tasks to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check status of processing audio tasks and update them accordingly';

    /**
     * Execute the console command.
     */
    public function handle(TranscriptionService $transcriptionService): int
    {
        $limit = (int) $this->option('limit');
        
        $this->info('Starting task status check...');
        
        // Получаем задачи в статусе "processing"
        $processingTasks = AudioTask::where('status', 'processing')
            ->whereNull('completed_at')
            ->orderBy('started_at')
            ->limit($limit)
            ->get();
        
        if ($processingTasks->isEmpty()) {
            $this->info('No processing tasks found.');
            return self::SUCCESS;
        }
        
        $this->info("Found {$processingTasks->count()} processing tasks.");
        
        $completedCount = 0;
        $failedCount = 0;
        $stillProcessingCount = 0;
        
        foreach ($processingTasks as $task) {
            try {
                $this->line("Checking task #{$task->id}...");
                
                // Проверяем статус в фейковом сервисе
                $statusResponse = $transcriptionService->checkProcessingStatus($task);
                
                switch ($statusResponse['status']) {
                    case 'completed':
                        $this->info("  Task #{$task->id} completed. Processing results...");
                        
                        // Запускаем обработку результатов
                        ProcessAudioTask::dispatch($task);
                        $completedCount++;
                        break;
                        
                    case 'failed':
                        $this->error("  Task #{$task->id} failed.");
                        
                        $task->markAsFailed('External service reported failure');
                        $failedCount++;
                        break;
                        
                    case 'processing':
                        $progress = $statusResponse['progress'] ?? 0;
                        $this->line("  Task #{$task->id} still processing... ({$progress}%)");
                        
                        // Обновляем метаданные с прогрессом
                        $metadata = $task->metadata ?? [];
                        $metadata['last_check'] = now()->toISOString();
                        $metadata['progress'] = $progress;
                        $task->update(['metadata' => $metadata]);
                        
                        $stillProcessingCount++;
                        break;
                }
                
            } catch (\Exception $e) {
                $this->error("  Error checking task #{$task->id}: {$e->getMessage()}");
                
                Log::error('Task status check failed', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $failedCount++;
            }
        }
        
        $this->newLine();
        $this->info('Task status check completed:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Completed', $completedCount],
                ['Failed', $failedCount],
                ['Still Processing', $stillProcessingCount],
            ]
        );
        
        // Проверяем "зависшие" задачи
        $this->checkStuckTasks();
        
        return self::SUCCESS;
    }
    
    /**
     * Проверяет и обрабатывает "зависшие" задачи
     */
    private function checkStuckTasks(): void
    {
        // Задачи, которые обрабатываются более 30 минут
        $stuckTasks = AudioTask::where('status', 'processing')
            ->where('started_at', '<', now()->subMinutes(30))
            ->get();
        
        if ($stuckTasks->isNotEmpty()) {
            $this->warn("Found {$stuckTasks->count()} stuck tasks (processing > 30 minutes).");
            
            foreach ($stuckTasks as $task) {
                $this->line("  Marking task #{$task->id} as failed (stuck).");
                
                $task->markAsFailed('Task stuck in processing state for more than 30 minutes');
                
                Log::warning('Task marked as stuck', [
                    'task_id' => $task->id,
                    'started_at' => $task->started_at,
                    'duration_minutes' => $task->started_at->diffInMinutes(now())
                ]);
            }
        }
    }
}
