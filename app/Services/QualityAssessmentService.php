<?php

namespace App\Services;

use App\Models\AudioTask;
use App\Models\TranscriptionResult;
use App\Models\QualityAssessment;
use App\Models\ServiceLog;
use Illuminate\Support\Facades\Log;

class QualityAssessmentService
{
    /**
     * Оценивает качество разговора с помощью фейковой LLM-системы
     */
    public function assessQuality(AudioTask $audioTask, TranscriptionResult $transcriptionResult): QualityAssessment
    {
        $startTime = microtime(true);
        
        try {
            Log::info('Starting quality assessment', ['task_id' => $audioTask->id]);
            
            // Имитируем задержку обработки LLM
            sleep(rand(1, 3));
            
            // Анализируем транскрибацию
            $transcriptionData = $transcriptionResult->transcription_data;
            $assessmentData = $this->generateQualityAssessment($transcriptionData);
            
            // Сохраняем результат оценки
            $qualityAssessment = QualityAssessment::create([
                'audio_task_id' => $audioTask->id,
                'assessment_data' => $assessmentData,
                'overall_score' => $assessmentData['overall_score'],
                'detailed_scores' => $assessmentData['detailed_scores'],
                'llm_response' => json_encode([
                    'model' => 'fake-llm-v1.0',
                    'processing_time' => rand(1000, 3000),
                    'tokens_used' => rand(500, 1500),
                    'confidence' => rand(85, 95) / 100,
                    'analysis' => $assessmentData
                ])
            ]);
            
            $responseTime = (int)((microtime(true) - $startTime) * 1000);
            
            // Логируем успешную операцию
            ServiceLog::logSuccess(
                $audioTask->id,
                'llm_service',
                'assess_quality',
                [
                    'segments_count' => count($transcriptionData),
                    'total_duration' => $this->calculateTotalDuration($transcriptionData),
                ],
                [
                    'overall_score' => $assessmentData['overall_score'],
                    'categories_assessed' => count($assessmentData['detailed_scores']),
                ],
                $responseTime
            );
            
            Log::info('Quality assessment completed', [
                'task_id' => $audioTask->id,
                'overall_score' => $assessmentData['overall_score']
            ]);
            
            return $qualityAssessment;
            
        } catch (\Exception $e) {
            $responseTime = (int)((microtime(true) - $startTime) * 1000);
            
            // Логируем ошибку
            ServiceLog::logError(
                $audioTask->id,
                'llm_service',
                'assess_quality',
                $e->getMessage(),
                [
                    'segments_count' => count($transcriptionData ?? []),
                ],
                $responseTime
            );
            
            throw $e;
        }
    }
    
