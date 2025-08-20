<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quality_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_task_id')->constrained()->onDelete('cascade');
            $table->json('assessment_data'); // Результаты оценки качества
            $table->decimal('overall_score', 5, 2)->nullable(); // Общая оценка
            $table->json('detailed_scores')->nullable(); // Детальные оценки
            $table->text('llm_response')->nullable(); // Ответ от LLM
            $table->timestamps();
            
            $table->index('audio_task_id');
            $table->index('overall_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_assessments');
    }
};
