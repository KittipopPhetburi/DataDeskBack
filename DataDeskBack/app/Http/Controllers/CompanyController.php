<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;

/**
 * CompanyController - คอนโทรลเลอร์จัดการบริษัทและสาขา
 *
 * รับผิดชอบ CRUD operations สำหรับบริษัท (Company) และสาขา (Branch)
 * - super_admin สามารถเห็นบริษัททั้งหมดในระบบ
 * - ผู้ใช้ทั่วไปจะเห็นเฉพาะบริษัทที่ตนเองสังกัด
 * - รองรับการจัดการสาขาภายใต้บริษัท (nested resource)
 * - Auto-generate ID สำหรับบริษัท (C001, C002, ...) และสาขา (B001, B002, ...)
 */
class CompanyController extends Controller
{
    /**
     * แสดงรายการบริษัททั้งหมด (พร้อมสาขา)
     *
     * - super_admin: เห็นบริษัททั้งหมด
     * - role อื่นๆ: เห็นเฉพาะบริษัทที่ตนเองสังกัด
     *
     * @param  Request  $request  HTTP request ที่มี user ที่ login อยู่
     * @return \Illuminate\Http\JsonResponse  รายการบริษัทพร้อมสาขาในรูป JSON
     */
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

    /**
     * แสดงรายละเอียดบริษัทตาม ID (พร้อมสาขา)
     *
     * @param  string  $id  รหัสบริษัท เช่น "C001"
     * @return \Illuminate\Http\JsonResponse  ข้อมูลบริษัทพร้อมสาขาในรูป JSON
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  ถ้าไม่พบบริษัท
     */
    public function show(string $id)
    {
        $company = Company::with('branches')->findOrFail($id);
        return response()->json($company);
    }

    /**
     * สร้างบริษัทใหม่
     *
     * - ถ้าไม่ส่ง id มา จะ Auto-generate ในรูปแบบ "C001", "C002", ...
     * - ตรวจสอบ ID ซ้ำ ถ้าซ้ำจะเลื่อนไปเลขถัดไปอัตโนมัติ
     * - บันทึก log เมื่อสร้างสำเร็จ
     *
     * @param  Request  $request  HTTP request ที่มี field: name (required), id (optional)
     * @return \Illuminate\Http\JsonResponse  ข้อมูลบริษัทที่สร้าง (HTTP 201)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        $data = $request->all();
        
        // Generate Auto ID for Company if not provided
        // รูปแบบ: C001, C002, C003, ...
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
        // ป้องกัน ID ซ้ำ (กรณี concurrency หรือส่ง id มาเอง)
        if (Company::where('id', $data['id'])->exists()) {
             $num = intval(substr($data['id'], 1)) + 1;
             $data['id'] = 'C' . str_pad($num, 3, '0', STR_PAD_LEFT);
        }

        \Log::info('Attempting to create company', $data);

        $company = Company::create($data);
        
        \Log::info('Company created successfully: ' . $company->id);
        
        return response()->json($company, 201);
    }

    /**
     * อัปเดตข้อมูลบริษัท
     *
     * @param  Request  $request  HTTP request ที่มี field ที่ต้องการอัปเดต
     * @param  string   $id       รหัสบริษัท เช่น "C001"
     * @return \Illuminate\Http\JsonResponse  ข้อมูลบริษัทที่อัปเดตแล้ว
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  ถ้าไม่พบบริษัท
     */
    public function update(Request $request, string $id)
    {
        $company = Company::findOrFail($id);
        $company->update($request->all());
        return response()->json($company);
    }

    /**
     * ลบบริษัท
     *
     * @param  string  $id  รหัสบริษัท เช่น "C001"
     * @return \Illuminate\Http\JsonResponse  ข้อความยืนยันการลบ
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  ถ้าไม่พบบริษัท
     */
    public function destroy(string $id)
    {
        Company::findOrFail($id)->delete();
        return response()->json(['message' => 'ลบบริษัทสำเร็จ']);
    }

    // ==================== Branch Management (การจัดการสาขา) ====================

    /**
     * สร้างสาขาใหม่ภายใต้บริษัท
     *
     * - Auto-generate ID ในรูปแบบ "B001", "B002", ...
     * - เก็บ ticket_prefix สำหรับสร้าง Ticket ID ของสาขา
     * - เก็บ technician_email สำหรับส่ง Email แจ้งเตือนช่างประจำสาขา
     *
     * @param  Request  $request    HTTP request ที่มี field: name (required), ticket_prefix, technician_email
     * @param  string   $companyId  รหัสบริษัท เช่น "C001"
     * @return \Illuminate\Http\JsonResponse  ข้อมูลสาขาที่สร้าง (HTTP 201)
     */
    public function storeBranch(Request $request, string $companyId)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        // Generate Auto ID for Branch
        // รูปแบบ: B001, B002, B003, ...
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

    /**
     * อัปเดตข้อมูลสาขา
     *
     * อัปเดตเฉพาะ field: name, ticket_prefix, technician_email
     * ตรวจสอบว่าสาขาอยู่ภายใต้บริษัทที่ระบุ
     *
     * @param  Request  $request    HTTP request ที่มี field ที่ต้องการอัปเดต
     * @param  string   $companyId  รหัสบริษัท เช่น "C001"
     * @param  string   $branchId   รหัสสาขา เช่น "B001"
     * @return \Illuminate\Http\JsonResponse  ข้อมูลสาขาที่อัปเดตแล้ว
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  ถ้าไม่พบสาขา
     */
    public function updateBranch(Request $request, string $companyId, string $branchId)
    {
        $branch = Branch::where('company_id', $companyId)->findOrFail($branchId);
        $branch->update($request->only('name', 'ticket_prefix', 'technician_email'));
        return response()->json($branch);
    }

    /**
     * ลบสาขา
     *
     * ตรวจสอบว่าสาขาอยู่ภายใต้บริษัทที่ระบุก่อนลบ
     *
     * @param  string  $companyId  รหัสบริษัท เช่น "C001"
     * @param  string  $branchId   รหัสสาขา เช่น "B001"
     * @return \Illuminate\Http\JsonResponse  ข้อความยืนยันการลบ
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  ถ้าไม่พบสาขา
     */
    public function destroyBranch(string $companyId, string $branchId)
    {
        Branch::where('company_id', $companyId)->findOrFail($branchId)->delete();
        return response()->json(['message' => 'ลบสาขาสำเร็จ']);
    }
}
