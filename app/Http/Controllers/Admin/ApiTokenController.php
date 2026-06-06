<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function index()
    {
        $tokens = ApiToken::with('creator')->latest()->get();

        return view('admin.settings.api-tokens', compact('tokens'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            'abilities'  => 'nullable|array',
        ]);

        $expiresAt = $request->filled('expires_at')
            ? \Carbon\Carbon::parse($request->expires_at)->endOfDay()
            : null;

        $abilities = $request->input('abilities', ['orders:create', 'orders:read']);

        $token = ApiToken::generate($request->name, $abilities, $expiresAt);

        return response()->json([
            'success'  => true,
            'token'    => $token->plainToken,
            'token_id' => $token->id,
        ]);
    }

    public function destroy(ApiToken $token)
    {
        $token->delete();

        return response()->json(['success' => true]);
    }

    public function toggle(ApiToken $token)
    {
        $token->update(['is_active' => !$token->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $token->is_active,
        ]);
    }
}
