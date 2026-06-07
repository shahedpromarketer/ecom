<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderNote;
use Illuminate\Http\Request;

class OrderNoteController extends Controller
{
    public function store(Request $request, $id)
    {
        $request->validate(['note_text' => 'required|string|max:1000']);

        $order = Order::findOrFail($id);

        $note = OrderNote::create([
            'order_id' => $order->id,
            'user_id'  => auth()->id(),
            'note'     => $request->note_text,
            'type'     => 'order',
        ]);

        $note->load('user');

        return response()->json([
            'success' => true,
            'note'    => [
                'id'         => $note->id,
                'note_text'  => $note->note,
                'user_name'  => $note->user->name ?? 'System',
                'created_at' => $note->created_at->format('d M Y H:i'),
            ],
        ]);
    }

    public function destroy($id, $noteId)
    {
        $note = OrderNote::where('id', $noteId)->where('order_id', $id)->firstOrFail();
        $note->delete();

        return response()->json(['success' => true]);
    }

    public function storeCustomer(Request $request, $id)
    {
        $request->validate(['note_text' => 'required|string|max:1000']);

        $order = Order::findOrFail($id);

        $note = OrderNote::create([
            'order_id' => $order->id,
            'user_id'  => auth()->id(),
            'note'     => $request->note_text,
            'type'     => 'customer',
        ]);

        $note->load('user');

        return response()->json([
            'success' => true,
            'note'    => [
                'id'         => $note->id,
                'note_text'  => $note->note,
                'user_name'  => $note->user->name ?? 'System',
                'created_at' => $note->created_at->format('d M Y H:i'),
            ],
        ]);
    }
}
