<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketHistory;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['asset', 'creator', 'assignee', 'approver', 'closer', 'company', 'branch']);

        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);
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
            $lastTicket = Ticket::orderBy('id', 'desc')->first();
            $nextId = 'T001';
            
            if ($lastTicket && preg_match('/^T(\d+)$/', $lastTicket->id, $matches)) {
                $num = intval($matches[1]) + 1;
                $nextId = 'T' . str_pad($num, 3, '0', STR_PAD_LEFT);
            }
            $data['id'] = $nextId;
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
}
