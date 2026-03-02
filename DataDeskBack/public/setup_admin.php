<?php

use Illuminate\Support\Facades\Artisan;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "<h1>Setup: Running Database Seeder</h1>";
echo "<pre>";

try {
    // Run db:seed
    // Note: This uses the existing Database\Seeders\DatabaseSeeder class
    echo "Running php artisan db:seed ...\n";
    
    // --force is required for production environment
    Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\DatabaseSeeder', 
        '--force' => true
    ]);
    
    echo Artisan::output();
    echo "\nSeeding completed successfully.\n";

    echo "\n---------------------------------------------------";
    echo "\n⚠️  IMPORTANT: Please DELETE this file immediately! ⚠️";
    echo "\n---------------------------------------------------";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nNote: If you see 'Duplicate entry', it means data already exists.";
}

echo "</pre>";
