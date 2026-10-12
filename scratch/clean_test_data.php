<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\User;

Course::whereIn('slug', ['test-empty-course', 'test-bimtek-multi-lesson', 'test-diklat-approval-cert-2026', 'pkp-pasuruan-test-2026'])->delete();
User::where('email', 'test_asn_approval@pasuruankab.go.id')->delete();

echo "Temporary test data cleaned successfully.\n";
