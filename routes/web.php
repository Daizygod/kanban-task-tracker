<?php

use App\Livewire\Actions\Logout;
use App\Livewire\Boards\ProjectBoard;
use App\Livewire\Projects\ProjectList;
use App\Livewire\Projects\ProjectSettings;
use App\Livewire\Time\WeeklyTimesheet;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/projects');
Route::redirect('/dashboard', '/projects')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('projects', ProjectList::class)->name('projects.index');
    Route::get('projects/{project}/settings', ProjectSettings::class)->name('projects.settings');
    Route::get('projects/{project}/{tab?}', ProjectBoard::class)
        ->where('tab', 'epics|stories|tasks')
        ->name('projects.board');

    Route::get('time/{user?}', WeeklyTimesheet::class)->name('time.index');

    Route::view('profile', 'profile')->name('profile');

    Route::post('logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');
});

require __DIR__.'/auth.php';
