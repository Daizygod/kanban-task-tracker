<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'description',
        'owner_id',
    ];

    protected static function booted(): void
    {
        static::created(function (Project $project) {
            $project->statuses()->createMany([
                ['name' => 'Открыта', 'order' => 1, 'is_final' => false],
                ['name' => 'В работе', 'order' => 2, 'is_final' => false],
                ['name' => 'Завершена', 'order' => 3, 'is_final' => true],
            ]);

            $project->priorities()->createMany([
                ['name' => 'Бэклог', 'color' => '#7A8087', 'order' => 1, 'is_default' => true],
                ['name' => 'Обычный', 'color' => '#59A869', 'order' => 2, 'is_default' => false],
                ['name' => 'Срочный', 'color' => '#F6743E', 'order' => 3, 'is_default' => false],
                ['name' => 'P1', 'color' => '#E5493A', 'order' => 4, 'is_default' => false],
            ]);

            // Создатель автоматически становится участником
            $project->members()->syncWithoutDetaching([$project->owner_id]);
        });
    }

    protected function key(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => mb_strtoupper($value),
        );
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class)->orderBy('order');
    }

    public function priorities(): HasMany
    {
        return $this->hasMany(Priority::class)->orderBy('order');
    }

    public function defaultPriority(): Priority
    {
        return $this->priorities()->where('is_default', true)->firstOrFail();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->id)->exists();
    }
}
