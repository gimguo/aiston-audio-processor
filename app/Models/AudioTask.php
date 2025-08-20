<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AudioTask extends Model
{
    /** @use HasFactory<\Database\Factories\AudioTaskFactory> */
    use HasFactory;

    protected $fillable = [
        'audio_url',
        'audio_identifier',
        'status',
        'parameters',
        'metadata',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'parameters' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function transcriptionResult(): HasOne
    {
        return $this->hasOne(TranscriptionResult::class);
    }

    public function qualityAssessment(): HasOne
    {
        return $this->hasOne(QualityAssessment::class);
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isEvaluated(): bool
    {
        return $this->status === 'evaluated';
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsEvaluated(): void
    {
        $this->update(['status' => 'evaluated']);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
