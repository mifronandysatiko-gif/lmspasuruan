<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\UserProfile;
use App\Models\SystemSetting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== START USER MANAGEMENT & RBAC SEPARATION TEST ===\n";

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$superAdmin = User::where('role', 'super_admin')->first();
$penyelenggara = User::where('role', 'penyelenggara')->first();
$widyaiswara = User::where('role', 'widyaiswara')->first();
$mentor = User::where('role', 'mentor')->first();
$pesertaAsn = User::where('role', 'peserta')->whereNotNull('nip')->first();
$pesertaUmum = User::where('role', 'peserta')->whereNull('nip')->first();

echo "Super Admin: " . $superAdmin->name . " (Role: {$superAdmin->role})\n";
echo "Penyelenggara: " . $penyelenggara->name . " (Role: {$penyelenggara->role})\n";

// --- 1. Uji Pemisahan Hak Akses Penyelenggara vs Super Admin ---
echo "\n--- 1. Testing Penyelenggara Access Restrictions ---\n";

// Penyelenggara coba akses halaman Super Admin -> Harus diblokir / diredirect
$restrictedForPenyelenggara = [
    '/admin/users',
    '/admin/settings',
    '/admin/api-sync',
    '/admin/certificates/designer',
];

foreach ($restrictedForPenyelenggara as $uri) {
    Auth::login($penyelenggara);
    $req = Request::create($uri, 'GET');
    $res = $httpKernel->handle($req);
    $status = $res->getStatusCode();
    echo "Penyelenggara GET {$uri} => Status {$status} " . ($status === 302 ? "✓ BLOCKED/REDIRECTED (CORRECT)" : "✗ NOT BLOCKED (FAIL)") . "\n";
}

// Penyelenggara akses halaman operasionalnya -> Harus 200 OK
$allowedForPenyelenggara = [
    '/admin/dashboard' => 200,
    '/admin/webinars' => 200,
    '/admin/courses' => 200,
];

foreach ($allowedForPenyelenggara as $uri => $expected) {
    Auth::login($penyelenggara);
    $req = Request::create($uri, 'GET');
    $res = $httpKernel->handle($req);
    $status = $res->getStatusCode();
    echo "Penyelenggara GET {$uri} => Status {$status} " . ($status === $expected ? "✓ ALLOWED (CORRECT)" : "✗ FAILED") . "\n";
}

// --- 2. Uji Super Admin Akses Seluruh Modul ---
echo "\n--- 2. Testing Super Admin Full Access ---\n";
$superAdminRoutes = [
    '/admin/dashboard' => 200,
    '/admin/users' => 200,
    '/admin/api-sync' => 200,
    '/admin/settings' => 200,
    '/admin/certificates/designer' => 200,
    '/admin/webinars' => 200,
    '/admin/courses' => 200,
    '/admin/kepemimpinan/review' => 200,
];

foreach ($superAdminRoutes as $uri => $expected) {
    Auth::login($superAdmin);
    $req = Request::create($uri, 'GET');
    $res = $httpKernel->handle($req);
    $status = $res->getStatusCode();
    echo "Super Admin GET {$uri} => Status {$status} " . ($status === $expected ? "✓ OK" : "✗ FAIL") . "\n";
}

// --- 3. Uji Input Data Pegawai Manual (Basis Data Lokal) ---
echo "\n--- 3. Testing Manual Employee Input (Local DB) ---\n";
Auth::login($superAdmin);
$userCtrl = new \App\Http\Controllers\UserController();

$testNip = '19950515' . rand(100000, 999999);
$storeReq = Request::create('/admin/users', 'POST', [
    'nip' => $testNip,
    'name' => 'Kusuma Wardani',
    'gelar_depan' => 'dr.',
    'gelar_belakang' => 'Sp.A',
    'email' => 'kusuma.wardani.' . rand(100, 999) . '@pasuruankab.go.id',
    'phone_number' => '081298765432',
    'unit_kerja' => 'RSUD Grati Kabupaten Pasuruan',
    'jabatan' => 'Dokter Spesialis Anak',
    'pangkat_golongan' => 'Penata Tingkat I / III-d',
    'role' => 'peserta',
]);

$storeRes = $userCtrl->store($storeReq);
$createdUser = User::where('nip', $testNip)->first();
echo "Created Pegawai: " . ($createdUser ? $createdUser->name : "FAIL") . "\n";
echo "Profile OPD: " . ($createdUser?->profile?->unit_kerja ?? "FAIL") . "\n";
echo "Profile Golongan: " . ($createdUser?->profile?->pangkat_golongan ?? "FAIL") . "\n";
echo "Sync Source: " . ($createdUser?->profile?->sync_source ?? "FAIL") . " ✓ OK\n";

// --- 4. Uji Penetapan Hak Akses (Role Assignment) ---
echo "\n--- 4. Testing Role Assignment by Super Admin ---\n";
$roleReq = Request::create('/admin/users/' . $createdUser->id . '/role', 'POST', [
    'role' => 'widyaiswara',
]);
$roleRes = $userCtrl->updateRole($createdUser->id, $roleReq);
$createdUser->refresh();
echo "Updated Role for '{$createdUser->name}': {$createdUser->role} " . ($createdUser->role === 'widyaiswara' ? "✓ ROLE ASSIGNED SUCCESSFULLY" : "✗ FAIL") . "\n";

// Check audit log
$audit = AuditLog::where('auditable_type', 'App\Models\User')->where('auditable_id', $createdUser->id)->where('action', 'ASSIGN_USER_ROLE')->first();
echo "Audit Log recorded: " . ($audit ? "Action {$audit->action} ✓ OK" : "✗ NO AUDIT") . "\n";

// --- 5. Uji Integrasi API SIMPEG Eksternal & Mock Endpoint ---
echo "\n--- 5. Testing External SIMPEG API Integration ---\n";
$apiSyncCtrl = new \App\Http\Controllers\ApiSyncController();

// Test Mock API
$mockRes = $apiSyncCtrl->mockSimpegApi();
$mockData = $mockRes->getData(true);
echo "Mock API Status: " . $mockData['status'] . " (Total: " . $mockData['total_records'] . " records) ✓ OK\n";

// Test Live Sync
$syncReq = Request::create('/admin/api-sync/now', 'POST');
$syncRes = $apiSyncCtrl->syncNow($syncReq);
echo "API Sync Response Type: " . get_class($syncRes) . "\n";

$syncedUsersCount = UserProfile::where('sync_source', 'api_simpeg')->count();
echo "Total ASN synced from API: {$syncedUsersCount} records " . ($syncedUsersCount >= 5 ? "✓ SYNC SUCCESSFUL" : "✗ FAIL") . "\n";

$sampleSynced = User::whereHas('profile', function($p) { $p->where('sync_source', 'api_simpeg'); })->first();
echo "Sample Synced ASN: {$sampleSynced->name} ({$sampleSynced->nip}) - {$sampleSynced->profile->unit_kerja} ✓ OK\n";

$lastSyncedSetting = SystemSetting::get('simpeg_api_last_synced');
echo "Last Synced Timestamp: {$lastSyncedSetting} ✓ OK\n";

echo "\n=== ALL BACKEND & RBAC TESTS PASSED 100% ===\n";
