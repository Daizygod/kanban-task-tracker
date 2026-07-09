<?php

namespace App\Support;

use App\Enums\TaskType;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

/**
 * Текстовый фильтр задач в стиле YouTrack: пары «поле: значение» + свободный текст.
 *
 * Поля: тип, статус, приоритет, исполнитель, автор, доска. Значение — одно слово
 * или фраза в кавычках («…» / "…"). Повтор поля или запятая в значении — ИЛИ,
 * разные поля — И. Всё остальное ищется по названию, описанию и номеру.
 * Спецзначения исполнителя: «я», «никто».
 */
class TaskFilter
{
    public const FIELDS = ['тип', 'статус', 'приоритет', 'исполнитель', 'автор', 'доска'];

    /**
     * Разбор строки: токены полей + оставшийся свободный текст.
     *
     * @return array{fields: array<string, list<string>>, text: string}
     */
    public static function parse(string $raw): array
    {
        $fields = [];

        $pattern = '/(?:^|\s)(' . implode('|', self::FIELDS) . ')\s*:\s*(«[^»]*»|"[^"]*"|\S+)/iu';

        $text = preg_replace_callback($pattern, function (array $m) use (&$fields) {
            $field = mb_strtolower($m[1]);
            $value = $m[2];

            if (str_starts_with($value, '"') || str_starts_with($value, '«')) {
                $values = [trim($value, '"«»')];
            } else {
                $values = explode(',', $value);
            }

            foreach ($values as $v) {
                if (trim($v) !== '') {
                    $fields[$field][] = trim($v);
                }
            }

            return ' ';
        }, $raw);

        return [
            'fields' => $fields,
            'text' => trim(preg_replace('/\s+/u', ' ', $text)),
        ];
    }

    /** Накладывает фильтр на запрос задач проекта */
    public static function apply($query, Project $project, string $raw): void
    {
        $raw = trim($raw);

        if ($raw === '') {
            return;
        }

        ['fields' => $fields, 'text' => $text] = self::parse($raw);

        foreach ($fields as $field => $values) {
            match ($field) {
                'тип' => self::applyType($query, $values),
                'статус' => $query->whereIn('status_id', self::matchIds($project->statuses, $values, fn ($s) => $s->name)),
                'приоритет' => $query->whereIn('priority_id', self::matchIds($project->priorities, $values, fn ($p) => $p->name)),
                'исполнитель' => self::applyUser($query, $project, $values, 'assignee_id', allowNobody: true),
                'автор' => self::applyUser($query, $project, $values, 'created_by', allowNobody: false),
                'доска' => self::applyBoard($query, $project, $values),
            };
        }

        if ($text !== '') {
            $query->where(function ($q) use ($text, $project) {
                $q->where('title', 'ilike', "%{$text}%")
                    ->orWhere('description', 'ilike', "%{$text}%")
                    // key провалидирован как [A-Z]{3}, интерполяция безопасна
                    ->orWhereRaw("'{$project->key}-' || number ilike ?", ["%{$text}%"]);
            });
        }
    }

    private static function applyType($query, array $values): void
    {
        $types = collect(TaskType::cases())
            ->filter(fn (TaskType $type) => collect($values)->contains(
                fn ($v) => mb_stripos($type->label(), $v) !== false || strcasecmp($type->value, $v) === 0
            ))
            ->map(fn (TaskType $type) => $type->value);

        $query->whereIn('type', $types->isEmpty() ? ['__none__'] : $types->all());
    }

    private static function applyUser($query, Project $project, array $values, string $column, bool $allowNobody): void
    {
        $values = collect($values);

        $wantNobody = $allowNobody && $values->contains(
            fn ($v) => in_array(mb_strtolower($v), ['никто', 'нет', 'пусто'], true)
        );

        $wantMe = $values->contains(fn ($v) => mb_strtolower($v) === 'я');

        $nameValues = $values->reject(
            fn ($v) => in_array(mb_strtolower($v), ['никто', 'нет', 'пусто', 'я'], true)
        )->values()->all();

        $ids = $nameValues === []
            ? []
            : self::matchIds($project->members, $nameValues, fn ($u) => $u->username.' '.$u->name);

        if ($wantMe) {
            $ids[] = Auth::id();
        }

        $query->where(function ($q) use ($ids, $wantNobody, $column) {
            if ($ids !== []) {
                $q->whereIn($column, $ids);
            }

            if ($wantNobody) {
                $q->orWhereNull($column);
            }

            if ($ids === [] && ! $wantNobody) {
                // Значение никому не соответствует — пустой результат, как в YouTrack
                $q->whereRaw('1 = 0');
            }
        });
    }

    private static function applyBoard($query, Project $project, array $values): void
    {
        $boards = $project->boards->filter(
            fn ($board) => collect($values)->contains(fn ($v) => mb_stripos($board->name, $v) !== false)
        );

        // Дефолтная доска содержит все задачи — она ничего не сужает
        if ($boards->contains(fn ($b) => $b->is_default)) {
            return;
        }

        if ($boards->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('boards', fn ($q) => $q->whereIn('boards.id', $boards->pluck('id')));
    }

    /**
     * ID элементов коллекции, в «стоге» которых встречается хоть одно из значений.
     *
     * @return list<int>
     */
    private static function matchIds($items, array $values, callable $haystack): array
    {
        return $items->filter(
            fn ($item) => collect($values)->contains(fn ($v) => mb_stripos($haystack($item), $v) !== false)
        )->pluck('id')->values()->all();
    }

    /**
     * Мета для клиентского автокомплита: поле → возможные значения.
     *
     * @return array<string, list<string>>
     */
    public static function meta(Project $project): array
    {
        return [
            'тип' => collect(TaskType::cases())->map(fn ($t) => $t->label())->all(),
            'статус' => $project->statuses->pluck('name')->all(),
            'приоритет' => $project->priorities->pluck('name')->all(),
            'исполнитель' => collect(['я', 'никто'])->concat($project->members->pluck('username'))->all(),
            'автор' => collect(['я'])->concat($project->members->pluck('username'))->all(),
            'доска' => $project->boards->pluck('name')->all(),
        ];
    }
}
