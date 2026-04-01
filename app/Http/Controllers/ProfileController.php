<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            // Redirect guests to login form
            $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
            return redirect()->guest($loginUrl);
        }

        $org = \App\Models\Organization::query()->first();
        $orders = \App\Models\Order::query()
            ->where('user_id', $user->id)
            ->with(['items' => function ($q) { $q->select('id','order_id','title','qty','unit_price'); }])
            ->latest('created_at')
            ->take(20)
            ->get();

        return view('profile.index', [
            'org' => $org,
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    public function updatePhoto(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->back()->withErrors(['auth' => 'Silakan login terlebih dahulu.']);
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        // Prefer S3/Minio when configured; fallback to public disk
        $preferS3 = config('filesystems.default') === 's3'
            || (bool) config('filesystems.disks.s3.endpoint')
            || (bool) env('AWS_ACCESS_KEY_ID');

        $disk = $preferS3 ? 's3' : 'public';
        $path = null;
        try {
            if ($disk === 's3') {
                // Store to S3 (private by default); URL will be generated via temporaryUrl accessor
                $path = $request->file('photo')->store('profiles', media_disk());
            } else {
                $path = $request->file('photo')->store('profiles', 'public');
            }
        } catch (\Throwable $e) {
            // Fallback to public disk if S3 upload fails
            $disk = 'public';
            $path = $request->file('photo')->store('profiles', 'public');
        }

        // Best-effort cleanup on both disks
        try { if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) { Storage::disk('public')->delete($user->profile_photo_path); } } catch (\Throwable) {}
        try { if ($user->profile_photo_path && Storage::disk(media_disk())->exists($user->profile_photo_path)) { Storage::disk(media_disk())->delete($user->profile_photo_path); } } catch (\Throwable) {}

        $user->profile_photo_path = $path;
        $user->save();

        // For XHR/JS requests, return fresh URL so client can update preview (important for signed S3 URLs)
        if ($request->expectsJson() || $request->boolean('_use_client_processed')) {
            $user->refresh();
            return response()->json([
                'message' => 'Foto profil berhasil diperbarui.',
                'url' => $user->profile_photo_url,
            ]);
        }

        return back()->with('status', 'Foto profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->back()->withErrors(['auth' => 'Silakan login terlebih dahulu.']);
        }

        $request->validate([
            'current_password' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // If user has a password, verify current
        if (! empty($user->password)) {
            if (! $request->filled('current_password') || ! Hash::check($request->input('current_password'), $user->password)) {
                return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
            }
        }

        $user->password = $request->input('password');
        $user->save();

        return back()->with('status', 'Password berhasil diperbarui.');
    }

    public function edit(Request $request)
    {
        $user = Auth::user();
        $org = \App\Models\Organization::query()->first();
        return view('profile.edit', [
            'user' => $user,
            'org' => $org,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save();

        $contact = $user->contact()->firstOrCreate([]);
        $contact->phone = $data['phone'] ?? null;
        $contact->email = $data['contact_email'] ?? null;
        $contact->address = $data['address'] ?? null;
        $contact->save();

        return redirect()->route('profile.index')->with('status', 'Profil berhasil diperbarui.');
    }
}
