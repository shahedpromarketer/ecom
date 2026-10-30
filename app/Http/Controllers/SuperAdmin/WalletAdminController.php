<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\WalletRechargeRequest;
use App\Models\WalletTransaction;
use App\Services\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Super admin: recharge requests (approve / reject), balances, per-order charges, payment numbers. */
class WalletAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->status, ['pending', 'approved', 'rejected', 'all'], true) ? $request->status : 'pending';

        return view('super-admin.wallet', [
            'status'      => $status,
            'requests'    => WalletRechargeRequest::withoutGlobalScopes()->with(['tenant', 'requester', 'reviewer'])
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))->latest('id')->paginate(30)->withQueryString(),
            'pending'     => WalletRechargeRequest::withoutGlobalScopes()->where('status', 'pending')->count(),
            'tenants'     => Tenant::orderBy('name')->get(),
            'paymentInfo' => Setting::getValue('wallet_payment_info', '', null),
            'recent'      => WalletTransaction::withoutGlobalScopes()->whereIn('type', ['recharge', 'adjustment'])->latest('id')->take(15)->get(),
        ]);
    }

    public function approve(Request $request, int $id)
    {
        $data = $request->validate(['amount' => 'nullable|numeric|min:1|max:1000000']);
        $r = WalletRechargeRequest::withoutGlobalScopes()->findOrFail($id);
        try {
            Wallet::approve($r, auth()->id(), isset($data['amount']) ? (float) $data['amount'] : null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Approved: ৳' . number_format((float) $r->fresh()->amount) . ' added to ' . $r->tenant?->name . '.');
    }

    public function reject(Request $request, int $id)
    {
        $data = $request->validate(['reason' => 'required|string|max:255']);
        $r = WalletRechargeRequest::withoutGlobalScopes()->where('status', 'pending')->findOrFail($id);
        $r->forceFill(['status' => 'rejected', 'reject_reason' => $data['reason'], 'reviewed_by' => auth()->id(), 'reviewed_at' => now()])->save();

        return back()->with('success', 'Request rejected.');
    }

    public function screenshot(int $id)
    {
        $r = WalletRechargeRequest::withoutGlobalScopes()->findOrFail($id);
        abort_unless($r->screenshot_path && Storage::disk('local')->exists($r->screenshot_path), 404);

        return Storage::disk('local')->response($r->screenshot_path);
    }

    /** Per-order charge, and manual add / take (e.g. welcome credit, refunds). */
    public function updateTenant(Request $request, int $id)
    {
        $data = $request->validate([
            'order_charge' => 'required|numeric|min:0|max:1000',
            'charge_on'    => ['required', \Illuminate\Validation\Rule::in(array_keys(Wallet::CHARGE_ON))],
            'adjust'       => 'nullable|numeric|min:-1000000|max:1000000',
            'note'         => 'nullable|string|max:255',
        ]);
        $tenant = Tenant::findOrFail($id);
        $tenant->forceFill(['order_charge' => $data['order_charge'], 'charge_on' => $data['charge_on']])->save();
        if (! empty($data['adjust']) && (float) $data['adjust'] !== 0.0) {
            Wallet::credit($tenant, (float) $data['adjust'], 'adjustment', $data['note'] ?: 'Adjusted by Ecom Pilot', null, auth()->id());
        }

        return back()->with('success', $tenant->name . ' updated. Balance: ৳' . number_format((float) $tenant->fresh()->wallet_balance, 2));
    }

    /** The bKash / Nagad / bank details shops see on their Wallet page. */
    public function paymentInfo(Request $request)
    {
        $data = $request->validate(['payment_info' => 'nullable|string|max:1000']);
        Setting::setValue('wallet_payment_info', trim((string) ($data['payment_info'] ?? '')), 'wallet', null);

        return back()->with('success', 'Payment details saved.');
    }
}
