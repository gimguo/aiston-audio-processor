<?php

namespace App\Services;

use App\Models\AudioTask;
use App\Models\TranscriptionResult;
use App\Models\ServiceLog;
use Illuminate\Support\Facades\Log;

class TranscriptionService
{
    /**
     * Обрабатывает аудио и возвращает результаты транскрибации и диоризации
     */
    public function processAudio(AudioTask $audioTask): TranscriptionResult
    {
        $startTime = microtime(true);
        
        try {
            Log::info('Starting transcription processing', ['task_id' => $audioTask->id]);
            
            // Имитируем обращение к внешнему сервису транскрибации
            $transcriptionData = $this->getFakeTranscriptionData();
            
            // Сохраняем результат
            $transcriptionResult = TranscriptionResult::create([
                'audio_task_id' => $audioTask->id,
                'transcription_data' => $transcriptionData,
                'raw_response' => json_encode([
                    'status' => 'completed',
                    'processing_time' => rand(30, 120),
                    'confidence' => rand(85, 98) / 100,
                    'segments' => $transcriptionData
                ])
            ]);
            
            $responseTime = (int)((microtime(true) - $startTime) * 1000);
            
            // Логируем успешную операцию
            ServiceLog::logSuccess(
                $audioTask->id,
                'transcription_service',
                'process_audio',
                [
                    'audio_url' => $audioTask->audio_url,
                    'audio_identifier' => $audioTask->audio_identifier,
                ],
                [
                    'segments_count' => count($transcriptionData),
                    'total_duration' => $this->calculateTotalDuration($transcriptionData),
                ],
                $responseTime
            );
            
            Log::info('Transcription processing completed', [
                'task_id' => $audioTask->id,
                'segments_count' => count($transcriptionData)
            ]);
            
            return $transcriptionResult;
            
        } catch (\Exception $e) {
            $responseTime = (int)((microtime(true) - $startTime) * 1000);
            
            // Логируем ошибку
            ServiceLog::logError(
                $audioTask->id,
                'transcription_service',
                'process_audio',
                $e->getMessage(),
                [
                    'audio_url' => $audioTask->audio_url,
                    'audio_identifier' => $audioTask->audio_identifier,
                ],
                $responseTime
            );
            
            throw $e;
        }
    }
    
    /**
     * Генерирует фейковые данные транскрибации и диоризации
     */
    private function getFakeTranscriptionData(): array
    {
        $speakers = ['S1', 'S2'];
        $phrases = [
            'S1' => [
                'Добрый день, как я могу помочь?',
                'Понял вас, сейчас проверю информацию.',
                'Да, я вижу вашу заявку в системе.',
                'Хорошо, я передам ваш запрос в соответствующий отдел.',
                'Спасибо за обращение, хорошего дня!',
                'Можете повторить ваш номер заказа?',
                'Сейчас я найду вашу заявку.',
                'Все понятно, мы решим этот вопрос.',
            ],
            'S2' => [
                'Здравствуйте, у меня проблема с заказом.',
                'Мой номер заказа 12345.',
                'Я заказывал товар неделю назад, но он до сих пор не пришел.',
                'Можете проверить статус доставки?',
                'Хорошо, буду ждать.',
                'Спасибо за помощь.',
                'До свидания.',
                'Когда примерно будет доставка?',
            ]
        ];
        
        $segments = [];
        $currentTime = 0.0;
        $segmentCount = rand(6, 12);
        
        for ($i = 0; $i < $segmentCount; $i++) {
            $speaker = $speakers[array_rand($speakers)];
            $text = $phrases[$speaker][array_rand($phrases[$speaker])];
            $duration = rand(20, 60) / 10; // От 2 до 6 секунд
            
            $segments[] = [
                'speaker' => $speaker,
                'start' => round($currentTime, 1),
                'end' => round($currentTime + $duration, 1),
                'text' => $text,
                'confidence' => rand(85, 98) / 100
            ];
            
            $currentTime += $duration + rand(5, 15) / 10; // Пауза между фразами
        }
        
        return $segments;
    }
    
    /**
     * Вычисляет общую продолжительность разговора
     */
    private function calculateTotalDuration(array $segments): float
    {
        if (empty($segments)) {
            return 0.0;
        }
        
        $lastSegment = end($segments);
        return $lastSegment['end'];
    }
    
    /**
     * Проверяет статус обработки (для периодических проверок)
     */
    public function checkProcessingStatus(AudioTask $audioTask): array
    {
        $startTime = microtime(true);
        
        try {
            // Имитируем проверку статуса во внешнем сервисе
            $statuses = ['processing', 'completed', 'failed'];
            $weights = [20, 75, 5]; // Вероятности статусов
            
            $status = $this->getWeightedRandomStatus($statuses, $weights);
            
            $response = [
                'status' => $status,
                'progress' => $status === 'processing' ? rand(10, 90) : 100,
                'estimated_completion' => $status === 'processing' ? rand(30, 300) : null,
            ];
            
            $responseTime = (int)((microtime(true) - $startTime) * 1000);
            
            ServiceLog::logSuccess(
                $audioTask->id,
                'transcription_service',
                'check_status',
                ['task_id' => $audioTask->id],
                $response,
                $responseTime
            );
            
            return $response;
            
        } catch (\Exception $e) {
            $responseTime = (int)((microtime(true) - $startTime) * 1000);
            
            ServiceLog::logError(
                $audioTask->id,
                'transcription_service',
                'check_status',
                $e->getMessage(),
                ['task_id' => $audioTask->id],
                $responseTime
            );
            
            throw $e;
        }
    }
    
    /**
     * Возвращает случайный статус с учетом весов
     */
    private function getWeightedRandomStatus(array $statuses, array $weights): string
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($statuses as $index => $status) {
            $currentWeight += $weights[$index];
            if ($random <= $currentWeight) {
                return $status;
            }
        }
        
        return $statuses[0];
    }
}