<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Видимость задач на пользовательских досках (дефолтная доска
        // показывает все задачи и pivot не использует)
        Schema::create('board_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['board_id', 'task_id']);
        });

        // Дефолтная доска для существующих проектов
        $now = now();

        foreach (DB::table('projects')->pluck('id') as $projectId) {
            DB::table('boards')->insert([
                'project_id' => $projectId,
                'name' => 'Все задачи',
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('board_task');
        Schema::dropIfExists('boards');
    }
};
