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
        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_task_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('service_name'); // Название сервиса (транскрибация, LLM и т.д.)
            $table->string('operation'); // Операция (создание, проверка статуса, оценка)
            $table->enum('status', ['success', 'error', 'warning']); // Статус операции
            $table->json('request_data')->nullable(); // Данные запроса
            $table->json('response_data')->nullable(); // Данные ответа
            $table->text('error_message')->nullable(); // Сообщение об ошибке
            $table->integer('response_time_ms')->nullable(); // Время ответа в мс
            $table->timestamps();
            
            $table->index(['service_name', 'operation']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_logs');
    }
};
