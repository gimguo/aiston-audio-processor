<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityAssessment extends Model
{
    /** @use HasFactory<\Database\Factories\QualityAssessmentFactory> */
    use HasFactory;

    protected $fillable = [
        'audio_task_id',
        'assessment_data',
        'overall_score',
        'detailed_scores',
        'llm_response',
    ];

    protected $casts = [
        'assessment_data' => 'array',
        'detailed_scores' => 'array',
        'overall_score' => 'decimal:2',
    ];

    public function audioTask(): BelongsTo
    {
        return $this->belongsTo(AudioTask::class);
    }

    public function getScoreGrade(): string
    {
        if (!$this->overall_score) {
            return 'N/A';
        }

        return match (true) {
            $this->overall_score >= 90 => 'Excellent',
            $this->overall_score >= 80 => 'Good',
            $this->overall_score >= 70 => 'Satisfactory',
            $this->overall_score >= 60 => 'Needs Improvement',
            default => 'Poor'
        };
    }
}
