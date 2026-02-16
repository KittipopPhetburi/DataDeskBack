<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use App\Models\SystemSetting;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Http\Request;

/**
 * TicketController - คอนโทรลเลอร์จัดการใบแจ้งซ่อม (Ticket)
 *
 * รับผิดชอบ CRUD operations สำหรับใบแจ้งซ่อม พร้อมระบบเสริม:
 * - การแบ่งข้อมูลตาม role (super_admin, admin, user) และ branch
 * - Auto-generate Ticket ID ตาม ticket_prefix ของสาขา (เช่น HQ-001, BKK-001)
 * - บันทึกประวัติการเปลี่ยนแปลง (TicketHistory)
 * - ส่ง Email แจ้งเตือนเมื่อสร้าง/เปลี่ยนสถานะ/มอบหมายงาน
 * - Public tracking (ไม่ต้อง login) ค้นหาด้วย Serial Number หรือ Ticket ID
 */
class TicketController extends Controller
{
    /**
     * แสดงรายการใบแจ้งซ่อมทั้งหมด (พร้อม eager load relationships)
     *
     * Access Control:
     * - super_admin: เห็นทุก ticket ในระบบ
     * - admin/helpdesk/technician: เห็นเฉพาะ ticket ในบริษัท/สาขาตัวเอง
     * - user: เห็นเฉพาะ ticket ที่ตัวเองสร้าง
     *
     * รองรับ query params สำหรับ filter:
     * - status: กรองตามสถานะ (open, in_progress, waiting_parts, closed)
     * - priority: กรองตามความสำคัญ
     *
     * @param  Request  $request  HTTP request ที่มี user และ query params
     * @return \Illuminate\Http\JsonResponse  รายการ ticket เรียงตาม created_at desc
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['asset', 'creator', 'assignee', 'approver', 'closer', 'company', 'branch']);

        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);

            // Access Control: Filter by branch if user has strict branch
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
        }

        if ($user->role === 'user') {
            $query->where('created_by', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    /**
     * สร้างใบแจ้งซ่อมใหม่
     *
     * ขั้นตอนการทำงาน:
     * 1. Validate fields: title, description, priority (required)
     * 2. Auto-generate Ticket ID:
     *    - ถ้า Branch มี ticket_prefix → ใช้รูปแบบ "{prefix}-001" (เช่น HQ-001)
     *    - ถ้าไม่มี → ใช้รูปแบบ "T001", "T002", ...
     * 3. ตั้งค่า status เริ่มต้นเป็น 'open'
     * 4. บันทึกประวัติ "สร้างใบแจ้งซ่อม" ลง TicketHistory
     * 5. ส่ง Email แจ้งเตือน (ถ้าเปิดใช้งาน):
     *    - Super Admin ทุกคน
     *    - Admin/Helpdesk/Technician ในสาขาเดียวกัน
     *    - ช่างประจำสาขา (technician_email)
     *
     * @param  Request  $request  HTTP request ที่มี field: title, description, priority (required), branch_id, asset_id, etc.
     * @return \Illuminate\Http\JsonResponse  ข้อมูล ticket ที่สร้าง พร้อม relations (HTTP 201)
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'priority' => 'required|string',
        ]);

        $user = $request->user();
        $data = $request->all();

        // Auto-generate Ticket ID
        if (empty($data['id'])) {
            // ดึง ticket_prefix จาก Branch ของ user
            $branch = \App\Models\Branch::find($data['branch_id'] ?? $user->branch_id);
            $prefix = $branch?->ticket_prefix;

            if ($prefix) {
                // หา ticket ล่าสุดที่ขึ้นต้นด้วย prefix นี้ เช่น HQ-001, HQ-002
                $lastTicket = Ticket::where('id', 'LIKE', $prefix . '-%')
                    ->orderByRaw('CAST(SUBSTRING_INDEX(id, "-", -1) AS UNSIGNED) DESC')
                    ->first();

                $nextNum = 1;
                if ($lastTicket && preg_match('/-(\d+)$/', $lastTicket->id, $matches)) {
                    $nextNum = intval($matches[1]) + 1;
                }
                $data['id'] = $prefix . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
            } else {
                // Fallback: ใช้รูปแบบ T001, T002, ... (ไม่มี prefix)
                $lastTicket = Ticket::where('id', 'LIKE', 'T%')
                    ->whereRaw("id NOT LIKE '%-%'")
                    ->orderByRaw('CAST(SUBSTRING(id, 2) AS UNSIGNED) DESC')
                    ->first();

                $nextId = 'T001';
                if ($lastTicket && preg_match('/^T(\d+)$/', $lastTicket->id, $matches)) {
                    $num = intval($matches[1]) + 1;
                    $nextId = 'T' . str_pad($num, 3, '0', STR_PAD_LEFT);
                }
                $data['id'] = $nextId;
            }
        }

        // กำหนดค่าเริ่มต้น
        $data['created_by'] = $user->id;
        $data['company_id'] = $data['company_id'] ?? $user->company_id;
        $data['branch_id'] = $data['branch_id'] ?? $user->branch_id;
        $data['status'] = 'open';

        $ticket = Ticket::create($data);

        // บันทึกประวัติการสร้าง
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'action' => 'สร้างใบแจ้งซ่อม',
            'description' => "สร้างใบแจ้งซ่อม: {$ticket->title}",
            'user_id' => $user->id,
        ]);

        // ส่ง Email แจ้งเตือนเฉพาะช่าง (technician) ของบริษัทและสาขาเดียวกับ ticket
        if ($this->isEmailNotificationEnabled()) {
            // ส่งให้ Technician ที่อยู่บริษัทเดียวกัน + สาขาเดียวกันเท่านั้น
            $recipients = User::where('company_id', $ticket->company_id)
                ->where('branch_id', $ticket->branch_id)
                ->where('role', 'technician')
                ->whereNotNull('email')
                ->get();

            foreach ($recipients as $recipient) {
                try {
                    $recipient->notify(new TicketCreatedNotification($ticket));
                } catch (\Exception $e) {
                    \Log::warning('Failed to send ticket created notification: ' . $e->getMessage());
                }
            }
        }

        return response()->json($ticket->load(['asset', 'creator', 'assignee', 'approver', 'closer']), 201);
    }

    /**
     * แสดงรายละเอียดใบแจ้งซ่อมตาม ID (พร้อมประวัติการเปลี่ยนแปลง)
     *
     * Eager load: asset, creator, assignee, approver, closer, company, branch, histories.user
     *
     * @param  string  $id  รหัสใบแจ้งซ่อม เช่น "HQ-001" หรือ "T001"
     * @return \Illuminate\Http\JsonResponse  ข้อมูล ticket พร้อม relations ทั้งหมด
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  ถ้าไม่พบ ticket
     */
    public function show(string $id)
    {
        $ticket = Ticket::with(['asset', 'creator', 'assignee', 'approver', 'closer', 'company', 'branch', 'histories.user'])->findOrFail($id);
        return response()->json($ticket);
    }

