<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewMerchantSubmission extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $merchant;

    public function __construct($merchant)
    {
        $this->merchant = $merchant;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pengajuan Merchant Baru',
            'message' => $this->merchant->nama_kantin . ' mengirim pengajuan merchant.',
            'url' => route('approval.merchant'),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Pengajuan Merchant Baru',
            'message' => $this->merchant->nama_kantin . ' mengirim pengajuan merchant.',
            'url' => route('approval.merchant'),
        ]);
    }
}