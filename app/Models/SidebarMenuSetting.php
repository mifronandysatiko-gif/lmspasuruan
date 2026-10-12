<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SidebarMenuSetting extends Model
{
    protected $table = 'sidebar_menu_settings';

    protected $fillable = [
        'role',
        'menu_key',
        'menu_label',
        'menu_group',
        'icon',
        'route_name',
        'is_visible',
        'order_index',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'order_index' => 'integer',
    ];

    /**
     * Cache memory internal untuk performa cepat tanpa query berulang saat render blade
     */
    protected static ?array $visibilityCache = null;

    /**
     * Daftar Master Menu Standar (Default Menu Registry)
     */
    public static function getDefaultRegistry(): array
    {
        return [
            // 1. Role: Peserta (ASN & Umum)
            'peserta' => [
                [
                    'menu_key' => 'peserta.dashboard',
                    'menu_label' => 'Dashboard Peserta',
                    'menu_group' => 'Menu Pembelajaran',
                    'icon' => 'fa-solid fa-gauge-high',
                    'route_name' => 'peserta.dashboard',
                    'is_visible' => true,
                    'order_index' => 1,
                ],
                [
                    'menu_key' => 'peserta.webinar',
                    'menu_label' => 'Webinar & Sesi Interaktif',
                    'menu_group' => 'Menu Pembelajaran',
                    'icon' => 'fa-solid fa-tower-broadcast',
                    'route_name' => 'peserta.webinar.index',
                    'is_visible' => true,
                    'order_index' => 2,
                ],
                [
                    'menu_key' => 'peserta.mooc',
                    'menu_label' => 'Katalog Pelatihan & Diklat',
                    'menu_group' => 'Menu Pembelajaran',
                    'icon' => 'fa-solid fa-book-open-reader',
                    'route_name' => 'peserta.katalog',
                    'is_visible' => true,
                    'order_index' => 3,
                ],
                [
                    'menu_key' => 'peserta.kepemimpinan',
                    'menu_label' => 'Diklat Kepemimpinan (PKA/PKP)',
                    'menu_group' => 'Menu Pembelajaran',
                    'icon' => 'fa-solid fa-award',
                    'route_name' => 'peserta.kepemimpinan',
                    'is_visible' => true,
                    'order_index' => 4,
                ],
                [
                    'menu_key' => 'peserta.literasi',
                    'menu_label' => 'Literasi Kompetensi ASN',
                    'menu_group' => 'Menu Pembelajaran',
                    'icon' => 'fa-solid fa-book-bookmark',
                    'route_name' => 'peserta.literasi.index',
                    'is_visible' => true,
                    'order_index' => 5,
                ],
                [
                    'menu_key' => 'peserta.certificates',
                    'menu_label' => 'E-Sertifikat Saya',
                    'menu_group' => 'Hasil Belajar',
                    'icon' => 'fa-solid fa-stamp',
                    'route_name' => 'peserta.certificates',
                    'is_visible' => true,
                    'order_index' => 6,
                ],
                [
                    'menu_key' => 'peserta.profile',
                    'menu_label' => 'Profil & Keamanan Akun',
                    'menu_group' => 'Pengaturan Akun',
                    'icon' => 'fa-solid fa-user-gear',
                    'route_name' => 'profile.show',
                    'is_visible' => true,
                    'order_index' => 7,
                ],
            ],

            // 2. Role: Super Admin
            'super_admin' => [
                [
                    'menu_key' => 'super_admin.dashboard',
                    'menu_label' => 'Dashboard Utama',
                    'menu_group' => 'Tata Kelola & Pegawai',
                    'icon' => 'fa-solid fa-chart-pie',
                    'route_name' => 'admin.dashboard',
                    'is_visible' => true,
                    'order_index' => 1,
                ],
                [
                    'menu_key' => 'super_admin.users',
                    'menu_label' => 'Data Pegawai & Akun',
                    'menu_group' => 'Tata Kelola & Pegawai',
                    'icon' => 'fa-solid fa-users-gear',
                    'route_name' => 'admin.users.index',
                    'is_visible' => true,
                    'order_index' => 2,
                ],
                [
                    'menu_key' => 'super_admin.api_sync',
                    'menu_label' => 'Integrasi API PINTAR',
                    'menu_group' => 'Tata Kelola & Pegawai',
                    'icon' => 'fa-solid fa-network-wired',
                    'route_name' => 'admin.api_sync.index',
                    'is_visible' => true,
                    'order_index' => 3,
                ],
                [
                    'menu_key' => 'super_admin.sidebar_menu',
                    'menu_label' => 'Kelola Menu Sidebar (Hak Akses)',
                    'menu_group' => 'Tata Kelola & Pegawai',
                    'icon' => 'fa-solid fa-list-check',
                    'route_name' => 'admin.sidebar_menu.index',
                    'is_visible' => true,
                    'order_index' => 4,
                ],
                [
                    'menu_key' => 'super_admin.certificates_designer',
                    'menu_label' => 'Visual Certificate Designer',
                    'menu_group' => 'Tata Kelola & Pegawai',
                    'icon' => 'fa-solid fa-compass-drafting',
                    'route_name' => 'admin.certificates.designer',
                    'is_visible' => true,
                    'order_index' => 5,
                ],
                [
                    'menu_key' => 'super_admin.certificates_manual',
                    'menu_label' => 'Penerbitan Sertifikat Manual',
                    'menu_group' => 'Tata Kelola & Pegawai',
                    'icon' => 'fa-solid fa-stamp',
                    'route_name' => 'admin.certificates.manual',
                    'is_visible' => true,
                    'order_index' => 6,
                ],
                [
                    'menu_key' => 'super_admin.webinars',
                    'menu_label' => 'Manajemen Webinar',
                    'menu_group' => 'Manajemen Program Diklat',
                    'icon' => 'fa-solid fa-calendar-check',
                    'route_name' => 'admin.webinars.index',
                    'is_visible' => true,
                    'order_index' => 7,
                ],
                [
                    'menu_key' => 'super_admin.courses',
                    'menu_label' => 'Pelatihan MOOC & Materi',
                    'menu_group' => 'Manajemen Program Diklat',
                    'icon' => 'fa-solid fa-graduation-cap',
                    'route_name' => 'admin.courses.index',
                    'is_visible' => true,
                    'order_index' => 7,
                ],
                [
                    'menu_key' => 'super_admin.questionnaires',
                    'menu_label' => 'Dashboard Hasil Kuesioner',
                    'menu_group' => 'Manajemen Program Diklat',
                    'icon' => 'fa-solid fa-square-poll-vertical',
                    'route_name' => 'admin.questionnaires.index',
                    'is_visible' => true,
                    'order_index' => 8,
                ],
                [
                    'menu_key' => 'super_admin.literasi',
                    'menu_label' => 'Kelola Pustaka & Jurnal',
                    'menu_group' => 'Manajemen Program Diklat',
                    'icon' => 'fa-solid fa-book-bookmark',
                    'route_name' => 'admin.literasi.index',
                    'is_visible' => true,
                    'order_index' => 9,
                ],
                [
                    'menu_key' => 'super_admin.kepemimpinan',
                    'menu_label' => 'Review Aksi Perubahan',
                    'menu_group' => 'Manajemen Program Diklat',
                    'icon' => 'fa-solid fa-list-check',
                    'route_name' => 'admin.kepemimpinan.review',
                    'is_visible' => true,
                    'order_index' => 10,
                ],
                [
                    'menu_key' => 'super_admin.profile',
                    'menu_label' => 'Profil & Keamanan',
                    'menu_group' => 'Pengaturan Akun',
                    'icon' => 'fa-solid fa-user-gear',
                    'route_name' => 'profile.show',
                    'is_visible' => true,
                    'order_index' => 11,
                ],
            ],

            // 3. Role: Penyelenggara
            'penyelenggara' => [
                [
                    'menu_key' => 'penyelenggara.dashboard',
                    'menu_label' => 'Dashboard Penyelenggara',
                    'menu_group' => 'Operasional Penyelenggara',
                    'icon' => 'fa-solid fa-chart-pie',
                    'route_name' => 'admin.dashboard',
                    'is_visible' => true,
                    'order_index' => 1,
                ],
                [
                    'menu_key' => 'penyelenggara.webinars',
                    'menu_label' => 'Manajemen Webinar & Sesi Live',
                    'menu_group' => 'Operasional Penyelenggara',
                    'icon' => 'fa-solid fa-calendar-check',
                    'route_name' => 'admin.webinars.index',
                    'is_visible' => true,
                    'order_index' => 2,
                ],
                [
                    'menu_key' => 'penyelenggara.courses',
                    'menu_label' => 'Pelatihan MOOC & Upload Materi',
                    'menu_group' => 'Operasional Penyelenggara',
                    'icon' => 'fa-solid fa-graduation-cap',
                    'route_name' => 'admin.courses.index',
                    'is_visible' => true,
                    'order_index' => 3,
                ],
                [
                    'menu_key' => 'penyelenggara.questionnaires',
                    'menu_label' => 'Dashboard Hasil Kuesioner',
                    'menu_group' => 'Operasional Penyelenggara',
                    'icon' => 'fa-solid fa-square-poll-vertical',
                    'route_name' => 'admin.questionnaires.index',
                    'is_visible' => true,
                    'order_index' => 4,
                ],
                [
                    'menu_key' => 'penyelenggara.literasi',
                    'menu_label' => 'Kelola Pustaka & Jurnal',
                    'menu_group' => 'Operasional Penyelenggara',
                    'icon' => 'fa-solid fa-book-bookmark',
                    'route_name' => 'admin.literasi.index',
                    'is_visible' => true,
                    'order_index' => 5,
                ],
                [
                    'menu_key' => 'penyelenggara.profile',
                    'menu_label' => 'Profil & Keamanan',
                    'menu_group' => 'Pengaturan Akun',
                    'icon' => 'fa-solid fa-user-gear',
                    'route_name' => 'profile.show',
                    'is_visible' => true,
                    'order_index' => 6,
                ],
            ],

            // 4. Role: Widyaiswara (Coach / Pengampu)
            'widyaiswara' => [
                [
                    'menu_key' => 'widyaiswara.dashboard',
                    'menu_label' => 'Dashboard Widyaiswara',
                    'menu_group' => 'Pengampu & Evaluator',
                    'icon' => 'fa-solid fa-chalkboard-user',
                    'route_name' => 'admin.dashboard',
                    'is_visible' => true,
                    'order_index' => 1,
                ],
                [
                    'menu_key' => 'widyaiswara.courses',
                    'menu_label' => 'Pelatihan & Penilaian Tugas',
                    'menu_group' => 'Pengampu & Evaluator',
                    'icon' => 'fa-solid fa-graduation-cap',
                    'route_name' => 'admin.courses.index',
                    'is_visible' => true,
                    'order_index' => 2,
                ],
                [
                    'menu_key' => 'widyaiswara.questionnaires',
                    'menu_label' => 'Dashboard Hasil Kuesioner',
                    'menu_group' => 'Pengampu & Evaluator',
                    'icon' => 'fa-solid fa-square-poll-vertical',
                    'route_name' => 'admin.questionnaires.index',
                    'is_visible' => true,
                    'order_index' => 3,
                ],
                [
                    'menu_key' => 'widyaiswara.courses',
                    'menu_label' => 'Hasil Belajar & Nilai Tugas',
                    'menu_group' => 'Pengampu & Evaluator',
                    'icon' => 'fa-solid fa-clipboard-check',
                    'route_name' => 'admin.courses.index',
                    'is_visible' => true,
                    'order_index' => 2,
                ],
                [
                    'menu_key' => 'widyaiswara.kepemimpinan',
                    'menu_label' => 'Bimbingan Aksi Perubahan',
                    'menu_group' => 'Pengampu & Evaluator',
                    'icon' => 'fa-solid fa-comments',
                    'route_name' => 'admin.kepemimpinan.review',
                    'is_visible' => true,
                    'order_index' => 3,
                ],
                [
                    'menu_key' => 'widyaiswara.profile',
                    'menu_label' => 'Profil & Keamanan',
                    'menu_group' => 'Pengaturan Akun',
                    'icon' => 'fa-solid fa-user-gear',
                    'route_name' => 'profile.show',
                    'is_visible' => true,
                    'order_index' => 4,
                ],
            ],

            // 5. Role: Mentor (Atasan Langsung)
            'mentor' => [
                [
                    'menu_key' => 'mentor.dashboard',
                    'menu_label' => 'Dashboard Mentor',
                    'menu_group' => 'Bimbingan Atasan Langsung',
                    'icon' => 'fa-solid fa-user-tie',
                    'route_name' => 'admin.dashboard',
                    'is_visible' => true,
                    'order_index' => 1,
                ],
                [
                    'menu_key' => 'mentor.kepemimpinan',
                    'menu_label' => 'Persetujuan Logbook Bawahan',
                    'menu_group' => 'Bimbingan Atasan Langsung',
                    'icon' => 'fa-solid fa-signature',
                    'route_name' => 'admin.kepemimpinan.review',
                    'is_visible' => true,
                    'order_index' => 2,
                ],
                [
                    'menu_key' => 'mentor.profile',
                    'menu_label' => 'Profil & Keamanan',
                    'menu_group' => 'Pengaturan Akun',
                    'icon' => 'fa-solid fa-user-gear',
                    'route_name' => 'profile.show',
                    'is_visible' => true,
                    'order_index' => 3,
                ],
            ],

            // 6. Common / Publik
            'common' => [
                [
                    'menu_key' => 'common.home',
                    'menu_label' => 'Kembali ke Beranda Publik',
                    'menu_group' => 'Publik',
                    'icon' => 'fa-solid fa-arrow-left',
                    'route_name' => 'home',
                    'is_visible' => true,
                    'order_index' => 1,
                ],
                [
                    'menu_key' => 'common.validation',
                    'menu_label' => 'Validasi Sertifikat Publik',
                    'menu_group' => 'Publik',
                    'icon' => 'fa-solid fa-qrcode',
                    'route_name' => 'validation.show',
                    'is_visible' => true,
                    'order_index' => 2,
                ],
            ],
        ];
    }

    /**
     * Memastikan database memiliki data master menu
     */
    public static function syncDefaultMenus(): void
    {
        $registry = self::getDefaultRegistry();

        foreach ($registry as $role => $menus) {
            foreach ($menus as $menu) {
                self::firstOrCreate(
                    [
                        'role' => $role,
                        'menu_key' => $menu['menu_key'],
                    ],
                    [
                        'menu_label' => $menu['menu_label'],
                        'menu_group' => $menu['menu_group'] ?? null,
                        'icon' => $menu['icon'] ?? null,
                        'route_name' => $menu['route_name'] ?? null,
                        'is_visible' => $menu['is_visible'] ?? true,
                        'order_index' => $menu['order_index'] ?? 0,
                    ]
                );
            }
        }

        self::clearCache();
    }

    /**
     * Membersihkan cache memory & application cache
     */
    public static function clearCache(): void
    {
        self::$visibilityCache = null;
        Cache::forget('sidebar_menu_visibility_map');
    }

    /**
     * Mendapatkan seluruh peta status visibilitas menu
     */
    public static function getVisibilityMap(): array
    {
        if (self::$visibilityCache !== null) {
            return self::$visibilityCache;
        }

        self::$visibilityCache = Cache::remember('sidebar_menu_visibility_map', 3600, function () {
            // Pastikan data tersinkron jika tabel masih kosong
            if (self::count() === 0) {
                self::syncDefaultMenus();
            }

            return self::pluck('is_visible', 'menu_key')->toArray();
        });

        return self::$visibilityCache;
    }

    /**
     * Pengecekan cepat apakah suatu menu diizinkan tampil untuk role tertentu
     *
     * @param string $role
     * @param string $menuKey
     * @param bool $default
     * @return bool
     */
    public static function isMenuVisible(string $role, string $menuKey, bool $default = true): bool
    {
        $map = self::getVisibilityMap();

        if (array_key_exists($menuKey, $map)) {
            return (bool) $map[$menuKey];
        }

        // Jika belum ada di DB, sinkronkan master menu dan coba baca kembali
        self::syncDefaultMenus();
        $map = self::getVisibilityMap();

        return (bool) ($map[$menuKey] ?? $default);
    }

    /**
     * Kembalikan seluruh menu ke pengaturan default awal
     */
    public static function resetToDefault(): void
    {
        self::truncate();
        self::syncDefaultMenus();
    }
}