    /**
     * Генерирует фейковую оценку качества разговора
     */
    private function generateQualityAssessment(array $transcriptionData): array
    {
        // Анализируем различные аспекты разговора
        $metrics = $this->analyzeConversationMetrics($transcriptionData);
        
        // Генерируем оценки по категориям
        $detailedScores = [
            'politeness' => $this->assessPoliteness($transcriptionData),
            'clarity' => $this->assessClarity($transcriptionData),
            'responsiveness' => $this->assessResponsiveness($transcriptionData),
            'problem_resolution' => $this->assessProblemResolution($transcriptionData),
            'professionalism' => $this->assessProfessionalism($transcriptionData),
        ];
        
        // Вычисляем общую оценку
        $overallScore = array_sum($detailedScores) / count($detailedScores);
        
        return [
            'overall_score' => round($overallScore, 2),
            'detailed_scores' => $detailedScores,
            'conversation_metrics' => $metrics,
            'recommendations' => $this->generateRecommendations($detailedScores),
            'summary' => $this->generateSummary($transcriptionData, $overallScore),
            'assessed_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Анализирует метрики разговора
     */
    private function analyzeConversationMetrics(array $transcriptionData): array
    {
        $totalDuration = $this->calculateTotalDuration($transcriptionData);
        $speakerStats = $this->calculateSpeakerStats($transcriptionData);
        
        return [
            'total_duration' => $totalDuration,
            'total_segments' => count($transcriptionData),
            'speaker_distribution' => $speakerStats,
            'average_segment_length' => $totalDuration / max(count($transcriptionData), 1),
            'conversation_pace' => $this->calculateConversationPace($transcriptionData),
        ];
    }
    
    /**
     * Оценивает вежливость
     */
    private function assessPoliteness(array $transcriptionData): float
    {
        $politeWords = ['спасибо', 'пожалуйста', 'извините', 'добрый день', 'здравствуйте', 'до свидания'];
        $politeCount = 0;
        $totalWords = 0;
        
        foreach ($transcriptionData as $segment) {
            $words = explode(' ', mb_strtolower($segment['text']));
            $totalWords += count($words);
            
            foreach ($words as $word) {
                if (in_array(trim($word, '.,!?'), $politeWords)) {
                    $politeCount++;
                }
            }
        }
        
        $politenessRatio = $totalWords > 0 ? ($politeCount / $totalWords) * 100 : 0;
        return min(100, max(60, 70 + $politenessRatio * 30));
    }
    
    /**
     * Оценивает ясность речи
     */
    private function assessClarity(array $transcriptionData): float
    {
        // Базируется на средней уверенности распознавания
        $totalConfidence = 0;
        $segmentCount = count($transcriptionData);
        
        foreach ($transcriptionData as $segment) {
            $totalConfidence += $segment['confidence'] ?? 0.9;
        }
        
        $averageConfidence = $segmentCount > 0 ? $totalConfidence / $segmentCount : 0.9;
        return round($averageConfidence * 100, 2);
    }
    
    /**
     * Оценивает отзывчивость
     */
    private function assessResponsiveness(array $transcriptionData): float
    {
        // Анализируем время ответа между репликами разных спикеров
        $responseTimes = [];
        
        for ($i = 1; $i < count($transcriptionData); $i++) {
            $current = $transcriptionData[$i];
            $previous = $transcriptionData[$i - 1];
            
            if ($current['speaker'] !== $previous['speaker']) {
                $responseTime = $current['start'] - $previous['end'];
                $responseTimes[] = $responseTime;
            }
        }
        
        if (empty($responseTimes)) {
            return rand(75, 85);
        }
        
        $averageResponseTime = array_sum($responseTimes) / count($responseTimes);
        
        // Чем меньше время ответа, тем лучше отзывчивость
        if ($averageResponseTime <= 1.0) {
            return rand(90, 95);
        } elseif ($averageResponseTime <= 2.0) {
            return rand(80, 89);
        } elseif ($averageResponseTime <= 3.0) {
            return rand(70, 79);
        } else {
            return rand(60, 69);
        }
    }
    
    /**
     * Оценивает решение проблемы
     */
    private function assessProblemResolution(array $transcriptionData): float
    {
        $resolutionWords = ['решим', 'поможем', 'сделаем', 'исправим', 'передам', 'проверю'];
        $problemWords = ['проблема', 'ошибка', 'не работает', 'не получается'];
        
        $resolutionCount = 0;
        $problemCount = 0;
        
        foreach ($transcriptionData as $segment) {
            $text = mb_strtolower($segment['text']);
            
            foreach ($resolutionWords as $word) {
                if (strpos($text, $word) !== false) {
                    $resolutionCount++;
                }
            }
            
            foreach ($problemWords as $word) {
                if (strpos($text, $word) !== false) {
                    $problemCount++;
                }
            }
        }
        
        if ($problemCount === 0) {
            return rand(80, 90); // Нет проблем = хорошо
        }
        
        $resolutionRatio = $resolutionCount / $problemCount;
        return min(100, max(50, 60 + $resolutionRatio * 40));
    }
    
    /**
     * Оценивает профессионализм
     */
    private function assessProfessionalism(array $transcriptionData): float
    {
        $professionalWords = ['конечно', 'обязательно', 'разумеется', 'безусловно'];
        $unprofessionalWords = ['не знаю', 'не могу', 'не умею'];
        
        $professionalCount = 0;
        $unprofessionalCount = 0;
        
        foreach ($transcriptionData as $segment) {
            $text = mb_strtolower($segment['text']);
            
            foreach ($professionalWords as $word) {
                if (strpos($text, $word) !== false) {
                    $professionalCount++;
                }
            }
            
            foreach ($unprofessionalWords as $word) {
                if (strpos($text, $word) !== false) {
                    $unprofessionalCount++;
                }
            }
        }
        
        $score = 75 + $professionalCount * 5 - $unprofessionalCount * 10;
        return max(50, min(100, $score));
    }
    
    /**
     * Генерирует рекомендации
     */
    private function generateRecommendations(array $detailedScores): array
    {
        $recommendations = [];
        
        foreach ($detailedScores as $category => $score) {
            if ($score < 70) {
                $recommendations[] = $this->getRecommendationForCategory($category);
            }
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Отличная работа! Продолжайте в том же духе.';
        }
        
        return $recommendations;
    }
    
    /**
     * Возвращает рекомендацию для категории
     */
    private function getRecommendationForCategory(string $category): string
    {
        $recommendations = [
            'politeness' => 'Рекомендуется чаще использовать вежливые обращения и благодарности.',
            'clarity' => 'Стоит говорить более четко и медленно для лучшего понимания.',
            'responsiveness' => 'Рекомендуется быстрее отвечать на вопросы клиентов.',
            'problem_resolution' => 'Необходимо более активно предлагать решения проблем клиентов.',
            'professionalism' => 'Стоит использовать более профессиональную лексику.',
        ];
        
        return $recommendations[$category] ?? 'Требуется улучшение в данной области.';
    }
    
    /**
     * Генерирует краткое резюме
     */
    private function generateSummary(array $transcriptionData, float $overallScore): string
    {
        $speakerStats = $this->calculateSpeakerStats($transcriptionData);
        $duration = $this->calculateTotalDuration($transcriptionData);
        
        $grade = match (true) {
            $overallScore >= 90 => 'отличное',
            $overallScore >= 80 => 'хорошее',
            $overallScore >= 70 => 'удовлетворительное',
            default => 'требует улучшения'
        };
        
        return sprintf(
            'Разговор продолжительностью %.1f секунд между %d участниками. Качество обслуживания: %s (%.1f баллов).',
            $duration,
            count($speakerStats),
            $grade,
            $overallScore
        );
    }
    
    /**
     * Вычисляет статистику по спикерам
     */
    private function calculateSpeakerStats(array $transcriptionData): array
    {
        $stats = [];
        
        foreach ($transcriptionData as $segment) {
            $speaker = $segment['speaker'];
            if (!isset($stats[$speaker])) {
                $stats[$speaker] = [
                    'segments' => 0,
                    'total_time' => 0,
                    'word_count' => 0,
                ];
            }
            
            $stats[$speaker]['segments']++;
            $stats[$speaker]['total_time'] += $segment['end'] - $segment['start'];
            $stats[$speaker]['word_count'] += str_word_count($segment['text']);
        }
        
        return $stats;
    }
    
    /**
     * Вычисляет темп разговора
     */
    private function calculateConversationPace(array $transcriptionData): string
    {
        if (empty($transcriptionData)) {
            return 'unknown';
        }
        
        $totalDuration = $this->calculateTotalDuration($transcriptionData);
        $segmentCount = count($transcriptionData);
        
        $averageSegmentDuration = $totalDuration / $segmentCount;
        
        if ($averageSegmentDuration < 3) {
            return 'fast';
        } elseif ($averageSegmentDuration < 6) {
            return 'normal';
        } else {
            return 'slow';
        }
    }
    
    /**
     * Вычисляет общую продолжительность разговора
     */
    private function calculateTotalDuration(array $transcriptionData): float
    {
        if (empty($transcriptionData)) {
            return 0.0;
        }
        
        $lastSegment = end($transcriptionData);
        return $lastSegment['end'];
    }
}