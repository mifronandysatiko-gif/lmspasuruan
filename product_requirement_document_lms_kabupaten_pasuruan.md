# Product Requirement Document (PRD)
# Learning Management System (LMS) Kabupaten Pasuruan

* **Versi Dokumen:** 1.0.0
* **Status:** Siap Pengembangan (Ready for Development)
* **Target Rilis:** Fase 1 (MVP)
* **Stack Teknologi Utama:** Laravel (PHP 8.2+), MySQL 8.0+, Tailwind CSS, Alpine.js / Livewire / Blade, Redis / Laravel Queue

---

## 1. Pendahuluan & Ringkasan Eksekutif

### 1.1 Latar Belakang
Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM) Kabupaten Pasuruan membutuhkan platform terintegrasi untuk mengelola pengembangan kompetensi Aparatur Sipil Negara (ASN) secara terstruktur, akuntabel, dan transparan. Selain pelatihan formal kedinasan, platform ini juga ditujukan untuk memfasilitasi transfer pengetahuan yang lebih luas melalui Webinar dan Bimbingan Teknis (Bimtek) daring terbuka bagi aparatur maupun masyarakat umum.

### 1.2 Tujuan Produk
1. **Otomatisasi Pelatihan:** Menyediakan ekosistem pembelajaran digital mencakup model MOOC mandiri, pelatihan kelompok (*cohort*), pelatihan fungsional, dan pelatihan kepemimpinan berjenjang.
2. **Event Knowledge Sharing Terbuka:** Mengakomodasi seminar/webinar/bimtek daring dengan alur presensi *real-time*, kuesioner, evaluasi terikat jendela waktu, dan penerbitan sertifikat instan.
3. **Kemandirian Administrasi (Zero-Code Customization):** Memberikan antarmuka bagi Super Admin untuk mengonfigurasi seluruh aspek non-struktural aplikasi (branding, identitas daerah, kontak, pengumuman, dan bobot default) tanpa perlu mengubah kode sumber.
4. **Akuntabilitas & Validitas Dokumen:** Menyediakan generator e-sertifikat berbasis template grafis dan barcode/QR code yang terverifikasi publik tanpa ketergantungan API pihak ketiga.

---

## 2. Aktor & Kontrol Akses Berbasis Peran (RBAC)

Aplikasi menerapkan pembagian hak akses terperinci:

| Peran (Role) | Kredensial Login | Deskripsi & Batas Wewenang |
| :--- | :--- | :--- |
| **Super Admin** | NIP / Username + Password | Hak akses tertinggi: konfigurasi dinamis sistem (*system settings*), manajemen pengguna semua tingkatan, manajemen master data, konfigurasi template sertifikat dan barcode, serta audit trail. |
| **Penyelenggara** | NIP / Email + Password | Bertanggung jawab atas pengelolaan event Webinar/Bimtek: membuat sesi, menekan tombol pemicu "Mulai Sesi", mengatur jendela waktu kuesioner & evaluasi, bank soal, dan memonitor kelulusan. |
| **Widyaiswara / Pengajar** | NIP + Password | Mengampu materi diklat teknis/fungsional, membuat materi & bank soal, memeriksa tugas/portofolio, serta bertindak sebagai **Coach** pada Pelatihan Kepemimpinan. |
| **Mentor** | NIP + Password | Atasan langsung peserta diklat kepemimpinan: memeriksa logbook mingguan, memvalidasi bukti output *milestone*, dan memberikan catatan persetujuan (*approval*). |
| **Peserta ASN** | NIP / Email + Password | Mengikuti pelatihan kedinasan (MOOC, Teknis, Fungsional, Kepemimpinan) dan webinar terbuka. Profil terikat NIP dan instansi. |
| **Peserta Umum** | Email + Password | Mendaftar secara mandiri untuk mengikuti webinar/bimtek daring terbuka. Pengisian NIP dan Jabatan bersifat opsional. |

---

## 3. Modul Konfigurasi Dinamis (Super Admin Engine)

Untuk mencegah modifikasi kode sumber di kemudian hari, Super Admin dibekali panel pengaturan terpusat berbasis basis data:

