<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL to avoid doctrine/dbal dependency issues
        // MySQL Syntax
        DB::statement('ALTER TABLE companies MODIFY logo LONGTEXT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE companies MODIFY logo VARCHAR(255)');
    }
};
