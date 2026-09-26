<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Dashboard Akuntabilitas SPKO';

    protected static ?string $navigationLabel = 'Dashboard Utama';

    public function getColumns(): int|string|array
    {
        return 12;
    }

    public function filtersForm(Form $form): Form
    {
        $currentYear = (int) now()->year;
        $yearOptions = [
            $currentYear - 1 => (string) ($currentYear - 1),
            $currentYear => (string) $currentYear,
            $currentYear + 1 => (string) ($currentYear + 1),
        ];

        return $form
            ->schema([
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                ])
                    ->schema([
                        Select::make('tahun')
                            ->label('Tahun Anggaran')
                            ->options($yearOptions)
                            ->default($currentYear)
                            ->selectablePlaceholder(false)
                            ->live(),

                        Select::make('bulan')
                            ->label('Bulan Pelaporan Kinerja')
                            ->options([
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ])
                            ->default(now()->subMonth()->month)
                            ->selectablePlaceholder(false)
                            ->live(),
                    ]),
            ]);
    }
}
