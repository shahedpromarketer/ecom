<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Deleted orders, products, categories and customers. Anything here can be
 * restored; only the shop owner can remove it for good.
 */
class RecycleBinController extends Controller
{
    public const TYPES = [
        'orders'     => ['label' => 'Orders',     'model' => Order::class],
        'customers'  => ['label' => 'Customers',  'model' => Customer::class],
        'products'   => ['label' => 'Products',   'model' => Product::class],
        'categories' => ['label' => 'Categories', 'model' => Category::class],
    ];

    public function index(Request $request)
    {
        $type  = array_key_exists($request->type, self::TYPES) ? $request->type : 'orders';
        $model = self::TYPES[$type]['model'];

        $items = $model::onlyTrashed()->with('deletedByUser')->latest('deleted_at')->paginate(30)->withQueryString();

        $counts = collect(self::TYPES)->map(fn ($t) => $t['model']::onlyTrashed()->count());

        return view('admin.recycle-bin.index', compact('type', 'items', 'counts'));
    }

    public function restore(Request $request, string $type)
    {
        $ids = $this->ids($request);
        $count = 0;

        foreach ($this->trashed($type, $ids) as $item) {
            $item->restore();
            ActivityLog::log('recycle.restored', $item, ucfirst(rtrim($type, 's')) . ' #' . $item->id . ' restored by ' . auth()->user()->name);
            $count++;
        }

        return back()->with('success', "{$count} item(s) restored.");
    }

    public function destroy(Request $request, string $type)
    {
        abort_unless(auth()->user()->hasFullAccess(), 403, 'Only the shop owner can delete permanently.');

        $ids = $this->ids($request);
        $count = 0;
        $kept = 0;

        foreach ($this->trashed($type, $ids) as $item) {
            try {
                $item->forceDelete();
                ActivityLog::log('recycle.purged', $item, ucfirst(rtrim($type, 's')) . ' #' . $item->id . ' permanently deleted by ' . auth()->user()->name);
                $count++;
            } catch (\Illuminate\Database\QueryException $e) {
                // e.g. a product that appears on past orders: keep it so order history stays intact.
                $kept++;
            }
        }

        $message = "{$count} item(s) permanently deleted.";
        if ($kept) {
            $message .= " {$kept} kept because past orders still refer to them.";
        }

        return back()->with('success', $message);
    }

    private function ids(Request $request): array
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        return array_map('intval', $request->ids);
    }

    private function trashed(string $type, array $ids)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        // Tenant scope still applies, so only this shop's deleted rows are found.
        return self::TYPES[$type]['model']::onlyTrashed()->whereIn('id', $ids)->get();
    }
}
