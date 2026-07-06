<?php

use App\Models\WorkType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->char('color', 7)->default('#7A8087');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_standard')->default(false);
            $table->timestamps();
        });

        // Стандартные типы для уже существующих проектов
        $now = now();

        foreach (DB::table('projects')->pluck('id') as $projectId) {
            DB::table('work_types')->insert(array_map(fn (array $type) => [
                'project_id' => $projectId,
                'name' => $type['name'],
                'color' => $type['color'],
                'order' => $type['order'],
                'is_standard' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ], WorkType::STANDARD));
        }

        Schema::table('time_logs', function (Blueprint $table) {
            $table->foreignId('work_type_id')->nullable()->constrained('work_types')->restrictOnDelete();
        });

        // Существующие записи времени относим к «Разработке» своего проекта
        DB::statement(<<<'SQL'
            UPDATE time_logs
            SET work_type_id = wt.id
            FROM tasks t
            JOIN work_types wt ON wt.project_id = t.project_id AND wt.name = 'Разработка' AND wt.is_standard
            WHERE time_logs.task_id = t.id
        SQL);

        Schema::table('time_logs', function (Blueprint $table) {
            $table->foreignId('work_type_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_type_id');
        });

        Schema::drop('work_types');
    }
};
