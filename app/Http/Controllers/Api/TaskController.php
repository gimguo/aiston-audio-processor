<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AudioTask;
use App\Jobs\ProcessAudioTask;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AudioTask::with(['transcriptionResult', 'qualityAssessment']);
        
        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Пагинация
        $perPage = min($request->get('per_page', 15), 100);
        $tasks = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'audio_url' => 'nullable|url',
                'audio_identifier' => 'nullable|string|max:255',
                'parameters' => 'nullable|array',
                'metadata' => 'nullable|array',
            ]);
            
            // Проверяем, что хотя бы один из параметров указан
            if (empty($validated['audio_url']) && empty($validated['audio_identifier'])) {
                throw ValidationException::withMessages([
                    'audio_source' => 'Either audio_url or audio_identifier must be provided'
                ]);
            }
            
            $task = AudioTask::create($validated);
            
            // Запускаем задачу в очереди
            ProcessAudioTask::dispatch($task);
            
            return response()->json([
                'success' => true,
                'message' => 'Audio processing task created successfully',
                'data' => $task->load(['transcriptionResult', 'qualityAssessment'])
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AudioTask $task): JsonResponse
    {
        $task->load(['transcriptionResult', 'qualityAssessment', 'serviceLogs']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $task->id,
                'audio_url' => $task->audio_url,
                'audio_identifier' => $task->audio_identifier,
                'status' => $task->status,
                'parameters' => $task->parameters,
                'metadata' => $task->metadata,
                'started_at' => $task->started_at,
                'completed_at' => $task->completed_at,
                'error_message' => $task->error_message,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
                'transcription_result' => $task->transcriptionResult ? [
                    'transcription_data' => $task->transcriptionResult->transcription_data,
                    'created_at' => $task->transcriptionResult->created_at,
                ] : null,
                'quality_assessment' => $task->qualityAssessment ? [
                    'assessment_data' => $task->qualityAssessment->assessment_data,
                    'overall_score' => $task->qualityAssessment->overall_score,
                    'detailed_scores' => $task->qualityAssessment->detailed_scores,
                    'score_grade' => $task->qualityAssessment->getScoreGrade(),
                    'created_at' => $task->qualityAssessment->created_at,
                ] : null,
                'service_logs' => $task->serviceLogs->map(function ($log) {
                    return [
                        'service_name' => $log->service_name,
                        'operation' => $log->operation,
                        'status' => $log->status,
                        'error_message' => $log->error_message,
                        'response_time_ms' => $log->response_time_ms,
                        'created_at' => $log->created_at,
                    ];
                })
            ]
        ]);
    }
}
