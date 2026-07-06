<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkType extends Model
{
    use HasFactory;

    /** Стандартные типы: создаются с проектом, их нельзя удалить */
    public const STANDARD = [
        ['name' => 'Обсуждение', 'color' => '#4A88C7', 'order' => 1],
        ['name' => 'Тестирование', 'color' => '#A36AC7', 'order' => 2],
        ['name' => 'Разработка', 'color' => '#59A869', 'order' => 3],
        ['name' => 'Документация', 'color' => '#D6AE58', 'order' => 4],
    ];

    /** Палитра для пользовательских типов (по кругу) */
    public const CUSTOM_COLORS = ['#E5493A', '#F6743E', '#3BA9BC', '#C75A8C', '#7A8087', '#59A869'];

    protected $fillable = [
        'project_id',
        'name',
        'color',
        'order',
        'is_standard',
    ];

    protected function casts(): array
    {
        return [
            'is_standard' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }
}
