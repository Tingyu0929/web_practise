<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

if (Schema::hasTable('external_links')) {
    Schema::dropIfExists('external_links');
    echo "Dropped external_links table\n";
} else {
    echo "external_links table does not exist\n";
}