### 3.1 Identitas Visual & Branding
* **Nama & Tagline Aplikasi:** Kustomisasi nama instansi dan moto aplikasi (default: "LMS Kabupaten Pasuruan").
* **Logo & Favicon:** Unggah logo resmi Pemerintah Kabupaten Pasuruan dan logo instansi (format PNG/SVG transparan).
* **Teks Footer & Hak Cipta:** Kustomisasi teks legalitas dan informasi instansi.
* **Banner & Pengumuman:** Manajemen slider spanduk di beranda dan teks berjalan (*marquee announcement*) untuk pengumuman darurat atau informasi agenda penting.

### 3.2 Parameter Operasional Global
* **Standar Jam Pelajaran (JP):** Penentuan durasi menit per 1 JP (default: $45$ menit).
* **Pengaturan Remedial Default:** Batas maksimal remedial (default: $5\times$) dan plafon nilai remedial (default: $90$).
* **Kontak & Helpdesk:** Konfigurasi nomor WhatsApp Helpdesk BKPSDM, email layanan bantuan, dan alamat kantor.
* **Sakelar Sistem:** Toggle untuk *Maintenance Mode* dan Toggle Buka/Tutup Pendaftaran Akun Peserta Umum.

---

## 4. Modul Webinar & Bimbingan Teknis Terbuka

### 4.1 Biodata & Registrasi Peserta
Peserta mendaftar ke sesi webinar menggunakan akun terdaftar. Formulir profil peserta memuat atribut wajib dan opsional:
* **Nama Lengkap & Gelar** (Wajib - dicetak langsung pada sertifikat).
* **Email** (Wajib - identitas login & notifikasi).
* **NIP** (Opsional - khusus peserta berstatus ASN).
* **Jabatan** (Opsional - khusus peserta berstatus ASN).
* **Unit Kerja / Instansi Asal** (Wajib - nama dinas, badan, kantor, atau institusi asal).
* **Pas Foto Resmi** (Wajib - format JPG/PNG resolusi standar).

### 4.2 Alur Gerbang Berurutan (*Sequential Gating*)
Peserta wajib melewati lima tahapan secara linier. Sistem mengunci tahap berikutnya sebelum tahap saat ini terpenuhi:

```
[1. Pendaftaran Webinar]
          │
          ▼
[2. Presensi / Daftar Hadir]  ──> Syarat: Tombol aktif HANYA setelah Penyelenggara klik "Mulai Sesi"
          │
          ▼
[3. Pengisian Kuesioner]      ──> Syarat: Terbuka HANYA pada rentang waktu (HH:MM - HH:MM)
          │
          ▼
[4. Evaluasi / Post-Test]     ──> Syarat: Terbuka pada rentang waktu (HH:MM - HH:MM)
          │                               Maksimal 5x remedial jika nilai < Passing Grade
          ▼
[5. Penerbitan E-Sertifikat]  ──> Syarat: Presensi Valid + Kuesioner Terisi + Evaluasi LULUS
```

### 4.3 Logika Evaluasi & Algoritma Remedial
1. Penyelenggara menetapkan batas nilai kelulusan (*passing grade*, misal: $75$).
2. **Percobaan Pertama (Attempt 1):**
   * Nilai murni berdasarkan skor pengerjaan:
     $$\text{Skor Akhir} = \text{Skor Mentah}$$
   * Jika $\text{Skor Akhir} \ge \text{Passing Grade}$, peserta dinyatakan **LULUS**.
3. **Mekanisme Remedial (Attempt 2 s.d. 6):**
   * Jika $\text{Skor Akhir} < \text{Passing Grade}$, tombol **"Kerjakan Remedial"** menjadi aktif.
   * Kesempatan remedial dibatasi maksimal **5 kali**.
   * **Batas Plafon Nilai (*Score Capping*):** Nilai pengerjaan remedial tidak boleh melebihi $90$:
     $$\text{Skor Akhir Remedial} = \min(\text{Skor Mentah}, 90)$$
   * Jika peserta remedial memperoleh skor $96$, maka nilai akhir yang dicatat sistem adalah $90$.
   * Jika hingga percobaan remedial ke-5 peserta gagal mencapai *passing grade*, sistem mengunci status menjadi **Gagal Permanen**. E-sertifikat tidak akan diterbitkan.

