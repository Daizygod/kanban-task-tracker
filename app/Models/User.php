<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /** Акцентный цвет по умолчанию — фуксия */
    public const DEFAULT_ACCENT = '#FF318C';

    protected $fillable = [
        'name',
        'email',
        'password',
        'accent_color',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Проекты, в которых пользователь участвует */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    /** Проекты, созданные пользователем */
    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function accentColor(): string
    {
        return $this->accent_color ?: self::DEFAULT_ACCENT;
    }

    /** «255 49 140» — для CSS-переменной rgb(var(--accent)) */
    public static function hexToRgbTriplet(string $hex): string
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');

        return "{$r} {$g} {$b}";
    }

    /** Осветлённый вариант для hover: каждый канал на 18% ближе к белому */
    public static function hexLightenTriplet(string $hex): string
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');

        $mix = fn (int $c) => (int) round($c + (255 - $c) * 0.18);

        return $mix($r).' '.$mix($g).' '.$mix($b);
    }

    /** Инициалы для аватара-кружка */
    public function initials(): string
    {
        return mb_strtoupper(
            collect(preg_split('/\s+/', trim($this->name)))
                ->filter()
                ->take(2)
                ->map(fn (string $word) => mb_substr($word, 0, 1))
                ->implode('')
        );
    }
}
