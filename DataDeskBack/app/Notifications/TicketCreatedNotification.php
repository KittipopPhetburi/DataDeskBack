<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * TicketCreatedNotification - การแจ้งเตือน Email เมื่อมีการสร้างใบแจ้งซ่อมใหม่
 *
 * ส่ง Email ไปยัง:
 * - Super Admin ทุกคน (ไม่จำกัดบริษัท/สาขา)
 * - Admin / Helpdesk / Technician ที่อยู่ในสาขาเดียวกัน
 * - ช่างประจำสาขา (technician_email ที่ตั้งค่าไว้ใน Branch)
 *
 * เนื้อหา Email:
 * - หัวข้อ (title) ของใบแจ้งซ่อม
 * - ระดับความสำคัญ (priority)
 * - รายละเอียด (description)
 * - ลิงก์ไปดูรายละเอียดใบแจ้งซ่อม
 *
 * @see TicketController::store()  ถูกเรียกใช้งานจาก method นี้
 */
class TicketCreatedNotification extends Notification
{
    use Queueable;

    /** @var int จำนวนครั้งที่ลองส่งซ้ำเมื่อล้มเหลว */
    public $tries = 1;

    /** @var array ระยะเวลาหน่วง (วินาที) ก่อนลองส่งซ้ำในแต่ละรอบ */
    public $backoff = [60, 300];

    /**
     * สร้าง Notification instance
     *
     * @param  Ticket  $ticket  ข้อมูลใบแจ้งซ่อมที่ถูกสร้างใหม่
     */
    public function __construct(
        protected Ticket $ticket
    ) {}

    /**
     * กำหนดช่องทางการส่งแจ้งเตือน
     *
     * @param  object  $notifiable  ผู้รับแจ้งเตือน (User หรือ AnonymousNotifiable)
     * @return array  ช่องทาง ['mail']
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * สร้างเนื้อหา Email แจ้งเตือนใบแจ้งซ่อมใหม่
     *
     * เนื้อหาประกอบด้วย:
     * - Subject: "แจ้งเตือน: ใบแจ้งซ่อมใหม่ #[Ticket ID]"
     * - ชื่อผู้รับ (ถ้าไม่มี ใช้ "เจ้าหน้าที่")
     * - หัวข้อ, ความสำคัญ, รายละเอียดของ Ticket
     * - ปุ่ม Action ลิงก์ไปหน้ารายละเอียด
     *
     * @param  object  $notifiable  ผู้รับแจ้งเตือน
     * @return MailMessage  Email ที่พร้อมส่ง
     */
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
