<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavedFilter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SavedFilterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'page'      => ['required', Rule::in(array_keys(SavedFilter::PAGES))],
            'name'      => 'required|string|max:100',
            'query'     => 'nullable|string|max:4000',
            'is_shared' => 'nullable|boolean',
        ]);

        // Keep only real filter parameters (no paging or one-off view switches).
        parse_str((string) ($data['query'] ?? ''), $params);
        unset($params['page'], $params['_token']);
        $query = http_build_query(array_filter($params, fn ($v) => $v !== '' && $v !== null && $v !== []));

        SavedFilter::updateOrCreate(
            ['user_id' => auth()->id(), 'page' => $data['page'], 'name' => $data['name']],
            ['query' => $query, 'is_shared' => $request->boolean('is_shared')]
        );

        return back()->with('success', 'Filter "' . $data['name'] . '" saved.');
    }

    public function destroy(SavedFilter $savedFilter)
    {
        // Your own filters; shop owners can also remove shared ones.
        abort_unless($savedFilter->user_id === auth()->id() || auth()->user()->hasFullAccess(), 403);

        $savedFilter->delete();

        return back()->with('success', 'Saved filter removed.');
    }
}
