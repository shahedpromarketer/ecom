<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;

class ApiTokenController extends Controller
{
    public function index()
    {
        $token = ApiToken::where('is_active', true)
            ->where('created_by', auth()->id())
            ->first();

        if (!$token) {
            $token = ApiToken::create([
                'name'       => 'Clothonic Plugin Token',
                'token'      => bin2hex(random_bytes(32)),
                'is_active'  => true,
                'created_by' => auth()->id(),
            ]);
        }

        return view('admin.settings.api-tokens', compact('token'));
    }

    public function regenerate()
    {
        ApiToken::where('created_by', auth()->id())->delete();

        ApiToken::create([
            'name'       => 'Clothonic Plugin Token',
            'token'      => bin2hex(random_bytes(32)),
            'is_active'  => true,
            'created_by' => auth()->id(),
        ]);

        return response()->json(['success' => true]);
    }
}
