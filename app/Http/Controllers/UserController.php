<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DataPegawai;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller
{
    private function currentUser()
    {
        return Auth::user();
    }

    public function index(Request $request)
    {
        $user = $this->currentUser();

        $roleFilter = $request->query('role', 'all');
        $opdFilter = $request->query('opd', 'all');
        $search = $request->query('q');

        $query = User::with('profile')->orderBy('id', 'desc');

        if ($roleFilter !== 'all') {
            if ($roleFilter === 'peserta_umum') {
                $query->where('role', 'peserta')->whereNull('nip');
            } elseif ($roleFilter === 'peserta_asn') {
                $query->where('role', 'peserta')->whereNotNull('nip');
            } else {
                $query->where('role', $roleFilter);
            }
        }

        if ($opdFilter !== 'all') {
            $query->whereHas('profile', function ($q) use ($opdFilter) {
                $q->where('unit_kerja', $opdFilter);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('profile', function ($p) use ($search) {
                      $p->where('jabatan', 'like', "%{$search}%")
                        ->orWhere('unit_kerja', 'like', "%{$search}%");
                  });
            });
        }

        $users = $query->paginate(15)->withQueryString();

        // Statistics
        $stats = [
            'total_users' => User::count(),
            'total_asn' => User::whereNotNull('nip')->count(),
            'total_penyelenggara' => User::where('role', 'penyelenggara')->count(),
            'total_widyaiswara' => User::where('role', 'widyaiswara')->count(),
            'total_mentor' => User::where('role', 'mentor')->count(),
            'total_umum' => User::where('role', 'peserta')->whereNull('nip')->count(),
            'total_inactive' => User::where('is_active', false)->count(),
        ];

        $opdList = UserProfile::select('unit_kerja')
            ->distinct()
            ->whereNotNull('unit_kerja')
            ->where('unit_kerja', '!=', '')
            ->orderBy('unit_kerja', 'asc')
            ->pluck('unit_kerja');

        return view('admin.users.index', compact('user', 'users', 'stats', 'opdList', 'roleFilter', 'opdFilter', 'search'));
    }

    public function store(Request $request)
    {
        $admin = $this->currentUser();

        $role = $request->input('role', 'peserta');

        $rules = [
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:30|unique:users,nip',
            'nik' => 'nullable|string|max:30',
            'email' => 'required|string|email|max:255|unique:users,email',
            'role' => 'required|string|in:super_admin,penyelenggara,widyaiswara,mentor,peserta',
            'unit_kerja' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'pangkat_golongan' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:30',
        ];

        // Username dan password wajib diisi khusus akun selain peserta (Penyelenggara, Coach, Mentor, Super Admin)
        if ($role !== 'peserta') {
            $rules['username'] = 'required|string|max:50|alpha_dash|unique:users,username';
            $rules['password'] = 'required|string|min:6';
        } else {
            $rules['username'] = 'nullable|string|max:50|alpha_dash|unique:users,username';
            $rules['password'] = 'nullable|string|min:6';
        }

        $request->validate($rules, [
            'username.required' => 'Username login akun wajib diisi untuk peran selain peserta.',
            'username.unique' => 'Username ini sudah digunakan, silakan pilih username lain.',
            'username.alpha_dash' => 'Username hanya boleh berupa kombinasi huruf, angka, strip (-), atau garis bawah (_).',
            'password.required' => 'Password akun wajib diisi untuk peran selain peserta.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        if ($role !== 'peserta') {
            $username = strtolower(trim($request->username));
            $password = Hash::make($request->password);
        } else {
            // Untuk peserta, generate username otomatis dan kata sandi default (NIP atau password123)
            $usernameBase = $request->username 
                ? trim($request->username) 
                : ($request->nip ?: ($request->email ? explode('@', $request->email)[0] : Str::slug($request->name)));
            
            $username = strtolower(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $usernameBase));
            if (empty($username) || User::where('username', $username)->exists()) {
                $username = ($username ?: 'peserta') . rand(100, 9999);
            }

            $rawPass = $request->password ?: ($request->nip ?: 'password123');
            $password = Hash::make($rawPass);
        }

        $newUser = User::create([
            'name' => $request->name,
            'nip' => $request->nip,
            'nik' => $request->nik,
            'jabatan' => $request->jabatan,
            'unit_kerja' => $request->unit_kerja,
            'username' => $username,
            'email' => $request->email,
            'password' => $password,
            'role' => $request->role,
            'is_active' => true,
        ]);

        UserProfile::create([
            'user_id' => $newUser->id,
            'nik' => $request->nik,
            'full_name' => $request->name,
            'gelar_depan' => $request->gelar_depan,
            'gelar_belakang' => $request->gelar_belakang,
            'jabatan' => $request->jabatan,
            'pangkat_golongan' => $request->pangkat_golongan,
            'unit_kerja' => $request->unit_kerja,
            'phone_number' => $request->phone_number,
            'sync_source' => 'local',
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'CREATE_USER_MANUAL',
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $newUser->id,
            'old_values' => null,
            'new_values' => [
                'name' => $newUser->name,
                'nip' => $newUser->nip,
                'nik' => $request->nik,
                'email' => $newUser->email,
                'role' => $newUser->role,
                'unit_kerja' => $request->unit_kerja,
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Data Pegawai '{$newUser->name}' berhasil ditambahkan ke basis data lokal LMS.");
    }

    public function update(int $id, Request $request)
    {
        $admin = $this->currentUser();
        $targetUser = User::with('profile')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:30|unique:users,nip,' . $targetUser->id,
            'nik' => 'nullable|string|max:30',
            'email' => 'required|string|email|max:255|unique:users,email,' . $targetUser->id,
            'unit_kerja' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'pangkat_golongan' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:30',
        ]);

        $oldValues = [
            'name' => $targetUser->name,
            'nip' => $targetUser->nip,
            'nik' => $targetUser->profile?->nik,
            'email' => $targetUser->email,
            'unit_kerja' => $targetUser->profile?->unit_kerja,
            'jabatan' => $targetUser->profile?->jabatan,
        ];

        $targetUser->update([
            'name' => $request->name,
            'nip' => $request->nip,
            'nik' => $request->nik,
            'jabatan' => $request->jabatan,
            'unit_kerja' => $request->unit_kerja,
            'email' => $request->email,
        ]);

        $profileData = [
            'full_name' => $request->name,
            'nik' => $request->nik,
            'gelar_depan' => $request->gelar_depan,
            'gelar_belakang' => $request->gelar_belakang,
            'jabatan' => $request->jabatan,
            'pangkat_golongan' => $request->pangkat_golongan,
            'unit_kerja' => $request->unit_kerja,
            'phone_number' => $request->phone_number,
        ];

        if ($targetUser->profile) {
            $targetUser->profile->update($profileData);
        } else {
            $targetUser->profile()->create($profileData);
        }

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'UPDATE_USER_DATA',
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $targetUser->id,
            'old_values' => $oldValues,
            'new_values' => array_merge($targetUser->only(['name', 'nip', 'email']), $profileData),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Data profil pegawai '{$targetUser->name}' berhasil diperbarui.");
    }

    public function updateRole(int $id, Request $request)
    {
        $admin = $this->currentUser();
        $targetUser = User::findOrFail($id);

        $request->validate([
            'role' => 'required|string|in:super_admin,penyelenggara,widyaiswara,mentor,peserta',
        ]);

        $oldRole = $targetUser->role;
        $newRole = $request->role;

        $targetUser->update(['role' => $newRole]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'ASSIGN_USER_ROLE',
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $targetUser->id,
            'old_values' => ['role' => $oldRole],
            'new_values' => ['role' => $newRole],
            'ip_address' => $request->ip(),
        ]);

        $roleLabels = [
            'super_admin' => 'Super Admin BKPSDM',
            'penyelenggara' => 'Penyelenggara Diklat',
            'widyaiswara' => 'Widyaiswara (Coach)',
            'mentor' => 'Mentor (Atasan Langsung)',
            'peserta' => 'Peserta Pembelajaran',
        ];

        return back()->with('success', "Hak akses untuk '{$targetUser->name}' berhasil ditetapkan sebagai [{$roleLabels[$newRole]}].");
    }

    public function toggleStatus(int $id, Request $request)
    {
        $admin = $this->currentUser();
        $targetUser = User::findOrFail($id);

        if ($targetUser->id === $admin->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $newState = !$targetUser->is_active;
        $targetUser->update(['is_active' => $newState]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'TOGGLE_USER_STATUS',
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $targetUser->id,
            'old_values' => ['is_active' => !$newState],
            'new_values' => ['is_active' => $newState],
            'ip_address' => $request->ip(),
        ]);

        $statusText = $newState ? 'diaktifkan kembali' : 'dinonaktifkan (suspend)';
        return back()->with('success', "Akun '{$targetUser->name}' berhasil {$statusText}.");
    }

    /**
     * Download format Excel (.xlsx) resmi untuk import akun pengguna & data pegawai.
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pegawai & Akun');

        $headers = [
            'A1' => 'NIP',
            'B1' => 'NIK',
            'C1' => 'NAMA_LENGKAP',
            'D1' => 'GELAR_DEPAN',
            'E1' => 'GELAR_BELAKANG',
            'F1' => 'EMAIL',
            'G1' => 'NO_HP',
            'H1' => 'UNIT_KERJA',
            'I1' => 'JABATAN',
            'J1' => 'PANGKAT_GOLONGAN',
            'K1' => 'ROLE',
            'L1' => 'USERNAME',
            'PASSWORD' => 'PASSWORD',
        ];

        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
        $headerValues = [
            'NIP', 'NIK', 'NAMA_LENGKAP', 'GELAR_DEPAN', 'GELAR_BELAKANG',
            'EMAIL', 'NO_HP', 'UNIT_KERJA', 'JABATAN', 'PANGKAT_GOLONGAN',
            'ROLE', 'USERNAME', 'PASSWORD'
        ];

        foreach ($headerValues as $idx => $val) {
            $sheet->setCellValue($colLetters[$idx] . '1', $val);
        }

        // Header Styling
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F3D62'], // Navy Pasuruan
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '08243A'],
                ],
            ],
        ];
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Sample Data Rows
        $samples = [
            [
                '198503152010011005',
                '3514011503850001',
                'Ahmad Fauzi',
                'Drs.',
                'M.Si',
                'ahmad.fauzi@pasuruankab.go.id',
                '081234567890',
                'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia',
                'Pranata Komputer Ahli Muda',
                'Penata Tk.I (III/d)',
                'peserta',
                '',
                '',
            ],
            [
                '197812102005012003',
                '3514025012780002',
                'Siti Rahmawati',
                'Dr.',
                'M.Pd',
                'siti.rahmawati@pasuruankab.go.id',
                '081298765432',
                'Dinas Pendidikan dan Kebudayaan',
                'Kepala Bidang Pembinaan Ketenagaan',
                'Pembina (IV/a)',
                'peserta',
                '',
                '',
            ],
            [
                '',
                '3514032005920003',
                'Budi Santoso',
                '',
                'S.Kom',
                'budi.santoso@pasuruankab.go.id',
                '081345678901',
                'BKPSDM Kabupaten Pasuruan',
                'Penyelenggara Pelatihan Teknis',
                '',
                'penyelenggara',
                'penyelenggara_budi',
                'Pasuruan2026!',
            ],
        ];

        $rowNum = 2;
        foreach ($samples as $sample) {
            foreach ($sample as $colIdx => $val) {
                $colLetter = $colLetters[$colIdx];
                $sheet->setCellValueExplicit($colLetter . $rowNum, $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $rowNum++;
        }

        // Auto width for columns A to M
        foreach ($colLetters as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Template_Import_Pegawai_LMS_Pasuruan.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import akun pengguna dan sinkronkan ke basis data master pegawai dari Excel/CSV.
     */
    public function import(Request $request)
    {
        $admin = $this->currentUser();

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], [
            'file.required' => 'Silakan pilih file Excel (.xlsx) atau CSV yang akan diimpor.',
            'file.mimes' => 'Format file yang didukung adalah Excel (.xlsx, .xls) atau CSV (.csv).',
            'file.max' => 'Ukuran file maksimal adalah 10 MB.',
        ]);

        $file = $request->file('file');
        
        try {
            $rows = ExcelImportService::parseFile($file->getPathname(), $file->getClientOriginalName());
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membaca file Excel/CSV: ' . $e->getMessage());
        }

        if (empty($rows)) {
            return back()->with('error', 'File yang diunggah tidak memiliki baris data atau kolom header tidak sesuai.');
        }

        $validRoles = ['super_admin', 'penyelenggara', 'widyaiswara', 'mentor', 'peserta'];
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $pegawaiCount = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Normalisasi pembacaan kolom dari row
                $nip = trim((string)($row['nip'] ?? $row['nomor_induk_pegawai'] ?? ''));
                $nik = trim((string)($row['nik'] ?? $row['nomor_induk_kependudukan'] ?? ''));
                $rawNama = trim((string)($row['nama_lengkap'] ?? $row['nama'] ?? $row['name'] ?? ''));
                $gelarDepan = trim((string)($row['gelar_depan'] ?? ''));
                $gelarBelakang = trim((string)($row['gelar_belakang'] ?? ''));
                $pureNama = DataPegawai::extractPureName($rawNama, $gelarDepan, $gelarBelakang);
                $nama = !empty($pureNama) ? $pureNama : $rawNama;
                $email = trim((string)($row['email'] ?? $row['surel'] ?? ''));
                $noHp = trim((string)($row['no_hp'] ?? $row['phone_number'] ?? $row['telepon'] ?? $row['whatsapp'] ?? ''));
                $unitKerja = trim((string)($row['unit_kerja'] ?? $row['opd'] ?? $row['instansi'] ?? ''));
                $jabatan = trim((string)($row['jabatan'] ?? ''));
                $pangkatGolongan = trim((string)($row['pangkat_golongan'] ?? $row['golongan'] ?? ''));
                $rawRole = strtolower(trim((string)($row['role'] ?? $row['peran'] ?? 'peserta')));
                $rawUsername = trim((string)($row['username'] ?? ''));
                $rawPassword = trim((string)($row['password'] ?? ''));

                // Jika nama kosong dan nip kosong, lewati baris
                if (empty($nama) && empty($nip)) {
                    $skippedCount++;
                    continue;
                }

                $role = in_array($rawRole, $validRoles) ? $rawRole : 'peserta';

                // 1. Simpan atau perbarui data master di tabel `data_pegawai` jika ada NIP
                if (!empty($nip)) {
                    DataPegawai::updateOrCreate(
                        ['nip' => $nip],
                        [
                            'nik' => !empty($nik) ? $nik : null,
                            'nama' => !empty($nama) ? $nama : $nip,
                            'gelar_depan' => !empty($gelarDepan) ? $gelarDepan : null,
                            'gelar_belakang' => !empty($gelarBelakang) ? $gelarBelakang : null,
                            'jabatan' => !empty($jabatan) ? $jabatan : null,
                            'unit_kerja' => !empty($unitKerja) ? $unitKerja : null,
                            'pangkat_golongan' => !empty($pangkatGolongan) ? $pangkatGolongan : null,
                            'email' => !empty($email) ? $email : null,
                            'no_hp' => !empty($noHp) ? $noHp : null,
                            'is_active' => true,
                        ]
                    );
                    $pegawaiCount++;
                }

                // 2. Cari akun User yang ada
                $existingUser = null;
                if (!empty($nip)) {
                    $existingUser = User::where('nip', $nip)->first();
                }
                if (!$existingUser && !empty($email)) {
                    $existingUser = User::where('email', $email)->first();
                }
                if (!$existingUser && !empty($rawUsername)) {
                    $existingUser = User::where('username', $rawUsername)->first();
                }

                if ($existingUser) {
                    // Update user yang sudah ada
                    $updateData = [
                        'name' => !empty($nama) ? $nama : $existingUser->name,
                        'nik' => !empty($nik) ? $nik : $existingUser->nik,
                        'jabatan' => !empty($jabatan) ? $jabatan : $existingUser->jabatan,
                        'unit_kerja' => !empty($unitKerja) ? $unitKerja : $existingUser->unit_kerja,
                        'role' => $role,
                    ];
                    if (!empty($nip) && empty($existingUser->nip)) {
                        $updateData['nip'] = $nip;
                    }
                    if (!empty($rawPassword)) {
                        $updateData['password'] = Hash::make($rawPassword);
                    }
                    $existingUser->update($updateData);

                    // Update atau create profile
                    UserProfile::updateOrCreate(
                        ['user_id' => $existingUser->id],
                        [
                            'nik' => !empty($nik) ? $nik : null,
                            'full_name' => !empty($nama) ? $nama : $existingUser->name,
                            'gelar_depan' => !empty($gelarDepan) ? $gelarDepan : null,
                            'gelar_belakang' => !empty($gelarBelakang) ? $gelarBelakang : null,
                            'jabatan' => !empty($jabatan) ? $jabatan : null,
                            'pangkat_golongan' => !empty($pangkatGolongan) ? $pangkatGolongan : null,
                            'unit_kerja' => !empty($unitKerja) ? $unitKerja : null,
                            'phone_number' => !empty($noHp) ? $noHp : null,
                            'sync_source' => 'import_excel',
                        ]
                    );

                    $updatedCount++;
                } else {
                    // Buat akun User baru
                    if (!empty($rawUsername)) {
                        $usernameCandidate = strtolower(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $rawUsername));
                    } elseif (!empty($nip)) {
                        $usernameCandidate = $nip;
                    } elseif (!empty($email)) {
                        $usernameCandidate = strtolower(explode('@', $email)[0]);
                    } else {
                        $usernameCandidate = Str::slug($nama);
                    }

                    $finalUsername = $usernameCandidate ?: 'user';
                    $suffix = 1;
                    while (User::where('username', $finalUsername)->exists()) {
                        $finalUsername = $usernameCandidate . $suffix;
                        $suffix++;
                    }

                    // Email logic
                    if (!empty($email)) {
                        $finalEmail = $email;
                        if (User::where('email', $finalEmail)->exists()) {
                            $finalEmail = $finalUsername . '@pasuruankab.go.id';
                        }
                    } else {
                        $finalEmail = (!empty($nip) ? $nip : $finalUsername) . '@pasuruankab.go.id';
                        $suffix = 1;
                        while (User::where('email', $finalEmail)->exists()) {
                            $finalEmail = (!empty($nip) ? $nip : $finalUsername) . $suffix . '@pasuruankab.go.id';
                            $suffix++;
                        }
                    }

                    // Password logic
                    if (!empty($rawPassword)) {
                        $passwordHash = Hash::make($rawPassword);
                    } elseif (!empty($nip)) {
                        $passwordHash = Hash::make($nip);
                    } else {
                        $passwordHash = Hash::make('password123');
                    }

                    $isPeserta = ($role === 'peserta');

                    $newUser = User::create([
                        'name' => $nama ?: ($nip ?: $finalUsername),
                        'nip' => !empty($nip) ? $nip : null,
                        'nik' => !empty($nik) ? $nik : null,
                        'jabatan' => !empty($jabatan) ? $jabatan : null,
                        'unit_kerja' => !empty($unitKerja) ? $unitKerja : null,
                        'username' => $finalUsername,
                        'email' => $finalEmail,
                        'password' => $passwordHash,
                        'role' => $role,
                        'is_active' => true,
                        // Peserta baru wajib menyelesaikan profil dan ganti kata sandi
                        'first_login_completed' => !$isPeserta,
                        'first_login_profile_updated' => false,
                        'first_login_password_changed' => false,
                    ]);

                    UserProfile::create([
                        'user_id' => $newUser->id,
                        'nik' => !empty($nik) ? $nik : null,
                        'full_name' => $nama ?: ($nip ?: $finalUsername),
                        'gelar_depan' => !empty($gelarDepan) ? $gelarDepan : null,
                        'gelar_belakang' => !empty($gelarBelakang) ? $gelarBelakang : null,
                        'jabatan' => !empty($jabatan) ? $jabatan : null,
                        'pangkat_golongan' => !empty($pangkatGolongan) ? $pangkatGolongan : null,
                        'unit_kerja' => !empty($unitKerja) ? $unitKerja : null,
                        'phone_number' => !empty($noHp) ? $noHp : null,
                        'sync_source' => 'import_excel',
                    ]);

                    $createdCount++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat memproses data import: ' . $e->getMessage());
        }

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'IMPORT_USERS_EXCEL',
            'auditable_type' => 'App\Models\User',
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => [
                'file_name' => $file->getClientOriginalName(),
                'created_users' => $createdCount,
                'updated_users' => $updatedCount,
                'pegawai_master_synced' => $pegawaiCount,
                'skipped_rows' => $skippedCount,
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Proses import selesai: {$createdCount} akun baru dibuat, {$updatedCount} akun diperbarui, dan {$pegawaiCount} data master pegawai tersinkronisasi.");
    }
}
