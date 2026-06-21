<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    // Mengubah ikon menjadi paket/box logistik khas ekspedisi
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    // Pengaturan label menu di sidebar navigasi
    protected static ?string $navigationLabel = 'Manajemen Resi';

    // Judul utama halaman tabel/list data
    protected static ?string $pluralModelLabel = 'Data Pengiriman Paket (Orders)';

    // Sebutan tunggal untuk entitas data
    protected static ?string $modelLabel = 'Resi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Utama & Pelacakan')
                    ->description('Detail nomor pelacakan paket dan status saat ini')
                    ->schema([
                        Forms\Components\TextInput::make('resi')
                            ->label('Nomor Resi / AWB')
                            ->required()
                            ->default(fn () => 'DIMX-' . strtoupper(uniqid())) // Otomatis membuat kode resi unik
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('status')
                            ->label('Status Pengiriman')
                            ->required()
                            ->options([
                                'Menunggu' => '📦 Menunggu (Gudang)',
                                'Diproses' => '🔄 Diproses (Sortir)',
                                'Dikirim' => '🚚 Dikirim (Kurir)',
                                'Sampai' => '✅ Sampai (Diterima)',
                                'Dibatalkan' => '❌ Dibatalkan',
                            ])
                            ->default('Menunggu'),
                    ])->columns(2),

                Forms\Components\Section::make('Pihak Terkait (Relasi)')
                    ->description('Pilih petugas input, pengirim, penerima, dan jenis layanan')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Petugas / Admin Input')
                            ->relationship('user', 'name') // Mengambil nama admin dari tabel users
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('sender_id')
                            ->label('Nama Pengirim')
                            ->relationship('sender', 'nama_pengirim') // Mengambil nama dari tabel senders
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('receiver_id')
                            ->label('Nama Penerima')
                            ->relationship('receiver', 'nama_penerima') // Mengambil nama dari tabel receivers
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('service_id')
                            ->label('Layanan Ekspedisi')
                            ->relationship('service', 'nama_layanan') // Mengambil nama layanan (misal: Reguler/Kilat)
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Paket & Komunikasi')
                    ->description('Spesifikasi fisik barang, biaya, serta nomor kontak aktif')
                    ->schema([
                        Forms\Components\TextInput::make('jenis_barang')
                            ->label('Jenis/Nama Barang')
                            ->placeholder('Contoh: Dokumen, Elektronik, Pakaian')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('berat')
                            ->label('Berat Paket (Kg)')
                            ->required()
                            ->numeric()
                            ->default(1.00),

                        Forms\Components\TextInput::make('harga')
                            ->label('Total Ongkos Kirim (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),

                        Forms\Components\TextInput::make('sender_phone')
                            ->label('No. Telp Pengirim')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('receiver_phone')
                            ->label('No. Telp Penerima')
                            ->tel()
                            ->maxLength(255),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resi')
                    ->label('No. Resi')
                    ->searchable()
                    ->copyable() // Admin bisa klik untuk copy nomor resi
                    // Menampilkan alamat tujuan di bawah nomor resi via accessor model Anda
                    ->description(fn (Order $record) => "Tujuan: " . ($record->alamat_tujuan ?? '-')) 
                    ->sortable(),

                Tables\Columns\TextColumn::make('sender.nama_pengirim')
                    ->label('Pengirim')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('receiver.nama_penerima')
                    ->label('Penerima')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis_barang')
                    ->label('Jenis Barang')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('berat')
                    ->label('Berat')
                    ->suffix(' Kg') // Menampilkan satuan kilogram
                    ->sortable(),

                Tables\Columns\TextColumn::make('harga')
                    ->label('Ongkir')
                    ->money('IDR', locale: 'id') // Format otomatis menjadi mata uang Rupiah Rp
                    ->sortable(),

                // Mengubah status menjadi dropdown instan langsung di baris tabel
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status Paket')
                    ->options([
                        'Menunggu' => '📦 Menunggu',
                        'Diproses' => '🔄 Diproses',
                        'Dikirim' => '🚚 Dikirim',
                        'Sampai' => '✅ Sampai',
                        'Dibatalkan' => '❌ Batal',
                    ])
                    ->selectablePlaceholder(false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Masuk')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                // Filter cepat data berdasarkan status paket di tabel
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Menunggu' => 'Menunggu',
                        'Diproses' => 'Diproses',
                        'Dikirim' => 'Dikirim',
                        'Sampai' => 'Sampai',
                        'Dibatalkan' => 'Dibatalkan',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}