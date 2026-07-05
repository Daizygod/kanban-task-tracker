<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            // hex-цвет полоски/точки приоритета, например #E5493A
            $table->char('color', 7);
            $table->unsignedInteger('order')->default(1);
            // Ровно один приоритет проекта — «по умолчанию» для новых задач
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('priorities');
    }
};
