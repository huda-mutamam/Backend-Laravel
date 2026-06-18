<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Sender;
use App\Models\Receiver;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pengirim (Senders)', Sender::count())
                ->description('Pelanggan aktif DimXpress')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('info'),
                
            Stat::make('Total Penerima (Receivers)', Receiver::count())
                ->description('Alamat tujuan paket')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),
                
            Stat::make('Total Pengguna Sistem', User::count())
                ->description('Admin & Staff')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
        ];
    }
}