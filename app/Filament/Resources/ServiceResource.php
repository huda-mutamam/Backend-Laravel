<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    // Menggunakan ikon truk/pengiriman untuk jenis layanan
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Tarif & Layanan';

    protected static ?string $pluralModelLabel = 'Manajemen Layanan Ekspedisi (Services)';

    protected static ?string $modelLabel = 'Layanan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('nama_layanan')
                            ->label('Nama Layanan')
                            ->placeholder('Contoh: DimXpress Reguler, DimXpress KILAT')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tarif')
                            ->label('Tarif per Kg (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),

                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Deskripsi / Estimasi Waktu')
                            ->placeholder('Contoh: Estimasi 2-3 hari kerja sampai tujuan.')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_layanan')
                    ->label('Jenis Layanan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif Ongkir')
                    ->money('IDR', locale: 'id') // Format otomatis Rupiah Rp
                    ->sortable(),

                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Keterangan / Estimasi')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}