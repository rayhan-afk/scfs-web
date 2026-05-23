<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PoRevisionResubmitted extends Notification implements ShouldBroadcast
{
    use Queueable;

    public string $nomorOrder;
    public string $namaMerchant;

    public function __construct(string $nomorOrder, string $namaMerchant)
    {
        $this->nomorOrder = $nomorOrder;
        $this->namaMerchant = $namaMerchant;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'PO Diajukan Ulang',
            'message' => "Merchant {$this->namaMerchant} mengajukan ulang PO {$this->nomorOrder} setelah revisi.",
            'url'     => route('lkbb.scf.approval'),
            'icon'    => 'info',
            'color'   => 'blue',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title'   => 'PO Diajukan Ulang',
            'message' => "Merchant {$this->namaMerchant} mengajukan ulang PO {$this->nomorOrder} setelah revisi.",
            'url'     => route('lkbb.scf.approval'),
            'icon'    => 'info',
            'color'   => 'blue',
        ]);
    }
}
