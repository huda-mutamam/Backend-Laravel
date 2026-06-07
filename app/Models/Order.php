<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\Sender;
use App\Models\Receiver;
use App\Models\Service;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    // ======================
    // MASS ASSIGNMENT
    // ======================
    protected $fillable = [
        'user_id',
        'sender_id',
        'receiver_id',
        'service_id',
        'resi',
        'berat',
        'harga',
        'status',
        'jenis_barang',
        'sender_phone',
        'receiver_phone',
    ];

    // ======================
    // CASTING DATA
    // ======================
    protected $casts = [
        'berat' => 'decimal:2',
        'harga' => 'decimal:2',
    ];

    // ======================
    // RELASI USER
    // ======================
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ======================
    // RELASI SENDER
    // ======================
    public function sender()
    {
        return $this->belongsTo(Sender::class, 'sender_id');
    }

    // ======================
    // RELASI RECEIVER
    // ======================
    public function receiver()
    {
        return $this->belongsTo(Receiver::class, 'receiver_id');
    }

    // ======================
    // RELASI SERVICE
    // ======================
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    // ======================
    // ACCESSOR (OPTIONAL)
    // ======================

    // biar gampang tampilkan alamat tujuan langsung
    public function getAlamatTujuanAttribute()
    {
        return $this->receiver?->alamat_tujuan;
    }

    // status warna (buat frontend nanti)
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'Menunggu' => 'warning',
            'Diproses' => 'info',
            'Dikirim' => 'primary',
            'Sampai' => 'success',
            'Dibatalkan' => 'danger',
            default => 'secondary',
        };
    }
}