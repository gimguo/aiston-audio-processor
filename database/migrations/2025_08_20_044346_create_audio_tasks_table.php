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
        Schema::create('audio_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('audio_url')->nullable();
            $table->string('audio_identifier')->nullable();
            $table->enum('status', ['new', 'processing', 'completed', 'failed', 'evaluated'])->default('new');
            $table->json('parameters')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_tasks');
    }
};
