<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiSyncController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LiteracyAdminController;
use App\Http\Controllers\LiteracyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValidationController;
use Illuminate\Support\Facades\Route;

// --- 1. Rute Publik ---
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/validasi/{hashToken?}', [ValidationController::class, 'show'])->name('validation.show');

// Mock API SIMPEG Eksternal (Simulasi Data Pegawai Pasuruan)
Route::get('/api/external-simpeg/mock-pegawai', [ApiSyncController::class, 'mockSimpegApi'])->name('api.mock.simpeg');

// --- 2. Autentikasi & Reset Kata Sandi ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::match(['get', 'post'], '/api/check-nip', [AuthController::class, 'checkNip'])->name('api.check_nip');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// Google OAuth Single Sign-On (SSO)
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Alur Pemulihan / Lupa Kata Sandi Publik
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// --- Profil & Keamanan Akun (Semua Pengguna Terautentikasi: Peserta ASN, Peserta UMUM, Pengelola) ---
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

// --- 3. Portal Peserta (Harus Login Terlebih Dahulu) ---
Route::prefix('peserta')->name('peserta.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [PesertaController::class, 'dashboard'])->name('dashboard');
    Route::get('/katalog', [PesertaController::class, 'katalog'])->name('katalog');
    Route::get('/mooc/{slug}', [PesertaController::class, 'moocShow'])->name('mooc.show');
    Route::post('/mooc/{slug}/complete-lesson/{lessonId}', [PesertaController::class, 'moocCompleteLesson'])->name('mooc.complete_lesson');
    Route::post('/mooc/{slug}/assignment', [PesertaController::class, 'moocSubmitAssignment'])->name('mooc.assignment');
    Route::post('/mooc/{slug}/quiz', [PesertaController::class, 'moocSubmitQuiz'])->name('mooc.quiz');
    Route::post('/mooc/{slug}/questionnaire', [PesertaController::class, 'moocQuestionnaireSubmit'])->name('mooc.questionnaire');
    Route::get('/webinar', [PesertaController::class, 'webinarsIndex'])->name('webinar.index');
    Route::get('/webinars', fn() => redirect()->route('peserta.webinar.index'))->name('webinars');
    Route::get('/webinar/{slug}', [PesertaController::class, 'webinarRoom'])->name('webinar.room');
    Route::post('/webinar/{id}/register', [PesertaController::class, 'webinarRegister'])->name('webinar.register');
    Route::post('/webinar/{id}/attend', [PesertaController::class, 'webinarAttend'])->name('webinar.attend');
    Route::post('/webinar/{id}/questionnaire', [PesertaController::class, 'webinarQuestionnaire'])->name('webinar.questionnaire');
    Route::post('/webinar/{id}/exam-submit', [PesertaController::class, 'webinarExamSubmit'])->name('webinar.exam_submit');
    Route::get('/kepemimpinan', [PesertaController::class, 'kepemimpinan'])->name('kepemimpinan');
    Route::post('/kepemimpinan/store', [PesertaController::class, 'kepemimpinanStore'])->name('kepemimpinan.store');
    Route::get('/sertifikat', [PesertaController::class, 'sertifikat'])->name('certificates');
    Route::get('/sertifikat/{id}/print', [PesertaController::class, 'sertifikatPrint'])->name('certificates.print');
    
    // Literasi Pengembangan Kompetensi (Jurnal, Karya Ilmiah, Policy Brief, Modul)
    Route::get('/literasi', [LiteracyController::class, 'index'])->name('literasi.index');
    Route::get('/literasi/{slug}', [LiteracyController::class, 'show'])->name('literasi.show');
    Route::get('/literasi/{id}/download', [LiteracyController::class, 'download'])->name('literasi.download');
});

