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
        Schema::create('transcription_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_task_id')->constrained()->onDelete('cascade');
            $table->json('transcription_data'); // Массив с данными транскрибации и диоризации
            $table->text('raw_response')->nullable(); // Сырой ответ от сервиса
            $table->timestamps();
            
            $table->index('audio_task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transcription_results');
    }
};
