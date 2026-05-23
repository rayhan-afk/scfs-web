<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PemasokApproved extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pemasok Disetujui',
            'message' => 'Pengajuan pemasok Anda telah disetujui. Gudang sudah aktif dan siap menerima PO.',
            'url' => route('pemasok.dashboard'),
            'icon' => 'success',
            'color' => 'green',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Pemasok Disetujui',
            'message' => 'Pengajuan pemasok Anda telah disetujui. Gudang sudah aktif dan siap menerima PO.',
            'url' => route('pemasok.dashboard'),
            'icon' => 'success',
            'color' => 'green',
        ]);
    }
}
