<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\WalletRechargeRequest;
use App\Models\WalletTransaction;
use App\Services\WalletStats;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** The shop's prepaid wallet: balance, history and "I paid, please recharge" requests. */
class WalletController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant ?? \App\Models\Tenant::find(\App\Support\Tenancy::id());
        abort_unless($tenant, 404);

        return view('admin.wallet.index', [
            'tenant'       => $tenant,
            'w'            => WalletStats::summary($tenant),
            'chart'        => WalletStats::chart($tenant, 30),
            'requests'     => WalletRechargeRequest::latest('id')->take(10)->get(),
            'paymentInfo'  => Setting::getValue('wallet_payment_info', null, null),
        ]);
    }

    /** Day by day: what each day cost; ?day=YYYY-MM-DD opens that day's orders. */
    public function history(Request $request)
    {
        $tenant = auth()->user()->tenant ?? \App\Models\Tenant::find(\App\Support\Tenancy::id());
        abort_unless($tenant, 404);
        $day = $request->filled('day') ? rescue(fn () => Carbon::createFromFormat('Y-m-d', $request->day)->startOfDay(), null, false) : null;
        $month = rescue(fn () => Carbon::createFromFormat('Y-m', (string) $request->month)->startOfMonth(), null, false) ?? now()->startOfMonth();

        return view('admin.wallet.history', [
            'tenant'       => $tenant,
            'w'            => WalletStats::summary($tenant),
            'day'          => $day,
            'month'        => $month,
            'days'         => $day ? collect() : WalletStats::daily($tenant, $month->copy(), $month->copy()->endOfMonth()),
            'transactions' => $day
                ? WalletTransaction::with('order')->whereBetween('created_at', [$day, $day->copy()->endOfDay()])->latest('id')->paginate(100)->withQueryString()
                : null,
        ]);
    }

    public function storeRequest(Request $request)
    {
        $data = $request->validate([
            'amount'         => 'required|numeric|min:10|max:1000000',
            'method'         => ['required', Rule::in(array_keys(WalletRechargeRequest::METHODS))],
            'screenshot'     => 'required|image|max:5120',
            'note'           => 'nullable|string|max:500',
        ]);

        // A double click / resend of the same request.
        if (WalletRechargeRequest::where('status', 'pending')->where('amount', $data['amount'])->where('created_at', '>=', now()->subMinutes(2))->exists()) {
            return back()->with('error', 'This request was already sent. Please wait for it to be checked.');
        }

        $path = $request->file('screenshot')->store('wallet/' . auth()->user()->tenant_id, 'local');
        WalletRechargeRequest::create([
            'amount' => $data['amount'], 'method' => $data['method'], 
            'note' => $data['note'] ?? null, 'screenshot_path' => $path,
            'status' => 'pending', 'requested_by' => auth()->id(),
        ]);

        return back()->with('success', 'Recharge request sent. Your balance is added as soon as we check the payment.');
    }

    /** The shop's own screenshot (stored privately). */
    public function screenshot(WalletRechargeRequest $recharge)
    {
        abort_unless($recharge->screenshot_path && Storage::disk('local')->exists($recharge->screenshot_path), 404);

        return Storage::disk('local')->response($recharge->screenshot_path);
    }
}
