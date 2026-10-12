<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Webinar;
use App\Models\WebinarQuestion;

$webinars = Webinar::all();

if ($webinars->isEmpty()) {
    echo "No webinars found.\n";
    exit(1);
}

foreach ($webinars as $webinar) {
    // Cek jika sudah ada pertanyaan
    if (WebinarQuestion::where('webinar_id', $webinar->id)->count() > 0) {
        echo "Webinar {$webinar->id} already has questions. Skipping...\n";
        continue;
    }

    echo "Seeding questions for Webinar {$webinar->id}: {$webinar->title}...\n";

    if ($webinar->id == 1) {
        // Soal Kuis Evaluasi (Quiz)
        $quizQuestions = [
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
        ];

        // Pertanyaan Kuesioner Kepuasan (Questionnaire)
        $questionnaireQuestions = [
            [
                'type' => 'questionnaire',
                'question_text' => 'Bagaimana penilaian Anda terhadap penguasaan materi dan kejelasan penyampaian narasumber?',
                'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang'],
                'correct_answer' => null,
                'order_number' => 1,
            ],
            [
                'type' => 'questionnaire',
                'question_text' => 'Kualitas jaringan siaran langsung, audio visual, dan kestabilan ruang webinar:',
                'options' => ['Sangat Memuaskan', 'Cukup Memuaskan', 'Kurang Memuaskan', 'Tidak Memuaskan'],
                'correct_answer' => null,
                'order_number' => 2,
            ],
            [
                'type' => 'questionnaire',
                'question_text' => 'Tingkat relevansi materi bimbingan teknis terhadap tugas dan fungsi Anda sehari-hari:',
                'options' => ['Sangat Relevan', 'Cukup Relevan', 'Kurang Relevan', 'Tidak Relevan'],
                'correct_answer' => null,
                'order_number' => 3,
            ],
            [
                'type' => 'questionnaire',
                'question_text' => 'Secara umum, bagaimana efektivitas alur sequential gating (presensi -> kuesioner -> kuis) pada LMS ini?',
                'options' => ['Sangat Tertib & Jelas', 'Cukup Baik', 'Perlu Penyesuaian Waktu', 'Membingungkan'],
                'correct_answer' => null,
                'order_number' => 4,
            ],
        ];
    } else {
        // Default generic questions for Webinar 2 & 3
        $quizQuestions = [
            [
                'type' => 'quiz',
                'question_text' => 'Apa tujuan utama diadakannya kegiatan peningkatan kompetensi ASN ini?',
                'options' => [
                    'A' => 'Meningkatkan profesionalitas, integritas, dan kualitas pelayanan kepada masyarakat',
                    'B' => 'Sekadar memenuhi jam kerja tanpa implementasi kerja nyata',
                    'C' => 'Mengurangi koordinasi antar bidang dalam unit kerja',
                    'D' => 'Menghindari tugas pelayanan publik di kantor',
                ],
                'correct_answer' => 'A',
                'order_number' => 1,
            ],
            [
                'type' => 'quiz',
                'question_text' => 'Prinsip akuntabilitas dalam pengelolaan kinerja aparatur menuntut setiap ASN untuk:',
                'options' => [
                    'A' => 'Bertanggung jawab atas pencapaian kinerja dan transparansi hasil kerja',
                    'B' => 'Menyembunyikan kendala kerja dari pimpinan',
                    'C' => 'Mengabaikan masukan evaluasi dari rekan kerja',
                    'D' => 'Bekerja secara individualistis tanpa sinergi tim',
                ],
                'correct_answer' => 'A',
                'order_number' => 2,
            ],
        ];

        $questionnaireQuestions = [
            [
                'type' => 'questionnaire',
                'question_text' => 'Bagaimana penilaian Anda terhadap materi yang disampaikan oleh narasumber?',
                'options' => ['Sangat Bermanfaat', 'Bermanfaat', 'Cukup Bermanfaat', 'Kurang Bermanfaat'],
                'correct_answer' => null,
                'order_number' => 1,
            ],
            [
                'type' => 'questionnaire',
                'question_text' => 'Bagaimana kenyamanan Anda dalam mengakses materi pada LMS ini?',
                'options' => ['Sangat Mudah', 'Cukup Mudah', 'Kurang Mudah', 'Sulit'],
                'correct_answer' => null,
                'order_number' => 2,
            ],
        ];
    }

    foreach (array_merge($quizQuestions, $questionnaireQuestions) as $q) {
        WebinarQuestion::create([
            'webinar_id' => $webinar->id,
            'type' => $q['type'],
            'question_text' => $q['question_text'],
            'options' => $q['options'],
            'correct_answer' => $q['correct_answer'],
            'order_number' => $q['order_number'],
        ]);
    }
}

echo "Successfully seeded webinar questions!\n";
