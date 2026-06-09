<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvoiceTemplateController extends Controller
{
    public function index()
    {
        $templates = \App\Models\InvoiceTemplate::all();
        $active    = \App\Models\InvoiceTemplate::where('is_active', true)->first();
        return view('admin.invoice-templates.index', compact('templates', 'active'));
    }

    public function activate($id)
    {
        \App\Models\InvoiceTemplate::query()->update(['is_active' => false]);
        \App\Models\InvoiceTemplate::findOrFail($id)->update(['is_active' => true]);
        return back()->with('success', 'Template activated successfully.');
    }
}