---

## 5. Modul Pelatihan Formal Kedinasan

### 5.1 MOOC (Massive Open Online Course)
* **Asynchronous Mandiri:** Terbuka untuk seluruh ASN.
* **Proteksi Pembelajaran:** Pemutar video materi dilengkapi pencegah percepatan (*anti-skip* / *disable forward scrubbing*).
* **Gated Activity:** Materi PDF atau dokumen wajib diakses tuntas sebelum kuis bab terbuka.
* **Kelulusan Otomatis:** Sertifikat terbit secara instan setelah seluruh modul selesai dan ujian akhir mencapai *passing grade*.

### 5.2 Pelatihan Teknis & Fungsional (Cohort-Based)
* **Struktur Angkatan:** Dikelola berdasarkan gelombang/angkatan dengan kuota peserta terbatas.
* **Penugasan & Portofolio:** Fasilitas unggah berkas tugas individu/kelompok dengan tenggat waktu (*deadline*).
* **Penilaian Widyaiswara:** Penilaian tugas dengan skala $0 - 100$ disertai kolom catatan evaluasi dan rubrik kompetensi.

### 5.3 Pelatihan Kepemimpinan (PKP, PKA, PKN)
* **Tahap 1 (On-Campus 1):** Pembelajaran teori klasikal/tatap maya dan penyusunan Rancangan Aksi Perubahan.
* **Tahap 2 (Off-Campus / Laboratorium Kepemimpinan):**
  * Peserta mengunggah logbook kegiatan mingguan dan bukti dukung capaian *milestone* (foto kegiatan, surat keputusan, draf regulasi).
  * Panel Bimbingan: **Coach** (Widyaiswara) dan **Mentor** (Atasan Langsung) dapat memberikan catatan revisi bertingkat dan tombol konfirmasi persetujuan (*approval*).
* **Tahap 3 (On-Campus 2 / Seminar Akhir):** Penginputan nilai seminar rancangan dan seminar implementasi aksi perubahan.

---

## 6. Generator E-Sertifikat & Verifikasi Barcode

### 6.1 Manajemen Template Mandiri
* Super Admin dapat mengunggah file gambar/PDF template kosong dengan resolusi tinggi ($300\text{ DPI}$).
* Super Admin dapat mengunggah aset gambar stempel atau barcode instansi.

### 6.2 Pemetaan Koordinat Visual (*Visual Coordinate Mapping*)
Super Admin dapat menentukan letak koordinat sumbu ($X, Y$), jenis font, ukuran font, dan perataan teks secara dinamis melalui antarmuka web untuk elemen:
* Nomor Sertifikat (format penomoran otomatis/manual).
* Nama Lengkap & Gelar Peserta.
* NIP dan Pangkat/Golongan (disembunyikan otomatis jika bernilai kosong/peserta umum).
* Judul Pelatihan / Sesi Webinar.
* Akumulasi Jam Pelajaran (JP).
* Tanggal Terbit Dokumen.
* Posisi Penempatan QR Code Validasi.

### 6.3 Halaman Validasi Publik
* Setiap sertifikat dilengkapi QR code dinamis yang mengarah ke URL publik: `https://lms.pasuruankab.go.id/validasi/{hash_token}`.
* Halaman ini dapat dibuka oleh publik/auditor tanpa proses login untuk menampilkan:
  * Status Keabsahan: **ASLI / TERDAFTAR RESMI**.
  * Nomor Sertifikat & Tanggal Terbit.
  * Nama Penerima & Instansi/Unit Kerja.
  * Judul Program & Total JP.

---

## 7. Desain Skema Basis Data (MySQL)

