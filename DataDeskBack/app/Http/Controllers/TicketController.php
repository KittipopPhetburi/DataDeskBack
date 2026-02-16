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

class TicketController extends Controller
{
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

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'priority' => 'required|string',
        ]);

        $user = $request->user();
        $data = $request->all();

        if (empty($data['id'])) {
            // ดึง ticket_prefix จาก Branch ของ user
            $branch = \App\Models\Branch::find($data['branch_id'] ?? $user->branch_id);
            $prefix = $branch?->ticket_prefix;

            if ($prefix) {
                // หา ticket ล่าสุดที่ขึ้นต้นด้วย prefix นี้
                $lastTicket = Ticket::where('id', 'LIKE', $prefix . '-%')
                    ->orderByRaw('CAST(SUBSTRING_INDEX(id, "-", -1) AS UNSIGNED) DESC')
                    ->first();

                $nextNum = 1;
                if ($lastTicket && preg_match('/-(\d+)$/', $lastTicket->id, $matches)) {
                    $nextNum = intval($matches[1]) + 1;
                }
                $data['id'] = $prefix . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
            } else {
                // Fallback: ใช้ T001 เหมือนเดิม
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

        $data['created_by'] = $user->id;
        $data['company_id'] = $data['company_id'] ?? $user->company_id;
        $data['branch_id'] = $data['branch_id'] ?? $user->branch_id;
        $data['status'] = 'open';

        $ticket = Ticket::create($data);

        // บันทึกประวัติ
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'action' => 'สร้างใบแจ้งซ่อม',
            'description' => "สร้างใบแจ้งซ่อม: {$ticket->title}",
            'user_id' => $user->id,
        ]);

        // ส่ง Email แจ้งเตือน Admin/Helpdesk/Technician ของสาขาเดียวกัน + Super Admin
        if ($this->isEmailNotificationEnabled()) {
            // ส่งให้ Super Admin ทุกคน (ไม่จำกัดบริษัท/สาขา)
            $superAdmins = User::where('role', 'super_admin')
                ->whereNotNull('email')
                ->get();

            // ส่งให้ Admin/Helpdesk/Technician ที่อยู่สาขาเดียวกัน
            $branchStaff = User::where('company_id', $ticket->company_id)
                ->where('branch_id', $ticket->branch_id)
                ->whereIn('role', ['admin', 'helpdesk', 'technician'])
                ->whereNotNull('email')
                ->get();

            $recipients = $superAdmins->merge($branchStaff)->unique('id');

            foreach ($recipients as $recipient) {
                try {
                    $recipient->notify(new TicketCreatedNotification($ticket));
                } catch (\Exception $e) {
                    \Log::warning('Failed to send ticket created notification: ' . $e->getMessage());
                }
            }

            // ส่ง Email แจ้งเตือนช่างประจำสาขา (ถ้ามี)
            $branch = $ticket->branch;
            if ($branch && $branch->technician_email) {
                try {
                    \Illuminate\Support\Facades\Notification::route('mail', $branch->technician_email)
                        ->notify(new TicketCreatedNotification($ticket));
                } catch (\Exception $e) {
                    \Log::warning('Failed to send ticket created notification to technician: ' . $e->getMessage());
                }
            }
        }

        return response()->json($ticket->load(['asset', 'creator', 'assignee', 'approver', 'closer']), 201);
    }

    public function show(string $id)
    {
        $ticket = Ticket::with(['asset', 'creator', 'assignee', 'approver', 'closer', 'company', 'branch', 'histories.user'])->findOrFail($id);
        return response()->json($ticket);
    }

    public function update(Request $request, string $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = $request->user();
        $oldStatus = $ticket->status;

        $oldAssignee = $ticket->assigned_to;
        $ticket->update($request->all());

        // ถ้าสถานะเปลี่ยน
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

            if ($request->status === 'closed') {
                $ticket->update(['closed_at' => now(), 'closed_by' => $user->id]);
            }

            // Save who changed the status
            $ticket->update(['approved_by' => $user->id]);

            // ส่ง Email แจ้งผู้สร้าง Ticket
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

        // ส่ง Email แจ้ง Technician เมื่อถูก assign
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

    public function destroy(string $id)
    {
        Ticket::findOrFail($id)->delete();
        return response()->json(['message' => 'ลบใบแจ้งซ่อมสำเร็จ']);
    }

    // ติดตามสถานะ (ไม่ต้อง auth)
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

    // ติดตามสถานะด้วย Ticket ID (ไม่ต้อง auth)
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

    private function isEmailNotificationEnabled(): bool
    {
        $setting = SystemSetting::where('key', 'emailNotifications')->first();
        if (!$setting) return true; // default enabled
        return in_array($setting->value, ['1', 'true', true], true);
    }
}
