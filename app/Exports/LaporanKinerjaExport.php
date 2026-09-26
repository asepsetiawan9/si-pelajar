<?php

namespace App\Exports;

use App\Exports\Sheets\IndikatorKinerjaSheet;
use App\Exports\Sheets\RekapitulasiUnitSheet;
use App\Exports\Sheets\RincianLayananSheet;
use App\Models\Laporan;
use App\Models\LaporanDetail;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanKinerjaExport implements WithMultipleSheets
{
    protected ?Laporan $laporan;

    protected ?LaporanDetail $detail;

    public function __construct(?Laporan $laporan = null, ?LaporanDetail $detail = null)
    {
        $this->laporan = $laporan;
        $this->detail = $detail;
    }

    public function sheets(): array
    {
        return [
            new IndikatorKinerjaSheet($this->laporan, $this->detail),
            new RincianLayananSheet($this->laporan, $this->detail),
            new RekapitulasiUnitSheet($this->laporan, $this->detail),
        ];
    }
}
