<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = new App\User();
$u->name = 'Real Name';
$u->first_name = '';
$u->last_name = '';

echo "Name: [" . $u->name . "]\n";

$u2 = new App\User();
$u2->first_name = 'John';
$u2->last_name = 'Doe';
// name should be implied via accessor if not set? 
// Wait, my accessor logic: if value !empty return value. else fallback.
// So if I don't set name, $value is null. Accessor returns fallback.
echo "Name2: [" . $u2->name . "]\n";
