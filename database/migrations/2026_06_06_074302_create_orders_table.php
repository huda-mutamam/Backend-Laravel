<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {

            $table->id();

            // 🔐 user pemesan
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 📦 relasi sender & receiver
            $table->foreignId('sender_id')->constrained('senders')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('receivers')->cascadeOnDelete();

            // 🚚 layanan pengiriman
            $table->foreignId('service_id')->nullable();

            // 📄 data order
            $table->string('resi')->unique();

            $table->decimal('berat', 8, 2);
            $table->decimal('harga', 12, 2);

            $table->enum('status', [
                'Menunggu',
                'Diproses',
                'Dikirim',
                'Sampai',
                'Dibatalkan'
            ])->default('Menunggu');

           $table->enum('jenis_barang', ['dokumen', 'paket', 'lainnya'])->nullable();
           
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};