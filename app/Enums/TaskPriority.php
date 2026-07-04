<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Minor = 'minor';
    case Normal = 'normal';
    case Major = 'major';
    case Critical = 'critical';
    case ShowStopper = 'show-stopper';

    public function label(): string
    {
        return match ($this) {
            self::Minor => 'Незначительный',
            self::Normal => 'Обычный',
            self::Major => 'Важный',
            self::Critical => 'Критический',
            self::ShowStopper => 'Блокер',
        };
    }

    /**
     * Цвет полоски приоритета на карточке (в духе YouTrack).
     */
    public function color(): string
    {
        return match ($this) {
            self::Minor => '#23a187',
            self::Normal => '#4bb4fd',
            self::Major => '#ffc84a',
            self::Critical => '#f6743e',
            self::ShowStopper => '#e5493a',
        };
    }

    public function sortWeight(): int
    {
        return match ($this) {
            self::ShowStopper => 5,
            self::Critical => 4,
            self::Major => 3,
            self::Normal => 2,
            self::Minor => 1,
        };
    }
}
