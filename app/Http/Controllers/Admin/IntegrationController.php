<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Models\WordPressSite;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function wordpressIndex()
    {
        $sites      = WordPressSite::withCount('logs')->latest()->get();
        $logs       = SyncLog::with(['site', 'order'])->latest()->take(50)->get();
        $webhookUrl = url('/api/wordpress/sync');

        return view('admin.integrations.wordpress.index', compact('sites', 'logs', 'webhookUrl'));
    }

    public function wordpressStore(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'site_url' => 'required|url|max:255',
        ]);

        WordPressSite::create([
            'name'     => $request->name,
            'site_url' => rtrim($request->site_url, '/'),
            'api_key'  => WordPressSite::generateApiKey(),
        ]);

        return back()->with('success', 'WordPress site connected successfully.');
    }

    public function wordpressDestroy(WordPressSite $site)
    {
        $site->delete();

        return back()->with('success', 'Site removed.');
    }

    public function regenerateKey(WordPressSite $site)
    {
        $newKey = WordPressSite::generateApiKey();
        $site->update(['api_key' => $newKey]);

        return response()->json(['success' => true, 'api_key' => $newKey]);
    }
}