```sql
-- Tabel Master Pengguna
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(30) UNIQUE NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'penyelenggara', 'widyaiswara', 'mentor', 'peserta') NOT NULL DEFAULT 'peserta',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Profil Lengkap Peserta
CREATE TABLE user_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    gelar_depan VARCHAR(50) NULL,
    gelar_belakang VARCHAR(50) NULL,
    jabatan VARCHAR(255) NULL,
    unit_kerja VARCHAR(255) NOT NULL,
    photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Pengaturan Dinamis Sistem (No-Code Admin Settings)
CREATE TABLE system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT NULL,
    setting_group ENUM('branding', 'general', 'evaluation', 'contact') NOT NULL DEFAULT 'general',
    field_type ENUM('text', 'textarea', 'image', 'number', 'boolean') NOT NULL DEFAULT 'text',
    updated_at TIMESTAMP NULL
);

-- Modul Webinar & Bimtek Terbuka
CREATE TABLE webinars (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organizer_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    banner_path VARCHAR(255) NULL,
    meeting_link TEXT NULL,
    jp INT UNSIGNED DEFAULT 2,
    is_live BOOLEAN DEFAULT FALSE,
    questionnaire_start_time TIME NULL,
    questionnaire_end_time TIME NULL,
    exam_start_time TIME NULL,
    exam_end_time TIME NULL,
    passing_grade INT DEFAULT 75,
    max_remedial INT DEFAULT 5,
    remedial_cap_score INT DEFAULT 90,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (organizer_id) REFERENCES users(id)
);

-- Presensi Peserta Webinar
CREATE TABLE webinar_attendances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    attended_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (webinar_id, user_id)
);

-- Percobaan Evaluasi & Remedial
CREATE TABLE webinar_exam_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    attempt_number INT NOT NULL DEFAULT 1,
    raw_score DECIMAL(5,2) NOT NULL,
    final_score DECIMAL(5,2) NOT NULL,
    is_passed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Template & Koordinat Sertifikat
CREATE TABLE certificate_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    background_path VARCHAR(255) NOT NULL,
    layout_coordinates JSON NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Penerbitan Sertifikat
CREATE TABLE certificates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    certificate_number VARCHAR(100) UNIQUE NOT NULL,
    hash_token VARCHAR(64) UNIQUE NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    webinar_id BIGINT UNSIGNED NULL,
    template_id BIGINT UNSIGNED NOT NULL,
    pdf_path VARCHAR(255) NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES certificate_templates(id)
);

-- Audit Trail
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    auditable_type VARCHAR(150) NULL,
    auditable_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 8. Kebutuhan Non-Fungsional (NFR)

* **Keamanan Data & Privasi:**
  * Penyimpanan password menggunakan algoritma hashing standar industri (`Argon2id` atau `Bcrypt`).
  * Perlindungan terhadap serangan SQL Injection menggunakan PDO Eloquent ORM.
  * Penerapan token CSRF pada seluruh form submission dan sanitasi data input XSS.
* **Performa & Beban Kerja:**
  * Pembangkitan file sertifikat PDF wajib dijalankan di latar belakang (*background job queue*) via worker Laravel agar beban server tetap stabil saat penutupan sesi serentak.
  * Penyimpanan cache session dan data konfigurasi statis menggunakan Redis.
* **Ketersediaan & Auditibilitas:**
  * Pencatatan log komprehensif pada setiap perubahan nilai evaluasi, penerbitan sertifikat, dan perombakan konfigurasi oleh Super Admin.
  * Desain antarmuka responsif (*mobile-friendly*) untuk kenyamanan akses peserta menggunakan smartphone.

---

## 9. Rencana Tahapan Rilis (Roadmap)

1. **Sprint 1: Fondasi Sistem & RBAC**
   * Setup Laravel, arsitektur basis data MySQL, manajemen profil, dan modul pengaturan dinamis Super Admin (*system settings*).
2. **Sprint 2: Modul Webinar & Engine Remedial**
   * Manajemen event oleh Penyelenggara, presensi live gate, kuesioner terikat jam, mesin ujian, dan algoritma remedial plafon nilai $90$.
3. **Sprint 3: Engine E-Sertifikat & Verifikasi Publik**
   * Upload template sertifikat, mapping koordinat visual, generator PDF via queue, dan portal validasi barcode publik.
4. **Sprint 4: Modul Pelatihan Formal (MOOC, Teknis, Kepemimpinan)**
   * Pemutar video anti-skip, penugasan widyaiswara, dan alur bimbingan logbook Coach & Mentor.
5. **Sprint 5: Pengujian Terpadu (UAT) & Peluncuran**
   * Load test simulasi ujian serentak, security audit, dan deployment di server instansi.