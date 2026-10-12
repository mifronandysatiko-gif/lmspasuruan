<?php

namespace App\Http\Controllers;

use App\Models\LiteracyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LiteracyController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Check if user is ASN
        $isAsn = $user ? $user->isAsn() : false;

        $category = $request->query('category');
        $search = $request->query('q');
        $year = $request->query('year');
        $sort = $request->query('sort', 'latest'); // latest, popular, title

        $query = LiteracyDocument::query()
            ->where('is_published', true);

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('institution', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('keywords', 'like', "%{$search}%");
            });
        }

        if ($year) {
            $query->where('publication_year', $year);
        }

        if ($sort === 'popular') {
            $query->orderBy('download_count', 'desc')->orderBy('view_count', 'desc');
        } elseif ($sort === 'title') {
            $query->orderBy('title', 'asc');
        } else {
            $query->orderBy('publication_year', 'desc')->orderBy('id', 'desc');
        }

        $documents = $query->paginate(9)->withQueryString();

        // Get distinct publication years for filter
        $availableYears = LiteracyDocument::where('is_published', true)
            ->distinct()
            ->orderBy('publication_year', 'desc')
            ->pluck('publication_year');

        // Statistics for summary badges
        $stats = [
            'total' => LiteracyDocument::where('is_published', true)->count(),
            'jurnal' => LiteracyDocument::where('is_published', true)->where('category', 'jurnal')->count(),
            'karya_ilmiah' => LiteracyDocument::where('is_published', true)->where('category', 'karya_ilmiah')->count(),
            'policy_brief' => LiteracyDocument::where('is_published', true)->where('category', 'policy_brief')->count(),
            'modul' => LiteracyDocument::where('is_published', true)->whereIn('category', ['modul_diklat', 'buku_panduan'])->count(),
        ];

        return view('peserta.literasi.index', compact('documents', 'availableYears', 'stats', 'isAsn', 'category', 'search', 'year', 'sort'));
    }

    public function show(string $slug)
    {
        $user = Auth::user();
        $isAsn = $user ? $user->isAsn() : false;

        $document = LiteracyDocument::with('uploader')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Increment view count
        $document->increment('view_count');

        // Related documents in same category
        $relatedDocuments = LiteracyDocument::where('id', '!=', $document->id)
            ->where('category', $document->category)
            ->where('is_published', true)
            ->limit(3)
            ->get();

        return view('peserta.literasi.show', compact('document', 'relatedDocuments', 'isAsn'));
    }

    public function download(int $id)
    {
        $user = Auth::user();

        if (!$user || !$user->isAsn()) {
            return redirect()->back()->with('error', 'Akses Unduhan Dibatasi: Dokumen ini merupakan referensi resmi aparatur ASN Pemerintah Kabupaten Pasuruan.');
        }

        $document = LiteracyDocument::where('id', $id)->where('is_published', true)->firstOrFail();

        // Increment download count
        $document->increment('download_count');

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            $path = Storage::disk('public')->path($document->file_path);
            $fileName = $document->slug . '.pdf';
            return response()->download($path, $fileName);
        }

        // Fallback dummy download if file missing
        $dummyContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000117 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF";
        return response($dummyContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $document->slug . '.pdf"',
        ]);
    }
}