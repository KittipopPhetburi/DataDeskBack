<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Models\Asset;
use App\Models\Ticket;
use App\Models\DataCenterLog;
use App\Models\SystemLog;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============ บริษัท ============
        Company::create([
            'id' => 'C001',
            'name' => 'DATACOMA ASIA',
            'expiry_date' => '2026-12-31',
        ]);
        Company::create([
            'id' => 'C002',
            'name' => 'ABC Corporation',
            'expiry_date' => '2026-03-31',
        ]);

        // ============ สาขา ============
        Branch::create(['id' => 'B001', 'name' => 'สำนักงานใหญ่ กรุงเทพ', 'company_id' => 'C001']);
        Branch::create(['id' => 'B002', 'name' => 'สาขาเชียงใหม่', 'company_id' => 'C001']);
        Branch::create(['id' => 'B003', 'name' => 'Head Office', 'company_id' => 'C002']);
        Branch::create(['id' => 'B004', 'name' => 'Branch Phuket', 'company_id' => 'C002']);

        // ============ ผู้ใช้งาน ============
        $users = [
            ['id' => 1, 'username' => 'superadmin', 'name' => 'Super Admin', 'email' => 'superadmin@system.com', 'password' => 'superadmin', 'role' => 'super_admin', 'company_id' => 'C001', 'branch_id' => 'B001'],
            ['id' => 2, 'username' => 'admin', 'name' => 'สมชาย ผู้จัดการ', 'email' => 'admin@datacoma.com', 'password' => 'admin', 'role' => 'admin', 'company_id' => 'C001', 'branch_id' => 'B001'],
            ['id' => 3, 'username' => 'tech', 'name' => 'สมศักดิ์ ช่างซ่อม', 'email' => 'tech@datacoma.com', 'password' => 'tech', 'role' => 'technician', 'company_id' => 'C001', 'branch_id' => 'B001'],
            ['id' => 4, 'username' => 'helpdesk', 'name' => 'สมหญิง ฝ่ายช่วยเหลือ', 'email' => 'helpdesk@datacoma.com', 'password' => 'helpdesk', 'role' => 'helpdesk', 'company_id' => 'C001', 'branch_id' => 'B001'],
            ['id' => 5, 'username' => 'user', 'name' => 'สมปอง พนักงาน', 'email' => 'user@datacoma.com', 'password' => 'user', 'role' => 'user', 'company_id' => 'C001', 'branch_id' => 'B001'],
        ];

        foreach ($users as $u) {
            User::create([
                'id' => $u['id'],
                'username' => $u['username'],
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => Hash::make($u['password']),
                'role' => $u['role'],
                'company_id' => $u['company_id'],
                'branch_id' => $u['branch_id'],
            ]);
        }

        // ============ ทรัพย์สิน ============
        Asset::create([
            'id' => 'A001', 'asset_code' => 'PC-001', 'serial_number' => 'SN123456789',
            'type' => 'Desktop Computer', 'brand' => 'Dell', 'model' => 'OptiPlex 7090',
            'start_date' => '2024-01-15', 'location' => 'ห้องบัญชี',
            'company_id' => 'C001', 'branch_id' => 'B001', 'responsible' => 5,
        ]);
        Asset::create([
            'id' => 'A002', 'asset_code' => 'NB-001', 'serial_number' => 'SN987654321',
            'type' => 'Notebook', 'brand' => 'HP', 'model' => 'ProBook 450 G8',
            'start_date' => '2024-02-20', 'location' => 'ฝ่ายขาย',
            'company_id' => 'C001', 'branch_id' => 'B001', 'responsible' => 4,
        ]);
        Asset::create([
            'id' => 'A003', 'asset_code' => 'PR-001', 'serial_number' => 'SN555666777',
            'type' => 'Printer', 'brand' => 'Canon', 'model' => 'MF445dw',
            'start_date' => '2023-12-10', 'location' => 'ห้องธุรการ',
            'company_id' => 'C001', 'branch_id' => 'B001', 'responsible' => 2,
        ]);
        Asset::create([
            'id' => 'A004', 'asset_code' => 'DC-SVR-001', 'serial_number' => 'SNSRV123456',
            'type' => 'Data Center', 'brand' => 'Dell', 'model' => 'PowerEdge R750',
            'start_date' => '2023-06-01', 'location' => 'Data Center - Rack A1',
            'company_id' => 'C001', 'branch_id' => 'B001', 'responsible' => 3,
            'department' => 'IT Infrastructure', 'ip_address' => '192.168.100.10',
            'diagram_file' => 'diagram-server-rack-a1.pdf',
            'images' => ['server-front.jpg', 'server-back.jpg', 'server-cables.jpg'],
        ]);
        Asset::create([
            'id' => 'A005', 'asset_code' => 'DC-SW-001', 'serial_number' => 'SNSWT789012',
            'type' => 'Data Center', 'brand' => 'Cisco', 'model' => 'Catalyst 9300',
            'start_date' => '2023-06-15', 'location' => 'Data Center - Rack B2',
            'company_id' => 'C001', 'branch_id' => 'B001', 'responsible' => 3,
            'department' => 'IT Infrastructure', 'ip_address' => '192.168.100.1',
            'diagram_file' => 'diagram-network-topology.pdf',
            'images' => ['switch-front.jpg'],
        ]);
        Asset::create([
            'id' => 'A006', 'asset_code' => 'DC-UPS-001', 'serial_number' => 'SNUPS345678',
            'type' => 'Data Center', 'brand' => 'APC', 'model' => 'Smart-UPS 3000VA',
            'start_date' => '2023-05-20', 'location' => 'Data Center - Power Room',
            'company_id' => 'C001', 'branch_id' => 'B001', 'responsible' => 3,
            'department' => 'IT Infrastructure',
            'diagram_file' => 'diagram-power-distribution.pdf',
            'images' => ['ups-unit.jpg', 'ups-battery.jpg'],
        ]);

        // Generate more assets
        $assetTypes = ['Desktop Computer', 'Notebook', 'Printer', 'Scanner', 'Projector', 'Switch', 'Server', 'UPS'];
        $brands = ['Dell', 'HP', 'Lenovo', 'Canon', 'Epson', 'Cisco', 'APC'];
        $departments = ['IT', 'HR', 'Finance', 'Sales', 'Marketing', 'Operations'];
        $locations = ['Room 101', 'Room 102', 'Room 201', 'Server Room', 'Meeting Room A', 'Meeting Room B'];

        for ($i = 7; $i <= 57; $i++) {
            $num = str_pad($i, 3, '0', STR_PAD_LEFT);
            $type = $assetTypes[array_rand($assetTypes)];
            Asset::create([
                'id' => "A{$num}",
                'asset_code' => "AS-{$num}",
                'serial_number' => "SN" . rand(100000000, 999999999),
                'type' => $type,
                'brand' => $brands[array_rand($brands)],
                'model' => "Model-" . rand(100, 999),
                'start_date' => date('Y-m-d', strtotime('-' . rand(1, 1000) . ' days')),
                'location' => $locations[array_rand($locations)],
                'company_id' => 'C001',
                'branch_id' => 'B001',
                'responsible' => rand(2, 5),
                'department' => $departments[array_rand($departments)],
                'ip_address' => rand(0, 1) ? "192.168.1." . rand(10, 250) : null,
                'diagram_file' => rand(0, 1) ? "diagram-{$num}.pdf" : null,
                'images' => rand(0, 1) ? ["image1-{$num}.jpg", "image2-{$num}.jpg"] : null,
            ]);
        }

        // ============ ใบแจ้งซ่อม ============
        Ticket::create([
            'id' => 'T001', 'title' => 'คอมพิวเตอร์เปิดไม่ติด',
            'description' => 'กดปุ่มเปิดเครื่องแล้วไม่มีอะไรขึ้น หน้าจอดับ',
            'asset_id' => 'A001', 'priority' => 'high', 'status' => 'in_progress',
            'created_by' => 5, 'assigned_to' => 3,
            'company_id' => 'C001', 'branch_id' => 'B001',
            'created_at' => '2026-02-08 09:30:00', 'updated_at' => '2026-02-08 10:15:00',
        ]);
        Ticket::create([
            'id' => 'T002', 'title' => 'Notebook ช้ามาก',
            'description' => 'เปิดโปรแกรมช้า ค้าง บางทีหน้าจอดำ',
            'asset_id' => 'A002', 'priority' => 'medium', 'status' => 'open',
            'created_by' => 4,
            'company_id' => 'C001', 'branch_id' => 'B001',
            'created_at' => '2026-02-09 08:00:00', 'updated_at' => '2026-02-09 08:00:00',
        ]);
        Ticket::create([
            'id' => 'T003', 'title' => 'เครื่องพิมพ์ติดกระดาษ',
            'description' => 'กระดาษติดในเครื่อง พิมพ์ไม่ออก',
            'asset_id' => 'A003', 'priority' => 'low', 'status' => 'closed',
            'created_by' => 2, 'assigned_to' => 3,
            'company_id' => 'C001', 'branch_id' => 'B001',
            'resolution' => 'ดึงกระดาษที่ติดออก ทำความสะอาดลูกยาง เทสพิมพ์ได้ปกติ',
            'closed_at' => '2026-02-07 15:30:00',
            'created_at' => '2026-02-07 14:20:00', 'updated_at' => '2026-02-07 15:30:00',
        ]);
        Ticket::create([
            'id' => 'T004', 'title' => 'จอคอมพิวเตอร์กระพริบ',
            'description' => 'หน้าจอกระพริบเป็นระยะ ใช้งานไม่สะดวก',
            'asset_id' => 'A001', 'priority' => 'medium', 'status' => 'waiting_parts',
            'created_by' => 5, 'assigned_to' => 3,
            'company_id' => 'C001', 'branch_id' => 'B001',
            'created_at' => '2026-02-06 11:00:00', 'updated_at' => '2026-02-08 16:00:00',
        ]);

        // ============ Data Center Logs ============
        $dcLogs = [
            ['id' => 'DC001', 'visitor_name' => 'สมชาย ช่างเทคนิค', 'visitor_company' => 'บริษัท เทคโนโลยี จำกัด', 'contact_number' => '081-234-5678', 'entry_time' => '2026-02-09 08:00:00', 'exit_time' => '2026-02-09 10:30:00', 'purpose' => 'ตรวจสอบและบำรุงรักษาเครื่องเซิร์ฟเวอร์', 'equipment_brought' => 'Laptop, Tool Kit, Multimeter', 'authorized_by' => 'สมชาย ผู้จัดการ', 'company_id' => 'C001', 'branch_id' => 'B001', 'created_by' => 2, 'notes' => 'เปลี่ยน Hard Disk เซิร์ฟเวอร์ Server-01'],
            ['id' => 'DC002', 'visitor_name' => 'วิทยา วิศวกร', 'visitor_company' => 'DATACOMA ASIA', 'contact_number' => '082-345-6789', 'entry_time' => '2026-02-09 13:00:00', 'exit_time' => '2026-02-09 15:00:00', 'purpose' => 'ติดตั้ง UPS ใหม่', 'equipment_brought' => 'UPS 3KVA, สายไฟ, เครื่องมือติดตั้ง', 'authorized_by' => 'สมชาย ผู้จัดการ', 'company_id' => 'C001', 'branch_id' => 'B001', 'created_by' => 2, 'notes' => 'ติดตั้ง UPS Rack-02 เรียบร้อย'],
            ['id' => 'DC003', 'visitor_name' => 'นพดล เครือข่าย', 'visitor_company' => null, 'contact_number' => '083-456-7890', 'entry_time' => '2026-02-08 09:00:00', 'exit_time' => '2026-02-08 12:00:00', 'purpose' => 'ตรวจสอบระบบ Network และ Switch', 'equipment_brought' => 'Laptop, Network Tester, Cable Tester', 'authorized_by' => 'สมศักดิ์ ช่างซ่อม', 'company_id' => 'C001', 'branch_id' => 'B001', 'created_by' => 3, 'notes' => null],
            ['id' => 'DC004', 'visitor_name' => 'ประยุทธ แอร์เย็น', 'visitor_company' => 'บริษัท แอร์คูล จำกัด', 'contact_number' => '084-567-8901', 'entry_time' => '2026-02-08 14:00:00', 'exit_time' => '2026-02-08 16:30:00', 'purpose' => 'บำรุงรักษาระบบปรับอากาศ Precision AC', 'equipment_brought' => 'เครื่องมือช่าง, สารทำความเย็น', 'authorized_by' => 'สมชาย ผู้จัดการ', 'company_id' => 'C001', 'branch_id' => 'B001', 'created_by' => 2, 'notes' => 'ทำความสะอาด Filter, เติมน้ำยาแอร์'],
            ['id' => 'DC005', 'visitor_name' => 'สมหญิง ฝ่ายช่วยเหลือ', 'visitor_company' => 'DATACOMA ASIA', 'contact_number' => '085-678-9012', 'entry_time' => '2026-02-07 10:00:00', 'exit_time' => '2026-02-07 11:00:00', 'purpose' => 'ตรวจสอบ Backup Tape', 'equipment_brought' => 'Tape Media', 'authorized_by' => 'สมชาย ผู้จัดการ', 'company_id' => 'C001', 'branch_id' => 'B001', 'created_by' => 4, 'notes' => 'เปลี่ยน Backup Tape ประจำสัปดาห์'],
            ['id' => 'DC006', 'visitor_name' => 'เจษฎา ไฟเบอร์', 'visitor_company' => 'บริษัท ไฟเบอร์เน็ต จำกัด', 'contact_number' => '086-789-0123', 'entry_time' => '2026-02-06 08:30:00', 'exit_time' => '2026-02-06 17:00:00', 'purpose' => 'ติดตั้งสาย Fiber Optic เพิ่มเติม', 'equipment_brought' => 'Fiber Cable, Fusion Splicer, OTDR', 'authorized_by' => 'สมชาย ผู้จัดการ', 'company_id' => 'C001', 'branch_id' => 'B001', 'created_by' => 2, 'notes' => 'ติดตั้งสาย Fiber เชื่อม DC กับ Office ชั้น 5'],
            ['id' => 'DC007', 'visitor_name' => 'สุรชัย ความปลอดภัย', 'visitor_company' => 'บริษัท ซีเคียว จำกัด', 'contact_number' => '087-890-1234', 'entry_time' => '2026-02-05 13:00:00', 'exit_time' => '2026-02-05 15:00:00', 'purpose' => 'ตรวจสอบระบบ CCTV และ Access Control', 'equipment_brought' => 'Laptop, Monitor', 'authorized_by' => 'สมชาย ผู้จัดการ', 'company_id' => 'C001', 'branch_id' => 'B001', 'created_by' => 2, 'notes' => 'อัพเดต Firmware กล้อง CCTV 4 ตัว'],
            ['id' => 'DC008', 'visitor_name' => 'สมศักดิ์ ช่างซ่อม', 'visitor_company' => 'DATACOMA ASIA', 'contact_number' => '088-901-2345', 'entry_time' => '2026-02-09 16:00:00', 'exit_time' => null, 'purpose' => 'Restart Server ที่มีปัญหา', 'equipment_brought' => 'Laptop', 'authorized_by' => 'สมชาย ผู้จัดการ', 'company_id' => 'C001', 'branch_id' => 'B001', 'created_by' => 3, 'notes' => 'กำลังตรวจสอบ Server-03 ที่ Not Responding'],
        ];

        foreach ($dcLogs as $log) {
            DataCenterLog::create($log);
        }

        // ============ System Logs ============
        $sysLogs = [
            ['user_id' => 1, 'user_name' => 'Super Admin', 'company_id' => 'C001', 'company_name' => 'DATACOMA ASIA', 'action' => 'LOGIN', 'module' => 'auth', 'description' => 'เข้าสู่ระบบ', 'ip_address' => '192.168.1.100', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'created_at' => '2026-02-09 08:30:00'],
            ['user_id' => 1, 'user_name' => 'Super Admin', 'company_id' => 'C001', 'company_name' => 'DATACOMA ASIA', 'action' => 'CREATE', 'module' => 'companies', 'description' => 'สร้างบริษัทใหม่: XYZ Company', 'ip_address' => '192.168.1.100', 'created_at' => '2026-02-09 08:45:00'],
            ['user_id' => 2, 'user_name' => 'สมชาย ผู้จัดการ', 'company_id' => 'C001', 'company_name' => 'DATACOMA ASIA', 'action' => 'CREATE', 'module' => 'users', 'description' => 'เพิ่มผู้ใช้งานใหม่: สมหมาย พนักงาน', 'ip_address' => '192.168.1.105', 'created_at' => '2026-02-09 09:00:00'],
            ['user_id' => 5, 'user_name' => 'สมปอง พนักงาน', 'company_id' => 'C001', 'company_name' => 'DATACOMA ASIA', 'action' => 'CREATE', 'module' => 'tickets', 'description' => 'สร้างใบแจ้งซ่อม #T005: คอมพิวเตอร์เปิดไม่ติด', 'ip_address' => '192.168.1.120', 'created_at' => '2026-02-09 09:15:00'],
            ['user_id' => 3, 'user_name' => 'สมศักดิ์ ช่างซ่อม', 'company_id' => 'C001', 'company_name' => 'DATACOMA ASIA', 'action' => 'UPDATE', 'module' => 'tickets', 'description' => 'อัปเดตสถานะใบแจ้งซ่อม #T001 → กำลังดำเนินการ', 'ip_address' => '192.168.1.110', 'created_at' => '2026-02-09 09:30:00'],
            ['user_id' => 2, 'user_name' => 'สมชาย ผู้จัดการ', 'company_id' => 'C001', 'company_name' => 'DATACOMA ASIA', 'action' => 'CREATE', 'module' => 'assets', 'description' => 'เพิ่มทรัพย์สิน: Printer Canon MF445dw', 'ip_address' => '192.168.1.105', 'created_at' => '2026-02-08 14:20:00'],
            ['user_id' => 1, 'user_name' => 'Super Admin', 'company_id' => 'C001', 'company_name' => 'DATACOMA ASIA', 'action' => 'UPDATE', 'module' => 'settings', 'description' => 'อัปเดตการตั้งค่าระบบ: เปิดใช้งานการแจ้งเตือนอัตโนมัติ', 'ip_address' => '192.168.1.100', 'created_at' => '2026-02-08 15:00:00'],
            ['user_id' => 3, 'user_name' => 'สมศักดิ์ ช่างซ่อม', 'company_id' => 'C001', 'company_name' => 'DATACOMA ASIA', 'action' => 'UPDATE', 'module' => 'tickets', 'description' => 'ปิดใบแจ้งซ่อม #T003 พร้อมสรุปผล', 'ip_address' => '192.168.1.110', 'created_at' => '2026-02-08 16:00:00'],
            ['user_id' => 2, 'user_name' => 'สมชาย ผู้จัดการ', 'company_id' => 'C001', 'company_name' => 'DATACOMA ASIA', 'action' => 'DELETE', 'module' => 'users', 'description' => 'ลบผู้ใช้งาน: U099 - ทดสอบระบบ', 'ip_address' => '192.168.1.105', 'created_at' => '2026-02-08 10:30:00'],
            ['user_id' => 1, 'user_name' => 'Super Admin', 'company_id' => 'C002', 'company_name' => 'ABC Corporation', 'action' => 'UPDATE', 'module' => 'companies', 'description' => 'อัปเดตวันหมดอายุ License: 2026-03-31', 'ip_address' => '192.168.1.100', 'created_at' => '2026-02-07 11:00:00'],
            ['user_id' => 4, 'user_name' => 'สมหญิง ฝ่ายช่วยเหลือ', 'company_id' => 'C001', 'company_name' => 'DATACOMA ASIA', 'action' => 'LOGIN', 'module' => 'auth', 'description' => 'เข้าสู่ระบบ', 'ip_address' => '192.168.1.115', 'created_at' => '2026-02-07 09:00:00'],
            ['user_id' => 1, 'user_name' => 'Super Admin', 'company_id' => 'C001', 'company_name' => 'DATACOMA ASIA', 'action' => 'CREATE', 'module' => 'companies', 'description' => 'เพิ่มสาขาใหม่: สาขาภูเก็ต', 'ip_address' => '192.168.1.100', 'created_at' => '2026-02-06 14:00:00'],
        ];

        foreach ($sysLogs as $log) {
            SystemLog::create($log);
        }
    }
}
