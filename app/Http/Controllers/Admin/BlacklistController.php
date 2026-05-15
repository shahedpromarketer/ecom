<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlacklistedPhone;
use Illuminate\Http\Request;

class BlacklistController extends Controller
{
    public function index()
    {
        $phones = BlacklistedPhone::with('blacklistedByUser')
            ->latest()
            ->paginate(25);

        return view('admin.blacklist.index', compact('phones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone'  => 'required|string|digits:11|unique:blacklisted_phones,phone',
            'reason' => 'nullable|string|max:255',
        ]);

        BlacklistedPhone::create([
            'phone'          => $request->phone,
            'reason'         => $request->reason,
            'blacklisted_by' => auth()->id(),
        ]);

        return back()->with('success', 'Phone number blacklisted.');
    }

    public function destroy(BlacklistedPhone $blacklist)
    {
        $blacklist->delete();

        return back()->with('success', 'Phone removed from blacklist.');
    }
}
