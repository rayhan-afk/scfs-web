<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PoNeedsRevision extends Notification implements ShouldBroadcast
{
    use Queueable;

    public string $nomorOrder;
    public string $catatan;

    public function __construct(string $nomorOrder, string $catatan)
    {
        $this->nomorOrder = $nomorOrder;
        $this->catatan = $catatan;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'PO Perlu Revisi',
            'message' => "PO {$this->nomorOrder} diminta revisi oleh LKBB. Catatan: {$this->catatan}",
            'url'     => route('merchant.riwayat-po'),
            'icon'    => 'warning',
            'color'   => 'amber',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title'   => 'PO Perlu Revisi',
            'message' => "PO {$this->nomorOrder} diminta revisi oleh LKBB. Catatan: {$this->catatan}",
            'url'     => route('merchant.riwayat-po'),
            'icon'    => 'warning',
            'color'   => 'amber',
        ]);
    }
}
