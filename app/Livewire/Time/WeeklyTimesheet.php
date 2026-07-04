<?php

namespace App\Livewire\Time;

use App\Models\TimeLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Учёт времени')]
class WeeklyTimesheet extends Component
{
    public int $viewedUserId;

    public string $weekStart;

    public function mount(?User $user = null): void
    {
        $user ??= Auth::user();

        abort_unless($this->canView($user), 403);

        $this->viewedUserId = $user->id;
        $this->weekStart = CarbonImmutable::now()->startOfWeek()->toDateString();
    }

    /** Смотреть можно себя и коллег по любому общему проекту */
    private function canView(User $user): bool
    {
        if ($user->id === Auth::id()) {
            return true;
        }

        $myProjectIds = Auth::user()->projects()->pluck('projects.id');

        return $user->projects()->whereIn('projects.id', $myProjectIds)->exists();
    }

    public function selectUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($this->canView($user)) {
            $this->viewedUserId = $user->id;
        }
    }

    public function previousWeek(): void
    {
        $this->weekStart = CarbonImmutable::parse($this->weekStart)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = CarbonImmutable::parse($this->weekStart)->addWeek()->toDateString();
    }

    public function currentWeek(): void
    {
        $this->weekStart = CarbonImmutable::now()->startOfWeek()->toDateString();
    }

    public function render()
    {
        $viewedUser = User::findOrFail($this->viewedUserId);
        $start = CarbonImmutable::parse($this->weekStart);

        $days = collect(range(0, 6))->map(fn (int $offset) => $start->addDays($offset));

        $logs = TimeLog::with(['task.project', 'task.status'])
            ->where('user_id', $viewedUser->id)
            ->whereBetween('logged_date', [$start->toDateString(), $start->addDays(6)->toDateString()])
            ->orderBy('id')
            ->get()
            ->groupBy(fn (TimeLog $log) => $log->logged_date->toDateString());

        $dayTotals = $days->mapWithKeys(fn ($day) => [
            $day->toDateString() => ($logs[$day->toDateString()] ?? collect())->sum('minutes'),
        ]);

        // Коллеги по всем моим проектам — их время тоже можно смотреть
        $myProjectIds = Auth::user()->projects()->pluck('projects.id');
        $teammates = User::whereHas('projects', fn ($q) => $q->whereIn('projects.id', $myProjectIds))
            ->orWhere('id', Auth::id())
            ->orderBy('name')
            ->get();

        return view('livewire.time.weekly-timesheet', [
            'viewedUser' => $viewedUser,
            'days' => $days,
            'logs' => $logs,
            'dayTotals' => $dayTotals,
            'weekTotal' => $dayTotals->sum(),
            'teammates' => $teammates,
            'isCurrentWeek' => $start->isSameWeek(CarbonImmutable::now()),
        ]);
    }
}
