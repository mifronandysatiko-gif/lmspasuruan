<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\LeadershipLogbook;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Webinar;
use App\Models\WebinarAttendance;
use App\Models\WebinarExamAttempt;
use App\Models\WebinarQuestion;
use App\Models\WebinarQuestionnaireResponse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Pengaturan Dinamis Sistem (No-Code Admin Settings)
        $settings = [
            [
                'setting_key' => 'app_name',
                'setting_value' => 'LMS Kabupaten Pasuruan',
                'setting_group' => 'branding',
                'field_type' => 'text',
                'label' => 'Nama Aplikasi',
                'description' => 'Nama resmi portal pembelajaran BKPSDM',
            ],
            [
                'setting_key' => 'app_tagline',
                'setting_value' => 'Mewujudkan ASN BerAKHLAK, Profesional, dan Berdaya Saing',
                'setting_group' => 'branding',
                'field_type' => 'text',
                'label' => 'Motto / Tagline',
                'description' => 'Slogan yang tampil pada header dan hero beranda',
            ],
            [
                'setting_key' => 'footer_copyright',
                'setting_value' => '© 2026 Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM) Pemerintah Kabupaten Pasuruan. Hak Cipta Dilindungi Undang-Undang.',
                'setting_group' => 'branding',
                'field_type' => 'textarea',
                'label' => 'Teks Footer & Hak Cipta',
                'description' => 'Teks legalitas di bagian bawah portal',
            ],
            [
                'setting_key' => 'marquee_announcement',
                'setting_value' => 'PENGUMUMAN PENTING: Pendaftaran Bimtek SPBE dan Pelatihan Kepemimpinan Pasuruan dibuka hingga 30 September 2026. Presensi Webinar wajib dilakukan saat sesi interaktif live berlangsung.',
                'setting_group' => 'branding',
                'field_type' => 'textarea',
                'label' => 'Teks Berjalan (Running Marquee)',
                'description' => 'Teks berjalan untuk informasi penting di bagian atas website',
            ],
            [
                'setting_key' => 'jp_duration_minutes',
                'setting_value' => '45',
                'setting_group' => 'general',
                'field_type' => 'number',
                'label' => 'Standar Durasi 1 JP (Menit)',
                'description' => 'Durasi menit per 1 Jam Pelajaran (JP)',
            ],
            [
                'setting_key' => 'default_passing_grade',
                'setting_value' => '75',
                'setting_group' => 'evaluation',
                'field_type' => 'number',
                'label' => 'Standar Passing Grade Evaluasi',
                'description' => 'Batas minimal nilai kelulusan post-test / ujian',
            ],
            [
                'setting_key' => 'default_max_remedial',
                'setting_value' => '5',
                'setting_group' => 'evaluation',
                'field_type' => 'number',
                'label' => 'Maksimal Kesempatan Remedial',
                'description' => 'Batas toleransi pengulangan ujian peserta jika belum lulus',
            ],
            [
                'setting_key' => 'default_remedial_cap_score',
                'setting_value' => '90',
                'setting_group' => 'evaluation',
                'field_type' => 'number',
                'label' => 'Plafon Nilai Remedial (Score Capping)',
                'description' => 'Nilai remedial tidak boleh melebihi plafon ini meskipun skor 100',
            ],
            [
                'setting_key' => 'helpdesk_whatsapp',
                'setting_value' => '0812-3456-7890',
                'setting_group' => 'contact',
                'field_type' => 'text',
                'label' => 'Nomor WhatsApp Helpdesk',
                'description' => 'Kontak pusat bantuan cepat BKPSDM Pasuruan',
            ],
            [
                'setting_key' => 'helpdesk_email',
                'setting_value' => 'bkpsdm@pasuruankab.go.id',
                'setting_group' => 'contact',
                'field_type' => 'text',
                'label' => 'Email Layanan Bantuan',
                'description' => 'Email resmi tiket pengaduan dan bantuan teknis',
            ],
            [
                'setting_key' => 'office_address',
                'setting_value' => 'Komplek Perkantoran Pemkab Pasuruan, Jl. Hayam Wuruk No. 14, Bangil, Pasuruan, Jawa Timur 67153',
                'setting_group' => 'contact',
                'field_type' => 'textarea',
                'label' => 'Alamat Kantor BKPSDM',
                'description' => 'Alamat fisik kantor BKPSDM Kabupaten Pasuruan',
            ],
            [
                'setting_key' => 'maintenance_mode',
                'setting_value' => '0',
                'setting_group' => 'general',
                'field_type' => 'boolean',
                'label' => 'Mode Pemeliharaan (Maintenance Mode)',
                'description' => 'Kunci akses publik saat sistem dalam pembaharuan berkala',
            ],
            [
                'setting_key' => 'allow_public_registration',
                'setting_value' => '1',
                'setting_group' => 'general',
                'field_type' => 'boolean',
                'label' => 'Buka Pendaftaran Akun Peserta Umum',
                'description' => 'Izinkan masyarakat / peserta non-ASN mendaftar webinar terbuka',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(['setting_key' => $setting['setting_key']], $setting);
        }

        // 2. Akun Pengguna Realistis (Semua Peran Sesuai PRD)
        $password = Hash::make('password123');

        // A. Super Admin BKPSDM
        $admin = User::create([
            'name' => 'Budi Santoso, S.STP, M.Si',
            'nip' => '198005122005011003',
            'username' => 'admin_bkpsdm',
            'email' => 'admin@pasuruankab.go.id',
            'password' => $password,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        UserProfile::create([
            'user_id' => $admin->id,
            'nik' => '3514051205800001',
            'full_name' => 'Budi Santoso',
            'gelar_depan' => '',
            'gelar_belakang' => 'S.STP, M.Si',
            'jabatan' => 'Pranata Komputer Ahli Madya / Administrator LMS',
            'unit_kerja' => 'BKPSDM Kabupaten Pasuruan',
            'phone_number' => '0811-2345-6789',
            'kategori_peserta' => 'asn_pasuruan',
        ]);

        // B. Penyelenggara Event Webinar / Bimtek
        $penyelenggara = User::create([
            'name' => 'Siti Nurhaliza, S.Sos, M.AP',
            'nip' => '198502142010012005',
            'username' => 'penyelenggara_diklat',
            'email' => 'diklat@pasuruankab.go.id',
            'password' => $password,
            'role' => 'penyelenggara',
            'is_active' => true,
        ]);
        UserProfile::create([
            'user_id' => $penyelenggara->id,
            'nik' => '3514025402850002',
            'full_name' => 'Siti Nurhaliza',
            'gelar_depan' => '',
            'gelar_belakang' => 'S.Sos, M.AP',
            'jabatan' => 'Analis Pengembangan Kompetensi Aparatur',
            'unit_kerja' => 'Bidang Pengembangan Kompetensi BKPSDM',
            'phone_number' => '0812-9876-5432',
            'kategori_peserta' => 'asn_pasuruan',
        ]);

        // C. Widyaiswara (Coach Pelatihan Kepemimpinan & Pengampu)
        $coach = User::create([
            'name' => 'Dr. H. Ahmad Fauzi, M.Pd',
            'nip' => '197608221999031002',
            'username' => 'coach_widyaiswara',
            'email' => 'coach.fauzi@pasuruankab.go.id',
            'password' => $password,
            'role' => 'widyaiswara',
            'is_active' => true,
        ]);
        UserProfile::create([
            'user_id' => $coach->id,
            'nik' => '3514082208760003',
            'full_name' => 'Ahmad Fauzi',
            'gelar_depan' => 'Dr. H.',
            'gelar_belakang' => 'M.Pd',
            'jabatan' => 'Widyaiswara Ahli Utama / Coach Diklat Kepemimpinan',
            'unit_kerja' => 'Badan Kepegawaian dan Pengembangan SDM',
            'phone_number' => '0813-1122-3344',
            'kategori_peserta' => 'asn_pasuruan',
        ]);

        // D. Mentor (Atasan Langsung Peserta Kepemimpinan)
        $mentor = User::create([
            'name' => 'Ir. Bambang Trihatmojo, M.M',
            'nip' => '197403151998031004',
            'username' => 'mentor_kabid',
            'email' => 'mentor.bambang@pasuruankab.go.id',
            'password' => $password,
            'role' => 'mentor',
            'is_active' => true,
        ]);
        UserProfile::create([
            'user_id' => $mentor->id,
            'nik' => '3514031503740004',
            'full_name' => 'Bambang Trihatmojo',
            'gelar_depan' => 'Ir.',
            'gelar_belakang' => 'M.M',
            'jabatan' => 'Kepala Bidang Infrastruktur & Kewilayahan',
            'unit_kerja' => 'Bappeda Kabupaten Pasuruan',
            'phone_number' => '0813-5566-7788',
            'kategori_peserta' => 'asn_pasuruan',
        ]);

        // E. Peserta ASN Pasuruan
        $pesertaAsn = User::create([
            'name' => 'Rahmat Hidayat, S.Kom',
            'nip' => '199208172019031008',
            'username' => 'peserta_asn',
            'email' => 'peserta.asn@pasuruankab.go.id',
            'password' => $password,
            'role' => 'peserta',
            'is_active' => true,
        ]);
        UserProfile::create([
            'user_id' => $pesertaAsn->id,
            'nik' => '3514171708920009',
            'full_name' => 'Rahmat Hidayat',
            'gelar_depan' => '',
            'gelar_belakang' => 'S.Kom',
            'jabatan' => 'Pranata Komputer Ahli Pertama',
            'unit_kerja' => 'Dinas Komunikasi dan Informatika Kabupaten Pasuruan',
            'phone_number' => '0857-1234-5678',
            'kategori_peserta' => 'asn_pasuruan',
        ]);

        // F. Peserta Umum (Non-ASN)
        $pesertaUmum = User::create([
            'name' => 'Dewi Lestari, S.E',
            'nip' => null,
            'username' => 'peserta_umum',
            'email' => 'peserta.umum@gmail.com',
            'password' => $password,
            'role' => 'peserta',
            'is_active' => true,
        ]);
        UserProfile::create([
            'user_id' => $pesertaUmum->id,
            'nik' => '3514995505950001',
            'full_name' => 'Dewi Lestari',
            'gelar_depan' => '',
            'gelar_belakang' => 'S.E',
            'jabatan' => 'Staf Riset dan Pengembangan',
            'unit_kerja' => 'Universitas Yudharta Pasuruan (Masyarakat Umum)',
            'phone_number' => '0878-9876-1234',
            'kategori_peserta' => 'umum',
        ]);

        // 3. Template Sertifikat & Koordinat Visual
        $certTemplate = CertificateTemplate::create([
            'name' => 'Blanko Resmi E-Sertifikat Diklat & Webinar BKPSDM Pasuruan 2026',
            'background_path' => 'templates/blanko_sertifikat_pasuruan.png',
            'layout_coordinates' => [
                'certificate_number' => ['x' => 420, 'y' => 210, 'font_size' => 15, 'align' => 'center'],
                'full_name' => ['x' => 420, 'y' => 295, 'font_size' => 26, 'align' => 'center', 'font_weight' => 'bold'],
                'nip' => ['x' => 420, 'y' => 330, 'font_size' => 14, 'align' => 'center'],
                'training_title' => ['x' => 420, 'y' => 380, 'font_size' => 18, 'align' => 'center', 'font_weight' => 'bold'],
                'jp' => ['x' => 420, 'y' => 415, 'font_size' => 14, 'align' => 'center'],
                'issued_at' => ['x' => 630, 'y' => 480, 'font_size' => 13, 'align' => 'center'],
                'qr_code' => ['x' => 150, 'y' => 460, 'size' => 90],
            ],
        ]);

        // 4. Modul Webinar & Bimtek Terbuka (Fokus Sequential Gating)
        $webinar1 = Webinar::create([
            'organizer_id' => $penyelenggara->id,
            'title' => 'Bimtek Tata Kelola SPBE & Keamanan Siber Pemerintahan Daerah Pasuruan',
            'slug' => 'bimtek-spbe-keamanan-siber-pasuruan-2026',
            'description' => 'Bimbingan teknis komprehensif bagi ASN Pemerintah Kabupaten Pasuruan mengenai implementasi Arsitektur SPBE, manajemen risiko informasi, dan kesiapsiagaan insiden siber pada unit kerja.',
            'banner_path' => 'banners/webinar_spbe.jpg',
            'meeting_link' => 'https://zoom.us/j/9876543210?pwd=pasuruan_spbe',
            'jp' => 4,
            'event_date' => Carbon::now()->toDateString(),
            'start_time' => '08:30:00',
            'end_time' => '12:00:00',
            'is_live' => true, // Sedang live sehingga tombol "Hadir" aktif
            'questionnaire_start_time' => '10:00:00',
            'questionnaire_end_time' => '12:30:00',
            'exam_start_time' => '10:30:00',
            'exam_end_time' => '14:00:00',
            'passing_grade' => 75,
            'max_remedial' => 5,
            'remedial_cap_score' => 90,
        ]);

        $webinar2 = Webinar::create([
            'organizer_id' => $penyelenggara->id,
            'title' => 'Webinar Series: Penerapan Nilai Dasar BerAKHLAK dalam Pelayanan Publik Prima',
            'slug' => 'webinar-berakhlak-pelayanan-publik-2026',
            'description' => 'Internalisasi nilai dasar Berorientasi Pelayanan, Akuntabel, Kompeten, Harmonis, Loyal, Adaptif, dan Kolaboratif untuk mewujudkan pelayanan prima bagi masyarakat Kabupaten Pasuruan.',
            'banner_path' => 'banners/webinar_berakhlak.jpg',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            'jp' => 2,
            'event_date' => Carbon::now()->addDays(3)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '11:30:00',
            'is_live' => false,
            'questionnaire_start_time' => '10:30:00',
            'questionnaire_end_time' => '12:00:00',
            'exam_start_time' => '11:00:00',
            'exam_end_time' => '13:00:00',
            'passing_grade' => 75,
            'max_remedial' => 5,
            'remedial_cap_score' => 90,
        ]);

        $webinar3 = Webinar::create([
            'organizer_id' => $penyelenggara->id,
            'title' => 'Bimtek Penyusunan SKP & Penilaian Kinerja ASN Berdasarkan PermenPANRB No. 6 Tahun 2022',
            'slug' => 'bimtek-skp-permenpanrb-2026',
            'description' => 'Panduan teknis perumusan sasaran kinerja pegawai, ekspektasi pimpinan, dialog kinerja berkelanjutan, dan konversi predikat kinerja pada ASN Pemerintah Kabupaten Pasuruan.',
            'banner_path' => 'banners/webinar_skp.jpg',
            'meeting_link' => 'https://zoom.us/j/1234567890',
            'jp' => 6,
            'event_date' => Carbon::now()->addDays(7)->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '15:00:00',
            'is_live' => false,
            'questionnaire_start_time' => '14:00:00',
            'questionnaire_end_time' => '16:00:00',
            'exam_start_time' => '14:30:00',
            'exam_end_time' => '17:00:00',
            'passing_grade' => 80,
            'max_remedial' => 5,
            'remedial_cap_score' => 90,
        ]);

        // Bank Soal Kuis Evaluasi & Kuesioner Kepuasan Webinar 1
        $webinar1Questions = [
            [
                'type' => 'quiz',
                'question_text' => 'Apa fokus utama penerapan domain Arsitektur SPBE pada Pemerintah Kabupaten Pasuruan?',
                'options' => [
                    'A' => 'Integrasi data layanan publik terpadu antar Perangkat Daerah',
                    'B' => 'Pengadaan server terpisah dan tertutup untuk setiap sub-bagian',
                    'C' => 'Pembatasan akses informasi publik secara sepihak',
                    'D' => 'Penggunaan perangkat lunak bajakan untuk menghemat anggaran',
                ],
                'correct_answer' => 'A',
                'order_number' => 1,
            ],
            [
                'type' => 'quiz',
                'question_text' => 'Berdasarkan prinsip keamanan informasi SPBE, tindakan paling tepat saat menerima email mencurigakan (phishing) dengan lampiran berkas adalah:',
                'options' => [
                    'A' => 'Segera mengklik dan mengunduh berkas lampiran untuk memastikan isinya',
                    'B' => 'Meneruskan email ke seluruh rekan kantor tanpa memeriksa keabsahannya',
                    'C' => 'Jangan membuka lampiran, dan segera laporkan ke Tim CSIRT Pasuruan',
                    'D' => 'Membalas email tersebut dengan menyertakan password akun SIMPEG',
                ],
                'correct_answer' => 'C',
                'order_number' => 2,
            ],
            [
                'type' => 'quiz',
                'question_text' => 'Manakah komponen yang TIDAK termasuk dalam indikator evaluasi kematangan SPBE instansi pemerintah daerah?',
                'options' => [
                    'A' => 'Kebijakan Tata Kelola SPBE Perangkat Daerah',
                    'B' => 'Manajemen Keamanan Informasi dan Audit TIK',
                    'C' => 'Jumlah akun media sosial pribadi milik masing-masing pegawai',
                    'D' => 'Keterpaduan Layanan Publik Berbasis Elektronik',
                ],
                'correct_answer' => 'C',
                'order_number' => 3,
            ],
            [
                'type' => 'quiz',
                'question_text' => 'Apa fungsi utama pembentukan tim Tanggap Insiden Siber (CSIRT) di lingkungan Pemerintah Kabupaten Pasuruan?',
                'options' => [
                    'A' => 'Melakukan koordinasi pencegahan, mitigasi, dan pemulihan insiden keamanan siber',
                    'B' => 'Memperbaiki kerusakan fisik kabel telepon gedung kantor',
                    'C' => 'Menghapus log audit server secara berkala tanpa dokumentasi',
                    'D' => 'Mengawasi penggunaan internet pegawai di luar jam kerja',
                ],
                'correct_answer' => 'A',
                'order_number' => 4,
            ],
            [
                'type' => 'questionnaire',
                'input_type' => 'options',
                'question_text' => 'Bagaimana penilaian Anda terhadap penguasaan materi dan kejelasan penyampaian narasumber?',
                'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang'],
                'correct_answer' => null,
                'order_number' => 1,
            ],
            [
                'type' => 'questionnaire',
                'input_type' => 'options',
                'question_text' => 'Kualitas jaringan siaran langsung, audio visual, dan kestabilan ruang webinar:',
                'options' => ['Sangat Memuaskan', 'Cukup Memuaskan', 'Kurang Memuaskan', 'Tidak Memuaskan'],
                'correct_answer' => null,
                'order_number' => 2,
            ],
            [
                'type' => 'questionnaire',
                'input_type' => 'options',
                'question_text' => 'Tingkat relevansi materi bimbingan teknis terhadap tugas dan fungsi Anda sehari-hari:',
                'options' => ['Sangat Relevan', 'Cukup Relevan', 'Kurang Relevan', 'Tidak Relevan'],
                'correct_answer' => null,
                'order_number' => 3,
            ],
            [
                'type' => 'questionnaire',
                'input_type' => 'options',
                'question_text' => 'Secara umum, bagaimana efektivitas alur sequential gating (presensi -> kuesioner -> kuis) pada LMS ini?',
                'options' => ['Sangat Tertib & Jelas', 'Cukup Baik', 'Perlu Penyesuaian Waktu', 'Membingungkan'],
                'correct_answer' => null,
                'order_number' => 4,
            ],
            [
                'type' => 'questionnaire',
                'input_type' => 'text',
                'question_text' => 'Tuliskan saran, masukan perbaikan, atau topik bimbingan teknis yang Anda harapkan pada sesi berikutnya:',
                'options' => null,
                'correct_answer' => null,
                'order_number' => 5,
            ],
        ];

        foreach ($webinar1Questions as $q) {
            WebinarQuestion::create([
                'webinar_id' => $webinar1->id,
                'type' => $q['type'],
                'input_type' => $q['input_type'] ?? 'options',
                'question_text' => $q['question_text'],
                'options' => $q['options'],
                'correct_answer' => $q['correct_answer'],
                'order_number' => $q['order_number'],
            ]);
        }

        // Presensi untuk Peserta ASN pada Webinar 1
        WebinarAttendance::create([
            'webinar_id' => $webinar1->id,
            'user_id' => $pesertaAsn->id,
            'attended_at' => Carbon::now()->subMinutes(40),
        ]);

        // Respons Kuesioner Kepuasan Peserta ASN pada Webinar 1 (Mendukung Opsi Skala & Isian Teks Bebas)
        $q1 = WebinarQuestion::where('webinar_id', $webinar1->id)->where('type', 'questionnaire')->where('order_number', 1)->first();
        $q2 = WebinarQuestion::where('webinar_id', $webinar1->id)->where('type', 'questionnaire')->where('order_number', 2)->first();
        $q3 = WebinarQuestion::where('webinar_id', $webinar1->id)->where('type', 'questionnaire')->where('order_number', 3)->first();
        $q4 = WebinarQuestion::where('webinar_id', $webinar1->id)->where('type', 'questionnaire')->where('order_number', 4)->first();
        $q5 = WebinarQuestion::where('webinar_id', $webinar1->id)->where('type', 'questionnaire')->where('order_number', 5)->first();

        $sampleAnswers = [];
        if ($q1) $sampleAnswers[$q1->id] = 'Sangat Baik';
        if ($q2) $sampleAnswers[$q2->id] = 'Sangat Memuaskan';
        if ($q3) $sampleAnswers[$q3->id] = 'Sangat Relevan';
        if ($q4) $sampleAnswers[$q4->id] = 'Sangat Tertib & Jelas';
        if ($q5) $sampleAnswers[$q5->id] = 'Materi sangat aplikatif untuk pengamanan aset digital OPD. Disarankan ada sesi praktik simulasi incident response pada pertemuan lanjutan.';

        WebinarQuestionnaireResponse::create([
            'webinar_id' => $webinar1->id,
            'user_id' => $pesertaAsn->id,
            'answers' => $sampleAnswers,
            'submitted_at' => Carbon::now()->subMinutes(30),
        ]);

        // Percobaan Ujian Peserta ASN (Attempt 1: Gagal, Attempt 2: Remedial dengan Plafon 90)
        WebinarExamAttempt::create([
            'webinar_id' => $webinar1->id,
            'user_id' => $pesertaAsn->id,
            'attempt_number' => 1,
            'raw_score' => 65.00,
            'final_score' => 65.00,
            'is_passed' => false,
            'created_at' => Carbon::now()->subMinutes(25),
        ]);

        WebinarExamAttempt::create([
            'webinar_id' => $webinar1->id,
            'user_id' => $pesertaAsn->id,
            'attempt_number' => 2,
            'raw_score' => 96.00, // Skor 96
            'final_score' => 90.00, // Capped di 90 sesuai PRD!
            'is_passed' => true,
            'created_at' => Carbon::now()->subMinutes(10),
        ]);

        // 5. Sertifikat Terbit & Verifikasi Publik
        $cert1 = Certificate::create([
            'certificate_number' => '893.3/0412/BKPSDM-PAS/2026',
            'hash_token' => 'PAS-2026-9B4F-7K2X-M8PQ-3W1R-5J6T',
            'user_id' => $pesertaAsn->id,
            'webinar_id' => $webinar1->id,
            'template_id' => $certTemplate->id,
            'training_title' => 'Bimtek Tata Kelola SPBE & Keamanan Siber Pemerintahan Daerah Pasuruan',
            'jp' => 4,
            'pdf_path' => 'certificates/cert_001.pdf',
            'issued_at' => Carbon::now()->subMinutes(5),
        ]);

        // 6. Kursus MOOC & Pelatihan Mandiri
        $course1 = Course::create([
            'title' => 'Orientasi Nilai & Etika Core Values BerAKHLAK ASN Kabupaten Pasuruan',
            'slug' => 'orientasi-berakhlak-pasuruan',
            'type' => 'mooc',
            'category' => 'Integritas & Karakter ASN',
            'description' => 'Kursus mandiri asynchronous wajib bagi seluruh ASN Kabupaten Pasuruan untuk memperkuat pondasi budaya kerja BerAKHLAK dalam mendukung reformasi birokrasi tematik.',
            'banner_path' => 'banners/course_berakhlak.jpg',
            'jp' => 10,
            'passing_grade' => 75,
            'status' => 'published',
        ]);

        $course2 = Course::create([
            'title' => 'Sistem Pengendalian Intern Pemerintah (SPIP) Terintegrasi & Manajemen Risiko',
            'slug' => 'spip-manajemen-risiko-pasuruan',
            'type' => 'teknis_fungsional',
            'category' => 'Akuntabilitas & Tata Kelola',
            'description' => 'Pelatihan teknis berbasis kelompok mengenai identifikasi risiko operasional dan penyusunan register risiko perangkat daerah di Kabupaten Pasuruan.',
            'banner_path' => 'banners/course_spip.jpg',
            'jp' => 16,
            'passing_grade' => 75,
            'status' => 'published',
        ]);

        $course3 = Course::create([
            'title' => 'Pelatihan Kepemimpinan Pengawas (PKP) Angkatan IV Tahun 2026',
            'slug' => 'pkp-angkatan-iv-pasuruan-2026',
            'type' => 'kepemimpinan',
            'category' => 'Pengembangan Kepemimpinan',
            'description' => 'Program pelatihan kepemimpinan struktural pengawas dengan metode blended learning: On-Campus 1, Off-Campus Laboratorium Kepemimpinan (Aksi Perubahan), dan On-Campus 2 Seminar Akhir.',
            'banner_path' => 'banners/course_pkp.jpg',
            'jp' => 72,
            'passing_grade' => 80,
            'status' => 'published',
        ]);

        // Materi Modul Course 1 (MOOC)
        CourseLesson::create([
            'course_id' => $course1->id,
            'title' => 'Modul 1: Landasan Filosofis & Regulasi Budaya Kerja ASN BerAKHLAK',
            'sequence' => 1,
            'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'duration_seconds' => 720,
            'pdf_path' => 'documents/modul_1_landasan_berakhlak.pdf',
            'content_summary' => 'Menjelaskan Surat Edaran MenPANRB No 20 Tahun 2021 dan relevansinya terhadap visi misi Bupati Pasuruan.',
            'has_quiz' => true,
        ]);

        CourseLesson::create([
            'course_id' => $course1->id,
            'title' => 'Modul 2: Berorientasi Pelayanan & Akuntabel dalam Tindakan Nyata',
            'sequence' => 2,
            'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'duration_seconds' => 900,
            'pdf_path' => 'documents/modul_2_pelayanan_akuntabel.pdf',
            'content_summary' => 'Studi kasus implementasi kepuasan masyarakat di loket pelayanan terpadu Kabupaten Pasuruan.',
            'has_quiz' => true,
        ]);

        CourseLesson::create([
            'course_id' => $course1->id,
            'title' => 'Modul 3: Kolaborasi Lintas OPD Mempercepat Penyelesaian Stunting & Kemiskinan Ekstrem',
            'sequence' => 3,
            'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'duration_seconds' => 850,
            'pdf_path' => 'documents/modul_3_kolaborasi_opd.pdf',
            'content_summary' => 'Sinergi lintas sektoral antara Dinkes, Dinsos, Bappeda, dan Pemerintah Desa di Kabupaten Pasuruan.',
            'has_quiz' => true,
        ]);

        // Pendaftaran Kursus Peserta ASN
        CourseEnrollment::create([
            'course_id' => $course1->id,
            'user_id' => $pesertaAsn->id,
            'progress_percentage' => 67,
            'status' => 'in_progress',
            'final_score' => null,
        ]);

        // 7. Logbook Kepemimpinan (Aksi Perubahan)
        LeadershipLogbook::create([
            'user_id' => $pesertaAsn->id,
            'coach_id' => $coach->id,
            'mentor_id' => $mentor->id,
            'week_number' => 1,
            'milestone_title' => 'Milestone 1: Pembentukan Tim Efektif & SK Tim Kerja Aksi Perubahan',
            'activity_date' => Carbon::now()->subDays(14)->toDateString(),
            'activity_description' => 'Melaksanakan rapat koordinasi awal bersama stakeholder internal Diskominfo dan penyusunan draf Keputusan Kepala Dinas mengenai Pembentukan Tim Efektif Digitalisasi SPBE.',
            'evidence_file_name' => 'SK_Tim_Efektif_dan_Notula_Rapat_Minggu_1.pdf',
            'status' => 'approved',
            'coach_feedback' => 'Struktur tim kerja sangat solid dan pembagian tugas per pokja sudah jelas. Lanjutkan ke milestone berikutnya.',
            'mentor_feedback' => 'SK sudah saya tandatangani selaku atasan langsung. Pastikan jadwal pertemuan mingguan tim tetap disiplin.',
            'approved_at' => Carbon::now()->subDays(10),
        ]);

        LeadershipLogbook::create([
            'user_id' => $pesertaAsn->id,
            'coach_id' => $coach->id,
            'mentor_id' => $mentor->id,
            'week_number' => 2,
            'milestone_title' => 'Milestone 2: Penyusunan Standar Operasional Prosedur (SOP) Keamanan Siber',
            'activity_date' => Carbon::now()->subDays(7)->toDateString(),
            'activity_description' => 'Finalisasi draf SOP penanganan insiden siber pada server OPD Kabupaten Pasuruan serta konsultasi teknis ke Tim CSIRT Provinsi Jawa Timur.',
            'evidence_file_name' => 'Draf_SOP_Keamanan_Informasi_V1.pdf',
            'status' => 'revision',
            'coach_feedback' => 'Perjelas matriks eskalasi insiden pada diagram alur halaman 4 agar tidak membingungkan operator dinas teknis.',
            'mentor_feedback' => 'Mohon diselaraskan dengan Perbup tentang Keamanan Informasi yang baru disahkan bulan lalu.',
            'approved_at' => null,
        ]);

        LeadershipLogbook::create([
            'user_id' => $pesertaAsn->id,
            'coach_id' => $coach->id,
            'mentor_id' => $mentor->id,
            'week_number' => 3,
            'milestone_title' => 'Milestone 3: Uji Coba Simulasi Pemulihan Bencana Sistem SPBE',
            'activity_date' => Carbon::now()->subDays(1)->toDateString(),
            'activity_description' => 'Melakukan ujicoba failover server basis data dari Data Center utama ke Backup Server Disaster Recovery Center (DRC) BKPSDM.',
            'evidence_file_name' => 'Laporan_Uji_Coba_DRC_Server.pdf',
            'status' => 'submitted',
            'coach_feedback' => null,
            'mentor_feedback' => null,
            'approved_at' => null,
        ]);

        // 8. Audit Log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'UPDATE_SYSTEM_SETTINGS',
            'auditable_type' => 'App\Models\SystemSetting',
            'auditable_id' => null,
            'old_values' => ['remedial_cap_score' => 85],
            'new_values' => ['remedial_cap_score' => 90],
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::now()->subHours(2),
        ]);

        AuditLog::create([
            'user_id' => $penyelenggara->id,
            'action' => 'TRIGGER_START_SESSION',
            'auditable_type' => 'App\Models\Webinar',
            'auditable_id' => $webinar1->id,
            'old_values' => ['is_live' => false],
            'new_values' => ['is_live' => true],
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::now()->subMinutes(50),
        ]);
    }
}
