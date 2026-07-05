<?php

use App\Livewire\Boards\ProjectBoard;
use App\Livewire\Tasks\TaskCreateModal;
use App\Livewire\Tasks\TaskModal;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$project = Project::find(1);
Auth::login(User::first());

$time = function (string $label, callable $fn) {
    $t = microtime(true);
    $fn();
    echo $label.': '.round((microtime(true) - $t) * 1000).'ms'.PHP_EOL;
};

// Прогрев
Livewire\Livewire::test(ProjectBoard::class, ['project' => $project, 'tab' => 'tasks']);

$time('ProjectBoard (полный)', fn () => Livewire\Livewire::test(ProjectBoard::class, ['project' => $project, 'tab' => 'tasks']));
$time('TaskModal', fn () => Livewire\Livewire::test(TaskModal::class, ['project' => $project]));
$time('TaskCreateModal', fn () => Livewire\Livewire::test(TaskCreateModal::class, ['project' => $project]));

$time('view только layout-страницы', function () use ($project) {
    view('livewire.boards.project-board', [
        'project' => $project,
        'tab' => 'tasks',
        'statuses' => $project->statuses,
        'rows' => [],
        'statusCounts' => [],
        'totalCards' => 0,
        'gridTemplate' => '1fr',
        'orphanRowTitle' => 'Без истории',
        'filter' => '',
        'collapsedRows' => [],
        'collapsedColumns' => [],
    ])->render();
});
