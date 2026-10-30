<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        return view('admin.profile', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->merge(['phone' => \App\Models\User::normalizePhone($request->phone), 'email' => $request->filled('email') ? trim($request->email) : null]);
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['nullable', 'required_without:phone', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'  => ['nullable', 'required_without:email', 'string', 'max:30', 'unique:users,phone,' . $user->id],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($data['avatar']);
        }

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('password_success', 'Password changed successfully.');
    }
}
