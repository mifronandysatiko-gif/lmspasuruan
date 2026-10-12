<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DataPegawai;
use App\Models\User;
use App\Models\UserProfile;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;

echo "=== TEST VERIFIKASI NIP DAN PENDAFTARAN AKUN ===\n\n";

$authCtrl = new AuthController();

// ----------------------------------------------------
// TEST 1: API checkNip dengan NIP yang ada di DataPegawai (Ahmad Zulkifli)
// ----------------------------------------------------
echo "TEST 1: Cek NIP terdaftar di data_pegawai (198804152011011001)...\n";
$req1 = Request::create('/api/check-nip', 'POST', ['nip' => '198804152011011001']);
$res1 = $authCtrl->checkNip($req1);
$data1 = json_decode($res1->getContent(), true);

assert($data1['valid'] === true, 'TEST 1 GAGAL: Harusnya valid true');
assert($data1['status'] === 'verified', 'TEST 1 GAGAL: Harusnya verified');
assert($data1['pegawai']['nama'] === 'Ahmad Zulkifli', 'TEST 1 GAGAL: Nama pegawai tidak cocok');
echo "  [OK] Valid: {$data1['valid']}, Nama: {$data1['pegawai']['nama']}, Unit: {$data1['pegawai']['unit_kerja']}\n\n";

// ----------------------------------------------------
// TEST 2: API checkNip dengan NIP fiktif yang TIDAK ADA di data_pegawai
// ----------------------------------------------------
echo "TEST 2: Cek NIP fiktif / tidak ada di data_pegawai (199999999999999999)...\n";
$req2 = Request::create('/api/check-nip', 'POST', ['nip' => '199999999999999999']);
$res2 = $authCtrl->checkNip($req2);
$data2 = json_decode($res2->getContent(), true);

assert($data2['valid'] === false, 'TEST 2 GAGAL: Harusnya valid false');
assert($data2['status'] === 'not_found', 'TEST 2 GAGAL: Harusnya status not_found');
echo "  [OK] Berhasil ditolak: {$data2['message']}\n\n";

// ----------------------------------------------------
// TEST 3: API checkNip dengan NIP yang sudah punya akun
// ----------------------------------------------------
$existingUser = User::whereNotNull('nip')->first();
echo "TEST 3: Cek NIP yang sudah memiliki akun LMS ({$existingUser->nip})...\n";
$req3 = Request::create('/api/check-nip', 'POST', ['nip' => $existingUser->nip]);
$res3 = $authCtrl->checkNip($req3);
$data3 = json_decode($res3->getContent(), true);

assert($data3['valid'] === false, 'TEST 3 GAGAL: Harusnya valid false');
assert($data3['status'] === 'already_registered', 'TEST 3 GAGAL: Harusnya status already_registered');
echo "  [OK] Berhasil terdeteksi akun sudah ada: {$data3['message']}\n\n";

// ----------------------------------------------------
// TEST 4: Pendaftaran ASN Pasuruan dengan NIP TIDAK VALID (Harus Ditolak di Server)
// ----------------------------------------------------
echo "TEST 4: Register ASN Pasuruan dengan NIP tidak terdaftar...\n";
$session = app('session.store');
\Illuminate\Support\Facades\Session::setDefaultDriver('array');
$req4 = Request::create('/register', 'POST', [
    'account_type' => 'asn_pasuruan',
    'nip' => '199999999999999999',
    'name' => 'Fulan bin Fulan',
    'email' => 'fulan.test@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'unit_kerja' => 'Instansi Tidak Diketahui',
]);
$req4->setLaravelSession(app('session')->driver());

$res4 = $authCtrl->register($req4);
// Respon adalah redirect back with errors
$errors = $res4->getSession() ? $res4->getSession()->get('errors') : session('errors');
assert($errors !== null && $errors->has('nip'), 'TEST 4 GAGAL: Server harus mengembalikan validation error untuk NIP tidak terdaftar');
echo "  [OK] Server menolak pendaftaran NIP tidak terdaftar: " . $errors->first('nip') . "\n\n";

// ----------------------------------------------------
// TEST 5: Pendaftaran ASN Pasuruan dengan NIP VALID di DataPegawai (Harus Berhasil)
// ----------------------------------------------------
echo "TEST 5: Register ASN Pasuruan dengan NIP valid (Dewi Anggraeni - 199106202014022002)...\n";
// Bersihkan akun jika sudah ada dari testing sebelumnya
User::where('nip', '199106202014022002')->orWhere('email', 'dewi.test@example.com')->delete();

