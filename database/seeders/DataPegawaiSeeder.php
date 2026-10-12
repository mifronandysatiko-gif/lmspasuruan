<?php

namespace Database\Seeders;

use App\Models\DataPegawai;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

class DataPegawaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pegawaiList = [
            // 1. Pegawai Pasuruan yang belum memiliki akun LMS (bisa langsung dipakai registrasi baru)
            [
                'nip' => '198804152011011001',
                'nik' => '3514011504880001',
                'nama' => 'Ahmad Zulkifli',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.Kom',
                'jabatan' => 'Pranata Komputer Ahli Muda',
                'unit_kerja' => 'Dinas Komunikasi dan Informatika',
                'pangkat_golongan' => 'Penata / III-c',
                'email' => 'ahmad.zulkifli@pasuruankab.go.id',
                'no_hp' => '081234567901',
                'is_active' => true,
            ],
            [
                'nip' => '199106202014022002',
                'nik' => '3514026006910002',
                'nama' => 'Dewi Anggraeni',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.STP, M.Tr.IP',
                'jabatan' => 'Analis Kebijakan Ahli Pertama',
                'unit_kerja' => 'Badan Perencanaan Pembangunan, Riset dan Inovasi Daerah (Bapperida)',
                'pangkat_golongan' => 'Penata Muda Tingkat I / III-b',
                'email' => 'dewi.anggraeni@pasuruankab.go.id',
                'no_hp' => '081234567902',
                'is_active' => true,
            ],
            [
                'nip' => '198511252008011003',
                'nik' => '3514032511850003',
                'nama' => 'Bambang Setiawan',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.H, M.H',
                'jabatan' => 'Pengelola Pengadaan Barang/Jasa Ahli Madya',
                'unit_kerja' => 'Bagian Pengadaan Barang dan Jasa Sekretariat Daerah',
                'pangkat_golongan' => 'Pembina / IV-a',
                'email' => 'bambang.setiawan@pasuruankab.go.id',
                'no_hp' => '081234567903',
                'is_active' => true,
            ],
            [
                'nip' => '199203102015032004',
                'nik' => '3514045003920004',
                'nama' => 'Nurul Farida',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.Pd, M.Pd',
                'jabatan' => 'Guru Ahli Pertama',
                'unit_kerja' => 'Dinas Pendidikan dan Kebudayaan',
                'pangkat_golongan' => 'Penata Muda / III-a',
                'email' => 'nurul.farida@pasuruankab.go.id',
                'no_hp' => '081234567904',
                'is_active' => true,
            ],
            [
                'nip' => '198709142010011005',
                'nik' => '3514051409870005',
                'nama' => 'Dimas Wahyu Pratama',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.Kep, Ns',
                'jabatan' => 'Perawat Ahli Muda',
                'unit_kerja' => 'RSUD Grati Kabupaten Pasuruan',
                'pangkat_golongan' => 'Penata / III-c',
                'email' => 'dimas.wahyu@pasuruankab.go.id',
                'no_hp' => '081234567905',
                'is_active' => true,
            ],
            [
                'nip' => '199602122022031006',
                'nik' => '3514061202960006',
                'nama' => 'Mochamad Arif Hidayat',
                'gelar_depan' => '',
                'gelar_belakang' => 'A.Md.Ak',
                'jabatan' => 'Pengelola Keuangan',
                'unit_kerja' => 'Badan Pengelolaan Keuangan dan Pendapatan Daerah',
                'pangkat_golongan' => 'Pengatur / II-c',
                'email' => 'arif.hidayat@pasuruankab.go.id',
                'no_hp' => '081234567906',
                'is_active' => true,
            ],
            [
                'nip' => '199312012019032007',
                'nik' => '3514074112930007',
                'nama' => 'Tri Wulandari',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.IP',
                'jabatan' => 'Arsiparis Ahli Pertama',
                'unit_kerja' => 'Dinas Perpustakaan dan Kearsipan',
                'pangkat_golongan' => 'Penata Muda Tingkat I / III-b',
                'email' => 'tri.wulandari@pasuruankab.go.id',
                'no_hp' => '081234567907',
                'is_active' => true,
            ],
            [
                'nip' => '199008242012021008',
                'nik' => '3514082408900008',
                'nama' => 'Hendra Gunawan',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.T',
                'jabatan' => 'Teknik Tata Bangunan dan Perumahan Ahli Muda',
                'unit_kerja' => 'Dinas Perumahan dan Kawasan Permukiman',
                'pangkat_golongan' => 'Penata / III-c',
                'email' => 'hendra.gunawan@pasuruankab.go.id',
                'no_hp' => '081234567908',
                'is_active' => true,
            ],

            // 2. Mock SIMPEG yang sudah ada di LMS
            [
                'nip' => '198403152009011002',
                'nik' => '3514091503840001',
                'nama' => 'Hendro Prasetyo',
                'gelar_depan' => 'Ir.',
                'gelar_belakang' => 'M.T',
                'jabatan' => 'Kepala Bidang Infrastruktur & Kewilayahan',
                'unit_kerja' => 'Badan Perencanaan Pembangunan Daerah (Bappeda)',
                'pangkat_golongan' => 'Pembina / IV-a',
                'email' => 'hendro.prasetyo@pasuruankab.go.id',
                'no_hp' => '081234567801',
                'is_active' => true,
            ],
            [
                'nip' => '198905202011012003',
                'nik' => '3514106005890002',
                'nama' => 'Ratna Kartikasari',
                'gelar_depan' => 'drg.',
                'gelar_belakang' => 'Sp.KG',
                'jabatan' => 'Dokter Gigi Madya',
                'unit_kerja' => 'RSUD Bangil Kabupaten Pasuruan',
                'pangkat_golongan' => 'Pembina Tingkat I / IV-b',
                'email' => 'ratna.kartika@pasuruankab.go.id',
                'no_hp' => '081234567802',
                'is_active' => true,
            ],
            [
                'nip' => '199307122019021004',
                'nik' => '3514111207930003',
                'nama' => 'Bayu Wicaksono',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.Kom, M.Eng',
                'jabatan' => 'Pranata Komputer Ahli Pertama (Keamanan Siber)',
                'unit_kerja' => 'Dinas Komunikasi dan Informatika',
                'pangkat_golongan' => 'Penata / III-c',
                'email' => 'bayu.wicaksono@pasuruankab.go.id',
                'no_hp' => '081234567803',
                'is_active' => true,
            ],
            [
                'nip' => '198711042010012005',
                'nik' => '3514124411870004',
                'nama' => 'Anisa Pujiastuti',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.Pd, M.Pd',
                'jabatan' => 'Pengawas Sekolah Ahli Madya',
                'unit_kerja' => 'Dinas Pendidikan dan Kebudayaan',
                'pangkat_golongan' => 'Pembina / IV-a',
                'email' => 'anisa.puji@pasuruankab.go.id',
                'no_hp' => '081234567804',
                'is_active' => true,
            ],
            [
                'nip' => '199502182020121006',
                'nik' => '3514131802950005',
                'nama' => 'Fajar Kurniawan',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.STP',
                'jabatan' => 'Analis SDM Aparatur Ahli Pertama',
                'unit_kerja' => 'Badan Kepegawaian dan Pengembangan SDM',
                'pangkat_golongan' => 'Penata Muda / III-a',
                'email' => 'fajar.kurniawan@pasuruankab.go.id',
                'no_hp' => '081234567805',
                'is_active' => true,
            ],
            [
                'nip' => '199009112015032007',
                'nik' => '3514145109900006',
                'nama' => 'Nur Indah Wahyuni',
                'gelar_depan' => 'Dra.',
                'gelar_belakang' => 'M.Si',
                'jabatan' => 'Auditor Ahli Muda',
                'unit_kerja' => 'Inspektorat Daerah Kabupaten Pasuruan',
                'pangkat_golongan' => 'Penata Tingkat I / III-d',
                'email' => 'indah.wahyuni@pasuruankab.go.id',
                'no_hp' => '081234567806',
                'is_active' => true,
            ],
            [
                'nip' => '199401222019031008',
                'nik' => '3514152201940007',
                'nama' => 'Rizky Firmansyah',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.H',
                'jabatan' => 'Perancang Peraturan Perundang-undangan',
                'unit_kerja' => 'Bagian Hukum Sekretariat Daerah',
                'pangkat_golongan' => 'Penata Muda Tingkat I / III-b',
                'email' => 'rizky.firmansyah@pasuruankab.go.id',
                'no_hp' => '081234567807',
                'is_active' => true,
            ],
            [
                'nip' => '198604192010012009',
                'nik' => '3514165904860008',
                'nama' => 'Siti Aisyah',
                'gelar_depan' => 'Hj.',
                'gelar_belakang' => 'S.Kep, Ners',
                'jabatan' => 'Administrator Kesehatan Ahli Muda',
                'unit_kerja' => 'Dinas Kesehatan Kabupaten Pasuruan',
                'pangkat_golongan' => 'Penata Tingkat I / III-d',
                'email' => 'siti.aisyah@pasuruankab.go.id',
                'no_hp' => '081234567808',
                'is_active' => true,
            ],
            [
                'nip' => '199208172019031008',
                'nik' => '3514171708920009',
                'nama' => 'Rahmat Hidayat',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.STP',
                'jabatan' => 'Staf Pelaksana',
                'unit_kerja' => 'BKPSDM Kabupaten Pasuruan',
                'pangkat_golongan' => 'Penata Muda / III-a',
                'email' => 'peserta.asn@pasuruankab.go.id',
                'no_hp' => '081234567890',
                'is_active' => true,
            ],
            [
                'nip' => '199407062023211019',
                'nik' => '3514180607940010',
                'nama' => 'Mifron Andysatiko',
                'gelar_depan' => '',
                'gelar_belakang' => 'S.Kom',
                'jabatan' => 'Pranata Komputer Ahli Pertama',
                'unit_kerja' => 'BKPSDM Kabupaten Pasuruan',
                'pangkat_golongan' => 'Penata Muda / III-a',
                'email' => 'mifronandysatiko@gmail.com',
                'no_hp' => '081234567999',
                'is_active' => true,
            ],
        ];

        foreach ($pegawaiList as $item) {
            DataPegawai::updateOrCreate(
                ['nip' => $item['nip']],
                $item
            );
        }

        // Sinkronkan NIK ke tabel user_profiles untuk akun User yang memiliki NIP cocok
        foreach ($pegawaiList as $p) {
            $matchedUser = User::where('nip', $p['nip'])->first();
            if ($matchedUser) {
                UserProfile::updateOrCreate(
                    ['user_id' => $matchedUser->id],
                    [
                        'nik' => $p['nik'],
                        'full_name' => $p['nama'],
                        'gelar_depan' => $p['gelar_depan'],
                        'gelar_belakang' => $p['gelar_belakang'],
                        'jabatan' => $p['jabatan'],
                        'unit_kerja' => $p['unit_kerja'],
                        'pangkat_golongan' => $p['pangkat_golongan'],
                        'kategori_peserta' => 'asn_pasuruan',
                    ]
                );
            }
        }

        // Perbarui profil pengguna yang sudah ada agar kategori_peserta & NIK umum terisi
        $usersWithNip = User::whereNotNull('nip')->get();
        foreach ($usersWithNip as $idx => $u) {
            $prof = UserProfile::firstOrCreate(['user_id' => $u->id], [
                'full_name' => $u->name,
                'unit_kerja' => 'Pemerintah Kabupaten Pasuruan',
            ]);
            if (empty($prof->nik)) {
                $prof->update([
                    'nik' => '3514' . str_pad((string)($u->id * 111111111111), 12, '0', STR_PAD_LEFT),
                    'kategori_peserta' => 'asn_pasuruan'
                ]);
            }
        }

        $usersWithoutNip = User::whereNull('nip')->get();
        foreach ($usersWithoutNip as $idx => $u) {
            $prof = UserProfile::firstOrCreate(['user_id' => $u->id], [
                'full_name' => $u->name,
                'unit_kerja' => 'Umum / Non-Pemerintah',
                'jabatan' => 'Peserta Terbuka',
            ]);
            if (empty($prof->nik)) {
                $prof->update([
                    'nik' => '351499' . str_pad((string)($u->id * 1234567890), 10, '0', STR_PAD_LEFT),
                    'kategori_peserta' => 'umum'
                ]);
            }
        }
    }
}
