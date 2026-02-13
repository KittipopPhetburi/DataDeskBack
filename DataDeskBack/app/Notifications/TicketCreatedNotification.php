<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Ticket $ticket
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("แจ้งเตือน: ใบแจ้งซ่อมใหม่ #{$this->ticket->id}")
            ->greeting("สวัสดี " . ($notifiable->name ?? 'เจ้าหน้าที่'))
            ->line("มีใบแจ้งซ่อมใหม่ถูกสร้างขึ้น")
            ->line("หัวข้อ: {$this->ticket->title}")
            ->line("ความสำคัญ: {$this->ticket->priority}")
            ->line("รายละเอียด: {$this->ticket->description}")
            ->action('ดูรายละเอียด', url("/tickets/{$this->ticket->id}"))
            ->line('กรุณาตรวจสอบและดำเนินการ')
            ->salutation('ขอแสดงความนับถือ');
    }
}
