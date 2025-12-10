<?php

use App\Models\PlayerStatistic;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$positions = PlayerStatistic::select('position')->distinct()->pluck('position');

echo "Unique positions in database:\n";
foreach ($positions as $pos) {
    echo "- " . ($pos ?? 'NULL') . "\n";
}
