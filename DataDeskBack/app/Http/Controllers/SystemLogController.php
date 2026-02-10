<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Illuminate\Http\Request;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // เฉพาะ super_admin เท่านั้น
        if ($user->role !== 'super_admin') {
            return response()->json(['message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        $query = SystemLog::query();

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }
}
