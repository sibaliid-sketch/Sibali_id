<?php

namespace App\Http\Controllers\LandingPage;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $data = Cache::remember('landing_home_data', 3600, function () {
            return [
                'stats' => $this->getStats(),
                'featured_programs' => $this->getFeaturedPrograms(),
                'testimonials' => $this->getTestimonials(),
                'latest_posts' => $this->getLatestPosts(),
            ];
        });

        return view('landing.home', $data);
    }

    protected function getStats()
    {
        return [
            'total_students' => DB::table('students')->count(),
            'total_classes' => DB::table('classes')->where('status', 'active')->count(),
            'total_teachers' => DB::table('users')->where('user_type', 'teacher')->count(),
            'success_rate' => 95, // Placeholder - calculate from actual data
        ];
    }

    protected function getFeaturedPrograms()
    {
        return DB::table('services')
            ->where('active', true)
            ->where('featured', true)
            ->orderBy('sort_order', 'asc')
            ->limit(6)
            ->get();
    }

    protected function getTestimonials()
    {
        return DB::table('testimonials')
            ->where('status', 'approved')
            ->where('featured', true)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();
    }

    protected function getLatestPosts()
    {
        return DB::table('contents')
            ->where('status', 'published')
            ->where('type', 'blog')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();
    }

    public function about()
    {
        $data = [
            'company' => config('company'),
            'team' => $this->getTeamMembers(),
            'achievements' => $this->getAchievements(),
        ];

        return view('landing.about', $data);
    }

    public function programs()
    {
        $programs = DB::table('services')
            ->where('active', true)
            ->orderBy('sort_order', 'asc')
            ->paginate(12);

        return view('landing.programs', compact('programs'));
    }

    public function programDetail($slug)
    {
        $program = DB::table('services')
            ->where('slug', $slug)
            ->where('active', true)
            ->first();

        if (!$program) {
            abort(404, 'Program tidak ditemukan');
        }

        return view('landing.program-detail', compact('program'));
    }

    public function contact()
    {
        return view('landing.contact');
    }

    public function submitContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'captcha' => 'required', // Add captcha validation
        ], [
            'name.required' => 'Nama wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'phone.required' => 'Nomor telepon wajib diisi',
            'subject.required' => 'Subjek wajib diisi',
            'message.required' => 'Pesan wajib diisi',
            'captcha.required' => 'Captcha wajib diisi',
        ]);

        try {
            // Create inquiry
            DB::table('sales_inquiries')->insert([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'subject' => $request->subject,
                'message' => $request->message,
                'source' => 'contact_form',
                'status' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Log activity
            activity('contact_form_submitted', 'inquiry', null, [
                'email' => $request->email,
                'subject' => $request->subject,
            ]);

            return redirect()->back()
                ->with('success', 'Terima kasih! Pesan Anda telah terkirim. Kami akan menghubungi Anda segera.');

        } catch (\Exception $e) {
            Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Gagal mengirim pesan. Silakan coba lagi.'])
                ->withInput();
        }
    }

    protected function getTeamMembers()
    {
        return DB::table('users')
            ->where('user_type', 'staff')
            ->where('show_in_team', true)
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    protected function getAchievements()
    {
        return [
            ['year' => '2020', 'title' => 'Berdiri', 'description' => 'PT. Siap Belajar Indonesia didirikan'],
            ['year' => '2021', 'title' => '500+ Siswa', 'description' => 'Mencapai 500 siswa aktif'],
            ['year' => '2022', 'title' => 'Ekspansi', 'description' => 'Membuka cabang baru'],
            ['year' => '2023', 'title' => '1000+ Siswa', 'description' => 'Mencapai 1000 siswa aktif'],
        ];
    }
}
