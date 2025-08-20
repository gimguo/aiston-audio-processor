<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLog extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceLogFactory> */
    use HasFactory;

    protected $fillable = [
        'audio_task_id',
        'service_name',
        'operation',
        'status',
        'request_data',
        'response_data',
        'error_message',
        'response_time_ms',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public function audioTask(): BelongsTo
    {
        return $this->belongsTo(AudioTask::class);
    }

    public static function logSuccess(
        ?int $audioTaskId,
        string $serviceName,
        string $operation,
        array $requestData = [],
        array $responseData = [],
        ?int $responseTimeMs = null
    ): self {
        return self::create([
            'audio_task_id' => $audioTaskId,
            'service_name' => $serviceName,
            'operation' => $operation,
            'status' => 'success',
            'request_data' => $requestData,
            'response_data' => $responseData,
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    public static function logError(
        ?int $audioTaskId,
        string $serviceName,
        string $operation,
        string $errorMessage,
        array $requestData = [],
        ?int $responseTimeMs = null
    ): self {
        return self::create([
            'audio_task_id' => $audioTaskId,
            'service_name' => $serviceName,
            'operation' => $operation,
            'status' => 'error',
            'request_data' => $requestData,
            'error_message' => $errorMessage,
            'response_time_ms' => $responseTimeMs,
        ]);
    }
}
