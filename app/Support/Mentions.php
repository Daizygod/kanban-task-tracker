<?php

namespace App\Support;

class Mentions
{
    /**
     * Логин: латиница/цифры/подчёркивание, внутри допустимы точки и дефисы,
     * но не в конце — чтобы точка после «@anna.» не попадала в логин.
     */
    public const USERNAME_PATTERN = '[a-z0-9_]+(?:[.\-][a-z0-9_]+)*';

    /**
     * Все @логины, упомянутые в тексте (в нижнем регистре, без дублей).
     *
     * @return list<string>
     */
    public static function usernames(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        preg_match_all('/@('.self::USERNAME_PATTERN.')/iu', $text, $matches);

        return array_values(array_unique(array_map('mb_strtolower', $matches[1])));
    }
}
