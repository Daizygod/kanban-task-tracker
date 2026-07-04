<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'minutes',
        'description',
        'logged_date',
    ];

    protected function casts(): array
    {
        return [
            'logged_date' => 'date',
        ];
    }

    /** «2 ч 30 м», «45 м», «3 ч» */
    protected function formattedDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                $hours = intdiv($this->minutes, 60);
                $minutes = $this->minutes % 60;

                return trim(
                    ($hours > 0 ? "{$hours} ч " : '').($minutes > 0 ? "{$minutes} м" : '')
                ) ?: '0 м';
            },
        );
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
