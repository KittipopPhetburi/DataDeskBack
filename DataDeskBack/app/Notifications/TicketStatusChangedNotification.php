<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChangedNotification extends Notification
{
    use Queueable;

    public $tries = 3;
    public $backoff = [60, 300];

    public function __construct(
        protected Ticket $ticket,
        protected string $oldStatus,
        protected string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusMap = [
            'open' => 'เปิดงาน',
            'in_progress' => 'กำลังดำเนินการ',
            'waiting_parts' => 'รออะไหล่',
            'closed' => 'ปิดงาน',
        ];

        $oldText = $statusMap[$this->oldStatus] ?? $this->oldStatus;
        $newText = $statusMap[$this->newStatus] ?? $this->newStatus;

        return (new MailMessage)
            ->subject("แจ้งเตือน: สถานะใบแจ้งซ่อม #{$this->ticket->id} เปลี่ยน")
            ->greeting("สวัสดี " . ($notifiable->name ?? 'ผู้ใช้งาน'))
            ->line("ใบแจ้งซ่อมของคุณมีการเปลี่ยนสถานะ")
            ->line("หัวข้อ: {$this->ticket->title}")
            ->line("สถานะเดิม: {$oldText}")
            ->line("สถานะใหม่: {$newText}")
            ->action('ดูรายละเอียด', url("/tickets/{$this->ticket->id}"))
            ->line('ขอบคุณที่ใช้บริการ DataDesk');
    }
}
