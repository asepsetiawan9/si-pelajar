<?php

namespace App\Exports\Sheets;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RincianLayananSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected ?Laporan $laporan;

    protected ?LaporanDetail $detail;

    public function __construct(?Laporan $laporan = null, ?LaporanDetail $detail = null)
    {
        $this->laporan = $laporan;
        $this->detail = $detail;
    }

    public function title(): string
    {
        return 'Rincian Layanan & Pemohon';
    }

    public function headings(): array
    {
        return [
            'No',
            'Unit Organisasi',
            'Nama Layanan / Kegiatan',
            'Jumlah Pemohon',
            'Satuan',
            'Keterangan',
        ];
    }

    public function collection()
    {
        $rows = collect();
        $no = 1;

        $details = $this->detail
            ? collect([$this->detail])
            : ($this->laporan ? $this->laporan->details()->with(['unitOrganisasi', 'layanans'])->get() : collect());

        foreach ($details as $detail) {
            $unitName = $detail->unitOrganisasi?->nama_unit ?? '-';

            foreach ($detail->layanans as $layanan) {
                $rows->push([
                    'no' => $no++,
                    'unit' => $unitName,
                    'nama_layanan' => $layanan->nama_layanan,
                    'jumlah' => (int) $layanan->jumlah,
                    'satuan' => $layanan->satuan ?: 'Pemohon',
                    'keterangan' => $layanan->keterangan ?: '-',
                ]);
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0284C7'], // Sky blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($highestRow > 1) {
            $sheet->getStyle("A1:{$highestCol}{$highestRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ]);

            $sheet->getStyle("D2:D{$highestRow}")->getNumberFormat()->setFormatCode('#,##0');
        }

        return [];
    }
}
