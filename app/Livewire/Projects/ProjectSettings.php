<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Status;
use App\Models\User;
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

    public function toggleFinal(int $statusId): void
    {
        $status = $this->findStatus($statusId);

        if ($status->is_final && $this->isLastFinal($status)) {
            $this->dispatch('toast', message: 'В проекте должен остаться хотя бы один финальный статус.', type: 'error');

            return;
        }

        $status->update(['is_final' => ! $status->is_final]);
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

        if ($status->is_final && $this->isLastFinal($status)) {
            $this->dispatch('toast', message: 'Нельзя удалить единственный финальный статус.', type: 'error');

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

    private function isLastFinal(Status $status): bool
    {
        return $this->project->statuses()
            ->where('is_final', true)
            ->whereKeyNot($status->id)
            ->doesntExist();
    }

    public function render()
    {
        return view('livewire.projects.project-settings', [
            'members' => $this->project->members()->orderBy('name')->get(),
            'statuses' => $this->project->statuses()->withCount('tasks')->get(),
        ])->title("Настройки — {$this->project->name}");
    }
}
