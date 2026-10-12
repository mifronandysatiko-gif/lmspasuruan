<?php

namespace App\Console\Commands;

use App\Http\Controllers\ApiSyncController;
use App\Models\DataPegawai;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncSimpegCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simpeg:sync 
                            {--all : Tarik seluruh halaman data pegawai sampai selesai}
                            {--limit=500 : Jumlah data per request (maksimal 500)}
                            {--page=1 : Halaman awal untuk ditarik}
                            {--pages=1 : Jumlah halaman yang ditarik (diabaikan jika --all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data pegawai ASN dari endpoint API SIMPEG / SIASN ke LMS Kab. Pasuruan';

    /**
     * Execute the console command.
     */
    public function handle(ApiSyncController $syncController)
    {
        $this->info("=== SINKRONISASI API SIMPEG KABUPATEN PASURUAN ===");

        $apiUrl = SystemSetting::get('simpeg_api_url', 'http://127.0.0.1:8000/api/external-simpeg/mock-pegawai');
        $apiAuthType = SystemSetting::get('simpeg_api_auth_type', 'bearer');
        $apiToken = SystemSetting::get('simpeg_api_token', 'PASURUAN-SIMPEG-SECURE-KEY-2026');

        $this->line("Endpoint  : {$apiUrl}");
        $this->line("Auth Type : {$apiAuthType}");

        $limit = min((int) $this->option('limit'), 500);
        if ($limit <= 0) $limit = 500;

        $startPage = max((int) $this->option('page'), 1);
        $fetchAll = $this->option('all');
        $pagesToFetch = $fetchAll ? 99999 : max((int) $this->option('pages'), 1);

        $totalSyncedPegawai = 0;
        $totalSyncedUsers = 0;

        $currentPage = $startPage;
        $pagesDone = 0;

        $this->info("Memulai proses sinkronisasi...");

        while ($pagesDone < $pagesToFetch) {
            $fetchUrl = $apiUrl;
            $separator = str_contains($fetchUrl, '?') ? '&' : '?';
            $fetchUrl .= "{$separator}limit={$limit}&page={$currentPage}";

            $this->line("Mengambil halaman {$currentPage} (limit: {$limit})...");

            try {
                if (str_contains($apiUrl, 'mock-pegawai') || str_contains($apiUrl, '127.0.0.1') || str_contains($apiUrl, 'localhost')) {
                    $items = $syncController->mockSimpegApi()->getData(true)['data'] ?? [];
                    $res = $syncController->processSyncBatch($items);
                    $totalSyncedPegawai += $res['pegawai_count'];
                    $totalSyncedUsers += $res['user_count'];
                    $this->info("Selesai memproses mock endpoint.");
                    break;
                }

                $httpClient = Http::timeout(30);
                if ($apiAuthType === 'bearer' && $apiToken) {
                    $httpClient = $httpClient->withToken($apiToken);
                } elseif ($apiAuthType === 'apikey' && $apiToken) {
                    $httpClient = $httpClient->withHeaders(['X-API-KEY' => $apiToken]);
                }

                $response = $httpClient->get($fetchUrl);
                if (!$response->successful()) {
                    $this->error("HTTP {$response->status()} pada halaman {$currentPage}.");
                    break;
                }

                $data = $response->json();
                $items = isset($data['data']) ? $data['data'] : (is_array($data) ? $data : []);

                if (empty($items)) {
                    $this->warn("Tidak ada data lagi pada halaman {$currentPage}. Selesai.");
                    break;
                }

                $count = count($items);
                $this->line("Memproses {$count} data pegawai...");

                $res = $syncController->processSyncBatch($items);
                $totalSyncedPegawai += $res['pegawai_count'];
                $totalSyncedUsers += $res['user_count'];

                $this->info("Halaman {$currentPage} berhasil: {$res['pegawai_count']} Master Pegawai & {$res['user_count']} Akun LMS.");

                $currentPage++;
                $pagesDone++;

                if ($count < $limit) {
                    $this->info("Mencapai akhir data (diterima {$count} < {$limit}).");
                    break;
                }

            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
                break;
            }
        }

        $nowStr = Carbon::now()->format('d M Y, H:i:s') . ' WIB';
        SystemSetting::set('simpeg_api_last_synced', $nowStr);

        $totalMaster = DataPegawai::count();
        $totalAsn = User::whereNotNull('nip')->count();

        $this->newLine();
        $this->info("=== HASIL SINKRONISASI ===");
        $this->line("Total batch diproses : {$pagesDone} halaman");
        $this->line("Pegawai disinkronkan : {$totalSyncedPegawai}");
        $this->line("Akun LMS diperbarui  : {$totalSyncedUsers}");
        $this->line("Total Master Pegawai : {$totalMaster}");
        $this->line("Total Akun ASN LMS   : {$totalAsn}");
        $this->info("Waktu sinkronisasi   : {$nowStr}");

        return Command::SUCCESS;
    }
}
