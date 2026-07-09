<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            // null — системное событие (сидер, консоль)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field', 30);
            $table->string('old_value', 300)->nullable();
            $table->string('new_value', 300)->nullable();
            $table->timestamp('created_at');

            $table->index(['task_id', 'created_at']);
        });

        // Бэкфилл «создания» для существующих задач, чтобы история не начиналась с пустоты
        DB::statement(<<<'SQL'
            INSERT INTO task_activities (task_id, user_id, field, created_at)
            SELECT id, created_by, 'created', created_at FROM tasks
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('task_activities');
    }
};
