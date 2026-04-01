<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserOtp;
use App\Services\WaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function show()
    {
        if (Auth::check()) return redirect()->route('profile.index');
        return view('auth.register');
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'phone' => ['required','string','max:50'],
            'password' => ['required','string','min:8','confirmed'],
        ]);

        // Normalize phone number via WA service if validation is enabled
        $svc = new WaService();
        $cfg = $svc->getConfig();
        $waNumber = $data['phone'];
        if ($cfg['validate_enabled'] ?? false) {
            $res = $svc->validateNumber($data['phone']);
            $waOk = (bool) ($res['isRegistered'] ?? false);
            $waNumber = (string) ($res['number'] ?? $data['phone']);
            if (! $waOk) {
                return back()->withErrors(['phone' => 'Nomor WhatsApp tidak valid / belum terdaftar.'])->withInput();
            }
        }

        // Create user immediately — no OTP required
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'type' => 'customer',
        ]);

        // Save phone to contact profile
        try {
            $user->contact()->firstOrCreate([])->update(['phone' => $waNumber]);
        } catch (\Throwable $e) {}

        // Log the user in
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile.index');
    }

    // -----------------------------------------------------------------
    // OTP verification (optional — can be triggered from profile page)
    // -----------------------------------------------------------------

    public function showOtp(Request $request)
    {
        $phone = session('phone');
        if (! $phone) return redirect()->route('register');
        $row = UserOtp::where('phone', $phone)->first();
        $cooldown = 0;
        if ($row) {
            $meta = $row->meta_json ?? [];
            if (!empty($meta['last_sent_at'])) {
                try {
                    $last = \Illuminate\Support\Carbon::parse($meta['last_sent_at']);
                    $diff = now()->diffInSeconds($last);
                    if ($diff < 60) { $cooldown = 60 - $diff; }
                } catch (\Throwable $e) { $cooldown = 0; }
            }
        }
        return view('auth.register-otp', ['phone' => $phone, 'cooldown' => $cooldown]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required','string'],
            'code' => ['required','string'],
        ]);
        $row = UserOtp::where('phone', $data['phone'])->first();
        if (! $row) return back()->withErrors(['code' => 'Kode tidak ditemukan.'])->withInput();
        if (now()->greaterThan($row->expires_at)) return back()->withErrors(['code' => 'Kode sudah kadaluarsa.'])->withInput();
        if (trim($row->code) !== trim($data['code'])) {
            $row->attempts = ($row->attempts ?? 0) + 1; $row->save();
            if ($row->attempts >= 5) {
                $row->delete();
                return redirect()->route('register')->withErrors(['phone' => 'Terlalu banyak percobaan OTP. Silakan daftar ulang.']);
            }
            $left = 5 - $row->attempts;
            return back()->withErrors(['code' => 'Kode tidak sesuai. Sisa percobaan: ' . $left])->withInput();
        }

        // If user is already logged in, mark phone as verified
        if (Auth::check()) {
            try {
                Auth::user()->contact()->firstOrCreate([])->update([
                    'phone' => $row->phone,
                    'phone_verified_at' => now(),
                ]);
            } catch (\Throwable $e) {}
        } else {
            // Legacy fallback: create user from OTP meta (for old pending registrations)
            $meta = $row->meta_json ?? [];
            $user = User::create([
                'name' => $meta['name'] ?? 'User',
                'email' => $meta['email'] ?? null,
                'password' => $meta['password'] ?? Hash::make(str()->random(12)),
                'type' => 'customer',
            ]);
            try { $user->contact()->firstOrCreate([])->update(['phone' => $row->phone]); } catch (\Throwable $e) {}
            Auth::login($user);
            $request->session()->regenerate();
        }

        $row->delete();
        return redirect()->route('profile.index');
    }

    public function resendOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required','string'],
        ]);
        $row = UserOtp::where('phone', $data['phone'])->first();
        if (! $row) {
            return back()->withErrors(['code' => 'Permintaan OTP tidak ditemukan. Silakan daftar ulang.']);
        }

        // Rate limit resend: max 5 times, cooldown 60s
        $meta = $row->meta_json ?? [];
        $resendCount = (int) ($meta['resend_count'] ?? 0);
        $lastSentAt = isset($meta['last_sent_at']) ? \Illuminate\Support\Carbon::parse($meta['last_sent_at']) : null;
        if ($resendCount >= 5) {
            return back()->withErrors(['code' => 'Batas kirim ulang OTP tercapai.']);
        }
        if ($lastSentAt && now()->diffInSeconds($lastSentAt) < 60) {
            $wait = 60 - now()->diffInSeconds($lastSentAt);
            return back()->withErrors(['code' => 'Tunggu ' . $wait . ' detik sebelum kirim ulang.']);
        }

        // Generate new code and extend expiry
        $code = (string) random_int(100000, 999999);
        $row->code = $code;
        $row->expires_at = now()->addMinutes(10);
        $meta['resend_count'] = $resendCount + 1;
        $meta['last_sent_at'] = now()->toISOString();
        $row->meta_json = $meta;
        $row->save();

        // Send via WA if enabled
        $svc = new WaService();
        $cfg = $svc->getConfig();
        if ($cfg['send_enabled'] ?? false) {
            $name = (string) data_get($row->meta_json, 'name', '');
            $email = (string) data_get($row->meta_json, 'email', '');
            $template = (string) ($cfg['message_template'] ?? 'Kode OTP registrasi Anda: {otp}. Berlaku 10 menit.');
            $msg = $svc->renderTemplate($template, ['otp' => $code, 'name' => $name, 'email' => $email]);
            $svc->sendText($row->phone, $msg);
        }

        return back()->with('status', 'OTP telah dikirim ulang.');
    }
}
