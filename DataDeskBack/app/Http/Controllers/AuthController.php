<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง',
            ], 401);
        }

        // ตรวจสอบ License
        $company = Company::find($user->company_id);
        if ($company && $company->expiry_date) {
            if ($company->expiry_date->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => "License ของบริษัท {$company->name} หมดอายุแล้ว\nกรุณาติดต่อผู้ดูแลระบบเพื่อต่ออายุการใช้งาน",
                ], 403);
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        // บันทึก System Log
        SystemLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'company_id' => $user->company_id,
            'company_name' => $company->name ?? '',
            'action' => 'LOGIN',
            'module' => 'auth',
            'description' => 'เข้าสู่ระบบ',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'companyId' => $user->company_id,
                'branchId' => $user->branch_id,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'ออกจากระบบสำเร็จ']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $company = Company::find($user->company_id);

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'companyId' => $user->company_id,
            'branchId' => $user->branch_id,
            'company' => $company,
        ]);
    }
}
