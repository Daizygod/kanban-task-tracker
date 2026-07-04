<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            // Всегда храним в минутах; в UI ввод в часах или минутах
            $table->unsignedInteger('minutes');
            $table->text('description')->nullable();
            $table->date('logged_date');
            $table->timestamps();

            $table->index(['user_id', 'logged_date']);
            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};
