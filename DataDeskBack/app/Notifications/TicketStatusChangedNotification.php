<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * TicketStatusChangedNotification - การแจ้งเตือน Email เมื่อสถานะใบแจ้งซ่อมเปลี่ยน
 *
 * ส่ง Email ไปยังผู้สร้าง Ticket (created_by) เพื่อแจ้งว่า
 * สถานะใบแจ้งซ่อมมีการเปลี่ยนแปลง พร้อมระบุสถานะเดิมและสถานะใหม่
 *
 * สถานะที่รองรับ (statusMap):
 * - open       → เปิดงาน
 * - in_progress → กำลังดำเนินการ
 * - waiting_parts → รออะไหล่
 * - closed     → ปิดงาน
 *
 * @see TicketController::update()  ถูกเรียกใช้งานจาก method นี้
 */
class TicketStatusChangedNotification extends Notification
{
    use Queueable;

    /** @var int จำนวนครั้งที่ลองส่งซ้ำเมื่อล้มเหลว */
    public $tries = 3;

    /** @var array ระยะเวลาหน่วง (วินาที) ก่อนลองส่งซ้ำ [60s, 300s] */
    public $backoff = [60, 300];

    /**
     * สร้าง Notification instance
     *
     * @param  Ticket  $ticket     ข้อมูลใบแจ้งซ่อม
     * @param  string  $oldStatus  สถานะเดิม เช่น 'open', 'in_progress'
     * @param  string  $newStatus  สถานะใหม่ เช่น 'closed', 'waiting_parts'
     */
    public function __construct(
        protected Ticket $ticket,
        protected string $oldStatus,
        protected string $newStatus
    ) {}

    /**
     * กำหนดช่องทางการส่งแจ้งเตือน
     *
     * @param  object  $notifiable  ผู้รับแจ้งเตือน (User)
     * @return array  ช่องทาง ['mail']
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * สร้างเนื้อหา Email แจ้งเตือนการเปลี่ยนสถานะ
     *
     * แปลงสถานะจาก key ภาษาอังกฤษเป็นข้อความภาษาไทย
     * เนื้อหาประกอบด้วย:
     * - Subject: "แจ้งเตือน: สถานะใบแจ้งซ่อม #[ID] เปลี่ยน"
     * - หัวข้อ Ticket, สถานะเดิม, สถานะใหม่
     * - ปุ่ม Action ลิงก์ไปหน้ารายละเอียด
     *
     * @param  object  $notifiable  ผู้รับแจ้งเตือน
     * @return MailMessage  Email ที่พร้อมส่ง
     */
    public function toMail(object $notifiable): MailMessage
    {
        // แปลงสถานะเป็นภาษาไทย
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
