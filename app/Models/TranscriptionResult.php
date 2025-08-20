<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranscriptionResult extends Model
{
    /** @use HasFactory<\Database\Factories\TranscriptionResultFactory> */
    use HasFactory;

    protected $fillable = [
        'audio_task_id',
        'transcription_data',
        'raw_response',
    ];

    protected $casts = [
        'transcription_data' => 'array',
    ];

    public function audioTask(): BelongsTo
    {
        return $this->belongsTo(AudioTask::class);
    }

    public function getFormattedTranscription(): array
    {
        return $this->transcription_data ?? [];
    }
}
