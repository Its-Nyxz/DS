<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationsBell extends Component
{
    public $notifications = [];
    public $unreadCount = 0;
    public $dropdownOpen = false;

    public function mount(): void
    {
        // Hitung jumlah unread tanpa ambil semua notifikasi
        $this->unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();
    }

    public function loadNotifications(): void
    {
        $this->notifications = auth()->user()
            ->notifications()
            ->latest()
            ->limit(5)
            ->get();

        $this->unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();
    }

    public function markAsRead($notificationId = null): void
    {
        $user = auth()->user();

        if ($notificationId) {
            $user->unreadNotifications()->where('id', $notificationId)->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications()->update(['read_at' => now()]);
        }

        $this->loadNotifications();
    }

    public function render()
    {
        return view('livewire.notifications-bell');
    }
}
