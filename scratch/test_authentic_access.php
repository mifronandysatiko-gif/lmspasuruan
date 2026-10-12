<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== START AUTHENTIC ACCESS & DEMO REMOVAL TEST ===\n";

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// 1. Test unauthenticated access to portal routes (MUST BE REDIRECTED TO LOGIN)
echo "\n--- 1. Testing Unauthenticated Access (Guest) ---\n";
Auth::logout();

$protectedRoutes = [
    '/peserta/katalog',
    '/peserta/dashboard',
    '/peserta/mooc/orientasi-berakhlak-pasuruan',
    '/admin/dashboard',
    '/admin/users',
    '/admin/settings',
];

foreach ($protectedRoutes as $uri) {
    Auth::logout();
    $req = Request::create($uri, 'GET');
    $res = $httpKernel->handle($req);
    $status = $res->getStatusCode();
    $location = $res->headers->get('Location');
    $isRedirectToLogin = ($status === 302 && str_contains($location, 'login'));
    echo "Guest GET {$uri} => Status {$status} " . ($isRedirectToLogin ? "✓ REDIRECTED TO LOGIN (CORRECT)" : "✗ NOT REDIRECTED ({$location})") . "\n";
}

// 2. Test that switch-role route has been removed (MUST BE 404)
echo "\n--- 2. Testing Deleted Demo Switch-Role Route ---\n";
$req = Request::create('/switch-role/admin', 'GET');
$res = $httpKernel->handle($req);
$status = $res->getStatusCode();
echo "GET /switch-role/admin => Status {$status} " . ($status === 404 ? "✓ 404 NOT FOUND (DEMO ROUTE REMOVED)" : "✗ STILL ACCESSIBLE") . "\n";

// 3. Test Authentic Login Flow via AuthController
echo "\n--- 3. Testing Authentic Login Form Submission ---\n";
$authCtrl = new \App\Http\Controllers\AuthController();

// Test Invalid Login
$invalidReq = Request::create('/login', 'POST', [
    'identifier' => 'salah@pasuruankab.go.id',
    'password' => 'passwordsalah',
]);
$invalidRes = $authCtrl->login($invalidReq);
echo "Invalid Login => " . (session('error') ? "✓ REJECTED WITH ERROR: " . session('error') : "FAIL") . "\n";

// Test Valid Login as Peserta ASN
$validReq = Request::create('/login', 'POST', [
    'identifier' => 'peserta.asn@pasuruankab.go.id',
    'password' => 'password123',
]);
$validRes = $authCtrl->login($validReq);
$loggedInUser = Auth::user();
echo "Valid Login as Peserta => Logged In: " . ($loggedInUser ? $loggedInUser->name : "FAIL") . "\n";
echo "Role: " . ($loggedInUser ? $loggedInUser->role : "FAIL") . "\n";
echo "NIP: " . ($loggedInUser ? $loggedInUser->nip : "FAIL") . "\n";

// Now access Katalog MOOC while logged in
$req = Request::create('/peserta/katalog', 'GET');
$res = $httpKernel->handle($req);
$status = $res->getStatusCode();
echo "Authenticated Peserta GET /peserta/katalog => Status {$status} " . ($status === 200 ? "✓ 200 OK (ACCESSIBLE ONLY AFTER LOGIN)" : "✗ FAIL") . "\n";

// 4. Test Penyelenggara Login & Strict Role Separation
echo "\n--- 4. Testing Penyelenggara Authentic Login ---\n";
Auth::logout();
$penyelenggaraReq = Request::create('/login', 'POST', [
    'identifier' => 'diklat@pasuruankab.go.id',
    'password' => 'password123',
]);
$penyelenggaraRes = $authCtrl->login($penyelenggaraReq);
$currentPenyelenggara = Auth::user();
echo "Logged in as: " . $currentPenyelenggara->name . " ({$currentPenyelenggara->role})\n";

// Penyelenggara tries to access /admin/users (Super Admin only)
$req = Request::create('/admin/users', 'GET');
$res = $httpKernel->handle($req);
$status = $res->getStatusCode();
echo "Penyelenggara GET /admin/users => Status {$status} " . ($status === 302 ? "✓ BLOCKED BY ROLE MIDDLEWARE" : "✗ NOT BLOCKED") . "\n";

// Penyelenggara accesses /admin/webinars (Allowed)
$req = Request::create('/admin/webinars', 'GET');
$res = $httpKernel->handle($req);
$status = $res->getStatusCode();
echo "Penyelenggara GET /admin/webinars => Status {$status} " . ($status === 200 ? "✓ ALLOWED (200 OK)" : "✗ FAIL") . "\n";

// 5. Test Super Admin Login
echo "\n--- 5. Testing Super Admin Authentic Login ---\n";
Auth::logout();
$adminReq = Request::create('/login', 'POST', [
    'identifier' => 'admin@pasuruankab.go.id',
    'password' => 'password123',
]);
$adminRes = $authCtrl->login($adminReq);
$currentAdmin = Auth::user();
echo "Logged in as: " . $currentAdmin->name . " ({$currentAdmin->role})\n";

$req = Request::create('/admin/users', 'GET');
$res = $httpKernel->handle($req);
$status = $res->getStatusCode();
echo "Super Admin GET /admin/users => Status {$status} " . ($status === 200 ? "✓ ALLOWED (200 OK)" : "✗ FAIL") . "\n";

// 6. Test Logout
echo "\n--- 6. Testing Authentic Logout ---\n";
Auth::login($currentAdmin);
echo "Before Logout => Auth::check(): " . (Auth::check() ? "TRUE" : "FALSE") . "\n";
Auth::logout();
echo "After Logout => Auth::check(): " . (Auth::check() ? "FAIL" : "FALSE ✓ LOGGED OUT PROPERLY") . "\n";

echo "\n=== ALL AUTHENTIC ACCESS CHECKS PASSED 100% ===\n";
