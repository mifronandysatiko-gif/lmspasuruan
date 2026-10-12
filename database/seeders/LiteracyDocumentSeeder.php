<?php

namespace Database\Seeders;

use App\Models\LiteracyDocument;
use App\Models\User;
use Illuminate\Database\Seeder;

class LiteracyDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();
        $penyelenggara = User::where('role', 'penyelenggara')->first();

        $adminId = $superAdmin ? $superAdmin->id : 1;
        $penyelenggaraId = $penyelenggara ? $penyelenggara->id : $adminId;

        $documents = [
            [
                'title' => 'Jurnal Tata Kelola & Inovasi Birokrasi Pemkab Pasuruan: Transformasi Digital Layanan Publik Berbasis SPBE',
                'slug' => 'jurnal-spbe-pasuruan-2026',
                'category' => 'jurnal',
                'author' => 'Tim Litbang Bappeda & Diskominfo Kabupaten Pasuruan',
                'institution' => 'Bappeda & Diskominfo Kabupaten Pasuruan',
                'publication_year' => 2026,
                'edition_volume' => 'Vol. 5 No. 1 (Januari - Juni 2026)',
                'isbn_issn' => 'ISSN: 2685-9122',
                'description' => 'Penelitian ini mengevaluasi arsitektur Sistem Pemerintahan Berbasis Elektronik (SPBE) Pemerintah Kabupaten Pasuruan dalam mengintegrasikan layanan administrasi kependudukan, perizinan terpadu, dan portal layanan ASN. Hasil telaah menunjukkan percepatan waktu siklus layanan hingga 64% serta peningkatan kepuasan masyarakat melalui interoperabilitas data antar Perangkat Daerah.',
                'keywords' => 'SPBE, Transformasi Digital, Pasuruan Maslahat, Layanan Publik',
                'file_path' => 'literasi/jurnal-spbe-pasuruan-2026.pdf',
                'file_size_kb' => 2450,
                'cover_image' => null,
                'view_count' => 0,
                'download_count' => 0,
                'is_published' => true,
                'uploaded_by' => $adminId,
            ],
            [
                'title' => 'Analisis Efektivitas Program "Sakera Mesem" dalam Peningkatan Kualitas Pelayanan Adminduk Terintegrasi',
                'slug' => 'karya-ilmiah-sakera-mesem-2026',
                'category' => 'karya_ilmiah',
                'author' => 'Kusuma Wardani, S.STP & Tim Inovasi Pelayanan',
                'institution' => 'Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pasuruan',
                'publication_year' => 2026,
                'edition_volume' => 'Karya Tulis Inovasi Kompetisi Pelayanan Publik (KIPP)',
                'isbn_issn' => null,
                'description' => 'Karya ilmiah dan dokumentasi praktik terbaik inovasi Sakera Mesem (Sistem Administrasi Kependudukan Efektif, Responsif, Akuntabel Menuju Sejahtera). Inovasi ini menjangkau 24 kecamatan termasuk wilayah pelosok lereng Bromo melalui layanan jemput bola dan perekaman keliling.',
                'keywords' => 'Inovasi Daerah, Sakera Mesem, Adminduk, KIPP 2026',
                'file_path' => 'literasi/karya-ilmiah-sakera-mesem-2026.pdf',
                'file_size_kb' => 3120,
                'cover_image' => null,
                'view_count' => 0,
                'download_count' => 0,
                'is_published' => true,
                'uploaded_by' => $penyelenggaraId,
            ],
            [
                'title' => 'Policy Brief: Optimalisasi Pola Karier dan Manajemen Talenta ASN Berdasarkan Sistem Merit',
                'slug' => 'policy-brief-merit-system-pasuruan-2026',
                'category' => 'policy_brief',
                'author' => 'Dr. H. Ahmad Fauzi, M.Pd (Widyaiswara Ahli Utama)',
                'institution' => 'Badan Kepegawaian dan Pengembangan SDM Kabupaten Pasuruan',
                'publication_year' => 2026,
                'edition_volume' => 'Seri Kebijakan Kepegawaian No. 03/BKPSDM/2026',
                'isbn_issn' => 'ISBN: 978-623-09-1122-4',
                'description' => 'Rekomendasi kebijakan strategis bagi pengambil keputusan di lingkungan Pemerintah Kabupaten Pasuruan mengenai akselerasi penerapan manajemen talenta (talent pool 9 kotak). Menekankan pentingnya pemenuhan 20 Jam Pelajaran (JP) pelatihan tahunan per aparatur sebagai indikator kesiapan promosi jabatan.',
                'keywords' => 'Policy Brief, Manajemen Talenta, Sistem Merit, Pola Karier ASN',
                'file_path' => 'literasi/policy-brief-merit-system-pasuruan-2026.pdf',
                'file_size_kb' => 1840,
                'cover_image' => null,
                'view_count' => 0,
                'download_count' => 0,
                'is_published' => true,
                'uploaded_by' => $adminId,
            ],
            [
                'title' => 'Panduan Implementasi Budaya Kerja BerAKHLAK dan Harmonisasi Pelayanan Publik Lingkup Pemkab Pasuruan',
                'slug' => 'modul-berakhlak-pasuruan-2026',
                'category' => 'modul_diklat',
                'author' => 'Bidang Pengembangan Kompetensi BKPSDM Kabupaten Pasuruan',
                'institution' => 'BKPSDM Kabupaten Pasuruan',
                'publication_year' => 2025,
                'edition_volume' => 'Buku Pedoman Pegawai Edisi Revisi',
                'isbn_issn' => 'ISBN: 978-602-18-9843-1',
                'description' => 'Modul komprehensif panduan operasionalisasi 7 core values ASN (Berorientasi Pelayanan, Akuntabel, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif) dalam aktivitas harian aparatur sipil negara di lingkungan Pemerintah Kabupaten Pasuruan.',
                'keywords' => 'BerAKHLAK, Budaya Kerja, Core Values ASN, Etika Birokrasi',
                'file_path' => 'literasi/modul-berakhlak-pasuruan-2026.pdf',
                'file_size_kb' => 4560,
                'cover_image' => null,
                'view_count' => 0,
                'download_count' => 0,
                'is_published' => true,
                'uploaded_by' => $penyelenggaraId,
            ],
            [
                'title' => 'Strategi Pengurangan Emisi Karbon dan Mitigasi Risiko Lingkungan pada Kawasan Industri Pasuruan',
                'slug' => 'karya-ilmiah-lingkungan-pasuruan-2026',
                'category' => 'karya_ilmiah',
                'author' => 'Ir. Hendro Prasetyo, M.T & Tim Pengendalian Lingkungan',
                'institution' => 'Dinas Lingkungan Hidup Kabupaten Pasuruan',
                'publication_year' => 2025,
                'edition_volume' => 'Prosiding Seminar Nasional Lingkungan Hidup 2025',
                'isbn_issn' => 'ISSN: 2541-1189',
                'description' => 'Karya penelitian terapan mengenai model kolaborasi pentahelix antara pemerintah daerah, korporasi kawasan industri PIER, dan masyarakat sekitar dalam pengelolaan limbah terpadu serta perluasan ruang terbuka hijau penunjang mitigasi perubahan iklim.',
                'keywords' => 'Mitigasi Lingkungan, Kawasan Industri, PIER Pasuruan, Green City',
                'file_path' => 'literasi/karya-ilmiah-lingkungan-pasuruan-2026.pdf',
                'file_size_kb' => 2890,
                'cover_image' => null,
                'view_count' => 0,
                'download_count' => 0,
                'is_published' => true,
                'uploaded_by' => $adminId,
            ],
        ];

        foreach ($documents as $doc) {
            LiteracyDocument::updateOrCreate(['slug' => $doc['slug']], $doc);
        }
    }
}