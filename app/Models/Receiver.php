<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receiver extends Model
{
    use HasFactory;

     protected $fillable = [
        'nama_penerima',
        'alamat_tujuan',
        'phone'
    ];
}
