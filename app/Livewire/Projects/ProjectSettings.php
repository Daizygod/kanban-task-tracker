<?php

namespace App\Livewire\Projects;

use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProjectSettings extends Component
{
    public Project $project;

    public string $name = '';

    public string $description = '';

    public string $memberEmail = '';

    public string $newStatusName = '';

    public bool $newStatusFinal = false;

    public string $newPriorityName = '';

    public string $newPriorityColor = '#59A869';

    public string $newWorkTypeName = '';

    public string $newBoardName = '';

    public function mount(Project $project): void
    {
        abort_unless($project->hasMember(Auth::user()), 403);

        $this->project = $project;
        $this->name = $project->name;
        $this->description = (string) $project->description;
    }

    public function saveGeneral(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['name' => 'название', 'description' => 'описание']);

        $this->project->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
        ]);

        $this->dispatch('toast', message: 'Настройки проекта сохранены.');
    }

    // -------------------------------------------------------------- Участники

    public function addMember(): void
    {
        $this->validate(
            ['memberEmail' => ['required', 'email']],
            attributes: ['memberEmail' => 'email'],
        );

        $user = User::where('email', $this->memberEmail)->first();

        if (! $user) {
            $this->addError('memberEmail', 'Пользователь с таким email не зарегистрирован.');

            return;
        }

        if ($this->project->hasMember($user)) {
            $this->addError('memberEmail', 'Этот пользователь уже участник проекта.');

            return;
        }

        $this->project->members()->attach($user->id);
        $this->memberEmail = '';
        $this->dispatch('toast', message: "{$user->name} добавлен(а) в проект.");
    }

    public function removeMember(int $userId): void
    {
        if ($this->project->members()->count() === 1) {
            $this->dispatch('toast', message: 'Нельзя удалить последнего участника проекта.', type: 'error');

            return;
        }

        $this->project->members()->detach($userId);

        if ($userId === Auth::id()) {
            $this->redirectRoute('projects.index');
        }
    }

    // ---------------------------------------------------------------- Статусы

    public function renameStatus(int $statusId, string $newName): void
    {
        $newName = trim($newName);

        if ($newName === '') {
            $this->dispatch('toast', message: 'Название статуса не может быть пустым.', type: 'error');

            return;
        }

        $this->findStatus($statusId)->update(['name' => $newName]);
    }

    /**
     * Финальный статус ровно один: пометка другого статуса финальным
     * автоматически снимает флаг с прежнего (поведение радиокнопки).
     */
    public function makeFinal(int $statusId): void
    {
        $status = $this->findStatus($statusId);

        if ($status->is_final) {
            $this->dispatch('toast', message: 'Финальный статус должен быть ровно один — чтобы снять флаг, назначьте финальным другой статус.', type: 'error');

            return;
        }

        $this->project->statuses()->where('is_final', true)->update(['is_final' => false]);
        $status->update(['is_final' => true]);
    }

    public function moveStatus(int $statusId, string $direction): void
    {
        $statuses = $this->project->statuses()->get()->values();
        $index = $statuses->search(fn (Status $s) => $s->id === $statusId);

        $neighborIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $neighborIndex < 0 || $neighborIndex >= $statuses->count()) {
            return;
        }

        $current = $statuses[$index];
        $neighbor = $statuses[$neighborIndex];

        [$currentOrder, $neighborOrder] = [$current->order, $neighbor->order];
        $current->update(['order' => $neighborOrder]);
        $neighbor->update(['order' => $currentOrder]);
    }

    public function deleteStatus(int $statusId): void
    {
        $status = $this->findStatus($statusId);

        if ($status->is_final) {
            $this->dispatch('toast', message: 'Нельзя удалить финальный статус — сначала назначьте финальным другой.', type: 'error');

            return;
        }

        if ($status->tasks()->exists()) {
            $this->dispatch('toast', message: 'В этом статусе есть задачи — сначала переместите их.', type: 'error');

            return;
        }

        $status->delete();
    }

    public function addStatus(): void
    {
        $this->validate(
            ['newStatusName' => ['required', 'string', 'max:60']],
            attributes: ['newStatusName' => 'название статуса'],
        );

        if ($this->newStatusFinal) {
            $this->project->statuses()->where('is_final', true)->update(['is_final' => false]);
        }

        $this->project->statuses()->create([
            'name' => trim($this->newStatusName),
            'order' => ((int) $this->project->statuses()->max('order')) + 1,
            'is_final' => $this->newStatusFinal,
        ]);

        $this->reset('newStatusName', 'newStatusFinal');
    }

    private function findStatus(int $statusId): Status
    {
        return $this->project->statuses()->findOrFail($statusId);
    }

    // -------------------------------------------------------------- Приоритеты

    public function renamePriority(int $priorityId, string $newName): void
    {
        $newName = trim($newName);

        if ($newName === '') {
            $this->dispatch('toast', message: 'Название приоритета не может быть пустым.', type: 'error');

            return;
        }

        $this->findPriority($priorityId)->update(['name' => $newName]);
    }

    public function setPriorityColor(int $priorityId, string $color): void
    {
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return;
        }

        $this->findPriority($priorityId)->update(['color' => mb_strtoupper($color)]);
        $this->dispatch('task-saved');
    }

    /**
     * Приоритет «по умолчанию» ровно один — как финальный статус.
     */
    public function makeDefaultPriority(int $priorityId): void
    {
        $priority = $this->findPriority($priorityId);

        if ($priority->is_default) {
            $this->dispatch('toast', message: 'Приоритет по умолчанию должен быть ровно один — назначьте другим приоритет по умолчанию.', type: 'error');

            return;
        }

        $this->project->priorities()->where('is_default', true)->update(['is_default' => false]);
        $priority->update(['is_default' => true]);
    }

    public function movePriority(int $priorityId, string $direction): void
    {
        $priorities = $this->project->priorities()->get()->values();
        $index = $priorities->search(fn (Priority $p) => $p->id === $priorityId);

        $neighborIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $neighborIndex < 0 || $neighborIndex >= $priorities->count()) {
            return;
        }

        $current = $priorities[$index];
        $neighbor = $priorities[$neighborIndex];

        [$currentOrder, $neighborOrder] = [$current->order, $neighbor->order];
        $current->update(['order' => $neighborOrder]);
        $neighbor->update(['order' => $currentOrder]);
    }

    public function deletePriority(int $priorityId): void
    {
        $priority = $this->findPriority($priorityId);

        if ($priority->is_default) {
            $this->dispatch('toast', message: 'Нельзя удалить приоритет по умолчанию — сначала назначьте другой.', type: 'error');

            return;
        }

        if ($this->project->priorities()->count() === 1) {
            $this->dispatch('toast', message: 'Нельзя удалить последний приоритет проекта.', type: 'error');

            return;
        }

        // Задачи с удаляемым приоритетом переводятся на приоритет по умолчанию
        $default = $this->project->priorities()->where('is_default', true)->first();
        $priority->tasks()->update(['priority_id' => $default->id]);

        $priority->delete();
    }

    public function addPriority(): void
    {
        $this->validate([
            'newPriorityName' => ['required', 'string', 'max:60'],
            'newPriorityColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], attributes: ['newPriorityName' => 'название приоритета', 'newPriorityColor' => 'цвет']);

        $this->project->priorities()->create([
            'name' => trim($this->newPriorityName),
            'color' => mb_strtoupper($this->newPriorityColor),
            'order' => ((int) $this->project->priorities()->max('order')) + 1,
            'is_default' => false,
        ]);

        $this->reset('newPriorityName');
    }

    private function findPriority(int $priorityId): Priority
    {
        return $this->project->priorities()->findOrFail($priorityId);
    }

    // -------------------------------------------------------------- Типы работ

    public function addWorkType(): void
    {
        $this->validate(
            ['newWorkTypeName' => ['required', 'string', 'max:60']],
            attributes: ['newWorkTypeName' => 'название типа'],
        );

        $name = trim($this->newWorkTypeName);

        if ($this->project->workTypes()->where('name', $name)->exists()) {
            $this->addError('newWorkTypeName', 'Такой тип работы уже есть.');

            return;
        }

        $customCount = $this->project->workTypes()->where('is_standard', false)->count();

        $this->project->workTypes()->create([
            'name' => $name,
            'color' => WorkType::CUSTOM_COLORS[$customCount % count(WorkType::CUSTOM_COLORS)],
            'order' => ((int) $this->project->workTypes()->max('order')) + 1,
            'is_standard' => false,
        ]);

        $this->reset('newWorkTypeName');
    }

    /** Цвет можно менять у любого типа, включая стандартные */
    public function setWorkTypeColor(int $workTypeId, string $color): void
    {
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return;
        }

        $this->project->workTypes()->findOrFail($workTypeId)->update(['color' => mb_strtoupper($color)]);
    }

    public function deleteWorkType(int $workTypeId): void
    {
        $workType = $this->project->workTypes()->findOrFail($workTypeId);

        if ($workType->is_standard) {
            $this->dispatch('toast', message: 'Стандартные типы работ удалить нельзя.', type: 'error');

            return;
        }

        if ($workType->timeLogs()->exists()) {
            $this->dispatch('toast', message: 'На этот тип уже записано время — удалить нельзя.', type: 'error');

            return;
        }

        $workType->delete();
    }

    // ------------------------------------------------------------------ Доски

    public function addBoard(): void
    {
        $this->validate(
            ['newBoardName' => ['required', 'string', 'max:120']],
            attributes: ['newBoardName' => 'название доски'],
        );

        $board = $this->project->boards()->create([
            'name' => trim($this->newBoardName),
            'is_default' => false,
        ]);

        // Новая доска стартует со всеми задачами — лишние выключаются в задаче
        $board->tasks()->attach($this->project->tasks()->pluck('id'));

        $this->reset('newBoardName');
        $this->dispatch('toast', message: "Доска «{$board->name}» создана.");
    }

    public function renameBoard(int $boardId, string $newName): void
    {
        $newName = trim($newName);

        if ($newName === '') {
            $this->dispatch('toast', message: 'Название доски не может быть пустым.', type: 'error');

            return;
        }

        $this->project->boards()->findOrFail($boardId)->update(['name' => $newName]);
    }

    public function deleteBoard(int $boardId): void
    {
        $board = $this->project->boards()->findOrFail($boardId);

        if ($board->is_default) {
            $this->dispatch('toast', message: 'Дефолтную доску удалить нельзя.', type: 'error');

            return;
        }

        $board->delete();
    }

    public function render()
    {
        return view('livewire.projects.project-settings', [
            'members' => $this->project->members()->orderBy('name')->get(),
            'statuses' => $this->project->statuses()->withCount('tasks')->get(),
            'priorities' => $this->project->priorities()->withCount('tasks')->get(),
            'workTypes' => $this->project->workTypes()->withCount('timeLogs')->get(),
            'boards' => $this->project->boards()->withCount('tasks')->get(),
        ])->title("Настройки — {$this->project->name}");
    }
}
