<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceTemplate;
use App\Models\Tenant;
use Illuminate\Http\Request;

class InvoiceTemplateController extends Controller
{
    // Get the active template ID for the current tenant from tenant settings
    private function activeTemplateId(): ?int
    {
        $user = auth()->user();
        if ($user->is_super_admin) {
            // Super admin: check first tenant or global is_active fallback
            return InvoiceTemplate::where('is_active', true)->value('id');
        }
        $tenant = Tenant::find($user->tenant_id);
        return $tenant?->settings['active_invoice_template_id'] ?? null;
    }

    public function index()
    {
        // All templates are global — no tenant filter
        $templates       = InvoiceTemplate::orderBy('size')->orderBy('style')->get();
        $activeId        = $this->activeTemplateId();
        $active          = $activeId ? $templates->find($activeId) : null;

        return view('admin.invoice-templates.index', compact('templates', 'active', 'activeId'));
    }

    public function activate($id)
    {
        $template = InvoiceTemplate::findOrFail($id);
        $user     = auth()->user();

        if ($user->is_super_admin) {
            // Super admin uses the legacy is_active flag
            InvoiceTemplate::query()->update(['is_active' => false]);
            $template->update(['is_active' => true]);
        } else {
            // Store active template per-tenant in tenant settings JSON
            $tenant           = Tenant::findOrFail($user->tenant_id);
            $settings         = $tenant->settings ?? [];
            $settings['active_invoice_template_id'] = (int) $id;
            $tenant->update(['settings' => $settings]);
        }

        return back()->with('success', 'Template activated successfully.');
    }
}
