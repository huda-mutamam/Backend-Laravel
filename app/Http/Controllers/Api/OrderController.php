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
        $orders = Order::with(['sender', 'receiver'])
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

    public function tracking($id)
    {
        $order = Order::with(['sender', 'receiver'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan'
            ], 404);
        }

        if ($order->status === 'Dibatalkan') {
            return response()->json([
                'success' => true,
                'resi'    => $order->resi,
                'service' => 'Service #' . $order->service_id,
                'tracking' => [
                    [
                        'status' => 'Pesanan dibatalkan',
                        'location' => '-',
                        'time' => $order->updated_at->format('d M Y, H:i'),
                        'isCompleted' => true,
                    ]
                ],
            ]);
        }

        $senderAddress   = $order->sender->alamat_asal ?? 'Lokasi pengirim';
        $receiverAddress = $order->receiver->alamat_tujuan ?? 'Lokasi tujuan';

        $steps = [
            ['status' => 'Pesanan diterima',        'location' => $senderAddress],
            ['status' => 'Paket dijemput kurir',    'location' => $senderAddress],
            ['status' => 'Paket dalam pengiriman',  'location' => 'Menuju ' . $receiverAddress],
            ['status' => 'Paket telah diterima',    'location' => $receiverAddress],
        ];

        $statusMap = [
            'Menunggu' => 0,
            'Diproses' => 1,
            'Dikirim'  => 2,
            'Sampai'   => 3,
        ];

        $currentIndex = $statusMap[$order->status] ?? 0;

        $tracking = [];
        foreach ($steps as $i => $step) {
            $tracking[] = [
                'status'      => $step['status'],
                'location'    => $step['location'],
                'time'        => $i <= $currentIndex
                                    ? $order->updated_at->format('d M Y, H:i')
                                    : '-',
                'isCompleted' => $i <= $currentIndex,
            ];
        }

        return response()->json([
            'success' => true,
            'resi'    => $order->resi,
            'service' => 'Service #' . $order->service_id,
            'tracking' => $tracking,
        ]);
    }

    /**
     * ✏️ UPDATE ORDER (edit alamat tujuan, berat, catatan)
     * Hanya bisa diedit kalau status masih "Menunggu"
     */
    public function update(Request $request, $id)
    {
        $order = Order::with('receiver')
            ->where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        if ($order->status !== 'Menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak dapat diubah karena sudah diproses.',
            ], 403);
        }

        $request->validate([
            'alamat_tujuan' => 'sometimes|string|max:255',
            'catatan'       => 'nullable|string|max:500',
            'berat'         => 'sometimes|numeric|min:0',
        ]);

        // Update alamat tujuan di tabel receiver (relasi)
        if ($request->filled('alamat_tujuan') && $order->receiver) {
            $order->receiver->update([
                'alamat_tujuan' => $request->alamat_tujuan,
            ]);
        }

        // Update field milik order sendiri, status TIDAK disentuh
        $order->update($request->only(['catatan', 'berat']));

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil diupdate',
            'data' => $order->fresh()->load(['sender', 'receiver']),
        ]);
    }

    /**
     * 🗑 DELETE ORDER
     * Hanya bisa dihapus kalau status masih "Menunggu"
     */
    public function destroy($id)
    {
        $order = Order::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        if ($order->status !== 'Menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak dapat dihapus karena sudah diproses.',
            ], 403);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dihapus'
        ]);
    }
}