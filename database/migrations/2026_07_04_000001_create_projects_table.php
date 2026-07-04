<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->char('key', 3)->unique();
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->constrained('users');
            // Сквозной счётчик номеров задач внутри проекта (BAC-173)
            $table->unsignedInteger('next_task_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
