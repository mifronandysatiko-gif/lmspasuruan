@extends('layouts.dashboard')

@section('page_title', 'Manajemen Repositori Literasi & Jurnal')
@section('page_subtitle', 'Pengelolaan publikasi jurnal ilmiah, karya tulis inovasi ASN, policy brief, dan modul kedinasan BKPSDM Pasuruan')

@section('content')
<div class="space-y-6" x-data="{
    openUploadModal: false,
    openEditModal: false,
    editData: {
        id: '',
        title: '',
        category: 'jurnal',
        author: '',
        institution: '',
        publication_year: '{{ date('Y') }}',
        edition_volume: '',
        isbn_issn: '',
        description: '',
        keywords: '',
        is_published: '1',
        cover_url: ''
    },
    openEdit(item) {
        this.editData = {
            id: item.id,
            title: item.title,
            category: item.category,
            author: item.author,
            institution: item.institution,
            publication_year: item.publication_year,
            edition_volume: item.edition_volume || '',
            isbn_issn: item.isbn_issn || '',
            description: item.description,
            keywords: item.keywords || '',
            is_published: item.is_published ? '1' : '0',
            cover_url: item.cover_url || ''
        };
        this.openEditModal = true;
    }
}">


    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm space-y-1 shadow-xs">
            <div class="font-bold flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                <span>Terjadi kesalahan pada input data:</span>
            </div>
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 1. Statistik Ringkasan Pustaka -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Total Dokumen</span>
                <span class="text-2xl font-extrabold text-slate-900 mt-1 block">{{ $stats['total'] }}</span>
                <span class="text-[11px] text-emerald-600 font-medium mt-0.5 block">{{ $stats['published'] }} Diterbitkan</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-pasuruan-50 text-pasuruan-700 flex items-center justify-center text-xl">
                <i class="fa-solid fa-book-bookmark"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Jurnal Ilmiah</span>
                <span class="text-2xl font-extrabold text-indigo-700 mt-1 block">{{ $stats['jurnal'] }}</span>
                <span class="text-[11px] text-slate-400 font-medium mt-0.5 block">Publikasi Riset Terindeks</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-xl">
                <i class="fa-solid fa-book-journal-whills"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Karya Tulis & Inovasi</span>
                <span class="text-2xl font-extrabold text-emerald-700 mt-1 block">{{ $stats['karya_ilmiah'] }}</span>
                <span class="text-[11px] text-slate-400 font-medium mt-0.5 block">KIPP & Inovasi OPD</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl">
                <i class="fa-solid fa-lightbulb"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Total Unduhan ASN</span>
                <span class="text-2xl font-extrabold text-amber-600 mt-1 block">{{ number_format($stats['total_downloads']) }}</span>
                <span class="text-[11px] text-slate-400 font-medium mt-0.5 block">{{ number_format($stats['total_views']) }} Pembaca</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-cloud-arrow-down"></i>
            </div>
        </div>
    </div>

    <!-- 2. Action Bar: Filters & Upload Button -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        
        <form action="{{ route('admin.literasi.index') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
            <div class="relative flex-1 min-w-[220px]">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul dokumen, penulis, atau OPD..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-pasuruan-600 focus:bg-white transition-colors">
            </div>

            <select name="category" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-pasuruan-600 focus:bg-white text-slate-700">
                <option value="all">Semua Kategori</option>
                <option value="jurnal" {{ request('category') === 'jurnal' ? 'selected' : '' }}>Jurnal Ilmiah</option>
                <option value="karya_ilmiah" {{ request('category') === 'karya_ilmiah' ? 'selected' : '' }}>Karya Ilmiah & Inovasi</option>
                <option value="policy_brief" {{ request('category') === 'policy_brief' ? 'selected' : '' }}>Policy Brief</option>
                <option value="modul_diklat" {{ request('category') === 'modul_diklat' ? 'selected' : '' }}>Modul Kedinasan</option>
                <option value="buku_panduan" {{ request('category') === 'buku_panduan' ? 'selected' : '' }}>Buku Panduan & Pedoman</option>
            </select>

            <select name="status" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-pasuruan-600 focus:bg-white text-slate-700">
                <option value="">Semua Status</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Diterbitkan</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draf</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-pasuruan-800 hover:bg-pasuruan-900 text-white font-bold text-xs rounded-xl transition-colors shadow-xs">
                Filter
            </button>

            @if(request('q') || request('category') || request('status'))
                <a href="{{ route('admin.literasi.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs flex items-center gap-1 transition-colors" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            @endif
        </form>

        <!-- Upload Button Trigger Modal -->
        <button type="button" @click="openUploadModal = true" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center justify-center gap-2 shrink-0">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <span>+ Upload Dokumen Baru</span>
        </button>
    </div>

    <!-- 3. Table of Documents -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Judul & Identitas Publikasi</th>
                        <th class="py-3.5 px-4">Kategori</th>
                        <th class="py-3.5 px-4">Penulis & Instansi</th>
                        <th class="py-3.5 px-4">Pengunggah</th>
                        <th class="py-3.5 px-4 text-center">Statistik</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($documents as $index => $doc)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4 text-center text-slate-400 font-medium">
                                {{ $documents->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 max-w-sm">
                                <div class="flex items-start gap-3">
                                    @if($doc->cover_url)
                                        <img src="{{ $doc->cover_url }}" alt="{{ $doc->title }}" class="w-11 h-14 object-cover rounded-lg border border-slate-200 shrink-0 shadow-2xs">
                                    @else
                                        <div class="w-11 h-14 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 shrink-0 text-base">
                                            <i class="fa-solid fa-book"></i>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 leading-snug line-clamp-2">
                                            {{ $doc->title }}
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500 mt-1">
                                            <span>Tahun: {{ $doc->publication_year }}</span>
                                            @if($doc->isbn_issn)
                                                <span>•</span>
                                                <span class="font-mono">{{ $doc->isbn_issn }}</span>
                                            @endif
                                            <span>•</span>
                                            <span>{{ $doc->formatted_file_size }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wide border {{ $doc->category_badge_class }}">
                                    {{ $doc->category_label }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800 truncate max-w-[200px]">
                                    {{ $doc->author }}
                                </div>
                                <div class="text-[11px] text-slate-500 truncate max-w-[200px]">
                                    {{ $doc->institution }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-[11px]">
                                <span class="font-semibold text-slate-800 block">
                                    {{ $doc->uploader ? $doc->uploader->name : 'Administrator' }}
                                </span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded font-mono {{ $doc->uploader && $doc->uploader->isSuperAdmin() ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $doc->uploader && $doc->uploader->isSuperAdmin() ? 'Super Admin' : 'Penyelenggara' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-2 text-[11px]">
                                    <span title="Dilihat" class="text-slate-500"><i class="fa-regular fa-eye"></i> {{ $doc->view_count }}</span>
                                    <span title="Diunduh" class="text-emerald-700 font-bold"><i class="fa-solid fa-download"></i> {{ $doc->download_count }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <form action="{{ route('admin.literasi.toggle_status', $doc->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-transform hover:scale-105 {{ $doc->is_published ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-300' }}" title="Klik untuk mengubah status">
                                        {{ $doc->is_published ? 'Diterbitkan' : 'Draf' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Preview Link -->
                                    <a href="{{ route('peserta.literasi.show', $doc->slug) }}" target="_blank" class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition-colors" title="Lihat Tampilan Peserta">
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <button type="button" @click="openEdit({{ json_encode($doc) }})" class="p-1.5 bg-pasuruan-50 hover:bg-pasuruan-100 text-pasuruan-700 rounded-lg transition-colors" title="Edit Dokumen">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <form action="{{ route('admin.literasi.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen \'{{ addslashes($doc->title) }}\'?');" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg transition-colors" title="Hapus Dokumen">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 text-slate-300 block"></i>
                                <span>Tidak ada dokumen literasi yang ditemukan.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
            <div class="px-5 py-3 border-t border-slate-200">
                {{ $documents->links() }}
            </div>
        @endif
    </div>

    <!-- 4. MODAL: UPLOAD DOKUMEN BARU -->
    <div x-show="openUploadModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-100 my-auto" @click.away="openUploadModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Upload Dokumen Literasi Baru</h3>
                        <p class="text-[11px] text-slate-500">Unggah publikasi jurnal, karya tulis ilmiah, atau modul kedinasan</p>
                    </div>
                </div>
                <button type="button" @click="openUploadModal = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.literasi.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Judul Dokumen / Publikasi <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" required placeholder="Contoh: Jurnal Inovasi Layanan Publik Pasuruan..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kategori Dokumen <span class="text-rose-500">*</span></label>
                        <select name="category" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                            <option value="jurnal">Jurnal Ilmiah</option>
                            <option value="karya_ilmiah">Karya Ilmiah & Inovasi</option>
                            <option value="policy_brief">Policy Brief</option>
                            <option value="modul_diklat">Modul Kedinasan</option>
                            <option value="buku_panduan">Buku Panduan & Pedoman</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tahun Publikasi <span class="text-rose-500">*</span></label>
                        <input type="number" name="publication_year" required min="2000" max="{{ date('Y') + 1 }}" value="{{ date('Y') }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Penulis / Tim Peneliti <span class="text-rose-500">*</span></label>
                        <input type="text" name="author" required placeholder="Nama penulis lengkap dengan gelar..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Instansi / Unit Kerja / OPD <span class="text-rose-500">*</span></label>
                        <input type="text" name="institution" required placeholder="Contoh: BKPSDM / Bappeda Pasuruan..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Edisi / Volume / Seri (Opsional)</label>
                        <input type="text" name="edition_volume" placeholder="Contoh: Vol. 5 No. 1 (2026)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nomor ISSN / ISBN (Opsional)</label>
                        <input type="text" name="isbn_issn" placeholder="Contoh: ISSN 2685-9122 / ISBN 978-..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Abstrak / Sinopsis Dokumen <span class="text-rose-500">*</span></label>
                    <textarea name="description" rows="4" required placeholder="Uraian ringkas latar belakang, metodologi, dan temuan utama inovasi..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white leading-relaxed"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kata Kunci / Keywords (Dipisah koma)</label>
                    <input type="text" name="keywords" placeholder="Contoh: SPBE, Inovasi Pelayanan, Pasuruan Maslahat" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <label class="block font-bold text-slate-800">Sampul / Background Dokumen (Opsional - JPG, PNG, WebP Maks. 2 MB)</label>
                    <input type="file" name="cover_image_file" accept="image/png,image/jpeg,image/webp,image/jpg" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-700 file:text-white hover:file:bg-slate-800 cursor-pointer">
                    <p class="text-[11px] text-slate-400">Jika diunggah, gambar ini akan menjadi sampul utama dokumen di katalog dan detail pembaca.</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <label class="block font-bold text-slate-800">Berkas Dokumen PDF (Maks. 25 MB)</label>
                    <input type="file" name="document_file" accept=".pdf" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-pasuruan-800 file:text-white hover:file:bg-pasuruan-900 cursor-pointer">
                    <p class="text-[11px] text-slate-400">Jika tidak diunggah, sistem akan membuatkan file PDF naskah draf otomatis.</p>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_published" id="is_published" value="1" checked class="rounded border-slate-300 text-pasuruan-800 focus:ring-pasuruan-600 w-4 h-4">
                    <label for="is_published" class="font-bold text-slate-700">Terbitkan langsung agar dapat dibaca oleh peserta ASN</label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openUploadModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Simpan & Upload Dokumen</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- 5. MODAL: EDIT DOKUMEN -->
    <div x-show="openEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-100 my-auto" @click.away="openEditModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-pasuruan-100 text-pasuruan-700 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Perbarui Informasi Dokumen Literasi</h3>
                        <p class="text-[11px] text-slate-500">Edit metadata publikasi dan ganti berkas PDF jika diperlukan</p>
                    </div>
                </div>
                <button type="button" @click="openEditModal = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form :action="'{{ url('admin/literasi') }}/' + editData.id" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Judul Dokumen / Publikasi <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" x-model="editData.title" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kategori Dokumen <span class="text-rose-500">*</span></label>
                        <select name="category" x-model="editData.category" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                            <option value="jurnal">Jurnal Ilmiah</option>
                            <option value="karya_ilmiah">Karya Ilmiah & Inovasi</option>
                            <option value="policy_brief">Policy Brief</option>
                            <option value="modul_diklat">Modul Kedinasan</option>
                            <option value="buku_panduan">Buku Panduan & Pedoman</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tahun Publikasi <span class="text-rose-500">*</span></label>
                        <input type="number" name="publication_year" x-model="editData.publication_year" required min="2000" max="{{ date('Y') + 1 }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Penulis / Tim Peneliti <span class="text-rose-500">*</span></label>
                        <input type="text" name="author" x-model="editData.author" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Instansi / Unit Kerja / OPD <span class="text-rose-500">*</span></label>
                        <input type="text" name="institution" x-model="editData.institution" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Edisi / Volume / Seri (Opsional)</label>
                        <input type="text" name="edition_volume" x-model="editData.edition_volume" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nomor ISSN / ISBN (Opsional)</label>
                        <input type="text" name="isbn_issn" x-model="editData.isbn_issn" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Abstrak / Sinopsis Dokumen <span class="text-rose-500">*</span></label>
                    <textarea name="description" rows="4" x-model="editData.description" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white leading-relaxed"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kata Kunci / Keywords (Dipisah koma)</label>
                    <input type="text" name="keywords" x-model="editData.keywords" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <label class="block font-bold text-slate-800">Sampul / Background Dokumen (Opsional)</label>
                    <template x-if="editData.cover_url">
                        <div class="flex items-center gap-3 p-2 bg-white rounded-lg border border-slate-200 mb-2">
                            <img :src="editData.cover_url" alt="Cover" class="w-14 h-16 object-cover rounded shadow-xs border border-slate-100">
                            <div class="text-xs">
                                <span class="font-bold text-slate-700 block">Sampul Dokumen Terpasang</span>
                                <span class="text-[11px] text-slate-400">Pilih berkas baru di bawah ini jika ingin mengganti sampul.</span>
                            </div>
                        </div>
                    </template>
                    <input type="file" name="cover_image_file" accept="image/png,image/jpeg,image/webp,image/jpg" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-700 file:text-white hover:file:bg-slate-800 cursor-pointer">
                    <p class="text-[11px] text-slate-400">Format JPG, PNG, atau WebP (Maks. 2 MB). Kosongkan jika tidak ingin mengubah sampul.</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <label class="block font-bold text-slate-800">Ganti Berkas PDF (Opsional)</label>
                    <input type="file" name="document_file" accept=".pdf" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-pasuruan-800 file:text-white hover:file:bg-pasuruan-900 cursor-pointer">
                    <p class="text-[11px] text-slate-400">Kosongkan jika tidak ingin mengganti file PDF yang sudah ada.</p>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_published" id="edit_is_published" value="1" :checked="editData.is_published === '1'" class="rounded border-slate-300 text-pasuruan-800 focus:ring-pasuruan-600 w-4 h-4">
                    <label for="edit_is_published" class="font-bold text-slate-700">Publikasikan Dokumen</label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openEditModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-pasuruan-800 hover:bg-pasuruan-900 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

</div>
@endsection