$req5 = Request::create('/register', 'POST', [
    'account_type' => 'asn_pasuruan',
    'nip' => '199106202014022002',
    'name' => 'Dewi Anggraeni',
    'email' => 'dewi.test@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'unit_kerja' => 'Bapperida Pasuruan',
]);
$req5->setLaravelSession(app('session')->driver());

$res5 = $authCtrl->register($req5);
assert($res5->isRedirection(), 'TEST 5 GAGAL: Harusnya redirect setelah pendaftaran berhasil');

$createdUser5 = User::where('email', 'dewi.test@example.com')->first();
assert($createdUser5 !== null, 'TEST 5 GAGAL: Akun user tidak terbuat');
assert($createdUser5->profile !== null, 'TEST 5 GAGAL: User profile tidak terbuat');
assert($createdUser5->profile->kategori_peserta === 'asn_pasuruan', 'TEST 5 GAGAL: Kategori peserta harus asn_pasuruan');
echo "  [OK] User ASN Pasuruan berhasil terdaftar! Kategori: {$createdUser5->profile->kategori_peserta}, Unit: {$createdUser5->profile->unit_kerja}\n\n";

// ----------------------------------------------------
// TEST 6: Pendaftaran ASN LUAR PASURUAN dengan NIP di luar master data_pegawai (Harus Berhasil)
// ----------------------------------------------------
echo "TEST 6: Register ASN Luar Pasuruan dengan NIP luar (199501012020011999)...\n";
User::where('nip', '199501012020011999')->orWhere('email', 'asn.luar.test@example.com')->delete();

$req6 = Request::create('/register', 'POST', [
    'account_type' => 'asn_luar',
    'nip' => '199501012020011999',
    'name' => 'Bambang Sudarmanto, S.E',
    'email' => 'asn.luar.test@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'unit_kerja' => 'Bappeda Provinsi Jawa Timur',
    'jabatan' => 'Perencana Ahli Muda',
]);
$req6->setLaravelSession(app('session')->driver());

$res6 = $authCtrl->register($req6);
assert($res6->isRedirection(), 'TEST 6 GAGAL: Harusnya redirect setelah register');

$createdUser6 = User::where('email', 'asn.luar.test@example.com')->first();
assert($createdUser6 !== null, 'TEST 6 GAGAL: Akun user luar tidak terbuat');
assert($createdUser6->profile->kategori_peserta === 'asn_luar', 'TEST 6 GAGAL: Kategori peserta harus asn_luar');
echo "  [OK] User ASN Luar Pasuruan berhasil terdaftar tanpa cek data_pegawai! Kategori: {$createdUser6->profile->kategori_peserta}, Instansi: {$createdUser6->profile->unit_kerja}\n\n";

// ----------------------------------------------------
// TEST 7: Pendaftaran PESERTA UMUM (Harus Berhasil)
// ----------------------------------------------------
echo "TEST 7: Register Peserta Umum...\n";
User::where('email', 'umum.test@example.com')->delete();

$req7 = Request::create('/register', 'POST', [
    'account_type' => 'umum',
    'name' => 'Citra Kirana',
    'email' => 'umum.test@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'unit_kerja' => 'Universitas Brawijaya',
]);
$req7->setLaravelSession(app('session')->driver());

$res7 = $authCtrl->register($req7);
assert($res7->isRedirection(), 'TEST 7 GAGAL: Harusnya redirect setelah register umum');

$createdUser7 = User::where('email', 'umum.test@example.com')->first();
assert($createdUser7 !== null, 'TEST 7 GAGAL: Akun umum tidak terbuat');
assert($createdUser7->profile->kategori_peserta === 'umum', 'TEST 7 GAGAL: Kategori peserta harus umum');
echo "  [OK] User Umum berhasil terdaftar! Kategori: {$createdUser7->profile->kategori_peserta}\n\n";

// Bersihkan data testing
$createdUser5->profile()->delete();
$createdUser5->delete();
$createdUser6->profile()->delete();
$createdUser6->delete();
$createdUser7->profile()->delete();
$createdUser7->delete();

echo "=== SEMUA 7 TEST VERIFIKASI NIP DAN PENDAFTARAN BERHASIL (100% PASS) ===\n";
