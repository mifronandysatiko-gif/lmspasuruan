<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LiteracyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LiteracyAdminController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');
        $category = $request->query('category');
        $status = $request->query('status');

        $query = LiteracyDocument::with('uploader');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('institution', 'like', "%{$search}%");
            });
        }

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($status !== null && $status !== '') {
            $query->where('is_published', $status === 'published');
        }

        $documents = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        $stats = [
            'total' => LiteracyDocument::count(),
            'published' => LiteracyDocument::where('is_published', true)->count(),
            'jurnal' => LiteracyDocument::where('category', 'jurnal')->count(),
            'karya_ilmiah' => LiteracyDocument::where('category', 'karya_ilmiah')->count(),
            'policy_brief' => LiteracyDocument::where('category', 'policy_brief')->count(),
            'modul' => LiteracyDocument::whereIn('category', ['modul_diklat', 'buku_panduan'])->count(),
            'total_downloads' => (int) LiteracyDocument::sum('download_count'),
            'total_views' => (int) LiteracyDocument::sum('view_count'),
        ];

        return view('admin.literasi.index', compact('documents', 'stats', 'search', 'category', 'status'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:jurnal,karya_ilmiah,policy_brief,modul_diklat,buku_panduan',
            'author' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'publication_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'edition_volume' => 'nullable|string|max:100',
            'isbn_issn' => 'nullable|string|max:50',
            'description' => 'required|string',
            'keywords' => 'nullable|string|max:255',
            'document_file' => 'nullable|file|mimes:pdf|max:25600',
            'cover_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_published' => 'nullable|boolean',
        ]);

        $filePath = null;
        $fileSizeKb = 0;

        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('literasi', $fileName, 'public');
            $fileSizeKb = round($file->getSize() / 1024);
        } else {
            // Default dummy file if none uploaded
            $slug = LiteracyDocument::generateUniqueSlug($validated['title']);
            $defaultPdfName = $slug . '.pdf';
            $defaultPdfPath = 'literasi/' . $defaultPdfName;
            
            $dummyContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000117 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF";
            Storage::disk('public')->put($defaultPdfPath, $dummyContent);
            $filePath = $defaultPdfPath;
            $fileSizeKb = 1500;
        }

        $coverImagePath = null;
        if ($request->hasFile('cover_image_file')) {
            $coverImagePath = $request->file('cover_image_file')->store('literasi/covers', 'public');
        }

        $document = LiteracyDocument::create([
            'title' => $validated['title'],
            'slug' => LiteracyDocument::generateUniqueSlug($validated['title']),
            'category' => $validated['category'],
            'author' => $validated['author'],
            'institution' => $validated['institution'],
            'publication_year' => $validated['publication_year'],
            'edition_volume' => $validated['edition_volume'] ?? null,
            'isbn_issn' => $validated['isbn_issn'] ?? null,
            'description' => $validated['description'],
            'keywords' => $validated['keywords'] ?? null,
            'file_path' => $filePath,
            'file_size_kb' => $fileSizeKb,
            'cover_image' => $coverImagePath,
            'is_published' => $request->has('is_published') ? (bool) $request->input('is_published') : true,
            'uploaded_by' => Auth::id() ?? 1,
        ]);

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'CREATE_LITERACY_DOCUMENT',
            'description' => "Pengguna mengunggah dokumen literasi baru: '{$document->title}' (Kategori: {$document->category_label})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.literasi.index')->with('success', "Dokumen '{$document->title}' berhasil diunggah ke repositori literasi.");
    }

    public function update(Request $request, int $id)
    {
        $document = LiteracyDocument::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:jurnal,karya_ilmiah,policy_brief,modul_diklat,buku_panduan',
            'author' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'publication_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'edition_volume' => 'nullable|string|max:100',
            'isbn_issn' => 'nullable|string|max:50',
            'description' => 'required|string',
            'keywords' => 'nullable|string|max:255',
            'document_file' => 'nullable|file|mimes:pdf|max:25600',
            'cover_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_published' => 'nullable|boolean',
        ]);

        if ($request->hasFile('document_file')) {
            // Delete old file if exists
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $file = $request->file('document_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $document->file_path = $file->storeAs('literasi', $fileName, 'public');
            $document->file_size_kb = round($file->getSize() / 1024);
        }

        if ($request->hasFile('cover_image_file')) {
            $document->cover_image = $request->file('cover_image_file')->store('literasi/covers', 'public');
        }

        $document->title = $validated['title'];
        $document->slug = LiteracyDocument::generateUniqueSlug($validated['title'], $document->id);
        $document->category = $validated['category'];
        $document->author = $validated['author'];
        $document->institution = $validated['institution'];
        $document->publication_year = $validated['publication_year'];
        $document->edition_volume = $validated['edition_volume'] ?? null;
        $document->isbn_issn = $validated['isbn_issn'] ?? null;
        $document->description = $validated['description'];
        $document->keywords = $validated['keywords'] ?? null;
        $document->is_published = $request->has('is_published') ? (bool) $request->input('is_published') : $document->is_published;
        $document->save();

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'UPDATE_LITERACY_DOCUMENT',
            'description' => "Pengguna memperbarui informasi dokumen literasi ID: {$document->id} ('{$document->title}')",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.literasi.index')->with('success', "Dokumen '{$document->title}' berhasil diperbarui.");
    }

    public function destroy(Request $request, int $id)
    {
        $document = LiteracyDocument::findOrFail($id);
        $title = $document->title;

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'DELETE_LITERACY_DOCUMENT',
            'description' => "Pengguna menghapus dokumen literasi: '{$title}'",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.literasi.index')->with('success', "Dokumen '{$title}' telah dihapus dari sistem.");
    }

    public function toggleStatus(Request $request, int $id)
    {
        $document = LiteracyDocument::findOrFail($id);
        $document->is_published = !$document->is_published;
        $document->save();

        $statusText = $document->is_published ? 'Diterbitkan' : 'Ditarik ke Draf';

        AuditLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => 'TOGGLE_LITERACY_STATUS',
            'description' => "Status publikasi dokumen '{$document->title}' diubah menjadi {$statusText}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', "Status dokumen '{$document->title}' berhasil diubah menjadi {$statusText}.");
    }
}