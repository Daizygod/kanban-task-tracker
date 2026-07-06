<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('board_order')->default(0)->after('priority_id');
        });

        // Бэкфилл: фиксируем текущий визуальный порядок колонок
        // (по важности приоритета, затем по номеру)
        DB::statement(<<<'SQL'
            UPDATE tasks SET board_order = ranked.rn
            FROM (
                SELECT tasks.id, ROW_NUMBER() OVER (
                    PARTITION BY tasks.project_id, tasks.status_id
                    ORDER BY priorities."order" DESC NULLS LAST, tasks.number
                ) AS rn
                FROM tasks
                LEFT JOIN priorities ON priorities.id = tasks.priority_id
            ) ranked
            WHERE tasks.id = ranked.id
        SQL);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('board_order');
        });
    }
};
