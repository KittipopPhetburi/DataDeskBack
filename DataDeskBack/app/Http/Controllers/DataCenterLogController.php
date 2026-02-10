<?php

namespace App\Http\Controllers;

use App\Models\DataCenterLog;
use Illuminate\Http\Request;

class DataCenterLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = DataCenterLog::with('creator');

        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);
        }

        return response()->json($query->orderBy('entry_time', 'desc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'visitor_name' => 'required|string',
            'contact_number' => 'required|string',
            'entry_time' => 'required',
            'purpose' => 'required|string',
            'authorized_by' => 'required|string',
        ]);

        $user = $request->user();
        $data = $request->all();

        if (empty($data['id'])) {
            $lastLog = DataCenterLog::orderBy('id', 'desc')->first();
            $nextId = 'DC001';
            
            if ($lastLog && preg_match('/^DC(\d+)$/', $lastLog->id, $matches)) {
                $num = intval($matches[1]) + 1;
                $nextId = 'DC' . str_pad($num, 3, '0', STR_PAD_LEFT);
            }
            $data['id'] = $nextId;
        }

        $data['company_id'] = $data['company_id'] ?? $user->company_id;
        $data['branch_id'] = $data['branch_id'] ?? $user->branch_id;
        $data['created_by'] = $user->id;

        $log = DataCenterLog::create($data);
        return response()->json($log, 201);
    }

    public function update(Request $request, string $id)
    {
        $log = DataCenterLog::findOrFail($id);
        $log->update($request->all());
        return response()->json($log);
    }

    public function recordExit(Request $request, string $id)
    {
        $log = DataCenterLog::findOrFail($id);
        $log->update([
            'exit_time' => $request->exit_time ?? now(),
        ]);

        return response()->json($log);
    }

    public function show(string $id)
    {
        $log = DataCenterLog::with('creator')->findOrFail($id);
        return response()->json($log);
    }

    public function destroy(string $id)
    {
        DataCenterLog::findOrFail($id)->delete();
        return response()->json(['message' => 'ลบบันทึกสำเร็จ']);
    }
}
