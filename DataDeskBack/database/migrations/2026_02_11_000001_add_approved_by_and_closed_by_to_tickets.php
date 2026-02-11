<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->after('assigned_to');
            $table->unsignedBigInteger('closed_by')->nullable()->after('approved_by');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['closed_by']);
            $table->dropColumn(['approved_by', 'closed_by']);
        });
    }
};
