<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\DataCenterLogController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemSettingController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/tickets/tracking/{serialNumber}', [TicketController::class, 'tracking']);
Route::get('/assets/search/{serialNumber}', [AssetController::class, 'search']);

// Test DB Connection
Route::get('/_test_db', function () {
    try {
        \DB::connection()->getPdo();
        return response()->json([
            'status' => 'ok', 
            'message' => 'Database connection successful', 
            'database' => \DB::connection()->getDatabaseName()
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// Test DB Write (Create Company)
Route::get('/_test_create', function () {
    try {
        $id = 'TEST' . rand(100, 999);
        $company = \App\Models\Company::create([
            'id' => $id,
            'name' => 'Test Company ' . $id,
            'expiry_date' => '2026-12-31'
        ]);
        return response()->json([
            'status' => 'success', 
            'message' => 'Company created successfully', 
            'data' => $company
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
    }
});

// FIX DB Schema (Change logo to LONGTEXT)
Route::get('/_fix_db', function () {
    try {
        \DB::statement("ALTER TABLE companies MODIFY logo LONGTEXT");
        return response()->json([
            'status' => 'success', 
            'message' => 'Database schema updated (logo column changed to LONGTEXT)' 
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// Protected routes (ต้อง login)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Companies & Branches
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::put('/companies/{id}', [CompanyController::class, 'update']);
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);
    Route::post('/companies/{companyId}/branches', [CompanyController::class, 'storeBranch']);
    Route::put('/companies/{companyId}/branches/{branchId}', [CompanyController::class, 'updateBranch']);
    Route::delete('/companies/{companyId}/branches/{branchId}', [CompanyController::class, 'destroyBranch']);

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Assets
    Route::get('/assets', [AssetController::class, 'index']);
    Route::post('/assets', [AssetController::class, 'store']);
    Route::get('/assets/{id}', [AssetController::class, 'show']);
    Route::put('/assets/{id}', [AssetController::class, 'update']);
    Route::delete('/assets/{id}', [AssetController::class, 'destroy']);
    Route::post('/assets/{id}/images', [AssetController::class, 'uploadImages']);
    Route::delete('/assets/{id}/images/{imageIndex}', [AssetController::class, 'deleteImage']);

    // Tickets
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::put('/tickets/{id}', [TicketController::class, 'update']);
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy']);

    // Data Center Logs
    Route::get('/datacenter/logs', [DataCenterLogController::class, 'index']);
    Route::post('/datacenter/logs', [DataCenterLogController::class, 'store']);
    Route::get('/datacenter/logs/{id}', [DataCenterLogController::class, 'show']);
    Route::put('/datacenter/logs/{id}/exit', [DataCenterLogController::class, 'recordExit']);
    Route::put('/datacenter/logs/{id}', [DataCenterLogController::class, 'update']);
    Route::delete('/datacenter/logs/{id}', [DataCenterLogController::class, 'destroy']);

    // System Settings
    Route::get('/system-settings', [SystemSettingController::class, 'index']);
    Route::post('/system-settings', [SystemSettingController::class, 'update']);

    // System Logs
    Route::get('/system-logs', [SystemLogController::class, 'index']);

    // Reports
    Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/reports/tickets-by-status', [ReportController::class, 'ticketsByStatus']);
    Route::get('/reports/tickets-by-priority', [ReportController::class, 'ticketsByPriority']);
    Route::get('/reports/assets-by-type', [ReportController::class, 'assetsByType']);
});
