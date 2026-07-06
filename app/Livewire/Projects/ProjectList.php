<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Проекты')]
class ProjectList extends Component
{
    public bool $showCreateModal = false;

    public string $name = '';

    public string $key = '';

    public string $description = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'key' => [
                'required',
                'string',
                'regex:/^[A-Za-z]{3}$/',
                Rule::unique('projects', 'key'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'название',
            'key' => 'ключ',
            'description' => 'описание',
        ];
    }

    protected $messages = [
        'key.regex' => 'Ключ — ровно 3 латинские буквы, например BAC.',
        'key.unique' => 'Такой ключ уже занят другим проектом.',
    ];

    #[On('project-create-open')]
    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    public function updatedKey(string $value): void
    {
        $this->key = mb_strtoupper($value);
    }

    public function createProject(): void
    {
        $validated = $this->validate();

        $project = Project::create([
            'name' => $validated['name'],
            'key' => $validated['key'],
            'description' => $validated['description'] ?: null,
            'owner_id' => Auth::id(),
        ]);

        $this->redirectRoute('projects.board', ['project' => $project]);
    }

    public function render()
    {
        $projects = Auth::user()
            ->projects()
            ->with('owner')
            ->withCount(['tasks', 'members'])
            ->orderBy('name')
            ->get();

        return view('livewire.projects.project-list', [
            'projects' => $projects,
        ]);
    }
}
