<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WordPressSite;
use Illuminate\Http\Request;

class PluginConnectController extends Controller
{
    /**
     * Called by the "Ecom Pilot Netpilot" WordPress plugin on save/activate.
     * Marks the site as plugin_connected and returns site info.
     */
    public function connect(Request $request)
    {
        $apiKey = $request->header('X-API-KEY')
            ?? $request->header('x-api-key')
            ?? $request->input('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key missing. Please enter your Ecom Pilot API key in the plugin settings.',
            ], 401);
        }

        $site = WordPressSite::where('api_key', $apiKey)->first();

        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key. Please check your Ecom Pilot API key.',
            ], 401);
        }

        if (!$site->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This site is currently inactive. Please contact support.',
            ], 403);
        }

        $site->update([
            'plugin_connected'    => true,
            'plugin_connected_at' => now(),
            'plugin_version'      => $request->input('plugin_version'),
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Ecom Pilot Netpilot connected successfully.',
            'site_name'  => $site->name,
            'site_url'   => $site->site_url,
            'tenant_id'  => $site->tenant_id,
            'connected_at' => $site->plugin_connected_at->toIso8601String(),
        ]);
    }

    /**
     * Called by the plugin on deactivation.
     */
    public function disconnect(Request $request)
    {
        $apiKey = $request->header('X-API-KEY')
            ?? $request->header('x-api-key')
            ?? $request->input('api_key');

        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'API key missing.'], 401);
        }

        $site = WordPressSite::where('api_key', $apiKey)->first();

        if (!$site) {
            return response()->json(['success' => false, 'message' => 'Invalid API key.'], 401);
        }

        $site->update([
            'plugin_connected'    => false,
            'plugin_connected_at' => null,
            'plugin_version'      => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Plugin disconnected.']);
    }

    /**
     * Plugin pings this to verify connection is still alive.
     */
    public function ping(Request $request)
    {
        $apiKey = $request->header('X-API-KEY')
            ?? $request->header('x-api-key')
            ?? $request->input('api_key');

        $site = $apiKey ? WordPressSite::where('api_key', $apiKey)->first() : null;

        if (!$site) {
            return response()->json(['success' => false, 'message' => 'Invalid API key.'], 401);
        }

        return response()->json([
            'success'          => true,
            'message'          => 'Ecom Pilot Netpilot is connected.',
            'plugin_connected' => $site->plugin_connected,
            'site_name'        => $site->name,
        ]);
    }
}
