<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DataPegawai;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ApiSyncController extends Controller
{
    private function currentUser()
    {
        return Auth::user();
    }

    public function index()
    {
        $user = $this->currentUser();

        $apiUrl = SystemSetting::get('simpeg_api_url', 'http://127.0.0.1:8000/api/external-simpeg/mock-pegawai');
        $apiEnabled = SystemSetting::get('simpeg_api_enabled', 'true') === 'true';
        $apiAuthType = SystemSetting::get('simpeg_api_auth_type', 'bearer');
        $apiToken = SystemSetting::get('simpeg_api_token', 'PASURUAN-SIMPEG-SECURE-KEY-2026');
        $lastSynced = SystemSetting::get('simpeg_api_last_synced');

        $totalMasterPegawai = DataPegawai::count();
        $totalSynced = UserProfile::where('sync_source', 'api_simpeg')->count();
        $totalLocal = UserProfile::where(function ($q) {
            $q->where('sync_source', 'local')
              ->orWhere('sync_source', 'manual_registration')
              ->orWhereNull('sync_source');
        })->count();
        $totalAsn = User::whereNotNull('nip')->count();

        $recentSyncAudits = AuditLog::with('user')
            ->where('action', 'SYNC_SIMPEG_API')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('admin.users.api', compact(
            'user',
            'apiUrl',
            'apiEnabled',
            'apiAuthType',
            'apiToken',
            'lastSynced',
            'totalMasterPegawai',
            'totalSynced',
            'totalLocal',
            'totalAsn',
            'recentSyncAudits'
        ));
    }

    public function updateSettings(Request $request)
    {
        $user = $this->currentUser();

        $request->validate([
            'simpeg_api_url' => 'required|url',
            'simpeg_api_auth_type' => 'required|string|in:bearer,apikey,basic,none',
            'simpeg_api_token' => 'nullable|string',
        ]);

        $apiEnabled = $request->boolean('simpeg_api_enabled') ? 'true' : 'false';

        SystemSetting::set('simpeg_api_enabled', $apiEnabled);
        SystemSetting::set('simpeg_api_url', $request->simpeg_api_url);
        SystemSetting::set('simpeg_api_auth_type', $request->simpeg_api_auth_type);
        SystemSetting::set('simpeg_api_token', (string) $request->simpeg_api_token);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_SIMPEG_API_SETTINGS',
            'auditable_type' => 'App\Models\SystemSetting',
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => $request->only(['simpeg_api_url', 'simpeg_api_auth_type']),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Konfigurasi integrasi API PINTAR / Aplikasi Eksternal berhasil disimpan.');
    }

    public function testConnection(Request $request): JsonResponse
    {
        $apiUrl = $request->input('simpeg_api_url', SystemSetting::get('simpeg_api_url'));
        $apiAuthType = $request->input('simpeg_api_auth_type', SystemSetting::get('simpeg_api_auth_type', 'bearer'));
        $apiToken = $request->input('simpeg_api_token', SystemSetting::get('simpeg_api_token'));

        if (empty($apiUrl)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'URL Endpoint belum ditentukan.',
            ], 422);
        }

        try {
            if (str_contains($apiUrl, 'mock-pegawai') || str_contains($apiUrl, '127.0.0.1') || str_contains($apiUrl, 'localhost')) {
                $mockData = $this->getMockPegawaiData();
                return response()->json([
                    'success' => true,
                    'status' => 'connected',
                    'message' => 'Berhasil terhubung ke endpoint simulasi lokal SIMPEG.',
                    'total_records' => count($mockData),
                    'sample' => array_slice($mockData, 0, 2),
                ]);
            }

            $httpClient = Http::timeout(12);
            if ($apiAuthType === 'bearer' && !empty($apiToken)) {
                $httpClient = $httpClient->withToken($apiToken);
            } elseif ($apiAuthType === 'apikey' && !empty($apiToken)) {
                $httpClient = $httpClient->withHeaders(['X-API-KEY' => $apiToken]);
            }

            $testUrl = $apiUrl;
            $separator = str_contains($testUrl, '?') ? '&' : '?';
            $testUrl .= "{$separator}limit=2";

            $response = $httpClient->get($testUrl);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => "Gagal terhubung ke endpoint API eksternal (HTTP {$response->status()}). Periksa kembali URL dan token otentikasi.",
                ], 400);
            }

            $data = $response->json();
            $meta = $data['meta'] ?? [];
            $records = isset($data['data']) ? $data['data'] : (is_array($data) ? $data : []);
            $totalRecords = $meta['total_records'] ?? (is_array($records) ? count($records) : 0);

            return response()->json([
                'success' => true,
                'status' => 'connected',
                'message' => 'Koneksi ke endpoint API berhasil dan terverifikasi.',
                'total_records' => $totalRecords,
                'api_name' => $meta['api_name'] ?? 'API Eksternal',
                'sample' => is_array($records) ? array_slice($records, 0, 2) : [],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Gagal menghubungi server API: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function syncNow(Request $request)
    {
        $user = $this->currentUser();

        $apiUrl = SystemSetting::get('simpeg_api_url', 'http://127.0.0.1:8000/api/external-simpeg/mock-pegawai');
        $apiAuthType = SystemSetting::get('simpeg_api_auth_type', 'bearer');
        $apiToken = SystemSetting::get('simpeg_api_token', 'PASURUAN-SIMPEG-SECURE-KEY-2026');

        $limit = min((int) $request->input('limit', 500), 500);
        if ($limit <= 0) $limit = 500;
        $startPage = max((int) $request->input('page', 1), 1);
        $pagesInput = $request->input('pages_to_fetch', 'all');
        $syncAll = ($pagesInput === 'all' || $request->boolean('sync_all'));

        if ($syncAll) {
            @set_time_limit(0);
            @ini_set('memory_limit', '512M');
            $pagesToFetch = 99999;
        } else {
            $pagesToFetch = max((int) $pagesInput, 1);
        }

        try {
            $totalSyncedPegawai = 0;
            $totalSyncedUsers = 0;
            $totalRecordsAvailable = null;

            if (str_contains($apiUrl, 'mock-pegawai') || str_contains($apiUrl, '127.0.0.1') || str_contains($apiUrl, 'localhost')) {
                $pegawaiList = $this->getMockPegawaiData();
                $res = $this->processSyncBatch($pegawaiList);
                $totalSyncedPegawai += $res['pegawai_count'];
                $totalSyncedUsers += $res['user_count'];
            } else {
                for ($currentPage = $startPage; $currentPage < ($startPage + $pagesToFetch); $currentPage++) {
                    $fetchUrl = $apiUrl;
                    $separator = str_contains($fetchUrl, '?') ? '&' : '?';
                    $fetchUrl .= "{$separator}limit={$limit}&page={$currentPage}";

                    $httpClient = Http::timeout(35);
                    if ($apiAuthType === 'bearer' && !empty($apiToken)) {
                        $httpClient = $httpClient->withToken($apiToken);
                    } elseif ($apiAuthType === 'apikey' && !empty($apiToken)) {
                        $httpClient = $httpClient->withHeaders(['X-API-KEY' => $apiToken]);
                    }

                    $response = $httpClient->get($fetchUrl);
                    if (!$response->successful()) {
                        if ($currentPage === $startPage) {
                            return back()->with('error', "Gagal menghubungi endpoint API eksternal (HTTP {$response->status()}). Pastikan endpoint aktif dan kredensial token benar.");
                        }
                        break;
                    }

                    $data = $response->json();
                    $pegawaiList = isset($data['data']) ? $data['data'] : (is_array($data) ? $data : []);
                    if (isset($data['meta']['total_records'])) {
                        $totalRecordsAvailable = $data['meta']['total_records'];
                    }

                    if (!is_array($pegawaiList) || empty($pegawaiList)) {
                        break;
                    }

                    $res = $this->processSyncBatch($pegawaiList);
                    $totalSyncedPegawai += $res['pegawai_count'];
                    $totalSyncedUsers += $res['user_count'];

                    if (count($pegawaiList) < $limit) {
                        break;
                    }

                    if ($currentPage % 5 === 0) {
                        gc_collect_cycles();
                    }
                }
            }

            if ($totalSyncedPegawai === 0) {
                return back()->with('warning', 'Endpoint API terhubung, namun tidak ada data pegawai yang berhasil diproses. Periksa struktur data atau nomor halaman.');
            }

            // Update timestamp & status terakhir sinkronisasi
            $nowStr = Carbon::now()->format('d M Y, H:i:s') . ' WIB';
            SystemSetting::set('simpeg_api_last_synced', $nowStr);
            SystemSetting::set('simpeg_api_enabled', 'true');

            $totalMaster = DataPegawai::count();
            $totalAsn = User::whereNotNull('nip')->count();

            // Catat Audit Trail
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'SYNC_SIMPEG_API',
                'auditable_type' => 'App\Models\DataPegawai',
                'auditable_id' => null,
                'old_values' => null,
                'new_values' => [
                    'endpoint' => $apiUrl,
                    'records_synced' => $totalSyncedPegawai,
                    'users_synced' => $totalSyncedUsers,
                    'total_master' => $totalMaster,
                    'synced_at' => $nowStr,
                    'sync_mode' => $syncAll ? 'full_all_records' : "partial_{$pagesToFetch}_pages",
                ],
                'ip_address' => $request->ip(),
            ]);

            $message = $syncAll 
                ? "Sinkronisasi Seluruh Data Berhasil! Sebanyak {$totalSyncedPegawai} data pegawai ASN dari sistem eksternal telah berhasil diperbarui ke dalam Master Data SIMPEG & Akun LMS (Total Master: {$totalMaster} pegawai)."
                : "Sinkronisasi Berhasil! Sebanyak {$totalSyncedPegawai} data pegawai ASN dari sistem eksternal telah berhasil diperbarui ke dalam Master Data SIMPEG & Akun LMS (Total Master: {$totalMaster} pegawai).";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan saat memproses data API: ' . $e->getMessage());
        }
    }

    /**
     * Endpoint AJAX untuk sinkronisasi batch per halaman dengan indikator progress visual langsung
     */
    public function syncBatchAjax(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Sesi autentikasi telah berakhir.'], 401);
        }

        $apiUrl = SystemSetting::get('simpeg_api_url', 'http://127.0.0.1:8000/api/external-simpeg/mock-pegawai');
        $apiAuthType = SystemSetting::get('simpeg_api_auth_type', 'bearer');
        $apiToken = SystemSetting::get('simpeg_api_token', 'PASURUAN-SIMPEG-SECURE-KEY-2026');

        $limit = min((int) $request->input('limit', 500), 500);
        if ($limit <= 0) $limit = 500;
        $page = max((int) $request->input('page', 1), 1);

        try {
            if (str_contains($apiUrl, 'mock-pegawai') || str_contains($apiUrl, '127.0.0.1') || str_contains($apiUrl, 'localhost')) {
                $mockData = $this->getMockPegawaiData();
                $res = $this->processSyncBatch($mockData);

                $nowStr = Carbon::now()->format('d M Y, H:i:s') . ' WIB';
                SystemSetting::set('simpeg_api_last_synced', $nowStr);
                SystemSetting::set('simpeg_api_enabled', 'true');

                return response()->json([
                    'success' => true,
                    'current_page' => 1,
                    'batch_count' => count($mockData),
                    'total_records' => count($mockData),
                    'total_pages' => 1,
                    'has_more' => false,
                    'next_page' => null,
                    'pegawai_count' => $res['pegawai_count'],
                    'user_count' => $res['user_count'],
                    'total_master' => DataPegawai::count(),
                    'total_asn' => User::whereNotNull('nip')->count(),
                    'message' => 'Semua data simulasi lokal berhasil disinkronkan.',
                ]);
            }

            $fetchUrl = $apiUrl;
            $separator = str_contains($fetchUrl, '?') ? '&' : '?';
            $fetchUrl .= "{$separator}limit={$limit}&page={$page}";

            $httpClient = Http::timeout(35);
            if ($apiAuthType === 'bearer' && !empty($apiToken)) {
                $httpClient = $httpClient->withToken($apiToken);
            } elseif ($apiAuthType === 'apikey' && !empty($apiToken)) {
                $httpClient = $httpClient->withHeaders(['X-API-KEY' => $apiToken]);
            }

            $response = $httpClient->get($fetchUrl);
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal menghubungi endpoint API (HTTP {$response->status()}) pada halaman {$page}.",
                ], 400);
            }

            $data = $response->json();
            $meta = $data['meta'] ?? [];
            $pegawaiList = isset($data['data']) ? $data['data'] : (is_array($data) ? $data : []);

            $totalRecords = $meta['total_records'] ?? null;
            $totalPages = $meta['total_pages'] ?? ($totalRecords ? (int) ceil($totalRecords / $limit) : null);

            if (empty($pegawaiList) || !is_array($pegawaiList)) {
                return response()->json([
                    'success' => true,
                    'current_page' => $page,
                    'batch_count' => 0,
                    'total_records' => $totalRecords,
                    'total_pages' => $totalPages,
                    'has_more' => false,
                    'next_page' => null,
                    'pegawai_count' => 0,
                    'user_count' => 0,
                    'total_master' => DataPegawai::count(),
                    'total_asn' => User::whereNotNull('nip')->count(),
                    'message' => "Halaman {$page} tidak memiliki data lagi (selesai).",
                ]);
            }

            $res = $this->processSyncBatch($pegawaiList);

            $hasMore = count($pegawaiList) >= $limit;
            if ($totalPages && $page >= $totalPages) {
                $hasMore = false;
            }

            $nowStr = Carbon::now()->format('d M Y, H:i:s') . ' WIB';
            SystemSetting::set('simpeg_api_last_synced', $nowStr);
            SystemSetting::set('simpeg_api_enabled', 'true');

            $totalMaster = DataPegawai::count();
            $totalAsn = User::whereNotNull('nip')->count();

            if (!$hasMore) {
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'SYNC_SIMPEG_API',
                    'auditable_type' => 'App\Models\DataPegawai',
                    'auditable_id' => null,
                    'old_values' => null,
                    'new_values' => [
                        'endpoint' => $apiUrl,
                        'total_records' => $totalRecords,
                        'final_page' => $page,
                        'total_master' => $totalMaster,
                        'synced_at' => $nowStr,
                        'mode' => 'full_sync_ajax',
                    ],
                    'ip_address' => $request->ip(),
                ]);
            }

            return response()->json([
                'success' => true,
                'current_page' => $page,
                'batch_count' => count($pegawaiList),
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'has_more' => $hasMore,
                'next_page' => $hasMore ? $page + 1 : null,
                'pegawai_count' => $res['pegawai_count'],
                'user_count' => $res['user_count'],
                'total_master' => $totalMaster,
                'total_asn' => $totalAsn,
                'message' => "Halaman {$page} berhasil diproses ({$res['pegawai_count']} data).",
            ]);

        } catch (\Throwable $e) {
            $safeMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8');
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses data: ' . $safeMessage,
            ], 500);
        }
    }

    /**
     * Memproses batch data pegawai dari array payload API (upsert ke DataPegawai, User, dan UserProfile)
     */
    public function processSyncBatch(array $items): array
    {
        $defaultPassword = Hash::make('password123');
        $syncedPegawaiCount = 0;
        $syncedUserCount = 0;

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $rawNip = $item['nip'] ?? $item['NIP'] ?? $item['nip_baru'] ?? null;
                $rawNama = $item['nama_lengkap'] ?? $item['nama'] ?? $item['name'] ?? $item['nama_pegawai'] ?? null;

                if (empty($rawNip) || empty($rawNama)) {
                    continue;
                }

                $nip = trim($this->ensureUtf8((string) $rawNip));
                $rawCleanNama = $this->cleanName((string) $rawNama);
                if (empty($nip) || empty($rawCleanNama)) {
                    continue;
                }

                $gelarDepan = $this->cleanGelar($item['gelar_depan'] ?? $item['gelar_dpn'] ?? '');
                $gelarBelakang = $this->cleanGelar($item['gelar_belakang'] ?? $item['gelar_blk'] ?? '');
                
                // Bersihkan nama murni agar gelar tidak dobel/ganda jika API SIMPEG sudah menyertakan gelar di dalam nama
                $pureNama = DataPegawai::extractPureName($rawCleanNama, $gelarDepan, $gelarBelakang);
                $nama = !empty($pureNama) ? $pureNama : $rawCleanNama;
                $fullName = $this->formatFullName($nama, $gelarDepan, $gelarBelakang);

                $jabatan = $this->sanitizeField($item['jabatan_nama'] ?? $item['jabatan'] ?? $item['nama_jabatan'] ?? null, 'Staf Pelaksana');
                $unitKerja = $this->sanitizeField($item['unit_kerja_nama'] ?? $item['opd'] ?? $item['unit_kerja'] ?? $item['nama_unit_kerja'] ?? null, 'Pemerintah Kabupaten Pasuruan');
                $golongan = $this->sanitizeField($item['golongan'] ?? $item['pangkat_golongan'] ?? $item['golongan_nama'] ?? null, 'Penata Muda / III-a');
                $rawEmail = !empty($item['email']) ? trim($this->ensureUtf8((string) $item['email'])) : null;
                $noHp = $this->sanitizeField($item['no_hp'] ?? $item['telepon'] ?? $item['phone'] ?? null);
                $nik = !empty($item['nik']) ? trim($this->ensureUtf8((string) $item['nik'])) : null;

                // 1. Upsert DataPegawai (Master Data SIMPEG ASN Pasuruan)
                DataPegawai::updateOrCreate(
                    ['nip' => $nip],
                    [
                        'nik' => $nik,
                        'nama' => $nama,
                        'gelar_depan' => $gelarDepan ?: null,
                        'gelar_belakang' => $gelarBelakang ?: null,
                        'jabatan' => $jabatan,
                        'unit_kerja' => $unitKerja,
                        'pangkat_golongan' => $golongan,
                        'email' => $rawEmail ?: null,
                        'no_hp' => $noHp ?: null,
                        'is_active' => true,
                    ]
                );
                $syncedPegawaiCount++;

                // 2. Safe email untuk User agar terhindar dari duplicate key exception
                $safeEmail = $rawEmail;
                if (empty($safeEmail) || !filter_var($safeEmail, FILTER_VALIDATE_EMAIL)) {
                    $safeEmail = $nip . '@pasuruankab.go.id';
                } else {
                    $conflict = User::where('email', $safeEmail)->where('nip', '!=', $nip)->exists();
                    if ($conflict) {
                        $safeEmail = $nip . '.' . $safeEmail;
                    }
                }

                // 3. Upsert User
                $userRecord = User::firstOrNew(['nip' => $nip]);
                if (!$userRecord->exists) {
                    $userRecord->name = $fullName;
                    $userRecord->username = $nip;
                    $userRecord->email = $safeEmail;
                    $userRecord->password = $defaultPassword;
                    $userRecord->role = 'peserta';
                    $userRecord->jabatan = $jabatan;
                    $userRecord->unit_kerja = $unitKerja;
                    $userRecord->is_active = true;
                    $userRecord->save();
                } else {
                    $userRecord->name = $fullName;
                    $userRecord->jabatan = $jabatan;
                    $userRecord->unit_kerja = $unitKerja;
                    $userRecord->save();
                }

                // 4. Upsert UserProfile
                $profile = UserProfile::firstOrNew(['user_id' => $userRecord->id]);
                $profile->full_name = $fullName;
                $profile->gelar_depan = $gelarDepan ?: null;
                $profile->gelar_belakang = $gelarBelakang ?: null;
                $profile->unit_kerja = $unitKerja;
                $profile->jabatan = $jabatan;
                $profile->pangkat_golongan = $golongan;
                if ($nik) {
                    $profile->nik = $nik;
                }
                if ($noHp) {
                    $profile->phone_number = $noHp;
                }
                $profile->kategori_peserta = 'asn_pasuruan';
                $profile->sync_source = 'api_simpeg';
                $profile->last_synced_at = Carbon::now();
                $profile->save();

                $syncedUserCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'pegawai_count' => $syncedPegawaiCount,
            'user_count' => $syncedUserCount,
        ];
    }

    /**
     * Pastikan string aman dari byte non-UTF-8, Windows-1252, dan karakter kontrol tak terlihat
     */
    public function ensureUtf8(?string $str): string
    {
        if ($str === null || $str === '') {
            return '';
        }

        // Jika string bukan valid UTF-8, konversi dari Windows-1252 / ISO-8859-1 ke UTF-8
        if (!mb_check_encoding($str, 'UTF-8')) {
            $converted = @mb_convert_encoding($str, 'UTF-8', 'Windows-1252, ISO-8859-1');
            if (mb_check_encoding($converted, 'UTF-8')) {
                $str = $converted;
            } else {
                $str = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
            }
        }

        // Hapus karakter kontrol tak terlihat (0x00 - 0x1F kecuali tab dan newline, plus 0x7F)
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
    }

    public function cleanName(string $rawNama): string
    {
        $nama = $this->ensureUtf8($rawNama);
        $nama = str_replace(['\t', '\n', '\r'], ' ', $nama);
        $nama = trim($nama, " \t\n\r\0\x0B\"'");
        // Hapus tanda awal termasuk bullet, dash, dan zero-width/BOM chars dengan modifier /u
        $nama = preg_replace('/^[-–—\s*•\x{200B}-\x{200D}\x{FEFF}\x{00AD}]+/u', '', $nama);
        // Hapus tanda akhir
        $nama = preg_replace('/[, \t\n\r\0\x0B\-–—\x{200B}-\x{200D}\x{FEFF}\x{00AD}]+$/u', '', $nama);
        // Normalisasi spasi ganda
        $nama = preg_replace('/\s+/u', ' ', $nama);
        return trim($nama, " \t\n\r\0\x0B\"'");
    }

    public function cleanGelar(?string $gelar): string
    {
        if (empty($gelar)) {
            return '';
        }
        $g = $this->ensureUtf8($gelar);
        $g = preg_replace('/^[-–—\s*•\x{200B}-\x{200D}\x{FEFF}\x{00AD}]+/u', '', $g);
        $g = preg_replace('/[, \t\n\r\0\x0B\-–—\x{200B}-\x{200D}\x{FEFF}\x{00AD}]+$/u', '', $g);
        $g = trim($g, " \t\n\r\0\x0B\"'-");
        return ($g === '-' || empty($g)) ? '' : $g;
    }

    public function formatFullName(string $cleanedNama, string $gelarDepan = '', string $gelarBelakang = ''): string
    {
        $fullName = $this->ensureUtf8($cleanedNama);

        if (!empty($gelarDepan) && $gelarDepan !== '-') {
            if (!str_starts_with(mb_strtolower($fullName), mb_strtolower($gelarDepan))) {
                $fullName = $gelarDepan . ' ' . $fullName;
            }
        }

        if (!empty($gelarBelakang) && $gelarBelakang !== '-') {
            if (!str_ends_with(mb_strtolower($fullName), mb_strtolower($gelarBelakang))) {
                $fullName = $fullName . ', ' . $gelarBelakang;
            }
        }

        return $fullName;
    }

    public function sanitizeField(?string $val, ?string $default = null): ?string
    {
        if ($val === null) {
            return $default;
        }
        $val = $this->ensureUtf8($val);
        $cleaned = preg_replace('/^[\s\x{200B}-\x{200D}\x{FEFF}\x{00AD}]+|[\s\x{200B}-\x{200D}\x{FEFF}\x{00AD}]+$/u', '', $val);
        $cleaned = trim($cleaned, " \t\n\r\0\x0B\"'");
        if ($cleaned === '' || $cleaned === '-') {
            return $default;
        }
        return $cleaned;
    }

    public function mockSimpegApi(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Layanan Simulasi API SIMPEG Pemerintah Kabupaten Pasuruan',
            'timestamp' => now()->toIso8601String(),
            'total_records' => count($this->getMockPegawaiData()),
            'data' => $this->getMockPegawaiData(),
        ]);
    }

    private function getMockPegawaiData(): array
    {
        return [
            [
                'nip' => '198403152009011002',
                'nama_lengkap' => 'Ir. Hendro Prasetyo, M.T',
                'gelar_depan' => 'Ir.',
                'gelar_belakang' => 'M.T',
                'email' => 'hendro.prasetyo@pasuruankab.go.id',
                'unit_kerja_nama' => 'Badan Perencanaan Pembangunan Daerah (Bappeda)',
                'jabatan_nama' => 'Kepala Bidang Infrastruktur & Kewilayahan',
                'golongan' => 'Pembina / IV-a',
                'no_hp' => '081234567801',
            ],
            [
                'nip' => '198905202011012003',
                'nama_lengkap' => 'drg. Ratna Kartikasari, Sp.KG',
                'gelar_depan' => 'drg.',
                'gelar_belakang' => 'Sp.KG',
                'email' => 'ratna.kartika@pasuruankab.go.id',
                'unit_kerja_nama' => 'RSUD Bangil Kabupaten Pasuruan',
                'jabatan_nama' => 'Dokter Gigi Madya',
                'golongan' => 'Pembina Tingkat I / IV-b',
                'no_hp' => '081234567802',
            ],
            [
                'nip' => '199307122019021004',
                'nama_lengkap' => 'Bayu Wicaksono, S.Kom, M.Eng',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.Kom, M.Eng',
                'email' => 'bayu.wicaksono@pasuruankab.go.id',
                'unit_kerja_nama' => 'Dinas Komunikasi dan Informatika',
                'jabatan_nama' => 'Pranata Komputer Ahli Pertama (Keamanan Siber)',
                'golongan' => 'Penata / III-c',
                'no_hp' => '081234567803',
            ],
            [
                'nip' => '198711042010012005',
                'nama_lengkap' => 'Anisa Pujiastuti, S.Pd, M.Pd',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.Pd, M.Pd',
                'email' => 'anisa.puji@pasuruankab.go.id',
                'unit_kerja_nama' => 'Dinas Pendidikan dan Kebudayaan',
                'jabatan_nama' => 'Pengawas Sekolah Ahli Madya',
                'golongan' => 'Pembina / IV-a',
                'no_hp' => '081234567804',
            ],
            [
                'nip' => '199502182020121006',
                'nama_lengkap' => 'Fajar Kurniawan, S.STP',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.STP',
                'email' => 'fajar.kurniawan@pasuruankab.go.id',
                'unit_kerja_nama' => 'Badan Kepegawaian dan Pengembangan SDM',
                'jabatan_nama' => 'Analis SDM Aparatur Ahli Pertama',
                'golongan' => 'Penata Muda / III-a',
                'no_hp' => '081234567805',
            ],
            [
                'nip' => '199009112015032007',
                'nama_lengkap' => 'Dra. Nur Indah Wahyuni, M.Si',
                'gelar_depan' => 'Dra.',
                'gelar_belakang' => 'M.Si',
                'email' => 'indah.wahyuni@pasuruankab.go.id',
                'unit_kerja_nama' => 'Inspektorat Daerah Kabupaten Pasuruan',
                'jabatan_nama' => 'Auditor Ahli Muda',
                'golongan' => 'Penata Tingkat I / III-d',
                'no_hp' => '081234567806',
            ],
            [
                'nip' => '199401222019031008',
                'nama_lengkap' => 'Rizky Firmansyah, S.H',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.H',
                'email' => 'rizky.firmansyah@pasuruankab.go.id',
                'unit_kerja_nama' => 'Bagian Hukum Sekretariat Daerah',
                'jabatan_nama' => 'Perancang Peraturan Perundang-undangan',
                'golongan' => 'Penata Muda Tingkat I / III-b',
                'no_hp' => '081234567807',
            ],
            [
                'nip' => '198604192010012009',
                'nama_lengkap' => 'Hj. Siti Aisyah, S.Kep, Ners',
                'gelar_depan' => 'Hj.',
                'gelar_belakang' => 'S.Kep, Ners',
                'email' => 'siti.aisyah@pasuruankab.go.id',
                'unit_kerja_nama' => 'Dinas Kesehatan Kabupaten Pasuruan',
                'jabatan_nama' => 'Administrator Kesehatan Ahli Muda',
                'golongan' => 'Penata Tingkat I / III-d',
                'no_hp' => '081234567808',
            ],
        ];
    }
}
