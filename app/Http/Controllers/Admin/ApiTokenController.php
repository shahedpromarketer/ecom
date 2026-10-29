<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;

/**
 * One plugin access token per shop. The token is stored as a SHA-256 hash,
 * so its plain value is shown exactly once — right after it is generated.
 */
class ApiTokenController extends Controller
{
    public function index()
    {
        $token = ApiToken::where('is_active', true)->latest('id')->first();

        return view('admin.settings.api-tokens', compact('token'));
    }

    public function regenerate()
    {
        // Scoped to the current shop by BelongsToTenant, so only this shop's token is replaced.
        ApiToken::query()->delete();

        $token = ApiToken::generate('Ecom Pilot Plugin Token');
        $token->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'token'   => $token->plainToken,
        ]);
    }
}