    /**
     * อัปเดตข้อมูลใบแจ้งซ่อม
     *
     * จัดการกรณีพิเศษ:
     * 1. เปลี่ยนสถานะ:
     *    - บันทึกประวัติ "เปลี่ยนสถานะ" ลง TicketHistory
     *    - ถ้าเปลี่ยนเป็น 'closed' จะบันทึก closed_at และ closed_by
     *    - บันทึกผู้อนุมัติ (approved_by)
     *    - ส่ง Email แจ้งผู้สร้าง Ticket (TicketStatusChangedNotification)
     *
     * 2. มอบหมายงาน (assign):
     *    - ส่ง Email แจ้ง Technician ที่ถูก assign (TicketAssignedNotification)
     *
     * @param  Request  $request  HTTP request ที่มี field ที่ต้องการอัปเดต
     * @param  string   $id       รหัสใบแจ้งซ่อม
     * @return \Illuminate\Http\JsonResponse  ข้อมูล ticket ที่อัปเดตพร้อม relations
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  ถ้าไม่พบ ticket
     */
    public function update(Request $request, string $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = $request->user();
        $oldStatus = $ticket->status;

        $oldAssignee = $ticket->assigned_to;
        $ticket->update($request->all());

        // ถ้าสถานะเปลี่ยน → บันทึกประวัติ + ส่ง Email
        if ($request->has('status') && $request->status !== $oldStatus) {
            $statusText = match ($request->status) {
                'open' => 'เปิดงาน',
                'in_progress' => 'กำลังดำเนินการ',
                'waiting_parts' => 'รออะไหล่',
                'closed' => 'ปิดงาน',
                default => $request->status,
            };

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'action' => 'เปลี่ยนสถานะ',
                'description' => "เปลี่ยนสถานะเป็น: {$statusText}",
                'user_id' => $user->id,
            ]);

            // ถ้าปิดงาน → บันทึกวันที่ปิดและผู้ปิด
            if ($request->status === 'closed') {
                $ticket->update(['closed_at' => now(), 'closed_by' => $user->id]);
            }

            // บันทึกผู้อนุมัติ/ผู้เปลี่ยนสถานะ
            $ticket->update(['approved_by' => $user->id]);

