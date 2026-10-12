<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$superAdmin = User::where('role', 'super_admin')->first();
Auth::login($superAdmin);

$adminController = new App\Http\Controllers\AdminController();

$req = Request::create('/admin/dashboard', 'GET');
$resp = $adminController->dashboard();
echo "Dashboard Rendered size: " . strlen($resp->render()) . " bytes\n";

$req2 = Request::create('/admin/sidebar-menu', 'GET');
$resp2 = $adminController->sidebarMenuIndex($req2);
echo "Sidebar Menu Rendered size: " . strlen($resp2->render()) . " bytes\n";

$req3 = Request::create('/admin/settings', 'GET');
$resp3 = $adminController->settings();
echo "Settings Rendered size: " . strlen($resp3->render()) . " bytes\n";

echo "✓ SEMUA ENDPOINT ADMIN BERHASIL DIAKSES TANPA ERROR!\n";
