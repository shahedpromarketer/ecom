<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierSetting;
use App\Models\CourierShipment;
use App\Services\Couriers\CourierFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/** Settings → Courier: the shop's courier accounts (several per courier allowed). */
class CourierController extends Controller
{
    public function index()
    {
        $accounts = CourierSetting::orderBy('courier')->orderByDesc('is_default')->orderBy('id')->get();
        $counts = CourierShipment::selectRaw('courier_account_id, count(*) c')->whereNotNull('courier_account_id')
            ->groupBy('courier_account_id')->pluck('c', 'courier_account_id');

        return view('admin.courier.index', [
            'accounts' => $accounts,
            'couriers' => CourierSetting::COURIERS,
            'counts'   => $counts,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $account = new CourierSetting(['courier' => $data['courier']]);
        $this->fill($account, $data);
        // The first account of a courier is its default.
        $account->is_default = ! CourierSetting::where('courier', $account->courier)->exists();
        $account->save();

        return redirect()->route('admin.courier.index')->with('success', $account->label() . ' added. Press "Test" to check the keys.');
    }

    public function update(Request $request, CourierSetting $account)
    {
        $data = $this->validated($request, $account);
        $this->fill($account, $data);
        $account->save();

        return redirect()->route('admin.courier.index')->with('success', $account->label() . ' saved.');
    }

    public function destroy(CourierSetting $account)
    {
        if (CourierShipment::where('courier_account_id', $account->id)->whereNotIn('status', CourierShipment::FINAL)->exists()) {
            return back()->with('error', 'This account still has parcels on the way. Turn it off instead of deleting it.');
        }
        $label = $account->label();
        $account->delete();
        if ($account->is_default) {
            CourierSetting::where('courier', $account->courier)->orderBy('id')->first()?->update(['is_default' => true]);
        }

        return back()->with('success', $label . ' deleted.');
    }

    public function makeDefault(CourierSetting $account)
    {
        CourierSetting::where('courier', $account->courier)->update(['is_default' => false]);
        $account->update(['is_default' => true]);

        return back()->with('success', $account->label() . ' is now the default ' . CourierSetting::COURIERS[$account->courier]['label'] . ' account.');
    }

    public function test(CourierSetting $account)
    {
        try {
            return response()->json(['success' => true, 'message' => CourierFactory::for($account)->testConnection()]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['success' => false, 'message' => 'Could not reach ' . $account->label() . '. Their server may be busy — try again in a minute.'], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function validated(Request $request, ?CourierSetting $account): array
    {
        $courier = $account?->courier ?? $request->input('courier');
        $rules = [
            'courier'         => [$account ? 'nullable' : 'required', Rule::in(array_keys(CourierSetting::COURIERS))],
            'name'            => 'nullable|string|max:100',
            'is_active'       => 'nullable|boolean',
            'sandbox'         => 'nullable|boolean',
            'extra.weight_kg' => 'nullable|numeric|min:0.1|max:50',
        ];
        foreach (array_keys(CourierSetting::COURIERS[$courier]['fields'] ?? []) as $field) {
            $rules[$field] = 'nullable|string|max:500';
        }

        return $request->validate($rules);
    }

    private function fill(CourierSetting $account, array $data): void
    {
        $account->name = ($data['name'] ?? '') !== '' ? $data['name'] : $account->name;
        $account->is_active = (bool) ($data['is_active'] ?? false);
        $account->sandbox = (bool) ($data['sandbox'] ?? false);

        $extra = $account->extra_config ?? [];
        foreach (array_keys(CourierSetting::COURIERS[$account->courier]['fields']) as $field) {
            $value = trim((string) Arr::get($data, $field, ''));
            // Secrets left empty keep the saved value.
            if ($value === '' && in_array($field, CourierSetting::SECRET_FIELDS, true) && $account->exists) {
                continue;
            }
            if (str_starts_with($field, 'extra.')) {
                $extra[substr($field, 6)] = $value;
            } else {
                $account->{$field} = $value;
            }
        }
        if (isset($data['extra']['weight_kg'])) {
            $extra['weight_kg'] = (float) $data['extra']['weight_kg'];
        }
        $account->extra_config = $extra;
    }
}
