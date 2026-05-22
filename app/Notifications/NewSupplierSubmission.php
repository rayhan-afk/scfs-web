<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSupplierSubmission extends Notification
{
    use Queueable;

    public $profile;

    /**
     * Create a new notification instance.
     */
    public function __construct($profile)
    {
        // Menyimpan data profile pemasok yang dikirim dari komponen
        $this->profile = $profile;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Sesuaikan channelnya, misalnya ke database atau email
        return ['database']; 
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Ada pengajuan Pemasok/Supplier baru menunggu approval.',
            'profile_id' => $this->profile->id,
            // Tambahkan data lain yang mau disimpan di notifikasi
        ];
    }
}