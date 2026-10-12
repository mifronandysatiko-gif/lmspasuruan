<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SidebarMenuSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

echo "========================================================\n";
echo "1. VERIFIKASI SINKRONISASI MASTER MENU SIDEBAR\n";
echo "========================================================\n";

SidebarMenuSetting::syncDefaultMenus();
$count = SidebarMenuSetting::count();
echo "Total menu terdaftar di database: {$count}\n";
assert($count >= 20, "Expected at least 20 registered menus, found {$count}");

$roles = ['peserta', 'super_admin', 'penyelenggara', 'widyaiswara', 'mentor', 'common'];
foreach ($roles as $r) {
    $rCount = SidebarMenuSetting::where('role', $r)->count();
    echo "- Role {$r}: {$rCount} menu\n";
    assert($rCount > 0, "No menus registered for role {$r}");
}
echo "✓ [PASS] Master menu sidebar berhasil disinkronkan ke database.\n";

echo "\n========================================================\n";
echo "2. VERIFIKASI LOGIKA isMenuVisible & IN-MEMORY CACHING\n";
echo "========================================================\n";

// Default harus true
$isVisibleWebinar = SidebarMenuSetting::isMenuVisible('peserta', 'peserta.webinar');
echo "peserta.webinar default visible: " . ($isVisibleWebinar ? 'true' : 'false') . "\n";
assert($isVisibleWebinar === true, "Default peserta.webinar should be visible");

// Sembunyikan peserta.kepemimpinan
$menuKepemimpinan = SidebarMenuSetting::where('role', 'peserta')->where('menu_key', 'peserta.kepemimpinan')->first();
$menuKepemimpinan->update(['is_visible' => false]);
SidebarMenuSetting::clearCache();

$isKepemimpinanVisible = SidebarMenuSetting::isMenuVisible('peserta', 'peserta.kepemimpinan');
echo "peserta.kepemimpinan after hide: " . ($isKepemimpinanVisible ? 'true' : 'false') . "\n";
assert($isKepemimpinanVisible === false, "peserta.kepemimpinan should be hidden (false)");

echo "✓ [PASS] isMenuVisible merespons perubahan status aktif/sembunyi secara akurat.\n";

echo "\n========================================================\n";
echo "3. VERIFIKASI RENDERING SIDEBAR PADA DASHBOARD LAYOUT\n";
echo "========================================================\n";

$userPeserta = User::where('role', 'peserta')->whereNotNull('nip')->first() ?? User::where('role', 'peserta')->first();
Auth::login($userPeserta);

$viewDataPeserta = [
    'user' => $userPeserta,
    'totalJp' => 12,
    'certificates' => collect(),
    'enrolledCourses' => collect(),
    'upcomingWebinars' => collect(),
    'attendedWebinarsCount' => 0,
    'recentCertificates' => collect(),
    'marqueeText' => 'Pengumuman LMS',
];

$renderedHtml = View::make('peserta.dashboard', $viewDataPeserta)->render();

assert(!str_contains($renderedHtml, 'Diklat Kepemimpinan'), "HTML should NOT contain 'Diklat Kepemimpinan' when hidden!");
assert(str_contains($renderedHtml, 'Webinar'), "HTML should contain 'Webinar' when visible!");
echo "✓ [PASS] Menu yang disembunyikan terbukti TIDAK TAMPIL pada HTML sidebar peserta.\n";

// Aktifkan kembali peserta.kepemimpinan
$menuKepemimpinan->update(['is_visible' => true]);
SidebarMenuSetting::clearCache();

$renderedHtml2 = View::make('peserta.dashboard', $viewDataPeserta)->render();

assert(str_contains($renderedHtml2, 'Diklat Kepemimpinan'), "HTML should contain 'Diklat Kepemimpinan' when visible!");
echo "✓ [PASS] Menu yang diaktifkan kembali berhasil MUNCUL pada HTML sidebar peserta.\n";

echo "\n========================================================\n";
echo "4. VERIFIKASI VIEW KELOLA MENU SIDEBAR SUPER ADMIN\n";
echo "========================================================\n";

$superAdmin = User::where('role', 'super_admin')->first();
Auth::login($superAdmin);

$rolesList = [
    'peserta' => 'Peserta (ASN & Umum)',
    'super_admin' => 'Super Admin BKPSDM',
    'penyelenggara' => 'Penyelenggara Diklat',
    'widyaiswara' => 'Widyaiswara (Coach)',
    'mentor' => 'Mentor (Atasan Langsung)',
    'common' => 'Tautan Publik & Umum',
];

$menusByRole = SidebarMenuSetting::orderBy('order_index')->get()->groupBy('role');

$renderedAdminPage = View::make('admin.sidebar_menu.index', [
    'user' => $superAdmin,
    'roles' => $rolesList,
    'menusByRole' => $menusByRole,
    'activeRole' => 'peserta',
])->render();

echo "Ukuran HTML Halaman Kelola Menu Sidebar: " . strlen($renderedAdminPage) . " bytes\n";
assert(strlen($renderedAdminPage) > 2000, "View admin.sidebar_menu.index rendered too small");
assert(str_contains($renderedAdminPage, 'Pengaturan Visibilitas Menu Sidebar'), "Admin page should have title header");
echo "✓ [PASS] Halaman Kelola Menu Sidebar Super Admin berhasil di-render 100% tanpa error.\n";

echo "\n========================================================\n";
echo "5. VERIFIKASI RESET MENU KE DEFAULT\n";
echo "========================================================\n";

SidebarMenuSetting::resetToDefault();
$allVisible = SidebarMenuSetting::where('is_visible', false)->count() === 0;
echo "Seluruh menu kembali visible true: " . ($allVisible ? 'true' : 'false') . "\n";
assert($allVisible === true, "All menus should be visible after reset");
echo "✓ [PASS] Fitur Reset ke Default berfungsi sempurna.\n";

echo "\n========================================================\n";
echo "SEMUA UJI COBA FITUR VISIBILITAS MENU SIDEBAR BERHASIL (100% SUKSES)!\n";
echo "========================================================\n";
