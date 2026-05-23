<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PemasokRejected extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $catatan;

    public function __construct($catatan)
    {
        $this->catatan = $catatan;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pengajuan Pemasok Ditolak',
            'message' => 'Pengajuan pemasok ditolak. Alasan: ' . $this->catatan,
            'url' => route('pemasok.application-status'),
            'icon' => 'danger',
            'color' => 'red',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Pengajuan Pemasok Ditolak',
            'message' => 'Pengajuan pemasok ditolak. Alasan: ' . $this->catatan,
            'url' => route('pemasok.application-status'),
            'icon' => 'danger',
            'color' => 'red',
        ]);
    }
}