// --- 4. Portal Pengelola (Super Admin, Penyelenggara, Coach, Mentor - Harus Login) ---
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    // Dashboard umum untuk semua pengelola
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // 4.1 Khusus Super Admin BKPSDM (Tata Kelola Pegawai, API Sync, Pengaturan Sistem, Desainer Blanko)
    Route::middleware('role:super_admin')->group(function () {
        // Data Pegawai & Pengguna (User Management)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
        Route::get('/users/template', [UserController::class, 'downloadTemplate'])->name('users.template');
        Route::post('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{id}/role', [UserController::class, 'updateRole'])->name('users.update_role');
        Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle_status');

        // Integrasi API SIMPEG / Aplikasi Lain
        Route::get('/api-sync', [ApiSyncController::class, 'index'])->name('api_sync.index');
        Route::post('/api-sync/settings', [ApiSyncController::class, 'updateSettings'])->name('api_sync.settings');
        Route::post('/api-sync/now', [ApiSyncController::class, 'syncNow'])->name('api_sync.now');
        Route::post('/api-sync/sync-batch', [ApiSyncController::class, 'syncBatchAjax'])->name('api_sync.batch');
        Route::post('/api-sync/test-connection', [ApiSyncController::class, 'testConnection'])->name('api_sync.test');

        // Manajemen Menu Sidebar (Tampilkan / Sembunyikan per Role)
        Route::get('/sidebar-menu', [AdminController::class, 'sidebarMenuIndex'])->name('sidebar_menu.index');
        Route::post('/sidebar-menu/update', [AdminController::class, 'sidebarMenuUpdate'])->name('sidebar_menu.update');
        Route::post('/sidebar-menu/reset', [AdminController::class, 'sidebarMenuReset'])->name('sidebar_menu.reset');

        // Pengaturan Sistem Dinamis (No-Code) & Antrean Email
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::post('/settings', [AdminController::class, 'settingsUpdate'])->name('settings.update');
        Route::post('/settings/queue/process', [AdminController::class, 'queueProcessNow'])->name('queue.process');
        Route::post('/settings/queue/retry-failed', [AdminController::class, 'queueRetryFailed'])->name('queue.retry_failed');

        // Visual Certificate Designer
        Route::get('/certificates/designer', [AdminController::class, 'certificatesDesigner'])->name('certificates.designer');
        Route::post('/certificates/save-coordinates', [AdminController::class, 'certificatesSaveCoordinates'])->name('certificates.save_coordinates');
        Route::post('/certificates/upload-blanko', [AdminController::class, 'certificatesUploadBlanko'])->name('certificates.upload_blanko');
        Route::post('/certificates/upload-lampiran-blanko', [AdminController::class, 'certificatesUploadLampiranBlanko'])->name('certificates.upload_lampiran_blanko');
        Route::post('/certificates/save-specimens', [AdminController::class, 'certificatesSaveSpecimens'])->name('certificates.save_specimens');
        Route::post('/certificates/store-template', [AdminController::class, 'certificatesStoreTemplate'])->name('certificates.store_template');
        Route::post('/certificates/update-target', [AdminController::class, 'certificatesUpdateTarget'])->name('certificates.update_target');
        Route::delete('/certificates/{id}/delete-template', [AdminController::class, 'certificatesDeleteTemplate'])->name('certificates.delete_template');

        // Penerbitan Sertifikat Manual (Khusus Super Admin)
        Route::get('/certificates/manual', [AdminController::class, 'certificatesManualIndex'])->name('certificates.manual');
        Route::get('/certificates/manual/search-users', [AdminController::class, 'certificatesManualSearchUsers'])->name('certificates.manual_search_users');
        Route::get('/certificates/manual/participants', [AdminController::class, 'certificatesManualGetParticipants'])->name('certificates.manual_participants');
        Route::post('/certificates/manual', [AdminController::class, 'certificatesManualStore'])->name('certificates.manual_store');
        Route::delete('/certificates/manual/{id}', [AdminController::class, 'certificatesManualDestroy'])->name('certificates.manual_destroy');
        Route::get('/certificates/{id}/print', [AdminController::class, 'certificatesPrint'])->name('certificates.print');
    });

    // 4.2 Penyelenggara Diklat & Super Admin (Operasional Webinar & Pelatihan MOOC)
    Route::middleware('role:super_admin,penyelenggara')->group(function () {
        // Webinar Management & Tarikan Data Excel
        Route::get('/webinars', [AdminController::class, 'webinarsIndex'])->name('webinars.index');
        Route::get('/webinars/export', [AdminController::class, 'webinarsExportAll'])->name('webinars.export');
        Route::get('/webinars/{id}/export-participants', [AdminController::class, 'webinarExportParticipants'])->name('webinars.export_participants');
        Route::post('/webinars', [AdminController::class, 'webinarStore'])->name('webinars.store');
        Route::post('/webinars/{id}/update', [AdminController::class, 'webinarUpdate'])->name('webinars.update');
        Route::post('/webinars/{id}/delete', [AdminController::class, 'webinarDestroy'])->name('webinars.destroy');
        Route::get('/webinars/{id}/manage', [AdminController::class, 'webinarManage'])->name('webinars.manage');
        Route::post('/webinars/{id}/toggle-live', [AdminController::class, 'webinarToggleLive'])->name('webinars.toggle_live');
        Route::post('/webinars/{id}/update-schedule', [AdminController::class, 'webinarUpdateSchedule'])->name('webinars.update_schedule');
        Route::post('/webinars/{id}/media', [AdminController::class, 'webinarUpdateMedia'])->name('webinars.update_media');
        Route::post('/webinars/{id}/questions', [AdminController::class, 'webinarStoreQuestion'])->name('webinars.questions.store');
        Route::post('/webinars/{id}/questions/import', [AdminController::class, 'webinarImportQuestions'])->name('webinars.questions.import');
        Route::get('/webinars/questions/template', [AdminController::class, 'webinarDownloadQuestionTemplate'])->name('webinars.questions.template');
        Route::post('/webinars/{id}/questions/{questionId}', [AdminController::class, 'webinarUpdateQuestion'])->name('webinars.questions.update');
        Route::post('/webinars/{id}/questions/{questionId}/delete', [AdminController::class, 'webinarDeleteQuestion'])->name('webinars.questions.delete');

        // MOOC Management & Tarikan Data Excel
        Route::get('/courses', [AdminController::class, 'coursesIndex'])->name('courses.index');
        Route::get('/courses/export', [AdminController::class, 'coursesExportAll'])->name('courses.export');
        Route::get('/courses/{id}/export-participants', [AdminController::class, 'courseExportParticipants'])->name('courses.export_participants');
        Route::post('/courses', [AdminController::class, 'courseStore'])->name('courses.store');
        Route::post('/courses/{id}/update', [AdminController::class, 'courseUpdate'])->name('courses.update');
        Route::post('/courses/{id}/delete', [AdminController::class, 'courseDestroy'])->name('courses.destroy');
        Route::get('/courses/{id}/manage', [AdminController::class, 'courseManage'])->name('courses.manage');
        Route::post('/courses/{id}/lessons', [AdminController::class, 'courseStoreLesson'])->name('courses.lessons.store');
        Route::post('/courses/{courseId}/lessons/{lessonId}/update', [AdminController::class, 'courseLessonUpdate'])->name('courses.lessons.update');
        Route::post('/courses/{courseId}/lessons/{lessonId}/delete', [AdminController::class, 'courseLessonDestroy'])->name('courses.lessons.delete');
        Route::post('/courses/{courseId}/lessons/{lessonId}/questions', [AdminController::class, 'courseLessonStoreQuestion'])->name('courses.lessons.questions.store');
        Route::post('/courses/{courseId}/lessons/{lessonId}/questions/import', [AdminController::class, 'courseLessonImportQuestions'])->name('courses.lessons.questions.import');
        Route::get('/courses/questions/template', [AdminController::class, 'courseDownloadQuestionTemplate'])->name('courses.questions.template');
        Route::post('/courses/{courseId}/lessons/{lessonId}/questions/{questionId}', [AdminController::class, 'courseLessonUpdateQuestion'])->name('courses.lessons.questions.update');
        Route::post('/courses/{courseId}/lessons/{lessonId}/questions/{questionId}/delete', [AdminController::class, 'courseLessonDeleteQuestion'])->name('courses.lessons.questions.delete');

        // Tarikan Data Excel E-Sertifikat Terbit
        Route::get('/certificates/export', [AdminController::class, 'certificatesExportAll'])->name('certificates.export');

        // Manajemen Repositori Literasi & Jurnal (Super Admin & Penyelenggara)
        Route::get('/literasi', [LiteracyAdminController::class, 'index'])->name('literasi.index');
        Route::post('/literasi', [LiteracyAdminController::class, 'store'])->name('literasi.store');
        Route::post('/literasi/{id}', [LiteracyAdminController::class, 'update'])->name('literasi.update');
        Route::post('/literasi/{id}/delete', [LiteracyAdminController::class, 'destroy'])->name('literasi.destroy');
        Route::post('/literasi/{id}/toggle-status', [LiteracyAdminController::class, 'toggleStatus'])->name('literasi.toggle_status');
    });

    // 4.3 Evaluasi Pembelajaran & Dashboard Hasil Kuesioner (Widyaiswara / Coach, Penyelenggara, Super Admin)
    Route::middleware('role:super_admin,penyelenggara,widyaiswara')->group(function () {
        Route::get('/courses/{id}/results', [AdminController::class, 'courseResults'])->name('courses.results');
        Route::post('/courses/{id}/grade/{enrollmentId}', [AdminController::class, 'courseGradeStudent'])->name('courses.grade');

        // Dashboard Hasil Kuesioner & Evaluasi Kepuasan
        Route::get('/questionnaires', [AdminController::class, 'questionnairesIndex'])->name('questionnaires.index');
        Route::get('/questionnaires/export-all', [AdminController::class, 'questionnairesExportAll'])->name('questionnaires.export_all');
        Route::get('/questionnaires/webinar/{id}', [AdminController::class, 'questionnaireWebinarDetail'])->name('questionnaires.webinar');
        Route::get('/questionnaires/webinar/{id}/export', [AdminController::class, 'questionnaireWebinarExport'])->name('questionnaires.webinar.export');
        Route::get('/questionnaires/course/{id}', [AdminController::class, 'questionnaireCourseDetail'])->name('questionnaires.course');
        Route::get('/questionnaires/course/{id}/export', [AdminController::class, 'questionnaireCourseExport'])->name('questionnaires.course.export');
    });

    // 4.4 Bimbingan Aksi Perubahan (Widyaiswara / Coach, Mentor, Super Admin)
    Route::middleware('role:super_admin,widyaiswara,mentor')->group(function () {
        Route::get('/kepemimpinan/review', [AdminController::class, 'kepemimpinanReview'])->name('kepemimpinan.review');
        Route::post('/kepemimpinan/{id}/status', [AdminController::class, 'kepemimpinanUpdateStatus'])->name('kepemimpinan.update_status');
    });
});
