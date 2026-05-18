<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class MerchantApproved extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Merchant Disetujui',
            'message' => 'Pengajuan merchant Anda telah disetujui dan toko sudah aktif.',
            'url' => route('merchant.dashboard'),
            'icon' => 'success',
            'color' => 'green',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Merchant Disetujui',
            'message' => 'Pengajuan merchant Anda telah disetujui dan toko sudah aktif.',
            'url' => route('merchant.dashboard'),
            'icon' => 'success',
            'color' => 'green',
        ]);
    }
}