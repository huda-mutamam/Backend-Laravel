<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SenderResource\Pages;
use App\Filament\Resources\SenderResource\RelationManagers;
use App\Models\Sender;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SenderResource extends Resource
{
    protected static ?string $model = Sender::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // Tambahkan 3 baris kode di bawah ini:
    protected static ?string $navigationLabel = 'Detail Pengirim'; // Nama di menu sidebar kiri

    protected static ?string $pluralModelLabel = 'Detail Pengirim'; // Judul halaman tabel utama

    protected static ?string $modelLabel = 'Detail Pengirim'; // Judul saat tambah/edit data tunggal

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_pengirim')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('alamat_asal')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_pengirim')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
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
            'index' => Pages\ListSenders::route('/'),
            'create' => Pages\CreateSender::route('/create'),
            'edit' => Pages\EditSender::route('/{record}/edit'),
        ];
    }
}
