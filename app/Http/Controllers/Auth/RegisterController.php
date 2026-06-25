<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;

class RegisterController extends Controller {
    public function show() {
        return view('auth.register');
    }

    public function store(Request $request) {
        $request->validate([
            'shop_name' => 'required|string|max:255',
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:8|confirmed',
            'phone'     => 'required|string|max:20',
        ]);

        $tenant = Tenant::create([
            'name'            => $request->shop_name,
            'email'           => $request->email,
            'phone'           => $request->phone,
            'status'          => 'active',
            'plan'            => 'basic',
            'plan_expires_at' => now()->addDays(14),
            'max_orders'      => 500,
            'max_users'       => 2,
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => bcrypt($request->password),
            'tenant_id'         => $tenant->id,
            'is_super_admin'    => false,
            'email_verified_at' => now(),
            'role'              => 'admin',
        ]);

        $tenant->update(['owner_id' => $user->id]);

        auth()->login($user);

        return redirect('/admin/dashboard')->with('success', 'Welcome! Your 14-day free trial has started.');
    }
}
