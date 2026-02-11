<?php
// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;
use App\Models\Asset;

// === Clean duplicate Tickets ===
echo "=== Tickets ===\n";
$tickets = Ticket::orderBy('id', 'asc')->get();
echo "Total tickets: " . $tickets->count() . "\n";

// Group by title + created_by to find duplicates
$grouped = $tickets->groupBy(function ($t) {
    return $t->title . '|' . $t->created_by;
});

$toDelete = [];
foreach ($grouped as $key => $group) {
    if ($group->count() > 1) {
        echo "Duplicate: '{$group->first()->title}' x{$group->count()}\n";
        // Keep the latest one (highest ID), delete the rest
        $keep = $group->last();
        echo "  Keeping: {$keep->id}\n";
        foreach ($group as $t) {
            if ($t->id !== $keep->id) {
                $toDelete[] = $t->id;
                echo "  Deleting: {$t->id}\n";
            }
        }
    }
}

if (count($toDelete) > 0) {
    Ticket::whereIn('id', $toDelete)->delete();
    echo "\nDeleted " . count($toDelete) . " duplicate tickets.\n";
} else {
    echo "\nNo duplicate tickets found.\n";
}

// === Clean duplicate Assets ===
echo "\n=== Assets ===\n";
$assets = Asset::orderBy('id', 'asc')->get();
echo "Total assets: " . $assets->count() . "\n";

$grouped = $assets->groupBy('asset_code');

$toDelete = [];
foreach ($grouped as $code => $group) {
    if ($group->count() > 1) {
        echo "Duplicate: '{$code}' x{$group->count()}\n";
        $keep = $group->first();
        echo "  Keeping: {$keep->id}\n";
        foreach ($group as $a) {
            if ($a->id !== $keep->id) {
                $toDelete[] = $a->id;
                echo "  Deleting: {$a->id}\n";
            }
        }
    }
}

if (count($toDelete) > 0) {
    Asset::whereIn('id', $toDelete)->delete();
    echo "\nDeleted " . count($toDelete) . " duplicate assets.\n";
} else {
    echo "\nNo duplicate assets found.\n";
}

echo "\n=== Done! ===\n";
$remainingTickets = Ticket::count();
$remainingAssets = Asset::count();
echo "Remaining tickets: {$remainingTickets}\n";
echo "Remaining assets: {$remainingAssets}\n";
