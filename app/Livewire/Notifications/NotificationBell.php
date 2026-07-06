<?php

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    /** Клик по уведомлению: помечаем прочитанным и открываем задачу */
    public function openNotification(int $notificationId): void
    {
        $notification = Auth::user()->userNotifications()->find($notificationId);

        if (! $notification) {
            return;
        }

        $notification->update(['read_at' => now()]);
        $this->open = false;
        $this->dispatch('open-task', taskId: $notification->task_id);
    }

    public function markAllRead(): void
    {
        Auth::user()->userNotifications()->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function render()
    {
        $notifications = $this->open
            ? Auth::user()->userNotifications()
                ->with(['actor', 'task.project'])
                ->latest()
                ->limit(30)
                ->get()
            : collect();

        return view('livewire.notifications.notification-bell', [
            'unreadCount' => Auth::user()->userNotifications()->whereNull('read_at')->count(),
            'notifications' => $notifications,
        ]);
    }
}
