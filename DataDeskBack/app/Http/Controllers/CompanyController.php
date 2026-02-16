<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            $companies = Company::with('branches')->get();
        } else {
            $companies = Company::with('branches')->where('id', $user->company_id)->get();
        }

        return response()->json($companies);
    }

    public function show(string $id)
    {
        $company = Company::with('branches')->findOrFail($id);
        return response()->json($company);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        $data = $request->all();
        
        // Generate Auto ID for Company if not provided
        if (empty($data['id'])) {
             // Find last ID starting with 'C'
             $lastCompany = Company::where('id', 'LIKE', 'C%')
                ->orderByRaw('LENGTH(id) DESC') // Order by length first to handle C9 vs C10
                ->orderBy('id', 'desc')
                ->first();

             $nextId = 'C001';
             
             if ($lastCompany && preg_match('/^C(\d+)$/', $lastCompany->id, $matches)) {
                 $num = intval($matches[1]) + 1;
                 $nextId = 'C' . str_pad($num, 3, '0', STR_PAD_LEFT);
             }
             $data['id'] = $nextId;
        }
        
        // Validate ID uniqueness manually if generated or provided
        if (Company::where('id', $data['id'])->exists()) {
             // If collision on generated ID (rare but possible with concurrency), try next
             // For simplicity, we just fail or retry? 
             // Let's just append random check or rely on unique constraint to fail.
             // But we should validate. 
             // Ideally use a retry loop or atomic lock, but for this scale:
             $num = intval(substr($data['id'], 1)) + 1;
             $data['id'] = 'C' . str_pad($num, 3, '0', STR_PAD_LEFT);
        }

        \Log::info('Attempting to create company', $data);

        $company = Company::create($data);
        
        \Log::info('Company created successfully: ' . $company->id);
        
        return response()->json($company, 201);
    }

    public function update(Request $request, string $id)
    {
        $company = Company::findOrFail($id);
        $company->update($request->all());
        return response()->json($company);
    }

    public function destroy(string $id)
    {
        Company::findOrFail($id)->delete();
        return response()->json(['message' => 'ลบบริษัทสำเร็จ']);
    }

    // Branch management
    public function storeBranch(Request $request, string $companyId)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        // Generate Auto ID for Branch
        // Find last ID starting with 'B'
        $lastBranch = Branch::where('id', 'LIKE', 'B%')
            ->orderByRaw('LENGTH(id) DESC')
            ->orderBy('id', 'desc')
            ->first();

        $nextId = 'B001';
        
        if ($lastBranch && preg_match('/^B(\d+)$/', $lastBranch->id, $matches)) {
            $num = intval($matches[1]) + 1;
            $nextId = 'B' . str_pad($num, 3, '0', STR_PAD_LEFT);
        }

        $branch = Branch::create([
            'id' => $nextId,
            'name' => $request->name,
            'ticket_prefix' => $request->ticket_prefix,
            'technician_email' => $request->technician_email,
            'company_id' => $companyId,
        ]);

        return response()->json($branch, 201);
    }

    public function updateBranch(Request $request, string $companyId, string $branchId)
    {
        $branch = Branch::where('company_id', $companyId)->findOrFail($branchId);
        $branch->update($request->only('name', 'ticket_prefix', 'technician_email'));
        return response()->json($branch);
    }

    public function destroyBranch(string $companyId, string $branchId)
    {
        Branch::where('company_id', $companyId)->findOrFail($branchId)->delete();
        return response()->json(['message' => 'ลบสาขาสำเร็จ']);
    }
}
