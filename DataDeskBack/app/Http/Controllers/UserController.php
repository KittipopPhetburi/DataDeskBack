<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = User::with(['company', 'branch']);

        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);

            // Access Control: Filter by branch if user has strict branch
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
        }

        return response()->json($query->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'username' => $u->username,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'companyId' => $u->company_id,
                'branchId' => $u->branch_id,
                'company' => $u->company,
                'branch' => $u->branch,
            ];
        }));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users',
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:4',
            'role' => 'required|string',
            'company_id' => 'required|string',
            'branch_id' => 'required|string',
        ]);

        $user = User::create([
            'username' => $request->username,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'company_id' => $request->company_id,
            'branch_id' => $request->branch_id,
        ]);

        return response()->json($user, 201);
    }

    public function show(int $id)
    {
        $user = User::with(['company', 'branch'])->findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->only(['username', 'name', 'email', 'role', 'company_id', 'branch_id']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return response()->json($user);
    }

    public function destroy(int $id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'ลบผู้ใช้สำเร็จ']);
    }
}