            // ส่ง Email แจ้งผู้สร้าง Ticket ว่าสถานะเปลี่ยน
            if ($this->isEmailNotificationEnabled()) {
                $creator = User::find($ticket->created_by);
                if ($creator && $creator->email) {
                    try {
                        $creator->notify(new TicketStatusChangedNotification($ticket, $oldStatus, $request->status));
                    } catch (\Exception $e) {
                        \Log::warning('Failed to send status change notification: ' . $e->getMessage());
                    }
                }
            }
        }

        // ส่ง Email แจ้ง Technician เมื่อถูก assign งานใหม่
        if ($request->has('assigned_to') && $request->assigned_to != $oldAssignee && $request->assigned_to) {
            if ($this->isEmailNotificationEnabled()) {
                $assignee = User::find($request->assigned_to);
                if ($assignee && $assignee->email) {
                    try {
                        $assignee->notify(new TicketAssignedNotification($ticket));
                    } catch (\Exception $e) {
                        \Log::warning('Failed to send assignment notification: ' . $e->getMessage());
                    }
                }
            }
        }

        return response()->json($ticket->load(['asset', 'creator', 'assignee', 'approver', 'closer']));
    }

    /**
     * ลบใบแจ้งซ่อม
     *
     * @param  string  $id  รหัสใบแจ้งซ่อม
     * @return \Illuminate\Http\JsonResponse  ข้อความยืนยันการลบ
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  ถ้าไม่พบ ticket
     */
    public function destroy(string $id)
    {
        Ticket::findOrFail($id)->delete();
        return response()->json(['message' => 'ลบใบแจ้งซ่อมสำเร็จ']);
    }

    /**
     * ติดตามสถานะใบแจ้งซ่อมด้วย Serial Number (Public - ไม่ต้อง login)
     *
     * ค้นหา Ticket ที่เกี่ยวข้องกับ Serial Number โดย:
     * - ค้นจาก asset.serial_number (อุปกรณ์ในระบบ)
     * - ค้นจาก custom_device_serial_number (อุปกรณ์ภายนอก)
     *
     * @param  string  $serialNumber  Serial Number ของอุปกรณ์
     * @return \Illuminate\Http\JsonResponse  รายการ ticket เรียงตาม created_at desc
     */
    public function tracking(string $serialNumber)
    {
        $ticket = Ticket::with(['asset', 'histories.user'])
            ->whereHas('asset', function ($q) use ($serialNumber) {
                $q->where('serial_number', $serialNumber);
            })
            ->orWhere('custom_device_serial_number', $serialNumber)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($ticket);
    }

    /**
     * ติดตามสถานะใบแจ้งซ่อมด้วย Ticket ID (Public - ไม่ต้อง login)
     *
     * พยายามค้นหา 3 ขั้นตอน:
     * 1. Exact match: ค้นหา ID ตรงเป๊ะ (เช่น "HQ-001")
     * 2. Normalized: ลองลบขีด "-" ออก (เช่น "T-028" → "T028")
     * 3. LIKE search: ค้นหาแบบ partial match (เช่น "028" จะเจอ "HQ-028")
     *
     * @param  string  $ticketId  Ticket ID ที่ต้องการค้นหา
     * @return \Illuminate\Http\JsonResponse  รายการ ticket (array) หรือ array ว่าง ถ้าไม่พบ
     */
    public function trackById(string $ticketId)
    {
        // 1. Exact match
        $ticket = Ticket::with(['asset', 'histories.user'])->find($ticketId);

        // 2. Try without dashes (e.g. T-028 -> T028)
        if (!$ticket) {
            $normalized = str_replace('-', '', $ticketId);
            $ticket = Ticket::with(['asset', 'histories.user'])->find($normalized);
        }

        // 3. Try LIKE search (partial match)
        if (!$ticket) {
            $ticket = Ticket::with(['asset', 'histories.user'])
                ->where('id', 'LIKE', '%' . $ticketId . '%')
                ->first();
        }

        if (!$ticket) {
            return response()->json([], 200);
        }

        return response()->json([$ticket]);
    }

    /**
     * ตรวจสอบว่าระบบ Email Notification เปิดใช้งานอยู่หรือไม่
     *
     * อ่านค่าจาก SystemSetting key='emailNotifications'
     * ถ้าไม่พบการตั้งค่า → default เป็น true (เปิดใช้งาน)
     *
     * @return bool  true ถ้าเปิดใช้งาน, false ถ้าปิด
     */
    private function isEmailNotificationEnabled(): bool
    {
        $setting = SystemSetting::where('key', 'emailNotifications')->first();
        if (!$setting) return true; // default enabled
        return in_array($setting->value, ['1', 'true', true], true);
    }
}
