<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $user = $this->create($request->all());

            // Create profile based on user type
            if ($request->user_type === 'student') {
                $this->createStudentProfile($user, $request);
            } elseif ($request->user_type === 'parent') {
                $this->createParentProfile($user, $request);
            }

            // Create CRM lead
            $this->createCRMLead($user, $request);

            // Log activity
            activity('user_registered', 'user', $user->id, [
                'user_type' => $user->user_type,
                'source' => $request->input('source', 'web')
            ]);

            DB::commit();

            // Auto login
            auth()->login($user);

            // Redirect based on user type
            return $this->redirectAfterRegistration($user);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Registrasi gagal. Silakan coba lagi.'])
                ->withInput();
        }
    }

    protected function validator(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'user_type' => ['required', 'in:student,parent'],
            'terms' => ['accepted'],
        ];

        // Additional rules for student
        if (isset($data['user_type']) && $data['user_type'] === 'student') {
            $rules['birthdate'] = ['required', 'date', 'before:today'];
            $rules['school_origin'] = ['nullable', 'string', 'max:255'];
            $rules['education_level'] = ['required', 'string'];
        }

        // Additional rules for parent
        if (isset($data['user_type']) && $data['user_type'] === 'parent') {
            $rules['relationship'] = ['required', 'string', 'in:father,mother,guardian'];
        }

        return Validator::make($data, $rules, [
            'name.required' => 'Nama lengkap wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'phone.required' => 'Nomor telepon wajib diisi',
            'phone.unique' => 'Nomor telepon sudah terdaftar',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'user_type.required' => 'Tipe pengguna wajib dipilih',
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan',
            'birthdate.required' => 'Tanggal lahir wajib diisi',
            'birthdate.before' => 'Tanggal lahir tidak valid',
            'education_level.required' => 'Tingkat pendidikan wajib dipilih',
            'relationship.required' => 'Hubungan dengan siswa wajib dipilih',
        ]);
    }

    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'user_type' => $data['user_type'],
            'email_verified_at' => null, // Will be verified via email
        ]);
    }

    protected function createStudentProfile($user, $request)
    {
        DB::table('students')->insert([
            'id' => $user->id,
            'nisn' => $this->generateNISN(),
            'birthdate' => $request->birthdate,
            'school_origin' => $request->school_origin,
            'education_level' => $request->education_level,
            'enrollment_date' => now(),
            'parent_id' => null, // Will be linked later
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createParentProfile($user, $request)
    {
        DB::table('parents')->insert([
            'id' => $user->id,
            'relationship' => $request->relationship,
            'emergency_contact' => $request->phone,
            'preferred_contact_method' => 'email',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createCRMLead($user, $request)
    {
        DB::table('sales_inquiries')->insert([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'source' => 'registration',
            'status' => 'new',
            'user_type' => $user->user_type,
            'notes' => 'Registered via website',
            'assigned_to' => null, // Will be auto-assigned
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function redirectAfterRegistration($user)
    {
        $redirects = [
            'student' => route('student.dashboard'),
            'parent' => route('parent.dashboard'),
            'teacher' => route('teacher.dashboard'),
            'admin' => route('admin.dashboard'),
            'staff' => route('staff.dashboard'),
        ];

        return redirect()->intended($redirects[$user->user_type] ?? route('home'))
            ->with('success', 'Registrasi berhasil! Selamat datang di Sibali.id');
    }

    protected function generateNISN()
    {
        // Generate unique NISN (10 digits)
        do {
            $nisn = str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
        } while (DB::table('students')->where('nisn', $nisn)->exists());

        return $nisn;
    }
}
