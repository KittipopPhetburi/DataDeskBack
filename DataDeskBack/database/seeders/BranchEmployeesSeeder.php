<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Branch;
use App\Models\User;

class BranchEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        // ============ 1. Config Branch B002 (Chiang Mai - DATACOMA) ============
        $branchB002 = Branch::find('B002');
        if ($branchB002) {
            $branchB002->update(['technician_email' => 'chiangmai_tech@example.com']);
            $this->command->info('Updated Technician Email for Branch B002 (Chiang Mai)');
        }

        $usersB002 = [
            ['id' => 101, 'username' => 'admin_cm', 'name' => 'ผู้จัดการ เชียงใหม่', 'email' => 'admin_cm@datacoma.com', 'password' => 'password', 'role' => 'admin', 'company_id' => 'C001', 'branch_id' => 'B002'],
            ['id' => 102, 'username' => 'tech_cm', 'name' => 'ช่าง เชียงใหม่', 'email' => 'chiangmai_tech@example.com', 'password' => 'password', 'role' => 'technician', 'company_id' => 'C001', 'branch_id' => 'B002'],
            ['id' => 103, 'username' => 'helpdesk_cm', 'name' => 'Helpdesk เชียงใหม่', 'email' => 'helpdesk_cm@datacoma.com', 'password' => 'password', 'role' => 'helpdesk', 'company_id' => 'C001', 'branch_id' => 'B002'],
            ['id' => 104, 'username' => 'user_cm', 'name' => 'พนักงาน เชียงใหม่', 'email' => 'user_cm@datacoma.com', 'password' => 'password', 'role' => 'user', 'company_id' => 'C001', 'branch_id' => 'B002'],
        ];

        foreach ($usersB002 as $u) {
            User::updateOrCreate(
                ['username' => $u['username']],
                [
                    'id' => $u['id'],
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'password' => Hash::make($u['password']),
                    'role' => $u['role'],
                    'company_id' => $u['company_id'],
                    'branch_id' => $u['branch_id'],
                ]
            );
        }
        $this->command->info('Created/Updated Users for Branch B002');


        // ============ 2. Config Branch B003 (Head Office - ABC Corp) ============
        $branchB003 = Branch::find('B003');
        if ($branchB003) {
            $branchB003->update(['technician_email' => 'abc_tech@example.com']);
            $this->command->info('Updated Technician Email for Branch B003 (ABC Corp)');
        }

        $usersB003 = [
            ['id' => 201, 'username' => 'admin_abc', 'name' => 'Manager ABC', 'email' => 'admin@abc.com', 'password' => 'password', 'role' => 'admin', 'company_id' => 'C002', 'branch_id' => 'B003'],
            ['id' => 202, 'username' => 'tech_abc', 'name' => 'Technician ABC', 'email' => 'abc_tech@example.com', 'password' => 'password', 'role' => 'technician', 'company_id' => 'C002', 'branch_id' => 'B003'],
            ['id' => 203, 'username' => 'helpdesk_abc', 'name' => 'Helpdesk ABC', 'email' => 'helpdesk@abc.com', 'password' => 'password', 'role' => 'helpdesk', 'company_id' => 'C002', 'branch_id' => 'B003'],
            ['id' => 204, 'username' => 'user_abc', 'name' => 'Employee ABC', 'email' => 'user@abc.com', 'password' => 'password', 'role' => 'user', 'company_id' => 'C002', 'branch_id' => 'B003'],
        ];

        foreach ($usersB003 as $u) {
            User::updateOrCreate(
                ['username' => $u['username']],
                [
                    'id' => $u['id'],
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'password' => Hash::make($u['password']),
                    'role' => $u['role'],
                    'company_id' => $u['company_id'],
                    'branch_id' => $u['branch_id'],
                ]
            );
        }
        $this->command->info('Created/Updated Users for Branch B003');

        // ============ 3. Assets & Tickets for Branch B002 (Chiang Mai) ============
        // Assets
        $assetsB002 = [
            ['id' => 'A-CM-001', 'asset_code' => 'CM-NB-001', 'serial_number' => 'CM123456', 'type' => 'Notebook', 'brand' => 'Lenovo', 'model' => 'ThinkPad E14', 'start_date' => '2025-01-10', 'location' => 'Office Chiang Mai', 'company_id' => 'C001', 'branch_id' => 'B002', 'responsible' => 104], // user_cm
            ['id' => 'A-CM-002', 'asset_code' => 'CM-PC-001', 'serial_number' => 'CM789012', 'type' => 'Desktop Computer', 'brand' => 'Dell', 'model' => 'Vostro 3000', 'start_date' => '2024-11-05', 'location' => 'Counter Service', 'company_id' => 'C001', 'branch_id' => 'B002', 'responsible' => 104],
        ];
        
        foreach ($assetsB002 as $asset) {
            \App\Models\Asset::firstOrCreate(['id' => $asset['id']], $asset);
        }

        // Tickets
        $ticketsB002 = [
            ['id' => 'T-CM-001', 'title' => 'Notebook เปิดช้า (Chiang Mai)', 'description' => 'User แจ้งว่าเครื่องช้ามาก ขอให้ตรวจสอบ', 'priority' => 'medium', 'status' => 'open', 'created_by' => 104, 'company_id' => 'C001', 'branch_id' => 'B002', 'asset_id' => 'A-CM-001'],
            ['id' => 'T-CM-002', 'title' => 'Printer หมึกหมด', 'description' => 'ขอเบิกหมึก Printer', 'priority' => 'low', 'status' => 'in_progress', 'created_by' => 101, 'assigned_to' => 102, 'company_id' => 'C001', 'branch_id' => 'B002'], // assigned to tech_cm
        ];

        foreach ($ticketsB002 as $ticket) {
            \App\Models\Ticket::firstOrCreate(['id' => $ticket['id']], $ticket);
        }
        $this->command->info('Created Assets & Tickets for Branch B002');

        // ============ 4. Assets & Tickets for Branch B003 (ABC Corp) ============
        // Assets
        $assetsB003 = [
            ['id' => 'A-ABC-001', 'asset_code' => 'ABC-SVR-001', 'serial_number' => 'ABC998877', 'type' => 'Server', 'brand' => 'HPE', 'model' => 'ProLiant DL380', 'start_date' => '2024-06-01', 'location' => 'Server Room HQ', 'company_id' => 'C002', 'branch_id' => 'B003', 'responsible' => 202], // tech_abc
            ['id' => 'A-ABC-002', 'asset_code' => 'ABC-SW-001', 'serial_number' => 'ABC112233', 'type' => 'Switch', 'brand' => 'Cisco', 'model' => 'Catalyst 2960', 'start_date' => '2024-06-15', 'location' => 'Network Rack', 'company_id' => 'C002', 'branch_id' => 'B003', 'responsible' => 202],
        ];

        foreach ($assetsB003 as $asset) {
            \App\Models\Asset::firstOrCreate(['id' => $asset['id']], $asset);
        }

        // Tickets
        $ticketsB003 = [
            ['id' => 'T-ABC-001', 'title' => 'Server Down (ABC Corp)', 'description' => 'Server หลักดับ เข้าใช้งานไม่ได้ ด่วน!', 'priority' => 'critical', 'status' => 'open', 'created_by' => 201, 'company_id' => 'C002', 'branch_id' => 'B003', 'asset_id' => 'A-ABC-001'],
            ['id' => 'T-ABC-002', 'title' => 'ขอเพิ่มสิทธิ์ Access Point', 'description' => 'User ขอ connect wifi ใหม่', 'priority' => 'medium', 'status' => 'closed', 'created_by' => 204, 'closed_by' => 202, 'closed_at' => now(), 'company_id' => 'C002', 'branch_id' => 'B003', 'asset_id' => 'A-ABC-002'],
        ];

        foreach ($ticketsB003 as $ticket) {
            \App\Models\Ticket::firstOrCreate(['id' => $ticket['id']], $ticket);
        }
        $this->command->info('Created Assets & Tickets for Branch B003');
    }
}
