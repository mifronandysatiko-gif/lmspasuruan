<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Webinar;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $appName = SystemSetting::get('app_name', 'LMS Kabupaten Pasuruan');
        $appTagline = SystemSetting::get('app_tagline', 'Mewujudkan ASN BerAKHLAK, Unggul, dan Berdaya Saing');
        $marqueeText = SystemSetting::get('marquee_announcement', 'Selamat datang di Portal LMS BKPSDM Kabupaten Pasuruan.');
        $helpdeskWa = SystemSetting::get('helpdesk_whatsapp', '0812-3456-7890');
        $helpdeskEmail = SystemSetting::get('helpdesk_email', 'bkpsdm@pasuruankab.go.id');

        $webinars = Webinar::with('organizer')->orderBy('event_date', 'asc')->get();
        $courses = Course::where('status', 'published')->take(6)->get();

        $stats = [
            'total_asn' => User::whereNotNull('nip')->count(),
            'total_webinar' => Webinar::count(),
            'total_courses' => Course::count(),
            'total_certificates' => Certificate::count(),
        ];

        return view('pages.home', compact(
            'appName',
            'appTagline',
            'marqueeText',
            'helpdeskWa',
            'helpdeskEmail',
            'webinars',
            'courses',
            'stats'
        ));
    }
}
