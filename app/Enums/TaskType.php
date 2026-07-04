<?php

namespace App\Enums;

enum TaskType: string
{
    case Epic = 'epic';
    case Story = 'story';
    case Task = 'task';

    public function label(): string
    {
        return match ($this) {
            self::Epic => 'Эпик',
            self::Story => 'История',
            self::Task => 'Задача',
        };
    }

    /**
     * Какой тип может быть родителем у данного типа (null — родитель запрещён).
     */
    public function allowedParentType(): ?self
    {
        return match ($this) {
            self::Epic => null,
            self::Story => self::Epic,
            self::Task => self::Story,
        };
    }

    /**
     * Какой тип могут иметь потомки данного типа.
     */
    public function childType(): ?self
    {
        return match ($this) {
            self::Epic => self::Story,
            self::Story => self::Task,
            self::Task => null,
        };
    }
}
