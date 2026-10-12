<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    public function show(Request $request, ?string $hashToken = null)
    {
        $token = $hashToken ?? $request->query('token');

        $certificate = null;
        $status = 'search';

        if ($token) {
            $certificate = Certificate::with(['user.profile', 'template', 'webinar', 'course.lessons'])
                ->where('hash_token', $token)
                ->orWhere('certificate_number', $token)
                ->first();

            $status = $certificate ? 'valid' : 'invalid';
        }

        return view('public.validation', compact('certificate', 'token', 'status'));
    }
}
