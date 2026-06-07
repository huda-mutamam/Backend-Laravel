<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Order;
use App\Models\Sender;
use App\Models\Receiver;

class OrderController extends Controller
{
    /**
     * 📦 CREATE ORDER
     */
    public function store(Request $request)
{
    $request->validate([
        'sender_nama' => 'required',
        'sender_alamat' => 'required',
        'receiver_nama' => 'required',
        'receiver_alamat' => 'required',
        'service_id' => 'required',
        'berat' => 'required',
        'harga' => 'required',
    ]);

    $sender = Sender::create([
        'nama_pengirim' => $request->sender_nama,
        'alamat_asal' => $request->sender_alamat,
        'phone' => $request->sender_phone,
    ]);

    $receiver = Receiver::create([
        'nama_penerima' => $request->receiver_nama,
        'alamat_tujuan' => $request->receiver_alamat,
        'phone' => $request->receiver_phone,
    ]);

    $order = Order::create([
        'user_id' => auth()->id() ?? 1, // fallback aman
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'service_id' => $request->service_id,
        'resi' => 'RESI-' . strtoupper(Str::random(10)),
        'berat' => $request->berat,
        'harga' => $request->harga,
        'status' => 'Menunggu',
        'jenis_barang' => $request->jenis_barang ?? null,
        'sender_phone' => $request->sender_phone,
        'receiver_phone' => $request->receiver_phone,
    ]);

    return response()->json([
        'success' => true,
        'data' => $order
    ]);
}

    /**
     * 📄 LIST ORDER
     */
  public function index()
{
    $orders = Order::with(['sender', 'receiver' ])
        ->where('user_id', auth()->id())
        ->latest()
        ->get();

    return response()->json([
        'success' => true,
        'data' => $orders
    ]);
}

    /**
     * 📦 DETAIL ORDER
     */
    public function show($id)
{
    $order = Order::with(['sender', 'receiver', 'service'])
        ->find($id);

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'Order tidak ditemukan'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $order
    ]);
}
    /**
     * 📍 TRACKING RESI (PUBLIC)
     */
    public function track($resi)
    {
        $order = Order::with(['sender', 'receiver', 'service'])
            ->where('resi', $resi)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Resi tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * ✏️ UPDATE STATUS
     */
    public function update(Request $request, $id)
    {
        $order = Order::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        $order->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil diupdate',
            'data' => $order
        ]);
    }

    /**
     * 🗑 DELETE ORDER
     */
    public function destroy($id)
    {
        $order = Order::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dihapus'
        ]);
    }
}