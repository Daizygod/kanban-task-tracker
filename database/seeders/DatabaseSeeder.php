<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Exceptions\StatusChangeBlockedException;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    /** @var array<string, string> email => имя */
    private const USERS = [
        'anna@example.com' => 'Анна Смирнова',
        'boris@example.com' => 'Борис Кузнецов',
        'viktor@example.com' => 'Виктор Орлов',
        'galina@example.com' => 'Галина Ветрова',
    ];

    public const PASSWORD = 'password';

    public function run(): void
    {
        $users = collect(self::USERS)
            ->map(fn (string $name, string $email) => User::factory()->create([
                'name' => $name,
                'email' => $email,
                'password' => self::PASSWORD,
            ]))
            ->values();

        [$anna, $boris, $viktor, $galina] = $users->all();

        // Проект 1: стандартные статусы, создаются хуком автоматически
        $portal = Project::create([
            'name' => 'Разработка портала',
            'key' => 'POR',
            'description' => 'Клиентский веб-портал: личный кабинет, каталог, оформление заказов.',
            'owner_id' => $anna->id,
        ]);
        $portal->members()->syncWithoutDetaching($users->pluck('id'));

        // Проект 2: кастомный набор статусов вместо стандартного
        $mobile = Project::create([
            'name' => 'Мобильное приложение',
            'key' => 'MOB',
            'description' => 'iOS/Android-приложение для клиентов.',
            'owner_id' => $boris->id,
        ]);
        $mobile->members()->syncWithoutDetaching([$boris->id, $viktor->id, $galina->id]);

        $mobile->statuses()->delete();
        $mobile->statuses()->createMany([
            ['name' => 'Бэклог', 'order' => 1, 'is_final' => false],
            ['name' => 'В разработке', 'order' => 2, 'is_final' => false],
            ['name' => 'Ревью', 'order' => 3, 'is_final' => false],
            ['name' => 'Тестирование', 'order' => 4, 'is_final' => false],
            ['name' => 'Готово', 'order' => 5, 'is_final' => true],
            ['name' => 'Отменена', 'order' => 6, 'is_final' => true],
        ]);

        $this->seedProjectTasks($portal->fresh());
        $this->seedProjectTasks($mobile->fresh());

        $this->command?->info('Демо-пользователи (пароль у всех: «'.self::PASSWORD.'»):');
        foreach (self::USERS as $email => $name) {
            $this->command?->line("  {$email} — {$name}");
        }
    }

    private function seedProjectTasks(Project $project): void
    {
        $members = $project->members;
        $statuses = $project->statuses;
        $firstStatus = $statuses->first();

        $epicTitles = [
            'Личный кабинет пользователя',
            'Каталог и поиск',
            'Оформление и оплата заказа',
        ];

        $storyTitles = [
            'Регистрация и вход по email',
            'Восстановление пароля',
            'Фильтры по категориям',
            'Полнотекстовый поиск',
            'Корзина покупок',
            'Интеграция платёжного шлюза',
        ];

        $taskTitles = [
            'Сверстать форму', 'Написать валидацию', 'Покрыть тестами',
            'Настроить роутинг', 'Добавить обработку ошибок', 'Оптимизировать запросы',
            'Написать документацию', 'Провести код-ревью', 'Настроить кэширование',
            'Добавить логирование', 'Исправить адаптивность', 'Обновить зависимости',
        ];

        $allTasks = collect();

        foreach ($epicTitles as $eIdx => $epicTitle) {
            $epic = $this->makeTask($project, TaskType::Epic, $epicTitle, null, $members, $firstStatus);
            $allTasks->push($epic);

            foreach (array_slice($storyTitles, $eIdx * 2, 2) as $storyTitle) {
                $story = $this->makeTask($project, TaskType::Story, $storyTitle, $epic, $members, $firstStatus);
                $allTasks->push($story);

                $count = random_int(2, 4);
                for ($i = 0; $i < $count; $i++) {
                    $allTasks->push($this->makeTask(
                        $project,
                        TaskType::Task,
                        Arr::random($taskTitles).': '.mb_strtolower(mb_substr($storyTitle, 0, 30)),
                        $story,
                        $members,
                        $firstStatus,
                    ));
                }
            }
        }

        // «Сироты» для блоков «Без эпика» / «Без истории»
        $allTasks->push($this->makeTask($project, TaskType::Story, 'Техдолг: рефакторинг сервисного слоя', null, $members, $firstStatus));

        foreach (['Обновить CI-пайплайн', 'Починить flaky-тесты'] as $title) {
            $allTasks->push($this->makeTask($project, TaskType::Task, $title, null, $members, $firstStatus));
        }

        $this->seedDependencies($allTasks);

        // Разбрасываем задачи по статусам через moveToStatus — заодно пишутся логи
        foreach ($allTasks as $task) {
            $targetStatus = $statuses->random();

            if ($targetStatus->id === $task->status_id) {
                continue;
            }

            try {
                $task->moveToStatus($targetStatus, $members->random());
            } catch (StatusChangeBlockedException) {
                // Зависимость заблокировала финальный статус — так и задумано в демо
            }
        }

        $this->seedComments($allTasks, $members);
        $this->seedTimeLogs($allTasks, $members);
    }

    private function makeTask(
        Project $project,
        TaskType $type,
        string $title,
        ?Task $parent,
        Collection $members,
        Status $status,
    ): Task {
        return Task::create([
            'project_id' => $project->id,
            'type' => $type,
            'parent_id' => $parent?->id,
            'status_id' => $status->id,
            'title' => $title,
            'description' => random_int(0, 2) > 0
                ? "Подробное описание: {$title}.\n\nКритерии приёмки:\n- Функциональность реализована\n- Тесты проходят\n- Код прошёл ревью"
                : null,
            'priority' => Arr::random(TaskPriority::cases()),
            'created_by' => $members->random()->id,
            'assignee_id' => random_int(0, 3) > 0 ? $members->random()->id : null,
            'due_date' => random_int(0, 2) > 0
                ? CarbonImmutable::now()->addDays(random_int(-5, 21))->toDateString()
                : null,
        ]);
    }

    private function seedDependencies(Collection $allTasks): void
    {
        $plainTasks = $allTasks->filter(fn (Task $t) => $t->type === TaskType::Task)->values();

        $created = 0;
        $attempts = 0;

        while ($created < 5 && $attempts < 40) {
            $attempts++;

            /** @var Task $task */
            $task = $plainTasks->random();
            /** @var Task $dependsOn */
            $dependsOn = $plainTasks->random();

            if ($task->is($dependsOn) || $task->wouldCreateDependencyCycle($dependsOn)) {
                continue;
            }

            if ($task->dependsOn()->whereKey($dependsOn->id)->exists()) {
                continue;
            }

            $task->dependsOn()->attach($dependsOn->id);
            $created++;
        }
    }

    private function seedComments(Collection $allTasks, Collection $members): void
    {
        $bodies = [
            'Посмотрел, в целом ок, но нужно уточнить требования у заказчика.',
            'Заблокировано на стороне бэкенда, жду ответа.',
            'Готово к ревью, посмотрите пожалуйста.',
            'Перенёс на следующую неделю по договорённости.',
            'Добавил обработку граничных случаев, проверьте на стейдже.',
            'Есть вопрос по макету — обсудим на дейли.',
        ];

        foreach ($allTasks->random(min(12, $allTasks->count())) as $task) {
            foreach (range(1, random_int(1, 3)) as $_) {
                Comment::create([
                    'task_id' => $task->id,
                    'user_id' => $members->random()->id,
                    'body' => Arr::random($bodies),
                ]);
            }
        }
    }

    private function seedTimeLogs(Collection $allTasks, Collection $members): void
    {
        $descriptions = [
            'Разработка функциональности',
            'Исправление багов',
            'Код-ревью',
            'Созвон с командой',
            'Написание тестов',
            'Проектирование решения',
            null,
        ];

        $loggable = $allTasks->filter(fn (Task $t) => $t->type !== TaskType::Epic)->values();
        $monday = CarbonImmutable::now()->startOfWeek();

        foreach ($members as $member) {
            // Записи на текущую и предыдущую неделю
            foreach (range(-7, 6) as $dayOffset) {
                if (random_int(0, 2) === 0) {
                    continue;
                }

                foreach (range(1, random_int(1, 3)) as $_) {
                    TimeLog::create([
                        'task_id' => $loggable->random()->id,
                        'user_id' => $member->id,
                        'minutes' => Arr::random([30, 45, 60, 90, 120, 180, 240, 300]),
                        'description' => Arr::random($descriptions),
                        'logged_date' => $monday->addDays($dayOffset)->toDateString(),
                    ]);
                }
            }
        }
    }
}
