<?php

use App\Jobs\FetchLeagues;
use Illuminate\Contracts\Console\Kernel;

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$id = (int)readline("Enter the league id: ");

echo "Fetching leagues for league id: $id\n";

// dispatch(new FetchLeagues(id: $id));
\App\Jobs\FetchTeams::dispatch($id, YEAR);

echo "Done\n";
