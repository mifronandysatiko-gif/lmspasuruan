<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Certificate;

$cert = Certificate::with(['user', 'course'])->orderBy('issued_at', 'desc')->first();
echo "Cert Number: " . $cert->certificate_number . PHP_EOL;
echo "Token: " . $cert->hash_token . PHP_EOL;
echo "URL: " . $cert->validation_url . PHP_EOL;
echo "User: " . $cert->user->name . " (NIP: " . $cert->user->nip . ")" . PHP_EOL;
echo "Training: " . $cert->training_title . PHP_EOL;
echo "JP: " . $cert->jp . " JP" . PHP_EOL;

$req = \Illuminate\Http\Request::create('/validasi/' . $cert->hash_token, 'GET');
$validationController = new \App\Http\Controllers\ValidationController();
$view = $validationController->show($req, $cert->hash_token);
echo "Validation view rendered successfully with certificate user: " . $view->getData()['certificate']->user->name . PHP_EOL;
