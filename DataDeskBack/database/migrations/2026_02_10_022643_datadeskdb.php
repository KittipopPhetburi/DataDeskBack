<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ตาราง บริษัท
        Schema::create('companies', function (Blueprint $table) {
            $table->string('id')->primary(); // C001, C002
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('line_token')->nullable();
            $table->string('telegram_token')->nullable();
            $table->string('notification_email')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        // ตาราง สาขา
        Schema::create('branches', function (Blueprint $table) {
            $table->string('id')->primary(); // B001, B002
            $table->string('name');
            $table->string('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->timestamps();
        });

        // แก้ไขตาราง users เพิ่มฟิลด์
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
            $table->string('role')->default('user')->after('password'); // super_admin, admin, technician, helpdesk, user
            $table->string('company_id')->after('role');
            $table->string('branch_id')->after('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });

        // ตาราง ทรัพย์สิน
        Schema::create('assets', function (Blueprint $table) {
            $table->string('id')->primary(); // A001, A002
            $table->string('asset_code');
            $table->string('serial_number');
            $table->string('type'); // Desktop Computer, Notebook, Printer, etc.
            $table->string('brand');
            $table->string('model');
            $table->date('start_date');
            $table->string('location');
            $table->string('company_id');
            $table->string('branch_id');
            $table->string('responsible'); // user id
            $table->string('department')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('diagram_file')->nullable();
            $table->json('images')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->timestamps();
        });

        // ตาราง ใบแจ้งซ่อม
        Schema::create('tickets', function (Blueprint $table) {
            $table->string('id')->primary(); // T001, T002
            $table->string('title');
            $table->text('description');
            $table->string('asset_id')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('status')->default('open'); // open, in_progress, waiting_parts, closed
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('company_id');
            $table->string('branch_id');
            $table->json('attachments')->nullable();
            $table->text('resolution')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('device_location')->nullable();
            $table->string('ip_address')->nullable();
            $table->decimal('repair_cost', 10, 2)->nullable();
            $table->string('replaced_part_name')->nullable();
            $table->string('replaced_part_serial_number')->nullable();
            $table->string('replaced_part_brand')->nullable();
            $table->string('replaced_part_model')->nullable();
            $table->json('images')->nullable();
            $table->string('custom_device_type')->nullable();
            $table->string('custom_device_serial_number')->nullable();
            $table->string('custom_device_asset_code')->nullable();
            $table->string('custom_device_brand')->nullable();
            $table->string('custom_device_model')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->timestamps();
        });

        // ตาราง ประวัติการทำงาน
        Schema::create('ticket_histories', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id');
            $table->string('action');
            $table->text('description');
            $table->unsignedBigInteger('user_id');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        // ตาราง บันทึกการเข้า-ออก Data Center
        Schema::create('data_center_logs', function (Blueprint $table) {
            $table->string('id')->primary(); // DC001, DC002
            $table->string('visitor_name');
            $table->string('visitor_company')->nullable();
            $table->string('contact_number');
            $table->timestamp('entry_time');
            $table->timestamp('exit_time')->nullable();
            $table->text('purpose');
            $table->text('equipment_brought')->nullable();
            $table->string('authorized_by');
            $table->string('company_id');
            $table->string('branch_id');
            $table->unsignedBigInteger('created_by');
            $table->text('notes')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        // ตาราง System Logs
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_name');
            $table->string('company_id');
            $table->string('company_name');
            $table->string('action'); // LOGIN, CREATE, UPDATE, DELETE
            $table->string('module'); // auth, users, tickets, assets, companies, settings
            $table->text('description');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('data_center_logs');
        Schema::dropIfExists('ticket_histories');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('assets');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['username', 'role', 'company_id', 'branch_id']);
        });
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};
